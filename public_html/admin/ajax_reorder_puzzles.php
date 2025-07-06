<?php
require_once __DIR__ . '/../includes/session.php';
require_admin();
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

// Decode the JSON payload from the request body
$data = json_decode(file_get_contents('php://input'), true);

// Check if the order data was sent and is an array
if (isset($data['order']) && is_array($data['order'])) {
    $puzzle_ids = $data['order'];

    // Use a database transaction for data integrity
    $mysqli->begin_transaction();
    try {
        // Prepare the statement once outside the loop for efficiency
        $stmt = $mysqli->prepare("UPDATE puzzles SET display_order = ? WHERE id = ?");

        foreach ($puzzle_ids as $index => $id) {
            $order = $index; // The new display order is the array index
            $puzzle_id = (int)$id; // Sanitize the ID

            // Bind parameters and execute
            $stmt->bind_param("ii", $order, $puzzle_id);
            $stmt->execute();
        }

        $stmt->close();
        $mysqli->commit(); // Commit the changes if all updates were successful
        echo json_encode(['success' => true, 'message' => 'Puzzle order updated successfully.']);

    } catch (Exception $e) {
        $mysqli->rollback(); // Roll back changes on error
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    // Respond with an error if the data is missing or invalid
    echo json_encode(['success' => false, 'message' => 'Invalid or missing order data.']);
}