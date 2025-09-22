<?php
// This file generates a clean, multi-page report for printing or saving as PDF.
require_once 'includes/functions.php';
check_auth(['it']);

// --- Date Filtering Logic ---
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');
$start_date = date('Y-m-d', strtotime("$year-$month-01"));
$end_date = date('Y-m-t', strtotime($start_date));
$current_user_id = $_SESSION['user_id'];

$thai_months = [
    '01' => 'มกราคม', '02' => 'กุมภาพันธ์', '03' => 'มีนาคม', '04' => 'เมษายน', 
    '05' => 'พฤษภาคม', '06' => 'มิถุนายน', '07' => 'กรกฎาคม', '08' => 'สิงหาคม', 
    '09' => 'กันยายน', '10' => 'ตุลาคม', '11' => 'พฤศจิกายน', '12' => 'ธันวาคม'
];
$report_title_month_year = "ประจำเดือน " . $thai_months[$month] . " " . ($year + 543);

$status_text_map = [
    'pending' => 'รอตรวจสอบ',
    'in_progress' => 'กำลังดำเนินการ',
    'done' => 'เสร็จสิ้น',
    'cannot_resolve' => 'แก้ไขไม่ได้',
    'awaiting_parts' => 'รอสั่งซื้ออุปกรณ์'
];

// --- Fetch all data needed for the report ---

// Personal Stats
$stats_sql = "
    SELECT
        COUNT(id) as total_assigned,
        SUM(CASE WHEN status NOT IN ('done') THEN 1 ELSE 0 END) as total_pending,
        SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as total_completed,
        AVG(CASE WHEN status = 'done' AND completed_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, created_at, completed_at) ELSE NULL END) as avg_minutes,
        AVG(CASE WHEN status = 'done' AND satisfaction_rating IS NOT NULL THEN satisfaction_rating ELSE NULL END) as avg_rating
    FROM issues
    WHERE assigned_to = ? AND DATE(created_at) BETWEEN ? AND ?
";
$stats_q = $conn->prepare($stats_sql);
$stats_q->bind_param("iss", $current_user_id, $start_date, $end_date);
$stats_q->execute();
$stats = $stats_q->get_result()->fetch_assoc();
$stats_q->close();


// Team Average Stats
$team_avg_sql = "
SELECT 
    AVG(completed_count) as avg_team_completed,
    AVG(pending_count) as avg_team_pending
FROM (
    SELECT 
        assigned_to,
        SUM(CASE WHEN status = 'done' AND DATE(completed_at) BETWEEN ? AND ? THEN 1 ELSE 0 END) as completed_count,
        SUM(CASE WHEN status NOT IN ('done') AND DATE(created_at) BETWEEN ? AND ? THEN 1 ELSE 0 END) as pending_count
    FROM issues
    WHERE assigned_to IN (SELECT id FROM users WHERE role IN ('it', 'admin'))
    GROUP BY assigned_to
) as team_stats
";
$team_avg_q = $conn->prepare($team_avg_sql);
$team_avg_q->bind_param("ssss", $start_date, $end_date, $start_date, $end_date);
$team_avg_q->execute();
$team_avg_stats = $team_avg_q->get_result()->fetch_assoc();
$team_avg_q->close();

// Detailed Issues for Work Orders and Analytics
$detailed_issues_sql = "SELECT i.*, u.fullname as assigned_to_name, u.position as assigned_to_position, u.department as assigned_to_department, u.division as assigned_to_division, r.division as reporter_division, TIMESTAMPDIFF(MINUTE, i.created_at, i.completed_at) as resolution_minutes FROM issues i LEFT JOIN users u ON i.assigned_to = u.id LEFT JOIN users r ON i.user_id = r.id WHERE i.assigned_to = ? AND DATE(i.created_at) BETWEEN ? AND ? ORDER BY i.created_at ASC";
$detailed_stmt = $conn->prepare($detailed_issues_sql);
$detailed_stmt->bind_param("iss", $current_user_id, $start_date, $end_date);
$detailed_stmt->execute();
$all_issues_for_month = $detailed_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$detailed_stmt->close();

