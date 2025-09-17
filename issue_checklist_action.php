<?php
// issue_checklist_action.php
require_once 'includes/functions.php';
header('Content-Type: application/json');

// อนุญาตให้ IT และ Admin เท่านั้นที่ทำ action ได้
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['it', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit();
}

// อ่านข้อมูล JSON ที่ส่งมาจาก Frontend
$data = json_decode(file_get_contents('php://input'), true);

$issue_id = $data['issue_id'] ?? 0;
$items = $data['items'] ?? [];
$current_user_id = $_SESSION['user_id'];

if ($issue_id > 0 && !empty($items)) {
    // เริ่มต้น Transaction เพื่อให้แน่ใจว่าข้อมูลจะถูกบันทึกทั้งหมดหรือยกเลิกทั้งหมด
    $conn->begin_transaction();
    try {
        // วนลูปทุกรายการ Checklist ที่ส่งมา
        foreach ($items as $item_description => $details) {
            $is_checked = isset($details['checked']) && $details['checked'] ? 1 : 0;
            $item_value = $details['value'] ?? null;

            // ตรวจสอบว่ามีรายการนี้อยู่แล้วหรือไม่
            $stmt_check = $conn->prepare("SELECT id FROM issue_checklist WHERE issue_id = ? AND item_description = ?");
            $stmt_check->bind_param("is", $issue_id, $item_description);
            $stmt_check->execute();
            $result = $stmt_check->get_result();
            
            if ($result->num_rows > 0) {
                // ถ้ามี -> อัปเดต
                $row = $result->fetch_assoc();
                $checklist_id = $row['id'];
                
                $stmt_update = $conn->prepare("UPDATE issue_checklist SET is_checked = ?, item_value = ?, checked_by = ?, checked_at = NOW() WHERE id = ?");
                $stmt_update->bind_param("isii", $is_checked, $item_value, $current_user_id, $checklist_id);
                if (!$stmt_update->execute()) {
                    throw new Exception("Failed to update item: " . $item_description);
                }
                $stmt_update->close();
            } else {
                // ถ้าไม่มี -> สร้างใหม่
                $stmt_insert = $conn->prepare("INSERT INTO issue_checklist (issue_id, item_description, is_checked, item_value, checked_by, checked_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt_insert->bind_param("isissi", $issue_id, $item_description, $is_checked, $item_value, $current_user_id);
                if (!$stmt_insert->execute()) {
                    throw new Exception("Failed to insert item: " . $item_description);
                }
                $stmt_insert->close();
            }
            $stmt_check->close();
        }
        
        // ถ้าทุกอย่างสำเร็จ, ยืนยันการเปลี่ยนแปลง
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Checklist saved successfully.']);

    } catch (Exception $e) {
        // หากเกิดข้อผิดพลาด, ยกเลิกการเปลี่ยนแปลงทั้งหมด
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Database transaction failed: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid data provided.']);
}

$conn->close();
?>

