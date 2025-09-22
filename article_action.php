<?php
require_once 'includes/functions.php';
check_auth(['it', 'admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_token();

    $action = $_POST['action'] ?? '';
    $current_user_id = $_SESSION['user_id'];
    $article_id = (int)($_POST['article_id'] ?? 0);

    // --- ADD or EDIT Action ---
    if ($action === 'add_article' || $action === 'edit_article') {
        
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $excerpt = trim($_POST['excerpt']) ?: substr(strip_tags($content), 0, 250) . '...';
        $tags = trim($_POST['tags']) ?: null;
        $status = in_array($_POST['status'], ['draft', 'published', 'archived']) ? $_POST['status'] : 'draft';

        if (empty($title) || empty($content)) {
            redirect_with_message('article_form.php' . ($article_id ? "?id=$article_id" : ''), 'error', 'กรุณากรอกหัวข้อและเนื้อหา');
        }

        // --- Handle Image Upload ---
        $image_path = null;
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/articles/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $file_ext = pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION);
            $new_name = 'article_' . time() . '_' . uniqid() . '.' . $file_ext;
            $image_path = $upload_dir . $new_name;

            if (!move_uploaded_file($_FILES['featured_image']['tmp_name'], $image_path)) {
                redirect_with_message('article_form.php' . ($article_id ? "?id=$article_id" : ''), 'error', 'ไม่สามารถอัปโหลดรูปภาพได้');
            }
        }
        
        $slug = createSlug($title);
        $published_at = ($status === 'published') ? date('Y-m-d H:i:s') : null;

        if ($action === 'add_article') {
            $stmt = $conn->prepare("INSERT INTO articles (author_id, title, slug, content, excerpt, tags, status, published_at, featured_image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssss", $current_user_id, $title, $slug, $content, $excerpt, $tags, $status, $published_at, $image_path);
            $message = 'สร้างบทความใหม่เรียบร้อยแล้ว';
        } else { // edit_article
            $current_data_stmt = $conn->prepare("SELECT featured_image_url, status FROM articles WHERE id = ?");
            $current_data_stmt->bind_param("i", $article_id);
            $current_data_stmt->execute();
            $current_data = $current_data_stmt->get_result()->fetch_assoc();
            $current_data_stmt->close();

            if (!$image_path) {
                $image_path = $current_data['featured_image_url'];
            } elseif ($current_data['featured_image_url'] && file_exists($current_data['featured_image_url'])) {
                unlink($current_data['featured_image_url']);
            }
            
            if ($status === 'published' && $current_data['status'] !== 'published') {
                 // Update published_at only when changing status to published
                 $published_at = date('Y-m-d H:i:s');
            } else {
                 $published_at = null; // We need to handle this more gracefully, but for now, don't update it
            }

            $sql = "UPDATE articles SET title=?, slug=?, content=?, excerpt=?, tags=?, status=?, featured_image_url=?";
            $types = "sssssss";
            $params = [$title, $slug, $content, $excerpt, $tags, $status, $image_path];

            if ($published_at) {
                $sql .= ", published_at = ?";
                $types .= "s";
                $params[] = $published_at;
            }
            $sql .= " WHERE id = ?";
            $types .= "i";
            $params[] = $article_id;

            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $message = 'แก้ไขบทความเรียบร้อยแล้ว';
        }
        
        if ($stmt->execute()) {
            redirect_with_message('admin_articles.php', 'success', $message);
        } else {
            // Check for duplicate slug
            if ($conn->errno == 1062) {
                 redirect_with_message('article_form.php' . ($article_id ? "?id=$article_id" : ''), 'error', 'หัวข้อนี้มีอยู่แล้ว กรุณาเปลี่ยนหัวข้อใหม่');
            }
            redirect_with_message('admin_articles.php', 'error', 'เกิดข้อผิดพลาด: ' . $stmt->error);
        }
        $stmt->close();
    }
    // --- DELETE Action ---
    elseif ($action === 'delete_article') {
        // Get image path before deleting
        $stmt_img = $conn->prepare("SELECT featured_image_url FROM articles WHERE id = ?");
        $stmt_img->bind_param("i", $article_id);
        $stmt_img->execute();
        $image_to_delete = $stmt_img->get_result()->fetch_assoc()['featured_image_url'];
        $stmt_img->close();

        $stmt = $conn->prepare("DELETE FROM articles WHERE id = ?");
        $stmt->bind_param("i", $article_id);
        if ($stmt->execute()) {
            if ($image_to_delete && file_exists($image_to_delete)) {
                unlink($image_to_delete);
            }
            redirect_with_message('admin_articles.php', 'success', 'ลบบทความเรียบร้อยแล้ว');
        } else {
             redirect_with_message('admin_articles.php', 'error', 'ไม่สามารถลบบทความได้');
        }
        $stmt->close();
    }
}
$conn->close();
header('Location: admin_articles.php');
exit();
?>