// Top 5 Frequent Problems & Departments
$top_problems_sql = "SELECT title, COUNT(id) as total FROM issues WHERE assigned_to = ? AND DATE(created_at) BETWEEN ? AND ? GROUP BY title ORDER BY total DESC, title ASC LIMIT 5";
$top_problems_q = $conn->prepare($top_problems_sql); $top_problems_q->bind_param("iss", $current_user_id, $start_date, $end_date); $top_problems_q->execute(); $top_problems_result = $top_problems_q->get_result(); $top_problems_q->close();
$top_departments_sql = "SELECT reporter_department, COUNT(id) as total FROM issues WHERE assigned_to = ? AND DATE(created_at) BETWEEN ? AND ? AND reporter_department IS NOT NULL AND reporter_department != '' GROUP BY reporter_department ORDER BY total DESC, reporter_department ASC LIMIT 5";
$top_departments_q = $conn->prepare($top_departments_sql); $top_departments_q->bind_param("iss", $current_user_id, $start_date, $end_date); $top_departments_q->execute(); $top_departments_result = $top_departments_q->get_result(); $top_departments_q->close();

// Pre-fetch all categories
$all_categories_result = $conn->query("SELECT name FROM categories WHERE is_active = 1 ORDER BY id ASC");
$all_categories = []; if ($all_categories_result) { while($row = $all_categories_result->fetch_assoc()) { $all_categories[] = $row['name']; } }

// Prepare data for charts (including doughnut charts)
$category_data = []; $urgency_data = [];
foreach($all_issues_for_month as $issue) { @$category_data[$issue['category']]++; @$urgency_data[$issue['urgency']]++; }
arsort($category_data); arsort($urgency_data);
$category_labels_json = json_encode(array_keys($category_data)); $category_values_json = json_encode(array_values($category_data));
$urgency_labels_json = json_encode(array_keys($urgency_data)); $urgency_values_json = json_encode(array_values($urgency_data));

