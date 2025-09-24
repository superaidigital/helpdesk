<?php
// issue_action.php
require_once 'includes/functions.php';

// ตรวจสอบสิทธิ์: อนุญาตให้เฉพาะ 'it' และ 'admin' เข้าถึงหน้านี้
check_auth(['it', 'admin']);

$current_user_id = $_SESSION['user_id'];

// --- Action: รับงาน (Accept) ผ่าน GET method ---
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] === 'accept') {
    $issue_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($issue_id > 0) {
        $stmt = $conn->prepare("UPDATE issues SET status = 'in_progress', assigned_to = ? WHERE id = ? AND status = 'pending'");
        $stmt->bind_param("ii", $current_user_id, $issue_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $comment_text = "รับเรื่องแล้ว กำลังดำเนินการตรวจสอบ";
            $comment_stmt = $conn->prepare("INSERT INTO comments (issue_id, user_id, comment_text) VALUES (?, ?, ?)");
            $comment_stmt->bind_param("iis", $issue_id, $current_user_id, $comment_text);
            $comment_stmt->execute();
            $comment_stmt->close();

            $issue_details_q = $conn->prepare("SELECT reporter_contact, title FROM issues WHERE id = ?");
            $issue_details_q->bind_param("i", $issue_id);
            $issue_details_q->execute();
            $issue_details = $issue_details_q->get_result()->fetch_assoc();
            $issue_details_q->close();

            if ($issue_details && filter_var($issue_details['reporter_contact'], FILTER_VALIDATE_EMAIL)) {
                send_email($issue_details['reporter_contact'], "[Helpdesk] เรื่อง #" . $issue_id . " ได้รับการตอบรับแล้ว", "<p>เรื่อง <strong>\"" . htmlspecialchars($issue_details['title']) . "\"</strong> ของท่าน ขณะนี้เจ้าหน้าที่ได้รับเรื่องและกำลังดำเนินการตรวจสอบแล้ว</p>");
            }
        }
        $stmt->close();
    }
    redirect_with_message('it_dashboard.php', 'success', 'รับงานเรียบร้อยแล้ว');
}

