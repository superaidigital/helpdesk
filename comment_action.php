<?php
// comment_action.php
require_once 'includes/functions.php';

check_auth(['it', 'admin']); // Only IT and Admins can perform these actions

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_token();

    $action = $_POST['action'] ?? '';
    $comment_id = isset($_POST['comment_id']) ? (int)$_POST['comment_id'] : 0;
    $issue_id = isset($_POST['issue_id']) ? (int)$_POST['issue_id'] : 0; // For redirecting back

    if ($comment_id <= 0 || $issue_id <= 0) {
        redirect_with_message("issue_view.php?id=$issue_id", 'error', 'ข้อมูลไม่ถูกต้อง');
    }

    // --- Fetch comment to check ownership ---
    $stmt = $conn->prepare("SELECT user_id FROM comments WHERE id = ?");
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        redirect_with_message("issue_view.php?id=$issue_id", 'error', 'ไม่พบความคิดเห็นที่ต้องการ');
    }
    $comment_owner_id = $result->fetch_assoc()['user_id'];
    $stmt->close();

    // --- Permission Check ---
    $is_admin = $_SESSION['role'] === 'admin';
    $is_owner = $_SESSION['user_id'] == $comment_owner_id;

    if (!$is_admin && !$is_owner) {
        redirect_with_message("issue_view.php?id=$issue_id", 'error', 'คุณไม่มีสิทธิ์ดำเนินการ');
    }
    
    // --- Perform Action ---
    if ($action === 'edit_comment') {
        $comment_text = trim($_POST['comment_text'] ?? '');
        if (empty($comment_text)) {
            redirect_with_message("issue_view.php?id=$issue_id", 'error', 'เนื้อหาความคิดเห็นห้ามว่าง');
        }

        $update_stmt = $conn->prepare("UPDATE comments SET comment_text = ? WHERE id = ?");
        $update_stmt->bind_param("si", $comment_text, $comment_id);
        $update_stmt->execute();
        $update_stmt->close();
        redirect_with_message("issue_view.php?id=$issue_id", 'success', 'แก้ไขความคิดเห็นเรียบร้อยแล้ว');

    } elseif ($action === 'delete_comment') {
        // Also delete associated files
        $files_to_delete = getCommentFiles($comment_id, $conn);
        foreach ($files_to_delete as $file) {
            if (file_exists($file['file_path'])) {
                unlink($file['file_path']);
            }
        }
        
        // Delete from file table and comment table
        $delete_files_stmt = $conn->prepare("DELETE FROM comment_files WHERE comment_id = ?");
        $delete_files_stmt->bind_param("i", $comment_id);
        $delete_files_stmt->execute();
        $delete_files_stmt->close();

        $delete_comment_stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
        $delete_comment_stmt->bind_param("i", $comment_id);
        $delete_comment_stmt->execute();
        $delete_comment_stmt->close();
        
        redirect_with_message("issue_view.php?id=$issue_id", 'success', 'ลบความคิดเห็นเรียบร้อยแล้ว');
    }
}

// Fallback redirect
$issue_id_fallback = isset($_POST['issue_id']) ? (int)$_POST['issue_id'] : 0;
header("Location: issue_view.php" . ($issue_id_fallback > 0 ? "?id=$issue_id_fallback" : ""));
exit();
?>