// Trend Data for the last 6 months
$trend_data = []; $trend_labels = [];
for ($i = 5; $i >= 0; $i--) {
    $current_month_date = new DateTime("$year-$month-01"); $current_month_date->modify("-$i months");
    $loop_year = $current_month_date->format('Y'); $loop_month = $current_month_date->format('m');
    $loop_start_date = "$loop_year-$loop_month-01"; $loop_end_date = date('Y-m-t', strtotime($loop_start_date));
    $trend_labels[] = $thai_months[$loop_month] . " " . substr($loop_year + 543, 2);
    
    $assigned_sql = "SELECT COUNT(id) as total FROM issues WHERE assigned_to = ? AND DATE(created_at) BETWEEN ? AND ?";
    $assigned_q = $conn->prepare($assigned_sql); $assigned_q->bind_param("iss", $current_user_id, $loop_start_date, $loop_end_date); $assigned_q->execute(); $trend_data['assigned'][] = $assigned_q->get_result()->fetch_assoc()['total'] ?? 0; $assigned_q->close();
    
    $completed_sql = "SELECT COUNT(id) as total FROM issues WHERE assigned_to = ? AND status = 'done' AND DATE(completed_at) BETWEEN ? AND ?";
    $completed_q = $conn->prepare($completed_sql); $completed_q->bind_param("iss", $current_user_id, $loop_start_date, $loop_end_date); $completed_q->execute(); $trend_data['completed'][] = $completed_q->get_result()->fetch_assoc()['total'] ?? 0; $completed_q->close();
}
$trend_labels_json = json_encode($trend_labels); $trend_assigned_json = json_encode($trend_data['assigned']); $trend_completed_json = json_encode($trend_data['completed']);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายงานผลการปฏิบัติงาน - <?php echo htmlspecialchars($_SESSION['fullname']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #fff; font-size: 14px; }
        .a4-page { width: 210mm; min-height: 297mm; padding: 1.5cm; margin: 1cm auto; border: 1px solid #eee; background: white; box-sizing: border-box; }
        .page-break { page-break-before: always; }
        .form-section { border: 1px solid #333; padding: 0.5rem; }
        .form-title { font-weight: bold; font-size: 1rem; }
        .form-subtitle { font-size: 0.8rem; margin-top: -4px; }
        .field { display: flex; align-items: flex-end; border-bottom: 1px dotted #999; margin-top: 0.25rem; padding-bottom: 1px; }
        .field-label { font-weight: bold; flex-shrink: 0; }
        .field-value { width: 100%; padding-left: 0.5rem; }
        .checkbox { font-family: sans-serif; font-size: 1rem; margin-right: 0.25rem; }
        
        @media print {
            body { margin: 0; -webkit-print-color-adjust: exact; font-size: 10px; }
            .a4-page { border: none; margin: 0; box-shadow: none; width: 100%; min-height: initial; padding: 1cm; }
            .print-cols-1 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
            .print-gap-4 { gap: 1rem; }
            .text-xl { font-size: 1.1rem; } .text-lg { font-size: 1rem; } .text-base { font-size: 0.8rem; } .text-sm { font-size: 0.7rem; } .text-xs { font-size: 0.6rem; }
            
            /* Styles specifically for the Work Order section when printing */
            .work-order-print .form-section { margin-top: 0.5rem !important; }
            .work-order-print .field { margin-top: 0.2rem; }
            .work-order-print, .work-order-print p, .work-order-print span, .work-order-print div { line-height: 1.3; }
            .work-order-print .min-h-\[3rem\] { min-height: 2rem; }
            .work-order-print .min-h-\[4rem\] { min-height: 2.5rem; }
            .work-order-print .min-h-\[2rem\] { min-height: 1.5rem; }
            .work-order-print .mt-10 { margin-top: 1.5rem !important; }
            .work-order-print .mt-6 { margin-top: 1rem !important; }
            .work-order-print .mt-4 { margin-top: 0.5rem !important; }
        }
    </style>
</head>
<body onload="window.print()">
    <!-- Page 1: Summary Report -->
    <div class="a4-page">
        <header class="text-center mb-4">
            <h1 class="text-xl font-bold">รายงานสรุปผลการปฏิบัติงาน</h1>
            <h2 class="text-lg">การให้บริการแจ้งปัญหาและให้คำปรึกษาด้าน IT</h2>
            <p class="text-lg"><?php echo $report_title_month_year; ?></p>
        </header>
        <div class="space-y-4">
            <!-- Stats -->
            <div class="grid grid-cols-4 gap-4 text-center text-sm">
                <div class="border p-2 rounded-lg"> <h3 class="font-bold">รับผิดชอบ</h3> <p class="text-2xl mt-1"><?php echo $stats['total_assigned'] ?? 0; ?></p> </div>
                <div class="border p-2 rounded-lg"> <h3 class="font-bold">เสร็จสิ้น</h3> <p class="text-2xl mt-1"><?php echo $stats['total_completed'] ?? 0; ?></p> </div>
                <div class="border p-2 rounded-lg"> <h3 class="font-bold">รอดำเนินการ</h3> <p class="text-2xl mt-1"><?php echo $stats['total_pending'] ?? 0; ?></p> </div>
                <div class="border p-2 rounded-lg"> <h3 class="font-bold">คะแนนเฉลี่ย</h3> <p class="text-2xl mt-1"><?php echo $stats['avg_rating'] ? number_format($stats['avg_rating'], 2) : 'N/A'; ?></p> </div>
            </div>

            <!-- Graphs Stacking Vertically for Print -->
            <div class="space-y-4">
                <div class="border p-4 rounded-lg"> <h3 class="text-base font-semibold text-center mb-2">เปรียบเทียบผลการปฏิบัติงาน</h3> <div class="h-40"><canvas id="printPerfChart"></canvas></div> </div>
                <div class="border p-4 rounded-lg"> <h3 class="text-base font-semibold text-center mb-2">แนวโน้มปริมาณงาน 6 เดือนล่าสุด</h3> <div class="h-40"><canvas id="printTrendChart"></canvas></div> </div>
            
                <div class="grid grid-cols-2 gap-6 print-cols-1 print-gap-4">
                    <div class="border p-4 rounded-lg">
                        <h3 class="text-base font-semibold text-center mb-2">สัดส่วนงานตามหมวดหมู่</h3>
                        <div class="h-40"><canvas id="printCategoryChart"></canvas></div>
                    </div>
                    <div class="border p-4 rounded-lg">
                        <h3 class="text-base font-semibold text-center mb-2">สัดส่วนความเร่งด่วนของงาน</h3>
                        <div class="h-40"><canvas id="printUrgencyChart"></canvas></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Page 2: Detailed List & Analytics -->
    <div class="a4-page page-break">
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-6 print-cols-1 print-gap-4">
                 <div class="border p-4 rounded-lg"> <h3 class="text-base font-semibold text-center mb-2">5 อันดับปัญหาที่พบบ่อย</h3> <ul class="space-y-1 text-xs"> <?php mysqli_data_seek($top_problems_result, 0); while($row = $top_problems_result->fetch_assoc()): ?> <li class="flex justify-between border-b pb-1"><span><?php echo htmlspecialchars($row['title']); ?></span> <span class="font-bold"><?php echo $row['total']; ?> ครั้ง</span></li> <?php endwhile; ?> </ul> </div>
                 <div class="border p-4 rounded-lg"> <h3 class="text-base font-semibold text-center mb-2">5 อันดับกองที่ใช้บริการสูงสุด</h3> <ul class="space-y-1 text-xs"> <?php mysqli_data_seek($top_departments_result, 0); while($row = $top_departments_result->fetch_assoc()): ?> <li class="flex justify-between border-b pb-1"><span><?php echo htmlspecialchars($row['reporter_department']); ?></span> <span class="font-bold"><?php echo $row['total']; ?> ครั้ง</span></li> <?php endwhile; ?> </ul> </div>
            </div>

            <div class="border p-4 rounded-lg">
                <h3 class="text-lg font-semibold text-center mb-2">รายการให้บริการแจ้งปัญหาและให้คำปรึกษาด้าน IT ทั้งหมดในเดือนนี้</h3>
                 <table class="min-w-full text-xs border-collapse border border-slate-400">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border border-slate-300 p-1 text-center">ลำดับ</th>
                            <th class="border border-slate-300 p-1 text-center">วันที่แจ้ง</th>
                            <th class="border border-slate-300 p-1 text-center">หัวข้อ</th>
                            <th class="border border-slate-300 p-1 text-center">ผู้แจ้ง</th>
                            <th class="border border-slate-300 p-1 text-center">สถานะ</th>
                            <th class="border border-slate-300 p-1 text-center">วันที่เสร็จสิ้น</th>
                            <th class="border border-slate-300 p-1 text-center">ใช้เวลาแก้ไข</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($all_issues_for_month) > 0): $counter = 1; foreach($all_issues_for_month as $issue): ?>
                        <tr class="border-b">
                            <td class="border border-slate-300 p-1 text-center"><?php echo $counter++; ?></td>
                            <td class="border border-slate-300 p-1"><?php echo date('d/m/', strtotime($issue['created_at'])) . (date('Y', strtotime($issue['created_at'])) + 543); ?></td>
                            <td class="border border-slate-300 p-1"><?php echo htmlspecialchars($issue['title']); ?></td>
                            <td class="border border-slate-300 p-1"><?php echo htmlspecialchars($issue['reporter_name']); ?></td>
                            <td class="border border-slate-300 p-1"><?php echo $status_text_map[$issue['status']] ?? htmlspecialchars($issue['status']); ?></td>
                            <td class="border border-slate-300 p-1"><?php echo $issue['completed_at'] ? (date('d/m/', strtotime($issue['completed_at'])) . (date('Y', strtotime($issue['completed_at'])) + 543)) : '-'; ?></td>
                            <td class="border border-slate-300 p-1"><?php echo formatDuration($issue['resolution_minutes']); ?></td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="7" class="text-center py-5 border border-slate-300">ไม่พบข้อมูลงานในเดือนที่เลือก</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Subsequent Pages: Individual Work Orders -->
    <?php foreach ($all_issues_for_month as $issue): ?>
        <div class="a4-page page-break work-order-print">
            <?php 
                $latest_comment = getLatestITComment($issue['id'], $issue['assigned_to'], $conn);
                $checklist_data = getIssueChecklistItems($issue['id'], $conn);
                $checklist_items_for_category = get_checklist_by_category($issue['category']);
                $thai_date = formatDate($issue['created_at']);
                @list($date_part, $time_part) = explode(',', $thai_date);
                $time_part = str_replace('น.', '', $time_part ?? '');
                $completed_time_str = $issue['completed_at'] ? formatDate($issue['completed_at']) : '-';
                $reporter_division = $issue['user_id'] ? ($issue['reporter_division'] ?? '') : ($issue['division'] ?? '');
            ?>
            <!-- Work Order HTML Content -->
             <header class="text-center relative mb-4"> <div class="absolute left-0 top-0"> <img src="assets/images/LogoSSKPao.png" alt="Logo" class="h-16 w-16"> </div> <h1 class="text-lg font-bold pt-2">แบบฟอร์มให้บริการ</h1> <p class="text-base">ฝ่าย <?php echo htmlspecialchars($issue['assigned_to_division'] ?: '......................'); ?> สังกัด <?php echo htmlspecialchars($issue['assigned_to_department'] ?: '......................'); ?> </p> </header> 
             <div class="form-section"> <div class="flex justify-between items-center"><p class="form-title">ส่วนที่ 1 สำหรับผู้แจ้ง</p><div class="flex space-x-4"><div class="field"><span class="field-label">เลขที่:</span> <span class="field-value"><?php echo $issue['id']; ?></span></div><div class="field"><span class="field-label">วันที่:</span> <span class="field-value"><?php echo trim($date_part ?? ''); ?></span></div><div class="field"><span class="field-label">เวลา:</span> <span class="field-value"><?php echo trim($time_part ?? ''); ?></span></div></div></div> <p class="form-subtitle">บันทึกการแจ้งปัญหา/ขอคำปรึกษา ด้าน IT</p> <div class="field mt-4"><span class="field-label">ชื่อ-นามสกุล:</span> <span class="field-value"><?php echo htmlspecialchars($issue['reporter_name']); ?></span></div> <div class="grid grid-cols-2 gap-x-6"><div class="field"><span class="field-label">สำนัก/กอง:</span> <span class="field-value"><?php echo htmlspecialchars($issue['reporter_department']); ?></span></div><div class="field"><span class="field-label">ฝ่าย/งาน:</span> <span class="field-value"><?php echo htmlspecialchars($reporter_division); ?></span></div></div> <div class="grid grid-cols-2 gap-x-6"><div class="field"><span class="field-label">โทร:</span> <span class="field-value"><?php echo htmlspecialchars($issue['reporter_contact']); ?></span></div><div class="field"><span class="field-label">ประเภทเครื่อง:</span> <span class="field-value"></span></div></div> <div class="field items-start mt-2"><span class="field-label">สาเหตุ/ปัญหา:</span><div class="field-value min-h-[3rem]"><?php echo nl2br(htmlspecialchars($issue['description'])); ?></div></div> <div class="mt-4"><p class="font-bold">หมวดหมู่รายการแจ้งปัญหา/ขอคำปรึกษา ด้าน IT</p><div class="grid grid-cols-2 gap-x-4 text-sm mt-1"><?php foreach ($all_categories as $cat_name): ?><div><span class="checkbox"><?php echo ($issue['category'] === $cat_name) ? '☑' : '☐'; ?></span><?php echo htmlspecialchars($cat_name); ?></div><?php endforeach; ?></div></div> </div>
             <div class="form-section mt-4"> <p class="form-title">ส่วนที่ 2 สำหรับเจ้าหน้าที่</p> <p class="form-subtitle">บันทึกการตรวจสอบและแก้ไข</p> <div class="grid grid-cols-2 gap-x-6 mt-2"><div class="field"><span class="field-label">เวลาเริ่มดำเนินการ:</span> <span class="field-value"><?php echo trim($time_part ?? ''); ?></span></div><div class="field"><span class="field-label">เวลาแล้วเสร็จ:</span> <span class="field-value"><?php echo $completed_time_str; ?></span></div></div>
            <?php if ($issue['category'] !== 'อื่นๆ'): ?>
            <div class="mt-4"><p class="font-bold">รายการตรวจสอบและแก้ไข:</p><div class="grid grid-cols-2 gap-x-4 text-sm mt-1"><?php foreach($checklist_items_for_category as $item): $is_checked = isset($checklist_data[$item]) && $checklist_data[$item]['checked']; $item_value = isset($checklist_data[$item]) ? $checklist_data[$item]['value'] : '';?><div><span class="checkbox"><?php echo $is_checked ? '☑' : '☐'; ?></span><span><?php echo htmlspecialchars($item); ?></span><?php if ($item === 'อื่นๆ' && $is_checked && !empty($item_value)): ?><span class="ml-2 text-indigo-600">(<?php echo htmlspecialchars($item_value); ?>)</span><?php endif; ?></div><?php endforeach; ?></div></div>
            <?php endif; ?>
             <div class="mt-4 space-y-2"><p><span class="checkbox"><?php echo $issue['status'] === 'done' ? '☑' : '☐'; ?></span> ดำเนินการแล้วเสร็จ สามารถใช้งานได้ปกติ</p><div class="field items-start ml-8"><span class="field-label">รายละเอียดการแก้ไขเพิ่มเติม:</span><div class="field-value min-h-[4rem]"><?php echo nl2br(htmlspecialchars($latest_comment ?? '')); ?></div></div><p><span class="checkbox"><?php echo $issue['status'] === 'awaiting_parts' ? '☑' : '☐'; ?></span> ขอให้หน่วยงาน สั่งซื้ออุปกรณ์เพื่อใช้ในการซ่อม</p><p><span class="checkbox"><?php echo $issue['status'] === 'cannot_resolve' ? '☑' : '☐'; ?></span> ไม่สามารถดำเนินการเองได้ / ให้ดำเนินการส่งซ่อม</p></div> <div class="mt-6 flex justify-end"><div class="w-2/5 text-center"><p class="min-h-[1rem]">............................................</p><p class="text-sm mt-1"><?php echo htmlspecialchars($issue['assigned_to_position'] ?? '............................................'); ?></p><p class="mt-1">(<?php echo htmlspecialchars($issue['assigned_to_name'] ?? '............................................'); ?>)</p><p class="text-sm">เจ้าหน้าที่ผู้ดำเนินการ</p></div></div> </div>
             <div class="form-section mt-4"> <p class="form-title">ส่วนที่ 3 สำหรับผู้รับบริการ</p> <p class="form-subtitle">แบบสำรวจความพึงพอใจ</p> <div class="mt-2"><p class="font-bold">ความพึงพอใจในการรับบริการจากเจ้าหน้าที่</p><div class="flex space-x-6 mt-1 text-sm"><span><span class="checkbox"><?php echo ($issue['satisfaction_rating'] == 5) ? '☑' : '☐'; ?></span> ดีมาก</span><span><span class="checkbox"><?php echo ($issue['satisfaction_rating'] == 4) ? '☑' : '☐'; ?></span> ดี</span><span><span class="checkbox"><?php echo ($issue['satisfaction_rating'] == 3) ? '☑' : '☐'; ?></span> พอใช้</span><span><span class="checkbox"><?php echo ($issue['satisfaction_rating'] <= 2 && !is_null($issue['satisfaction_rating'])) ? '☑' : '☐'; ?></span> ควรปรับปรุง</span></div></div> <div class="field items-start mt-2"><span class="field-label">ข้อเสนอแนะ:</span><div class="field-value min-h-[2rem]"></div></div> <div class="flex justify-between mt-10"><div class="w-2/5 text-center"><div class="min-h-[3rem] flex items-center justify-center"><?php if (!empty($issue['signature_image']) && file_exists($issue['signature_image'])): ?><img src="<?php echo htmlspecialchars($issue['signature_image']); ?>" alt="ลายมือชื่อผู้รับบริการ" class="max-h-16"><?php else: ?><p>ลงชื่อ............................................</p><?php endif; ?></div><p class="mt-1">(<?php echo htmlspecialchars($issue['reporter_name']); ?>)</p><p class="text-sm">ผู้รับบริการ</p></div><div class="w-2/5 text-center"><p class="min-h-[1rem]">ลงชื่อ............................................</p><p class="mt-1">(............................................)</p><p class="text-sm">หัวหน้า<?php echo htmlspecialchars($issue['assigned_to_division'] ? 'ฝ่าย' . $issue['assigned_to_division'] : 'ฝ่าย......................'); ?></p></div></div> </div>
        </div>
    <?php endforeach; ?>

    <script>
        // This script runs on the print page to render charts before printing
        document.addEventListener('DOMContentLoaded', function () {
            const sarabunFont = { family: 'Sarabun' };
            const chartDefaults = { 
                responsive: true, 
                maintainAspectRatio: false, 
                plugins: { legend: { labels: { font: sarabunFont } } },
                animation: { duration: 0 }
            };

            // Performance Comparison Bar Chart
            new Chart(document.getElementById('printPerfChart').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: ['เสร็จสิ้น', 'รอดำเนินการ'],
                    datasets: [
                        { label: 'ผลงานของคุณ', data: [<?php echo $stats['total_completed'] ?? 0; ?>, <?php echo $stats['total_pending'] ?? 0; ?>], backgroundColor: 'rgba(79, 70, 229, 0.7)' },
                        { label: 'ค่าเฉลี่ยของทีม', data: [<?php echo round($team_avg_stats['avg_team_completed'] ?? 0, 1); ?>, <?php echo round($team_avg_stats['avg_team_pending'] ?? 0, 1); ?>], backgroundColor: 'rgba(107, 114, 128, 0.7)' }
                    ]
                },
                options: { ...chartDefaults, plugins: { legend: { display: true, position: 'top', ...chartDefaults.plugins.legend } }, scales: { y: { beginAtZero: true } } }
            });

            // Workload Trend Line Chart
            new Chart(document.getElementById('printTrendChart').getContext('2d'), {
                type: 'line',
                data: {
                    labels: <?php echo $trend_labels_json; ?>,
                    datasets: [
                        { label: 'งานที่ได้รับมอบหมาย', data: <?php echo $trend_assigned_json; ?>, borderColor: 'rgba(59, 130, 246, 1)', backgroundColor: 'rgba(59, 130, 246, 0.1)', fill: true, tension: 0.1 },
                        { label: 'งานที่เสร็จสิ้น', data: <?php echo $trend_completed_json; ?>, borderColor: 'rgba(16, 185, 129, 1)', backgroundColor: 'rgba(16, 185, 129, 0.1)', fill: true, tension: 0.1 }
                    ]
                },
                options: { ...chartDefaults, plugins: { legend: { display: true, position: 'top', ...chartDefaults.plugins.legend } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
            });

            // Doughnut Charts
            new Chart(document.getElementById('printCategoryChart').getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: <?php echo $category_labels_json; ?>,
                    datasets: [{ data: <?php echo $category_values_json; ?>, backgroundColor: ['#4F46E5', '#10B981', '#F59E0B', '#3B82F6', '#EF4444', '#6B7280'], hoverOffset: 4 }]
                },
                options: { ...chartDefaults, plugins: { legend: { position: 'right', ...chartDefaults.plugins.legend } } }
            });

            new Chart(document.getElementById('printUrgencyChart').getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: <?php echo $urgency_labels_json; ?>,
                    datasets: [{ data: <?php echo $urgency_values_json; ?>, backgroundColor: ['#DC2626', '#F59E0B', '#10B981'], hoverOffset: 4 }]
                },
                options: { ...chartDefaults, plugins: { legend: { position: 'right', ...chartDefaults.plugins.legend } } }
            });
        });
    </script>
</body>
</html>
