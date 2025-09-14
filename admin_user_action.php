<?php
// admin_user_action.php
require_once 'includes/functions.php';
check_auth(['admin']);

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_token();
    
    $action = $_POST['action'] ?? '';
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    
    if ($action === 'add_user') {
        // --- Action: เพิ่มผู้ใช้งานใหม่ ---
        $fullname = trim($_POST['fullname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $position = trim($_POST['position'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $division = trim($_POST['division'] ?? ''); // Get Division data
        $phone = trim($_POST['phone'] ?? '');
        $line_id = trim($_POST['line_id'] ?? '');
        $role = $_POST['role'] ?? 'user';
        $cropped_image_data = $_POST['cropped_image_data'] ?? '';

        if (empty($fullname) || empty($email) || empty($password)) {
            redirect_with_message('admin_user_form.php', 'error', 'กรุณากรอกข้อมูล ชื่อ, อีเมล, และรหัสผ่านให้ครบถ้วน');
        }

        $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
             redirect_with_message('admin_user_form.php', 'error', 'มีผู้ใช้งานอีเมลนี้ในระบบแล้ว');
        }
        $stmt_check->close();

        $new_image_path = null;
        if (!empty($cropped_image_data)) {
            $new_image_path = save_base64_image($cropped_image_data, 'uploads/avatars/');
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (fullname, email, position, department, division, phone, line_id, role, password, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssss", $fullname, $email, $position, $department, $division, $phone, $line_id, $role, $hashed_password, $new_image_path);
        $stmt->execute();
        $stmt->close();

        redirect_with_message('admin_users.php', 'success', 'เพิ่มผู้ใช้งานใหม่เรียบร้อยแล้ว');

    } elseif ($action === 'edit_user' && $user_id > 0) {
        // --- Action: แก้ไขข้อมูลผู้ใช้งาน ---
        $fullname = trim($_POST['fullname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $position = trim($_POST['position'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $division = trim($_POST['division'] ?? ''); // Get Division data
        $phone = trim($_POST['phone'] ?? '');
        $line_id = trim($_POST['line_id'] ?? '');
        $role = $_POST['role'] ?? 'user';
        $password = $_POST['password'] ?? '';
        $cropped_image_data = $_POST['cropped_image_data'] ?? '';
        
        if (empty($fullname) || empty($email)) {
            redirect_with_message('admin_user_form.php?id=' . $user_id, 'error', 'กรุณากรอกข้อมูล ชื่อ และอีเมลให้ครบถ้วน');
        }
        
        $current_user_data = getUserById($user_id, $conn);
        
        if ($current_user_data['email'] !== $email) {
            $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt_check->bind_param("si", $email, $user_id);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) {
                 redirect_with_message('admin_user_form.php?id='.$user_id, 'error', 'อีเมลนี้ถูกใช้งานโดยบัญชีอื่นแล้ว');
            }
            $stmt_check->close();
        }
        
        $image_url_to_update = $current_user_data['image_url'];

        if (!empty($cropped_image_data)) {
            $new_image_path = save_base64_image($cropped_image_data, 'uploads/avatars/');
            if($new_image_path){
                if (!empty($current_user_data['image_url']) && $current_user_data['image_url'] !== 'assets/images/user.png' && file_exists($current_user_data['image_url'])) {
                    unlink($current_user_data['image_url']);
                }
                $image_url_to_update = $new_image_path;
            }
        }
        
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET fullname=?, email=?, position=?, department=?, division=?, phone=?, line_id=?, role=?, password=?, image_url=? WHERE id=?");
            $stmt->bind_param("ssssssssssi", $fullname, $email, $position, $department, $division, $phone, $line_id, $role, $hashed_password, $image_url_to_update, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET fullname=?, email=?, position=?, department=?, division=?, phone=?, line_id=?, role=?, image_url=? WHERE id=?");
            $stmt->bind_param("sssssssssi", $fullname, $email, $position, $department, $division, $phone, $line_id, $role, $image_url_to_update, $user_id);
        }
        $stmt->execute();
        $stmt->close();
        
        redirect_with_message('admin_users.php', 'success', 'บันทึกการเปลี่ยนแปลงเรียบร้อยแล้ว');

    } elseif ($action === 'delete_user' && $user_id > 0) {
        // --- Action: ลบผู้ใช้งาน ---
        if ($user_id === $_SESSION['user_id']) {
            redirect_with_message('admin_users.php', 'error', 'ไม่สามารถลบบัญชีของตัวเองได้');
        }
        
        $user_to_delete = getUserById($user_id, $conn);
        if ($user_to_delete && !empty($user_to_delete['image_url']) && $user_to_delete['image_url'] !== 'assets/images/user.png') {
            if (file_exists($user_to_delete['image_url'])) {
                unlink($user_to_delete['image_url']);
            }
        }
        
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
        redirect_with_message('admin_users.php', 'success', 'ลบผู้ใช้งานเรียบร้อยแล้ว');
    }
}

// Fallback redirect
header("Location: admin_users.php");
exit();
?>

