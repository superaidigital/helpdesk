<?php
// track_issue_action.php
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_close_job'])) {
    validate_csrf_token(); // --- ADDED: CSRF Protection ---

    $issue_id = isset($_POST['issue_id']) ? (int)$_POST['issue_id'] : 0;
    $rating = isset($_POST['satisfaction_rating']) ? (int)$_POST['satisfaction_rating'] : null;
    $signature_data = $_POST['signature_data'] ?? '';
    $source_page = $_POST['source'] ?? 'track_issue'; // --- ADDED: Determine where to redirect back ---

    if ($issue_id > 0 && !empty($signature_data)) {
        // 1. จัดการลายเซ็น: แปลง Base64 เป็นไฟล์รูปภาพ
        $signature_image_path = null;
        if (strpos($signature_data, 'data:image/png;base64,') === 0) {
            $base64_data = base64_decode(substr($signature_data, strlen('data:image/png;base64,')));
            
            $upload_dir = 'uploads/signatures/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_name = 'sig_' . $issue_id . '_' . uniqid() . '.png';
            $file_path = $upload_dir . $file_name;

            if (file_put_contents($file_path, $base64_data)) {
                $signature_image_path = $file_path;
            }
        }

        // 2. อัปเดตฐานข้อมูล
        if ($signature_image_path) {
            $stmt = $conn->prepare("UPDATE issues SET satisfaction_rating = ?, signature_image = ? WHERE id = ?");
            $stmt->bind_param("isi", $rating, $signature_image_path, $issue_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    // --- MODIFIED: Redirect back to the correct page ---
    $redirect_url = ($source_page === 'issue_view') ? "issue_view.php?id=" . $issue_id : "track_issue.php?id=" . $issue_id;
    header("Location: " . $redirect_url);
    exit();

} else {
    // ถ้าไม่ได้เข้ามาอย่างถูกต้อง
    header("Location: index.php");
    exit();
}
?>
