<?php
// admin_system_action.php
require_once 'includes/functions.php';
check_auth(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_token();

    $action = $_POST['action'] ?? '';
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    
    // Action: เพิ่มหมวดหมู่
    if ($action === 'add_category') {
        $name = trim($_POST['name'] ?? '');
        $icon = trim($_POST['icon'] ?? 'fa-question-circle');
        $description = trim($_POST['description'] ?? '');

        if (!empty($name) && !empty($icon)) {
            $stmt = $conn->prepare("INSERT INTO categories (name, icon, description) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $icon, $description);
            $stmt->execute();
            $stmt->close();
            redirect_with_message('admin_system.php', 'success', 'เพิ่มหมวดหมู่ใหม่เรียบร้อยแล้ว');
        } else {
            redirect_with_message('admin_system.php', 'error', 'กรุณากรอกชื่อและไอคอนของหมวดหมู่');
        }
    }
    
    // Action: แก้ไขหมวดหมู่
    elseif ($action === 'edit_category' && $category_id > 0) {
        $name = trim($_POST['name'] ?? '');
        $icon = trim($_POST['icon'] ?? 'fa-question-circle');
        $description = trim($_POST['description'] ?? '');

        if (!empty($name) && !empty($icon)) {
            $stmt = $conn->prepare("UPDATE categories SET name = ?, icon = ?, description = ? WHERE id = ?");
            $stmt->bind_param("sssi", $name, $icon, $description, $category_id);
            $stmt->execute();
            $stmt->close();
            redirect_with_message('admin_system.php', 'success', 'แก้ไขข้อมูลหมวดหมู่เรียบร้อยแล้ว');
        } else {
            redirect_with_message('admin_system.php', 'error', 'กรุณากรอกชื่อและไอคอนของหมวดหมู่');
        }
    }

    // Action: ลบหมวดหมู่
    elseif ($action === 'delete_category' && $category_id > 0) {
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $stmt->close();
        redirect_with_message('admin_system.php', 'success', 'ลบหมวดหมู่เรียบร้อยแล้ว');
    }
}

// ถ้าไม่มี action ที่ตรงกัน
header("Location: admin_system.php");
exit();
?>

