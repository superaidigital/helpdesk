<?php
// admin_kb_action.php
require_once 'includes/functions.php';
check_auth(['it', 'admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_token();

    $action = $_POST['action'] ?? '';
    $kb_id = isset($_POST['kb_id']) ? (int)$_POST['kb_id'] : 0;
    
    if ($action === 'add_kb' || $action === 'edit_kb') {
        $question = trim($_POST['question'] ?? '');
        // Sanitize the answer by removing any potential HTML tags
        $answer = trim(strip_tags($_POST['answer'] ?? ''));
        $category = trim($_POST['category'] ?? '');
        $created_by = $_SESSION['user_id'];

        if (empty($question) || empty($answer) || empty($category)) {
            redirect_with_message('admin_kb_form.php' . ($kb_id ? '?id='.$kb_id : ''), 'error', 'กรุณากรอกข้อมูลให้ครบทุกช่อง');
        }

        if ($action === 'add_kb') {
            $stmt = $conn->prepare("INSERT INTO knowledge_base (category, question, answer, created_by) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $category, $question, $answer, $created_by);
            $message = 'เพิ่มบทความใหม่เรียบร้อยแล้ว';
        } else { // edit_kb
            $stmt = $conn->prepare("UPDATE knowledge_base SET category=?, question=?, answer=? WHERE id=?");
            $stmt->bind_param("sssi", $category, $question, $answer, $kb_id);
             $message = 'แก้ไขข้อมูลบทความเรียบร้อยแล้ว';
        }
        $stmt->execute();
        $stmt->close();
        redirect_with_message('admin_kb.php', 'success', $message);
    }
    elseif ($action === 'delete_kb' && $kb_id > 0) {
        $stmt = $conn->prepare("DELETE FROM knowledge_base WHERE id = ?");
        $stmt->bind_param("i", $kb_id);
        $stmt->execute();
        $stmt->close();
        redirect_with_message('admin_kb.php', 'success', 'ลบบทความเรียบร้อยแล้ว');
    }
}

// Fallback redirect if the action is not recognized
header("Location: admin_kb.php");
exit();
?>

