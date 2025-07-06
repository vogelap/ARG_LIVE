<?php
// File: tests/UserTest.php

use PHPUnit\Framework\TestCase;

class UserTest extends TestCase {
    private $mysqli;
    private $user;

    protected function setUp(): void {
        $this->mysqli = new MockMysqli();
        $this->user = new User($this->mysqli);
    }

    public function testRegisterSuccess() {
        // Expect a check for existing user, which should return no results
        $this->mysqli->expected_results['/SELECT id FROM users WHERE/'] = [];
        // Expect an insert, we don't need to define a result for this
        $this->mysqli->insert_id = 101; // Simulate a new user ID

        $result = $this->user->register('testplayer', 'player@example.com', 'password123');

        $this->assertEquals(101, $result);
        // Check if the INSERT query was prepared
        $this->assertStringContainsString('INSERT INTO users', end($this->mysqli->prepared_statements)->query);
    }

    public function testRegisterFailureDuplicate() {
        // Simulate that the user/email already exists
        $this->mysqli->expected_results['/SELECT id FROM users WHERE/'] = [['id' => 1]];

        $result = $this->user->register('testplayer', 'player@example.com', 'password123');
        $this->assertFalse($result);
    }

    public function testLoginSuccess() {
        $hashed_password = password_hash('correctpass', PASSWORD_DEFAULT);
        $this->mysqli->expected_results['/SELECT id, username, password, is_admin FROM users WHERE email =/'] = [
            ['id' => 1, 'username' => 'testuser', 'password' => $hashed_password, 'is_admin' => 0]
        ];

        $result = User::login($this->mysqli, 'user@example.com', 'correctpass');
        $this->assertIsArray($result);
        $this->assertEquals(1, $result['id']);
    }

    public function testLoginFailure() {
        // No user found
        $this->mysqli->expected_results['/SELECT id, username, password, is_admin FROM users WHERE email =/'] = [];
        $result = User::login($this->mysqli, 'user@example.com', 'wrongpassword');
        $this->assertFalse($result);
    }

    public function testUpdateProfile() {
        // Expect a check for duplicate username/email which should return empty
        $this->mysqli->expected_results['/SELECT id FROM users WHERE \(username = \? OR email = \?\) AND id != \?/'] = [];
        
        $result = $this->user->updateProfile(1, 'newname', 'new@example.com', 'newpassword', 0);
        
        $this->assertTrue($result);
        // Check if the UPDATE query was prepared and contains the password field
        $this->assertStringContainsString('UPDATE users SET username = ?, email = ?, password = ?, is_admin = ? WHERE id = ?', end($this->mysqli->prepared_statements)->query);
    }

    public function testPasswordResetCycle() {
        $this->mysqli->insert_id = 1; // For token creation

        // 1. Generate token
        $token = $this->user->generatePasswordResetToken('user@example.com');
        $this->assertIsString($token);
        $this->assertStringContainsString('INSERT INTO password_resets', end($this->mysqli->prepared_statements)->query);

        // 2. Verify token
        $this->mysqli->expected_results['/SELECT email, expires_at FROM password_resets WHERE token =/'] = [
            ['email' => 'user@example.com', 'expires_at' => date('Y-m-d H:i:s', time() + 3600)]
        ];
        $email = $this->user->verifyPasswordResetToken($token);
        $this->assertEquals('user@example.com', $email);

        // 3. Reset password
        $result = $this->user->resetPassword('user@example.com', 'newpassword');
        $this->assertTrue($result);
        
        // Check that both an UPDATE and a DELETE statement were prepared in the transaction
        $last_two_queries = array_slice(array_map(fn($s) => $s->query, $this->mysqli->prepared_statements), -2);
        $this->assertStringContainsString('UPDATE users SET password = ? WHERE email = ?', $last_two_queries[0]);
        $this->assertStringContainsString('DELETE FROM password_resets WHERE email = ?', $last_two_queries[1]);
    }

    public function testPlayerViewTokenCycle() {
        // 1. Generate token
        $token = $this->user->generateLoginToken(1);
        $this->assertIsString($token);
        $this->assertEquals(64, strlen($token));

        // 2. Verify token
        $this->mysqli->expected_results['/SELECT user_id FROM login_tokens WHERE token =/'] = [['user_id' => 1]];
        $user_id = $this->user->verifyLoginToken($token);
        $this->assertEquals(1, $user_id);
        
        // Check that the DELETE statement was prepared
        $this->assertStringContainsString('DELETE FROM login_tokens WHERE token = ?', end($this->mysqli->prepared_statements)->query);
    }
}
