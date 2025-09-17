<?php
// issue_checklist_action.php
require_once 'includes/functions.php';

// Set header to return JSON
header('Content-Type: application/json');

// Wrap the entire script in a try-catch block for robust error handling
try {
    // Check for user permission
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['it', 'admin'])) {
        throw new Exception('Permission denied');
    }

    // Decode incoming JSON data
    $data = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data provided.');
    }

    $issue_id = $data['issue_id'] ?? 0;
    $checklist_data = $data['checklist_data'] ?? null;
    $current_user_id = $_SESSION['user_id'];

    // Validate the received data
    if ($issue_id <= 0 || !is_array($checklist_data)) {
        throw new Exception('Invalid or empty data provided.');
    }

    // --- Database Transaction ---
    // Use a transaction to ensure all updates succeed or none do.
    $conn->autocommit(FALSE);
    if (!$conn->begin_transaction()) {
        throw new Exception('Failed to start database transaction.');
    }

    // Prepare statements for efficiency
    $stmt_check = $conn->prepare("SELECT id FROM issue_checklist WHERE issue_id = ? AND item_description = ?");
    $stmt_update = $conn->prepare("UPDATE issue_checklist SET is_checked = ?, item_value = ?, checked_by = ?, checked_at = NOW() WHERE id = ?");
    $stmt_insert = $conn->prepare("INSERT INTO issue_checklist (issue_id, item_description, is_checked, item_value, checked_by, checked_at) VALUES (?, ?, ?, ?, ?, NOW())");

    foreach ($checklist_data as $description => $item) {
        $is_checked = !empty($item['checked']) ? 1 : 0;
        $item_value = $item['value'] ?? null;

        // Check if the item already exists for this issue
        $stmt_check->bind_param("is", $issue_id, $description);
        $stmt_check->execute();
        $result = $stmt_check->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing item
            $row = $result->fetch_assoc();
            $checklist_id = $row['id'];
            $stmt_update->bind_param("isii", $is_checked, $item_value, $current_user_id, $checklist_id);
            if (!$stmt_update->execute()) {
                throw new Exception("Failed to update item: " . $description . " - " . $stmt_update->error);
            }
        } else {
            // Insert new item
            $stmt_insert->bind_param("isisi", $issue_id, $description, $is_checked, $item_value, $current_user_id);
            if (!$stmt_insert->execute()) {
                throw new Exception("Failed to insert item: " . $description . " - " . $stmt_insert->error);
            }
        }
    }
    
    // Close prepared statements
    $stmt_check->close();
    $stmt_update->close();
    $stmt_insert->close();

    // If all queries were successful, commit the transaction
    if (!$conn->commit()) {
        throw new Exception('Failed to commit transaction.');
    }

    // Send a success response
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // An error occurred, rollback the transaction if active
    if ($conn->ping() && $conn->autocommit) {
         $conn->rollback();
    }
   
    // Log the error (optional but recommended for production)
    error_log("Checklist Action Error: " . $e->getMessage());

    // Send a JSON error response
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'PHP Fatal Error: ' . $e->getMessage()]);

} finally {
    // Always restore autocommit mode
    if (isset($conn) && $conn->ping()) {
        $conn->autocommit(TRUE);
    }
    // The connection will be closed by the calling script's footer
}
?>

