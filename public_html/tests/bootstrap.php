<?php
// File: tests/bootstrap.php

// Load the project's configuration and autoloader
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

// Load the classes to be tested
require_once __DIR__ . '/../includes/classes/User.php';
require_once __DIR__ . '/../includes/classes/Puzzle.php';
require_once __DIR__ . '/../includes/classes/GameManager.php';


/**
 * A flexible mock of the mysqli class for testing purposes.
 * It allows tests to define expected results for specific queries.
 */
class MockMysqli {
    public $expected_results = [];
    public $prepared_statements = [];
    public $transaction_active = false;
    public $insert_id = 1;
    public $affected_rows = 1;

    public function prepare($query) {
        $stmt = new MockMysqli_Stmt($this, $query);
        $this->prepared_statements[] = $stmt;
        return $stmt;
    }

    public function query($query) {
        // Return a predefined result for direct queries
        foreach ($this->expected_results as $regex => $result) {
            if (preg_match($regex, $query)) {
                return new MockMysqli_Result($result);
            }
        }
        return new MockMysqli_Result([]); // Default to empty result
    }

    public function begin_transaction() { $this->transaction_active = true; }
    public function commit() { $this->transaction_active = false; }
    public function rollback() { $this->transaction_active = false; }
    public function close() {}
}

/**
 * A mock of the mysqli_stmt class.
 */
class MockMysqli_Stmt {
    private $mysqli;
    public $query;
    public $params = [];
    public $num_rows = 0;

    public function __construct($mysqli, $query) {
        $this->mysqli = $mysqli;
        $this->query = $query;
    }

    public function bind_param($types, ...$params) {
        $this->params = $params;
    }

    public function execute() {
        return true; // Assume execution is always successful for the mock
    }

    public function get_result() {
        foreach ($this->mysqli->expected_results as $regex => $resultData) {
            if (preg_match($regex, $this->query)) {
                // You can add more logic here to filter results based on $this->params if needed
                return new MockMysqli_Result($resultData);
            }
        }
        return new MockMysqli_Result([]);
    }
    
    public function store_result() {
        // This is tricky to mock perfectly without more context,
        // but for many cases, we can simulate num_rows based on expected results.
        foreach ($this->mysqli->expected_results as $regex => $resultData) {
            if (preg_match($regex, $this->query)) {
                $this->num_rows = count($resultData);
                return;
            }
        }
        $this->num_rows = 0;
    }
    
    public function __get($name) {
        if ($name === 'insert_id') return $this->mysqli->insert_id;
        if ($name === 'affected_rows') return $this->mysqli->affected_rows;
        return null;
    }

    public function close() {}
}

/**
 * A mock of the mysqli_result class.
 */
class MockMysqli_Result {
    private $data;
    private $position = 0;

    public function __construct($data) {
        // Ensure data is an array of associative arrays
        $this->data = (is_array(reset($data))) ? $data : [$data];
        if (empty($data) || !is_array(reset($data))) {
            $this->data = [];
        }
    }

    public function fetch_assoc() {
        if (isset($this->data[$this->position])) {
            return $this->data[$this->position++];
        }
        return null;
    }

    public function fetch_all($mode) {
        return $this->data;
    }
    
    public function __get($name) {
        if ($name === 'num_rows') return count($this->data);
        return null;
    }
}
