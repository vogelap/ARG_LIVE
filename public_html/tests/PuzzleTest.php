<?php
// File: tests/PuzzleTest.php

use PHPUnit\Framework\TestCase;

class PuzzleTest extends TestCase {
    private $mysqli;
    private $puzzle_manager;

    protected function setUp(): void {
        $this->mysqli = new MockMysqli();
        $this->puzzle_manager = new Puzzle($this->mysqli);
    }

    public function testFind() {
        $this->mysqli->expected_results['/SELECT \* FROM puzzles WHERE id =/'] = [
            ['id' => 10, 'title' => 'Test Puzzle', 'solution' => 'test']
        ];
        $puzzle = $this->puzzle_manager->find(10);
        $this->assertIsArray($puzzle);
        $this->assertEquals('Test Puzzle', $puzzle['title']);
    }

    public function testGetAll() {
        $this->mysqli->expected_results['/SELECT \* FROM puzzles ORDER BY/'] = [
            ['id' => 1, 'title' => 'Puzzle A', 'display_order' => 1],
            ['id' => 2, 'title' => 'Puzzle B', 'display_order' => 2]
        ];
        $puzzles = $this->puzzle_manager->getAll();
        $this->assertCount(2, $puzzles);
        $this->assertEquals('Puzzle A', $puzzles[0]['title']);
    }

    public function testGetHints() {
        $this->mysqli->expected_results['/SELECT \* FROM hints WHERE puzzle_id =/'] = [
            ['id' => 1, 'hint_text' => 'Hint 1'],
            ['id' => 2, 'hint_text' => 'Hint 2']
        ];
        $hints = $this->puzzle_manager->getHints(1);
        $this->assertCount(2, $hints);
        $this->assertEquals('Hint 1', $hints[0]['hint_text']);
    }

    public function testDelete() {
        $this->mysqli->affected_rows = 1;
        $result = $this->puzzle_manager->delete(1);
        $this->assertTrue($result);
        $this->assertStringContainsString('DELETE FROM puzzles WHERE id = ?', end($this->mysqli->prepared_statements)->query);
    }
    
    public function testUpdateHints() {
        $this->puzzle_manager->updateHints(1, ['New Hint 1', 'New Hint 2']);
        
        // Check that it first deletes old hints, then inserts new ones
        $delete_query = $this->mysqli->prepared_statements[0]->query;
        $insert_query = $this->mysqli->prepared_statements[1]->query;
        
        $this->assertStringContainsString('DELETE FROM hints WHERE puzzle_id = ?', $delete_query);
        $this->assertStringContainsString('INSERT INTO hints (puzzle_id, hint_text, display_order) VALUES (?, ?, ?)', $insert_query);
    }

    public function testGetNextDisplayOrder() {
        $this->mysqli->expected_results['/SELECT MAX\(display_order\)/'] = [['max_order' => 99]];
        $next_order = $this->puzzle_manager->getNextDisplayOrder();
        $this->assertEquals(100, $next_order);
    }
}