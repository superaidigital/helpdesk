<?php
// profile_action.php
require_once 'includes/functions.php';
check_auth(['user', 'it', 'admin']);

/**
 * ฟังก์ชันสำหรับแปลง Base64 string เป็นไฟล์รูปภาพและบันทึก
 * @param string $base64_string ข้อมูลรูปภาพในรูปแบบ Base64
 * @param string $output_folder โฟลเดอร์ที่ต้องการบันทึก
 * @return string|null مسیرไฟล์ที่บันทึกสำเร็จ หรือ null หากล้มเหลว
 */
function save_base64_image($base64_string, $output_folder) {
    if (empty($base64_string) || !preg_match('/^data:image\/(\w+);base64,/', $base64_string, $type)) {
        return null;
    }
    
    $data = substr($base64_string, strpos($base64_string, ',') + 1);
    $type = strtolower($type[1]); // e.g., 'png'
    $data = base64_decode($data);

    if ($data === false) {
        return null;
    }

    if (!is_dir($output_folder)) {
        mkdir($output_folder, 0777, true);
    }
    
    $file_name = 'avatar_' . uniqid() . '.' . $type;
    $file_path = $output_folder . $file_name;

    if (file_put_contents($file_path, $data)) {
        return $file_path;
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    validate_csrf_token();

    $user_id = $_SESSION['user_id'];
    
    // รับข้อมูลและทำความสะอาด
    $fullname = trim($_POST['fullname']);
    $position = trim($_POST['position']);
    $department = trim($_POST['department']);
    $division = trim($_POST['division']); // รับข้อมูล "ฝ่าย"
    $phone = trim($_POST['phone']);
    $line_id = trim($_POST['line_id']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $cropped_image_data = $_POST['cropped_image_data'] ?? '';

    if (empty($fullname)) {
        redirect_with_message('profile.php', 'error', 'ชื่อ-สกุล ไม่สามารถเว้นว่างได้');
    }

    $current_user_data = getUserById($user_id, $conn);
    $image_url_to_update = $current_user_data['image_url'];

    // จัดการรูปภาพ
    if (!empty($cropped_image_data)) {
        $new_image_path = save_base64_image($cropped_image_data, 'uploads/avatars/');
        if ($new_image_path) {
            // ลบรูปเก่า (ถ้ามีและไม่ใช่รูปดีฟอลต์หรือ placeholder)
            if (!empty($current_user_data['image_url']) && $current_user_data['image_url'] !== 'assets/images/user.png' && file_exists($current_user_data['image_url'])) {
                unlink($current_user_data['image_url']);
            }
            $image_url_to_update = $new_image_path;
        }
    }
    
    // ตรวจสอบและอัปเดตรหัสผ่าน (ถ้ามีการกรอก)
    if (!empty($new_password)) {
        if ($new_password !== $confirm_password) {
            redirect_with_message('profile.php', 'error', 'รหัสผ่านใหม่ไม่ตรงกัน');
        }
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt_pass = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt_pass->bind_param("si", $hashed_password, $user_id);
        $stmt_pass->execute();
        $stmt_pass->close();
    }

    // อัปเดตข้อมูลอื่นๆ
    $stmt = $conn->prepare("UPDATE users SET fullname=?, position=?, department=?, division=?, phone=?, line_id=?, image_url=? WHERE id=?");
    $stmt->bind_param("sssssssi", $fullname, $position, $department, $division, $phone, $line_id, $image_url_to_update, $user_id);
    
    if ($stmt->execute()) {
        // อัปเดตชื่อใน Session ด้วย เพื่อให้แสดงผลทันทีบน Navbar
        $_SESSION['fullname'] = $fullname;
        redirect_with_message('profile.php', 'success', 'บันทึกข้อมูลโปรไฟล์เรียบร้อยแล้ว');
    } else {
        redirect_with_message('profile.php', 'error', 'เกิดข้อผิดพลาดในการบันทึกข้อมูล');
    }
    $stmt->close();
    $conn->close();

} else {
    // ถ้าไม่ได้เข้ามาอย่างถูกต้อง
    header("Location: profile.php");
    exit();
}
?>

