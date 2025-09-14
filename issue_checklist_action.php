<?php
// issue_checklist_action.php
require_once 'includes/functions.php';
header('Content-Type: application/json');

// อนุญาตให้ IT และ Admin เท่านั้นที่ทำ action ได้
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['it', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$issue_id = $data['issue_id'] ?? 0;
$item_description = $data['item_description'] ?? '';
$is_checked = isset($data['is_checked']) ? ($data['is_checked'] ? 1 : 0) : null;
$item_value = $data['item_value'] ?? null;
$current_user_id = $_SESSION['user_id'];

if ($issue_id > 0 && !empty($item_description)) {
    // Check if the item already exists for this issue
    $stmt_check = $conn->prepare("SELECT id FROM issue_checklist WHERE issue_id = ? AND item_description = ?");
    $stmt_check->bind_param("is", $issue_id, $item_description);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    
    $success = false;
    if ($result->num_rows > 0) {
        // Update existing item
        $row = $result->fetch_assoc();
        $checklist_id = $row['id'];
        
        if (!is_null($is_checked)) {
            // Update checkbox state
            $stmt_update = $conn->prepare("UPDATE issue_checklist SET is_checked = ?, checked_by = ?, checked_at = NOW() WHERE id = ?");
            $stmt_update->bind_param("iii", $is_checked, $current_user_id, $checklist_id);
            $success = $stmt_update->execute();
            $stmt_update->close();
        } elseif (!is_null($item_value)) {
            // Update text value (for 'อื่นๆ')
            $stmt_update = $conn->prepare("UPDATE issue_checklist SET item_value = ?, checked_by = ?, checked_at = NOW() WHERE id = ?");
            $stmt_update->bind_param("sii", $item_value, $current_user_id, $checklist_id);
            $success = $stmt_update->execute();
            $stmt_update->close();
        }

    } else {
        // Insert new item
        $stmt_insert = $conn->prepare("INSERT INTO issue_checklist (issue_id, item_description, is_checked, item_value, checked_by, checked_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $is_checked_db = $is_checked ?? 0; // Default to unchecked if not provided
        $stmt_insert->bind_param("isissi", $issue_id, $item_description, $is_checked_db, $item_value, $current_user_id);
        $success = $stmt_insert->execute();
        $stmt_insert->close();
    }

    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid data.']);
}

$conn->close();
?>