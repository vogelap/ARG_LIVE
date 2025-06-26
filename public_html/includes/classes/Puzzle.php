<?php
class Puzzle {
    private $mysqli;

    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }

    public function find($id) {
        $stmt = $this->mysqli->prepare("SELECT * FROM puzzles WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function getAll() {
        $result = $this->mysqli->query("SELECT * FROM puzzles ORDER BY display_order ASC, id ASC");
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getNextDisplayOrder() {
        $result = $this->mysqli->query("SELECT MAX(display_order) as max_order FROM puzzles");
        $row = $result->fetch_assoc();
        if ($row && !is_null($row['max_order'])) {
            return (int)$row['max_order'] + 1;
        }
        return 0;
    }
    
    public function getDashboardPuzzlesForPlayer($player_id) {
        $query = "
            SELECT 
                p.id, p.title, p.display_order,
                pp.status, pp.solved_at
            FROM puzzles p
            JOIN player_progress pp ON p.id = pp.puzzle_id
            WHERE p.is_visible = 1
              AND pp.player_id = ?
              AND (pp.status = 'unlocked' OR pp.status = 'solved')
              AND (p.release_time IS NULL OR p.release_time <= NOW())
            ORDER BY p.display_order ASC
        ";
        $stmt = $this->mysqli->prepare($query);
        $stmt->bind_param("i", $player_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getHints($puzzle_id) {
        $stmt = $this->mysqli->prepare("SELECT * FROM hints WHERE puzzle_id = ? ORDER BY display_order ASC");
        $stmt->bind_param("i", $puzzle_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function updateHints($puzzle_id, $hints) {
        $delete_stmt = $this->mysqli->prepare("DELETE FROM hints WHERE puzzle_id = ?");
        $delete_stmt->bind_param("i", $puzzle_id);
        $delete_stmt->execute();

        if (!empty($hints)) {
            $insert_stmt = $this->mysqli->prepare("INSERT INTO hints (puzzle_id, hint_text, display_order) VALUES (?, ?, ?)");
            foreach ($hints as $index => $hint_text) {
                if (!empty($hint_text)) {
                    $order = $index;
                    $insert_stmt->bind_param("isi", $puzzle_id, $hint_text, $order);
                    $insert_stmt->execute();
                }
            }
        }
    }
    
    public function delete($id) {
        $stmt = $this->mysqli->prepare("DELETE FROM puzzles WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function getMismatchedPuzzles($media_url) {
        $stmt = $this->mysqli->prepare("SELECT id, title FROM puzzles WHERE media_url = ?");
        $stmt->bind_param("s", $media_url);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}