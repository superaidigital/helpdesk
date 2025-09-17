<?php
// export_report.php
require_once 'includes/functions.php';
check_auth(['it', 'admin']);

// รับค่า Parameters จาก URL
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$user_id_filter = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

// สร้างชื่อไฟล์
$filename_user = "All_Staff"; 
if ($user_id_filter !== null) {
    // **Security Check:** IT users can only export their own data.
    if ($_SESSION['role'] === 'it') {
        $user_id_filter = $_SESSION['user_id'];
        $filename_user = str_replace(' ', '_', $_SESSION['fullname']);
    } 
    // Admin can export for a specific user
    elseif ($_SESSION['role'] === 'admin') {
        $user_data = getUserById($user_id_filter, $conn);
        $filename_user = $user_data ? str_replace(' ', '_', $user_data['fullname']) : "User_{$user_id_filter}";
    }
}
$filename = "Helpdesk_Report_{$filename_user}_from_{$start_date}_to_{$end_date}.csv";


// --- สร้าง SQL Query ---
$sql = "SELECT i.id, i.title, i.category, i.status, i.reporter_name, u.fullname as assigned_to, i.created_at, i.completed_at 
        FROM issues i 
        LEFT JOIN users u ON i.assigned_to = u.id 
        WHERE DATE(i.created_at) BETWEEN ? AND ?";

$params = [$start_date, $end_date];
$types = "ss";

// เพิ่มเงื่อนไขถ้ามีการระบุ user id
if ($user_id_filter !== null) {
    $sql .= " AND i.assigned_to = ?";
    $params[] = $user_id_filter;
    $types .= "i";
}
$sql .= " ORDER BY i.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// --- สร้างไฟล์ CSV ---
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// *** FIX: Add UTF-8 BOM to make Excel open Thai characters correctly ***
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// สร้าง Header ของไฟล์ CSV (เป็นภาษาไทย)
fputcsv($output, [
    'หมายเลขเรื่อง', 'หัวข้อ', 'หมวดหมู่', 'สถานะ', 'ผู้แจ้ง', 'ผู้รับผิดชอบ', 'วันที่แจ้ง', 'วันที่เสร็จสิ้น', 'ระยะเวลา (นาที)'
]);

// วนลูปเพื่อใส่ข้อมูลลงในไฟล์ CSV
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $duration = '-';
        if ($row['completed_at'] && $row['created_at']) {
            $start = new DateTime($row['created_at']);
            $end = new DateTime($row['completed_at']);
            $diff = $start->diff($end);
            $duration = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
        }
        
        // แปลงสถานะเป็นภาษาไทย
        $status_th = [
            'pending' => 'รอตรวจสอบ',
            'in_progress' => 'กำลังดำเนินการ',
            'done' => 'เสร็จสิ้น',
            'cannot_resolve' => 'แก้ไขไม่ได้'
        ];

        fputcsv($output, [
            $row['id'],
            $row['title'],
            $row['category'],
            $status_th[$row['status']] ?? $row['status'],
            $row['reporter_name'],
            $row['assigned_to'] ?? '-',
            $row['created_at'],
            $row['completed_at'] ?? '-',
            $duration
        ]);
    }
}

fclose($output);
$stmt->close();
$conn->close();
exit();
?>

