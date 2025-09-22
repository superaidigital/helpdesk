<?php
// submit_issue.php
require_once 'includes/functions.php'; // This starts session

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. รับข้อมูลจากฟอร์มทั้งหมด
    $reporter_name = trim($_POST['reporter_name'] ?? '');
    $reporter_contact = trim($_POST['reporter_contact'] ?? '');
    $reporter_position = trim($_POST['reporter_position'] ?? null);
    $reporter_department = trim($_POST['reporter_department'] ?? null);
    $category = trim($_POST['category'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $urgency = trim($_POST['urgency'] ?? 'ปกติ');
    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null; // Get user ID if logged in

    // 2. บันทึกข้อมูลหลักของปัญหาลงในตาราง `issues`
    $stmt = $conn->prepare("INSERT INTO issues (user_id, reporter_name, reporter_contact, reporter_position, reporter_department, category, title, description, urgency) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssssss", $user_id, $reporter_name, $reporter_contact, $reporter_position, $reporter_department, $category, $title, $description, $urgency);
    
    if ($stmt->execute()) {
        $issue_id = $conn->insert_id; // ดึง ID ของเรื่องที่เพิ่งสร้าง

        // 3. จัดการไฟล์ที่อัปโหลด (ถ้ามี)
        if (isset($_FILES['issue_files']) && count(array_filter($_FILES['issue_files']['name'])) > 0) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            foreach ($_FILES['issue_files']['name'] as $key => $name) {
                if ($_FILES['issue_files']['error'][$key] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['issue_files']['tmp_name'][$key];
                    
                    // สร้างชื่อไฟล์ใหม่ที่ไม่ซ้ำกันเพื่อป้องกันการเขียนทับ
                    $file_extension = pathinfo($name, PATHINFO_EXTENSION);
                    $new_file_name = 'issue_' . $issue_id . '_' . uniqid() . '.' . $file_extension;
                    $file_path = $upload_dir . $new_file_name;
                    
                    if (move_uploaded_file($tmp_name, $file_path)) {
                        // บันทึกข้อมูลไฟล์ลงตาราง `issue_files`
                        $file_stmt = $conn->prepare("INSERT INTO issue_files (issue_id, file_name, file_path) VALUES (?, ?, ?)");
                        $file_stmt->bind_param("iss", $issue_id, $name, $file_path);
                        $file_stmt->execute();
                        $file_stmt->close();
                    }
                }
            }
        }

        // 4. ส่งอีเมลแจ้งเตือนเจ้าหน้าที่
        $it_notification_email = "it.support@example.com"; // <-- TODO: This should be configured in a central place
        $email_subject = "[Helpdesk] มีเรื่องแจ้งใหม่ #" . $issue_id . ": " . $title;
        $email_body = "<h2>มีเรื่องแจ้งใหม่ในระบบ Helpdesk</h2>"
                    . "<p><strong>หมายเลข:</strong> #$issue_id</p>"
                    . "<p><strong>ผู้แจ้ง:</strong> " . htmlspecialchars($reporter_name) . "</p>"
                    . "<p><strong>หัวข้อ:</strong> " . htmlspecialchars($title) . "</p>"
                    . "<p><strong>หมวดหมู่:</strong> " . htmlspecialchars($category) . "</p>"
                    . "<p>กรุณาเข้าระบบเพื่อตรวจสอบและรับงาน</p>";
        
        // send_email($it_notification_email, $email_subject, $email_body);
        
        // 5. ส่งต่อไปยังหน้าขอบคุณ
        header("Location: public_thankyou.php?id=" . $issue_id);
        exit();

    } else {
        // กรณีเกิดข้อผิดพลาดในการบันทึกข้อมูลหลัก
        error_log("Submit Issue Error: " . $stmt->error);
        redirect_with_message('public_form.php', 'error', 'เกิดข้อผิดพลาดในการบันทึกข้อมูล กรุณาลองใหม่อีกครั้ง');
    }

    $stmt->close();
    $conn->close();

} else {
    // ถ้าไม่ได้เข้ามาผ่าน POST method
    header("Location: public_form.php");
    exit();
}