// --- Actions: จัดการงานผ่าน POST method จากฟอร์ม ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    validate_csrf_token();
    
    $action = $_POST['action'] ?? '';
    $issue_id = isset($_POST['issue_id']) ? (int)$_POST['issue_id'] : 0;
    if ($issue_id === 0) {
        redirect_with_message('it_dashboard.php', 'error', 'Invalid Issue ID');
    }

    // --- **NEW**: Fetch issue details for validation ---
    $stmt_check = $conn->prepare("SELECT assigned_to, status FROM issues WHERE id = ?");
    $stmt_check->bind_param("i", $issue_id);
    $stmt_check->execute();
    $issue_to_validate = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    if (!$issue_to_validate) {
        redirect_with_message('it_dashboard.php', 'error', 'ไม่พบปัญหาที่ต้องการดำเนินการ');
    }

    // --- **MODIFIED**: Define Permissions based on fetched data ---
    $is_admin = $_SESSION['role'] === 'admin';
    $is_assigned_it = $issue_to_validate['assigned_to'] !== null && $_SESSION['user_id'] === (int)$issue_to_validate['assigned_to'];
    $is_job_open = $issue_to_validate['status'] !== 'done';

    // Admin can do anything. Assigned IT can work on open jobs.
    $can_perform_actions = ($is_assigned_it && $is_job_open) || $is_admin;
    $can_edit_reporter = ($is_assigned_it && $is_job_open) || $is_admin;
    
    // --- Action: แก้ไขข้อมูลผู้แจ้ง ---
    if ($action === 'edit_reporter' && $can_edit_reporter) {
        $reporter_name = trim($_POST['reporter_name'] ?? '');
        $reporter_contact = trim($_POST['reporter_contact'] ?? '');
        $reporter_position = trim($_POST['reporter_position'] ?? '');
        $reporter_department = trim($_POST['reporter_department'] ?? '');
        $reporter_division = trim($_POST['division'] ?? '');

        if (!empty($reporter_name)) {
            $stmt = $conn->prepare("UPDATE issues SET reporter_name = ?, reporter_contact = ?, reporter_position = ?, reporter_department = ?, division = ? WHERE id = ?");
            $stmt->bind_param("sssssi", $reporter_name, $reporter_contact, $reporter_position, $reporter_department, $reporter_division, $issue_id);
            $stmt->execute();
            $stmt->close();
            redirect_with_message("issue_view.php?id=$issue_id", 'success', 'อัปเดตข้อมูลผู้แจ้งเรียบร้อยแล้ว');
        } else {
            redirect_with_message("issue_view.php?id=$issue_id", 'error', 'ข้อมูลไม่ถูกต้อง ไม่สามารถอัปเดตได้');
        }
    } elseif ($action === 'edit_reporter') {
        redirect_with_message("issue_view.php?id=$issue_id", 'error', 'คุณไม่มีสิทธิ์แก้ไขข้อมูลนี้');
    }
    
    // --- Action: อัปเดตสถานะและเพิ่มความคิดเห็น ---
    if (isset($_POST['submit_update']) && $can_perform_actions) {
        $new_status = $_POST['status'];
        $comment_text = trim($_POST['comment_text']);
        $attachment_link = trim($_POST['attachment_link']);
        $completed_at_sql = "";

        if ($new_status === 'done' && $issue_to_validate['status'] !== 'done') {
            $completed_at_sql = ", completed_at = NOW()";
        }
        $stmt = $conn->prepare("UPDATE issues SET status = ? $completed_at_sql WHERE id = ?");
        $stmt->bind_param("si", $new_status, $issue_id);
        $stmt->execute();
        $stmt->close();

        if (!empty($comment_text)) {
            $comment_stmt = $conn->prepare("INSERT INTO comments (issue_id, user_id, comment_text, attachment_link) VALUES (?, ?, ?, ?)");
            $comment_stmt->bind_param("iiss", $issue_id, $current_user_id, $comment_text, $attachment_link);
            $comment_stmt->execute();
            $comment_id = $conn->insert_id;
            $comment_stmt->close();
            
            if (isset($_FILES['comment_files']) && count(array_filter($_FILES['comment_files']['name'])) > 0) {
                $upload_dir = 'uploads/comments/';
                if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
                foreach ($_FILES['comment_files']['name'] as $key => $name) {
                    if ($_FILES['comment_files']['error'][$key] === UPLOAD_ERR_OK) {
                        $tmp_name = $_FILES['comment_files']['tmp_name'][$key];
                        $file_ext = pathinfo($name, PATHINFO_EXTENSION);
                        $new_name = 'comment_' . $comment_id . '_' . uniqid() . '.' . $file_ext;
                        $file_path = $upload_dir . $new_name;
                        if (move_uploaded_file($tmp_name, $file_path)) {
                            $file_stmt = $conn->prepare("INSERT INTO comment_files (comment_id, file_name, file_path) VALUES (?, ?, ?)");
                            $file_stmt->bind_param("iss", $comment_id, $name, $file_path);
                            $file_stmt->execute();
                            $file_stmt->close();
                        }
                    }
                }
            }
        }
        
        if ($new_status === 'done') {
             $issue_details_q = $conn->prepare("SELECT reporter_contact, title FROM issues WHERE id = ?");
            $issue_details_q->bind_param("i", $issue_id);
            $issue_details_q->execute();
            $issue_details = $issue_details_q->get_result()->fetch_assoc();
            $issue_details_q->close();

            if ($issue_details && filter_var($issue_details['reporter_contact'], FILTER_VALIDATE_EMAIL)) {
                $track_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/track_issue.php?id=" . $issue_id;
                $email_body = "<p>เรียน ผู้แจ้ง,</p>"
                            . "<p>เรื่อง <strong>\"" . htmlspecialchars($issue_details['title']) . "\"</strong> ของท่าน เจ้าหน้าที่ได้ดำเนินการแก้ไขเรียบร้อยแล้ว</p>"
                            . "<p>กรุณาตรวจสอบและลงลายมือชื่อเพื่อปิดงาน พร้อมทั้งประเมินความพึงพอใจได้ที่ลิงก์ด้านล่าง</p>"
                            . "<p><a href='$track_link'>$track_link</a></p>";
                send_email($issue_details['reporter_contact'], "[Helpdesk] เรื่อง #" . $issue_id . " ดำเนินการเสร็จสิ้น", $email_body);
            }
        }

        redirect_with_message("issue_view.php?id=$issue_id", 'success', 'อัปเดตข้อมูลเรียบร้อยแล้ว');
    } elseif (isset($_POST['submit_update'])) {
        redirect_with_message("issue_view.php?id=$issue_id", 'error', 'คุณไม่มีสิทธิ์ดำเนินการ');
    }

    // --- Action: ส่งต่องาน ---
    if (isset($_POST['submit_forward']) && $can_perform_actions) {
        $forward_to_id = (int)$_POST['forward_to_user_id'];
        $forward_to_user = getUserById($forward_to_id, $conn);
        
        if ($forward_to_user) {
            $stmt = $conn->prepare("UPDATE issues SET status = 'pending', assigned_to = ? WHERE id = ?");
            $stmt->bind_param("ii", $forward_to_id, $issue_id);
            $stmt->execute();

            $comment_text = "ส่งต่องานให้กับ " . htmlspecialchars($forward_to_user['fullname']);
            $comment_stmt = $conn->prepare("INSERT INTO comments (issue_id, user_id, comment_text) VALUES (?, ?, ?)");
            $comment_stmt->bind_param("iis", $issue_id, $current_user_id, $comment_text);
            $comment_stmt->execute();

            redirect_with_message('it_dashboard.php', 'success', 'ส่งต่องานเรียบร้อยแล้ว');
        }
    } elseif (isset($_POST['submit_forward'])) {
        redirect_with_message("issue_view.php?id=$issue_id", 'error', 'คุณไม่มีสิทธิ์ส่งต่องาน');
    }

    // --- Action: เก็บเป็น Knowledge Base ---
    if (isset($_POST['submit_kb']) && $can_perform_actions) {
        $solution_text = trim($_POST['comment_text']);

        if (empty($solution_text)) {
            redirect_with_message("issue_view.php?id=$issue_id", 'error', 'กรุณาป้อนวิธีแก้ไขในช่องความคิดเห็นก่อน');
        }

        $issue_stmt = $conn->prepare("SELECT title, category FROM issues WHERE id = ?");
        $issue_stmt->bind_param("i", $issue_id);
        $issue_stmt->execute();
        $issue_details = $issue_stmt->get_result()->fetch_assoc();
        $issue_stmt->close();

        if ($issue_details) {
            $kb_stmt = $conn->prepare("INSERT INTO knowledge_base (issue_id_source, category, question, answer, created_by) VALUES (?, ?, ?, ?, ?)");
            $kb_stmt->bind_param("isssi", $issue_id, $issue_details['category'], $issue_details['title'], $solution_text, $current_user_id);
            $kb_stmt->execute();
            $kb_stmt->close();
            
            redirect_with_message("issue_view.php?id=$issue_id", 'success', 'บันทึกวิธีแก้ไขลงในฐานความรู้เรียบร้อยแล้ว');
        }
    } elseif (isset($_POST['submit_kb'])) {
        redirect_with_message("issue_view.php?id=$issue_id", 'error', 'คุณไม่มีสิทธิ์ดำเนินการ');
    }
}
?>