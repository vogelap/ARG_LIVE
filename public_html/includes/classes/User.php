<?php
// File: arg_game/includes/classes/User.php

class User {
    private $mysqli;

    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }

    // --- Session-based Methods ---

    public static function hasSeenIntro() {
        return isset($_SESSION['has_seen_intro']) && $_SESSION['has_seen_intro'] === true;
    }

    /**
     * MODIFIED: This is now a non-static method that updates the database and the session.
     */
    public function markIntroAsSeen() {
        if (!isset($_SESSION['user_id'])) {
            return;
        }
        $user_id = $_SESSION['user_id'];

        // Update the session to prevent re-checks during this session
        $_SESSION['has_seen_intro'] = true;

        // Update the database to make it permanent
        $stmt = $this->mysqli->prepare("UPDATE users SET has_seen_intro = 1 WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }


    // --- Login and Token Methods ---

    public static function login($mysqli, $email, $password) {
        // MODIFIED: Fetches has_seen_intro
        $stmt = $mysqli->prepare("SELECT id, username, password, is_admin, has_seen_intro FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                return $user;
            }
        }
        return false;
    }

    public function generateLoginToken($user_id) {
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", time() + 60);
        $stmt = $this->mysqli->prepare("INSERT INTO login_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $token, $expires);
        return $stmt->execute() ? $token : false;
    }

    public function verifyLoginToken($token) {
        $stmt = $this->mysqli->prepare("SELECT user_id FROM login_tokens WHERE token = ? AND expires_at > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        if ($result) {
            $delete_stmt = $this->mysqli->prepare("DELETE FROM login_tokens WHERE token = ?");
            $delete_stmt->bind_param("s", $token);
            $delete_stmt->execute();
            return $result['user_id'];
        }
        return false;
    }
    
    public function findByEmail($email) {
        $stmt = $this->mysqli->prepare("SELECT id, username, email, is_admin FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function updateLastLogin($user_id) {
        $stmt = $this->mysqli->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }
    
    // --- User Management & Password Reset Methods ---

    public function generatePasswordResetToken($email) {
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", time() + 3600); // Token is valid for 1 hour
        $stmt = $this->mysqli->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $email, $token, $expires);
        return $stmt->execute() ? $token : false;
    }

    public function verifyPasswordResetToken($token) {
        $stmt = $this->mysqli->prepare("SELECT email, expires_at FROM password_resets WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        if ($result && strtotime($result['expires_at']) > time()) {
            return $result['email'];
        }
        return false;
    }

    public function resetPassword($email, $password) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $this->mysqli->begin_transaction();
        try {
            $stmt_update = $this->mysqli->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt_update->bind_param("ss", $hashed_password, $email);
            $stmt_update->execute();

            $stmt_delete = $this->mysqli->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt_delete->bind_param("s", $email);
            $stmt_delete->execute();
            
            $this->mysqli->commit();
            return true;
        } catch (Exception $e) {
            $this->mysqli->rollback();
            return false;
        }
    }

    public function register($username, $email, $password, $is_admin = 0) {
        $stmt_check = $this->mysqli->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt_check->bind_param("ss", $username, $email);
        $stmt_check->execute();
        $stmt_check->store_result();
        if ($stmt_check->num_rows > 0) {
            $stmt_check->close();
            return false;
        }
        $stmt_check->close();
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->mysqli->prepare("INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $username, $email, $hashed_password, $is_admin);
        return $stmt->execute() ? $stmt->insert_id : false;
    }

    public function updateProfile($id, $username, $email, $password = '', $is_admin = null) {
        $stmt_check = $this->mysqli->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt_check->bind_param("ssi", $username, $email, $id);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) return false;

        $params = ['username' => $username, 'email' => $email];
        $types = 'ss';
        $sql = "UPDATE users SET username = ?, email = ?";
        if (!empty($password)) {
            $sql .= ", password = ?"; $types .= 's';
            $params['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        if ($is_admin !== null) {
            $sql .= ", is_admin = ?"; $types .= 'i';
            $params['is_admin'] = (int)$is_admin;
        }
        $sql .= " WHERE id = ?"; $types .= 'i';
        $params['id'] = $id;
        $stmt = $this->mysqli->prepare($sql);
        $stmt->bind_param($types, ...array_values($params));
        return $stmt->execute();
    }

    public function delete($id) {
        $this->mysqli->begin_transaction();
        try {
            $stmt_progress = $this->mysqli->prepare("DELETE FROM player_progress WHERE player_id = ?");
            $stmt_progress->bind_param("i", $id);
            $stmt_progress->execute();
            $stmt_progress->close();

            $stmt_tokens = $this->mysqli->prepare("DELETE FROM login_tokens WHERE user_id = ?");
            $stmt_tokens->bind_param("i", $id);
            $stmt_tokens->execute();
            $stmt_tokens->close();

            $stmt_user = $this->mysqli->prepare("DELETE FROM users WHERE id = ?");
            $stmt_user->bind_param("i", $id);
            $stmt_user->execute();
            
            $affected_rows = $stmt_user->affected_rows;
            $stmt_user->close();

            if ($affected_rows > 0) {
                $this->mysqli->commit();
                return true;
            } else {
                $this->mysqli->rollback();
                return false;
            }
        } catch (Exception $e) {
            $this->mysqli->rollback();
            return false;
        }
    }

    public function find($id) {
        $stmt = $this->mysqli->prepare("SELECT id, username, email, is_admin FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function getUsername($user_id) {
        $stmt = $this->mysqli->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result ? $result['username'] : null;
    }

    public function getAll() {
        // MODIFIED: Added u.last_login to the SELECT statement
        $query = "SELECT u.id, u.username, u.email, u.created_at, u.last_login, u.is_admin, COUNT(pp.id) as solved_count 
                  FROM users u 
                  LEFT JOIN player_progress pp ON u.id = pp.player_id AND pp.status = 'solved' 
                  GROUP BY u.id 
                  ORDER BY u.username ASC";
        $result = $this->mysqli->query($query);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}