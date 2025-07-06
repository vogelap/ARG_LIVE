<?php
// File: arg_game/includes/classes/GameManager.php

class GameManager {
    private $mysqli;

    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }

    /**
     * Checks if a submitted answer for a puzzle is correct.
     * It is case-insensitive and trims whitespace.
     * @param int $puzzle_id The ID of the puzzle.
     * @param string $submitted_answer The answer provided by the player.
     * @return bool True if the answer is correct, false otherwise.
     */
    public function checkSolution($puzzle_id, $submitted_answer) {
        $puzzle_obj = new Puzzle($this->mysqli);
        $puzzle = $puzzle_obj->find($puzzle_id);
        if (!$puzzle) {
            return false;
        }
        return strtolower(trim($submitted_answer)) === strtolower(trim($puzzle['solution']));
    }

    /**
     * Records a puzzle as solved, unlocks the next set of puzzles,
     * and returns all newly unlocked puzzles.
     * @param int $player_id The ID of the player.
     * @param int $puzzle_id The ID of the puzzle just solved.
     * @return array An array of newly unlocked puzzles, each with an 'id' and 'title'.
     */
    public function recordSolve($player_id, $puzzle_id) {
        // Get a list of puzzles that are currently unlocked *before* this solve.
        $previously_unlocked_ids = $this->getUnlockedPuzzleIds($player_id);

        // Update the current puzzle to 'solved'.
        $stmt = $this->mysqli->prepare("UPDATE player_progress SET status = 'solved', solved_at = NOW() WHERE player_id = ? AND puzzle_id = ? AND status = 'unlocked'");
        $stmt->bind_param("ii", $player_id, $puzzle_id);
        
        if ($stmt->execute()) {
            // This method checks for and unlocks all puzzles for which the solved puzzle was a prerequisite.
            $this->unlockNextPuzzles($player_id, $puzzle_id);
            
            // Get the new list of unlocked puzzles and compare it to the old list to find the difference.
            $newly_unlocked_ids = $this->getUnlockedPuzzleIds($player_id);
            $diff_ids = array_diff($newly_unlocked_ids, $previously_unlocked_ids);

            if (empty($diff_ids)) {
                return [];
            }

            // Fetch the details for the newly unlocked puzzles.
            $placeholders = implode(',', array_fill(0, count($diff_ids), '?'));
            $next_puzzles_stmt = $this->mysqli->prepare("SELECT id, title FROM puzzles WHERE id IN ($placeholders) ORDER BY display_order ASC");
            $types = str_repeat('i', count($diff_ids));
            $next_puzzles_stmt->bind_param($types, ...$diff_ids);
            $next_puzzles_stmt->execute();
            $result = $next_puzzles_stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }

    /**
     * Resets all progress for a given player.
     * This uses a transaction to safely delete progress and restart the game.
     * @param int $player_id The ID of the player to reset.
     * @return bool True on success, false on failure.
     */
    public function resetPlayerProgress($player_id) {
        $this->mysqli->begin_transaction();
        try {
            // Delete existing progress
            $stmt_delete = $this->mysqli->prepare("DELETE FROM player_progress WHERE player_id = ?");
            $stmt_delete->bind_param("i", $player_id);
            $stmt_delete->execute();
            $stmt_delete->close();

            // MODIFIED: Reset the 'has_seen_intro' flag for the user
            $stmt_reset_intro = $this->mysqli->prepare("UPDATE users SET has_seen_intro = 0 WHERE id = ?");
            $stmt_reset_intro->bind_param("i", $player_id);
            $stmt_reset_intro->execute();
            $stmt_reset_intro->close();

            // Unlock the initial puzzles for a fresh start
            $this->unlockInitialPuzzles($player_id);
            
            $this->mysqli->commit();
            return true;
        } catch (Exception $e) {
            $this->mysqli->rollback();
            // In a real application, you might want to log the error $e->getMessage()
            return false;
        }
    }


    /**
     * Unlocks all puzzles that have no prerequisites for a new player.
     * @param int $player_id The ID of the player.
     */
    public function unlockInitialPuzzles($player_id) {
        $query = "SELECT id FROM puzzles WHERE is_visible = 1 AND id NOT IN (SELECT DISTINCT puzzle_id FROM puzzle_prerequisites)";
        $result = $this->mysqli->query($query);
        if ($result) {
            $initial_puzzles = $result->fetch_all(MYSQLI_ASSOC);
            foreach ($initial_puzzles as $puzzle) {
                $this->unlockPuzzleForPlayer($player_id, $puzzle['id']);
            }
        }
    }
    
    // --- Helper and Status-Checking Methods ---

    public function isPuzzleUnlocked($player_id, $puzzle_id) {
        $status = $this->getPuzzleStatusForPlayer($player_id, $puzzle_id);
        return $status === 'unlocked' || $status === 'solved';
    }

    public function getPuzzleStatusForPlayer($player_id, $puzzle_id) {
        $stmt = $this->mysqli->prepare("SELECT status FROM player_progress WHERE player_id = ? AND puzzle_id = ?");
        $stmt->bind_param("ii", $player_id, $puzzle_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            return $result->fetch_assoc()['status'];
        }
        return 'locked';
    }

    public function hasPlayerCompletedGame($player_id) {
        $total_puzzles_result = $this->mysqli->query("SELECT COUNT(id) as total FROM puzzles WHERE is_visible = 1");
        $total_puzzles = (int)$total_puzzles_result->fetch_assoc()['total'];
        
        $solved_stmt = $this->mysqli->prepare("SELECT COUNT(id) as solved FROM player_progress WHERE player_id = ? AND status = 'solved'");
        $solved_stmt->bind_param("i", $player_id);
        $solved_stmt->execute();
        $solved_count = (int)$solved_stmt->get_result()->fetch_assoc()['solved'];
        
        return ($total_puzzles > 0 && $total_puzzles === $solved_count);
    }
    
    public function getStorySoFar($player_id) {
        $query = "
            SELECT p.title, pp.solved_at, p.story_text
            FROM player_progress pp
            JOIN puzzles p ON pp.puzzle_id = p.id
            WHERE pp.player_id = ? AND pp.status = 'solved' AND p.story_text IS NOT NULL AND p.story_text != ''
            ORDER BY pp.solved_at ASC
        ";
        $stmt = $this->mysqli->prepare($query);
        $stmt->bind_param("i", $player_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    private function unlockNextPuzzles($player_id, $solved_puzzle_id) {
        $stmt = $this->mysqli->prepare("SELECT puzzle_id FROM puzzle_prerequisites WHERE prerequisite_puzzle_id = ?");
        $stmt->bind_param("i", $solved_puzzle_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $potential_puzzles = $result->fetch_all(MYSQLI_ASSOC);

        foreach ($potential_puzzles as $potential) {
            $puzzle_to_unlock_id = $potential['puzzle_id'];
            if ($this->areAllPrerequisitesSolved($player_id, $puzzle_to_unlock_id)) {
                $this->unlockPuzzleForPlayer($player_id, $puzzle_to_unlock_id);
            }
        }
    }

    private function areAllPrerequisitesSolved($player_id, $puzzle_id) {
        $stmt = $this->mysqli->prepare("SELECT prerequisite_puzzle_id FROM puzzle_prerequisites WHERE puzzle_id = ?");
        $stmt->bind_param("i", $puzzle_id);
        $stmt->execute();
        $prerequisites = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        if (empty($prerequisites)) return true;

        $prereq_ids = array_column($prerequisites, 'prerequisite_puzzle_id');
        $placeholders = implode(',', array_fill(0, count($prereq_ids), '?'));
        
        $stmt_progress = $this->mysqli->prepare("SELECT COUNT(id) as solved_count FROM player_progress WHERE player_id = ? AND status = 'solved' AND puzzle_id IN ($placeholders)");
        $types = "i" . str_repeat('i', count($prereq_ids));
        $stmt_progress->bind_param($types, $player_id, ...$prereq_ids);
        $stmt_progress->execute();
        $solved_count = (int)$stmt_progress->get_result()->fetch_assoc()['solved_count'];
        
        return count($prerequisites) === $solved_count;
    }
    
    private function unlockPuzzleForPlayer($player_id, $puzzle_id) {
        $stmt = $this->mysqli->prepare("INSERT INTO player_progress (player_id, puzzle_id, status, unlocked_at) VALUES (?, ?, 'unlocked', NOW()) ON DUPLICATE KEY UPDATE status = IF(status = 'locked', 'unlocked', status)");
        $stmt->bind_param("ii", $player_id, $puzzle_id);
        return $stmt->execute();
    }

    private function getUnlockedPuzzleIds($player_id) {
        $stmt = $this->mysqli->prepare("SELECT puzzle_id FROM player_progress WHERE player_id = ? AND status = 'unlocked'");
        $stmt->bind_param("i", $player_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $ids = [];
        while ($row = $result->fetch_assoc()) {
            $ids[] = $row['puzzle_id'];
        }
        return $ids;
    }
}