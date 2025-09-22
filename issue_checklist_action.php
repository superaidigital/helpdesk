<?php
// issue_checklist_action.php
require_once 'includes/functions.php';

// Set header to return JSON
header('Content-Type: application/json');

// Wrap the entire script in a try-catch block for robust error handling
try {
    // Check for user permission (basic login check)
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['it', 'admin'])) {
        throw new Exception('Permission denied: Not logged in as IT/Admin.');
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

    // --- **NEW**: Detailed Permission Check ---
    $stmt_check_perm = $conn->prepare("SELECT assigned_to, status FROM issues WHERE id = ?");
    $stmt_check_perm->bind_param("i", $issue_id);
    $stmt_check_perm->execute();
    $issue_to_validate = $stmt_check_perm->get_result()->fetch_assoc();
    $stmt_check_perm->close();

    if (!$issue_to_validate) {
        throw new Exception('Issue not found.');
    }

    $is_assigned_it = $current_user_id === (int)$issue_to_validate['assigned_to'];
    $is_job_open = $issue_to_validate['status'] !== 'done';
    
    if (!$is_assigned_it || !$is_job_open) {
        throw new Exception('Permission denied: You are not assigned to this issue or it is already closed.');
    }

    // --- Database Transaction ---
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

        $stmt_check->bind_param("is", $issue_id, $description);
        $stmt_check->execute();
        $result = $stmt_check->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $checklist_id = $row['id'];
            $stmt_update->bind_param("isii", $is_checked, $item_value, $current_user_id, $checklist_id);
            if (!$stmt_update->execute()) {
                throw new Exception("Failed to update item: " . $description . " - " . $stmt_update->error);
            }
        } else {
            $stmt_insert->bind_param("isisi", $issue_id, $description, $is_checked, $item_value, $current_user_id);
            if (!$stmt_insert->execute()) {
                throw new Exception("Failed to insert item: " . $description . " - " . $stmt_insert->error);
            }
        }
    }
    
    $stmt_check->close();
    $stmt_update->close();
    $stmt_insert->close();

    if (!$conn->commit()) {
        throw new Exception('Failed to commit transaction.');
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($conn->ping() && $conn->autocommit) {
         $conn->rollback();
    }
    error_log("Checklist Action Error: " . $e->getMessage());
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);

} finally {
    if (isset($conn) && $conn->ping()) {
        $conn->autocommit(TRUE);
    }
}
?>
