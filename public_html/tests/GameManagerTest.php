<?php
// File: tests/GameManagerTest.php

use PHPUnit\Framework\TestCase;

class GameManagerTest extends TestCase {
    private $mysqli;
    private $game_manager;

    protected function setUp(): void {
        $this->mysqli = new MockMysqli();
        $this->game_manager = new GameManager($this->mysqli);
    }

    public function testCheckSolutionCorrectAndIncorrect() {
        $this->mysqli->expected_results['/SELECT \* FROM puzzles WHERE id =/'] = [
            ['id' => 1, 'solution' => 'Correct Answer']
        ];
        
        $this->assertTrue($this->game_manager->checkSolution(1, 'correct answer ')); // Test case-insensitivity and trim
        $this->assertFalse($this->game_manager->checkSolution(1, 'wrong answer'));
    }

    public function testHasPlayerCompletedGame() {
        // Simulate a game with 5 total puzzles
        $this->mysqli->expected_results['/SELECT COUNT\(id\) as total FROM puzzles WHERE is_visible = 1/'] = [['total' => 5]];
        // Simulate the player has solved all 5
        $this->mysqli->expected_results['/SELECT COUNT\(id\) as solved FROM player_progress WHERE player_id = \? AND status = \'solved\'/'] = [['solved' => 5]];
        
        $this->assertTrue($this->game_manager->hasPlayerCompletedGame(1));
    }
    
    public function testHasPlayerNotCompletedGame() {
        // Simulate a game with 5 total puzzles
        $this->mysqli->expected_results['/SELECT COUNT\(id\) as total FROM puzzles WHERE is_visible = 1/'] = [['total' => 5]];
        // Simulate the player has solved only 4
        $this->mysqli->expected_results['/SELECT COUNT\(id\) as solved FROM player_progress WHERE player_id = \? AND status = \'solved\'/'] = [['solved' => 4]];
        
        $this->assertFalse($this->game_manager->hasPlayerCompletedGame(1));
    }
    
    public function testRecordSolveAndUnlockNext() {
        // --- Setup the test scenario ---
        // Player 1 solves Puzzle 10.
        // Puzzle 20 requires Puzzle 10.
        
        // 1. Get currently unlocked puzzles (assume none for this test)
        $this->mysqli->expected_results['/SELECT puzzle_id FROM player_progress WHERE player_id = \? AND status = \'unlocked\'/'] = [];

        // 2. recordSolve() will update the puzzle to 'solved'
        // We don't need to mock this, just check the query is prepared.

        // 3. unlockNextPuzzles() is called. It checks what puzzles depend on the one just solved.
        // Simulate that Puzzle 20 requires Puzzle 10.
        $this->mysqli->expected_results['/SELECT puzzle_id FROM puzzle_prerequisites WHERE prerequisite_puzzle_id = \?/'] = [['puzzle_id' => 20]];
        
        // 4. areAllPrerequisitesSolved() is called for Puzzle 20.
        // Simulate that Puzzle 20 only has one prerequisite (Puzzle 10).
        $this->mysqli->expected_results['/SELECT prerequisite_puzzle_id FROM puzzle_prerequisites WHERE puzzle_id = \?/'] = [['prerequisite_puzzle_id' => 10]];

        // Simulate that the player has now solved that prerequisite.
        $this->mysqli->expected_results['/SELECT COUNT\(id\) as solved_count FROM player_progress WHERE player_id = \? AND status = \'solved\' AND puzzle_id IN/'] = [['solved_count' => 1]];

        // 5. After unlocking, recordSolve() will check for the list of newly unlocked puzzles.
        // We will now simulate that Puzzle 20 is unlocked.
        $this->mysqli->expected_results['/SELECT puzzle_id FROM player_progress WHERE player_id = \? AND status = \'unlocked\'/'] = [['puzzle_id' => 20]];
        
        // Finally, it fetches the title of the newly unlocked puzzle.
        $this->mysqli->expected_results['/SELECT id, title FROM puzzles WHERE id IN/'] = [['id' => 20, 'title' => 'The Next Puzzle']];

        // --- Execute the method ---
        $newly_unlocked = $this->game_manager->recordSolve(1, 10);
        
        // --- Assertions ---
        $this->assertIsArray($newly_unlocked);
        $this->assertCount(1, $newly_unlocked);
        $this->assertEquals('The Next Puzzle', $newly_unlocked[0]['title']);

        // Check that the correct sequence of queries was prepared
        $queries = array_map(fn($s) => $s->query, $this->mysqli->prepared_statements);
        $this->assertStringContainsString("UPDATE player_progress SET status = 'solved'", $queries[0]);
        $this->assertStringContainsString("SELECT puzzle_id FROM puzzle_prerequisites WHERE prerequisite_puzzle_id = ?", $queries[1]);
        $this->assertStringContainsString("SELECT prerequisite_puzzle_id FROM puzzle_prerequisites WHERE puzzle_id = ?", $queries[2]);
        $this->assertStringContainsString("SELECT COUNT(id) as solved_count FROM player_progress", $queries[3]);
        $this->assertStringContainsString("INSERT INTO player_progress (player_id, puzzle_id, status, unlocked_at) VALUES (?, ?, 'unlocked', NOW())", $queries[4]);
    }
}
