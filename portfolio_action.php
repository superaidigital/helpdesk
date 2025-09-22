<?php
require_once 'includes/functions.php';
check_auth(['it', 'admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_token();

    $action = $_POST['action'] ?? '';
    $current_user_id = $_SESSION['user_id'];

    // ADD or EDIT Action
    if ($action === 'add_portfolio' || $action === 'edit_portfolio') {
        $item_id = (int)($_POST['item_id'] ?? 0);
        $title = trim($_POST['project_title']);
        $description = trim($_POST['project_description']);
        $category = trim($_POST['project_category']) ?: null;
        $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $tech = trim($_POST['technologies_used']) ?: null;
        $url = trim($_POST['project_url']) ?: null;
        $image_path = null;

        // --- Handle File Upload ---
        if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/portfolio/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_ext = pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION);
            $new_name = 'proj_' . $current_user_id . '_' . uniqid() . '.' . $file_ext;
            $image_path = $upload_dir . $new_name;
            if (!move_uploaded_file($_FILES['main_image']['tmp_name'], $image_path)) {
                redirect_with_message('my_portfolio.php', 'error', 'ไม่สามารถอัปโหลดรูปภาพได้');
            }
        }

        if ($action === 'add_portfolio') {
            $stmt = $conn->prepare(
                "INSERT INTO it_portfolio (user_id, project_title, project_description, project_category, start_date, end_date, technologies_used, project_url, main_image_url) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("issssssss", $current_user_id, $title, $description, $category, $start_date, $end_date, $tech, $url, $image_path);
            $message = 'เพิ่มผลงานใหม่เรียบร้อยแล้ว';
        } else { // edit_portfolio
            // Fetch current image path to avoid overwriting with null
            $stmt_img = $conn->prepare("SELECT main_image_url FROM it_portfolio WHERE id = ? AND user_id = ?");
            $stmt_img->bind_param("ii", $item_id, $current_user_id);
            $stmt_img->execute();
            $current_image = $stmt_img->get_result()->fetch_assoc()['main_image_url'];
            $stmt_img->close();
            
            if (!$image_path) {
                $image_path = $current_image; // Keep old image if no new one is uploaded
            } else if ($current_image && file_exists($current_image)) {
                unlink($current_image); // Delete old image
            }

            $stmt = $conn->prepare(
                "UPDATE it_portfolio SET project_title=?, project_description=?, project_category=?, start_date=?, end_date=?, technologies_used=?, project_url=?, main_image_url=? 
                 WHERE id = ? AND user_id = ?"
            );
            $stmt->bind_param("ssssssssii", $title, $description, $category, $start_date, $end_date, $tech, $url, $image_path, $item_id, $current_user_id);
            $message = 'แก้ไขข้อมูลผลงานเรียบร้อยแล้ว';
        }

        if ($stmt->execute()) {
            redirect_with_message('my_portfolio.php', 'success', $message);
        } else {
            redirect_with_message('my_portfolio.php', 'error', 'เกิดข้อผิดพลาด: ' . $stmt->error);
        }
        $stmt->close();
    }
    // DELETE Action
    elseif ($action === 'delete_portfolio') {
        $item_id = (int)($_POST['item_id'] ?? 0);
        
        // First, get the image path to delete the file
        $stmt_img = $conn->prepare("SELECT main_image_url FROM it_portfolio WHERE id = ? AND user_id = ?");
        $stmt_img->bind_param("ii", $item_id, $current_user_id);
        $stmt_img->execute();
        $image_to_delete = $stmt_img->get_result()->fetch_assoc()['main_image_url'];
        $stmt_img->close();

        // Delete from DB
        $stmt = $conn->prepare("DELETE FROM it_portfolio WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $item_id, $current_user_id);
        if ($stmt->execute()) {
            // If DB deletion is successful, delete the image file
            if ($image_to_delete && file_exists($image_to_delete)) {
                unlink($image_to_delete);
            }
            redirect_with_message('my_portfolio.php', 'success', 'ลบผลงานเรียบร้อยแล้ว');
        } else {
            redirect_with_message('my_portfolio.php', 'error', 'ไม่สามารถลบผลงานได้');
        }
        $stmt->close();
    }
}
$conn->close();
header('Location: my_portfolio.php');
exit();
?>
