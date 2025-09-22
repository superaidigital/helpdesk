<?php
$page_title = "รายงานผลการปฏิบัติงานประจำเดือน";
require_once 'includes/functions.php';
check_auth(['admin']);
require_once 'includes/header.php';

// --- Date Filtering Logic ---
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

$start_date = date('Y-m-d', strtotime("$year-$month-01"));
$end_date = date('Y-m-t', strtotime($start_date));

$thai_months = [
    '01' => 'มกราคม', '02' => 'กุมภาพันธ์', '03' => 'มีนาคม',
    '04' => 'เมษายน', '05' => 'พฤษภาคม', '06' => 'มิถุนายน',
    '07' => 'กรกฎาคม', '08' => 'สิงหาคม', '09' => 'กันยายน',
    '10' => 'ตุลาคม', '11' => 'พฤศจิกายน', '12' => 'ธันวาคม'
];
$report_title_month_year = "ประจำเดือน " . $thai_months[$month] . " " . ($year + 543);

// --- Overall Stats Query for the selected month ---
$stmt = $conn->prepare("
    SELECT
        (SELECT COUNT(id) FROM issues WHERE DATE(created_at) BETWEEN ? AND ?) as total_assigned,
        COUNT(id) as total_completed,
        AVG(TIMESTAMPDIFF(MINUTE, created_at, completed_at)) as avg_minutes,
        AVG(satisfaction_rating) as avg_rating
    FROM issues
    WHERE status = 'done' AND completed_at IS NOT NULL AND DATE(completed_at) BETWEEN ? AND ?
");
$stmt->bind_param("ssss", $start_date, $end_date, $start_date, $end_date);
$stmt->execute();
$overall_stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// --- Main Query for Staff Performance ---
$sql = "
    SELECT
        u.id, u.fullname, u.position,
        (SELECT COUNT(*) FROM issues i_assigned WHERE i_assigned.assigned_to = u.id AND DATE(i_assigned.created_at) BETWEEN ? AND ?) AS total_assigned,
        COUNT(i_completed.id) as total_done,
        AVG(TIMESTAMPDIFF(MINUTE, i_completed.created_at, i_completed.completed_at)) as avg_minutes,
        AVG(i_completed.satisfaction_rating) as avg_rating
    FROM users u
    LEFT JOIN issues i_completed ON u.id = i_completed.assigned_to AND i_completed.status = 'done' AND i_completed.completed_at IS NOT NULL AND DATE(i_completed.completed_at) BETWEEN ? AND ?
    WHERE u.role IN ('it', 'admin')
    GROUP BY u.id, u.fullname, u.position
    ORDER BY total_done DESC, u.fullname ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $start_date, $end_date, $start_date, $end_date);
$stmt->execute();
$staff_performance_result = $stmt->get_result();
$staff_performance_data = [];
while($row = $staff_performance_result->fetch_assoc()) {
    $staff_performance_data[] = $row;
}
$stmt->close();

// --- Analytics Queries ---
// Top 5 Problems
$stmt = $conn->prepare("SELECT title, COUNT(id) as total FROM issues WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY title ORDER BY total DESC, title ASC LIMIT 5");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$top_problems_result = $stmt->get_result();

// Top 5 Departments
$stmt = $conn->prepare("SELECT reporter_department, COUNT(id) as total FROM issues WHERE DATE(created_at) BETWEEN ? AND ? AND reporter_department IS NOT NULL AND reporter_department != '' GROUP BY reporter_department ORDER BY total DESC, reporter_department ASC LIMIT 5");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$top_departments_result = $stmt->get_result();

// Monthly Trend (Last 6 months)
$stmt = $conn->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(id) as total FROM issues WHERE created_at >= DATE_SUB(?, INTERVAL 5 MONTH) GROUP BY month ORDER BY month ASC");
$stmt->bind_param("s", $start_date);
$stmt->execute();
$trend_q = $stmt->get_result();
$trend_data = [];
while($row = $trend_q->fetch_assoc()){ $trend_data[] = $row; }

// Urgency Breakdown
$stmt = $conn->prepare("SELECT urgency, COUNT(id) as total FROM issues WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY urgency");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$urgency_result = $stmt->get_result();
$urgency_data = [];
while($row = $urgency_result->fetch_assoc()){ $urgency_data[] = $row; }


// --- Prepare data for Chart.js ---
$chart_labels_staff = array_column($staff_performance_data, 'fullname');
$chart_data_done = array_column($staff_performance_data, 'total_done');
$chart_data_assigned = array_column($staff_performance_data, 'total_assigned');
$chart_labels_trend = array_map(function($item) use ($thai_months) {
    list($y, $m) = explode('-', $item['month']);
    return $thai_months[$m] . ' ' . (substr($y, 2) + 43);
}, $trend_data);
$chart_values_trend = array_column($trend_data, 'total');
$chart_labels_urgency = array_column($urgency_data, 'urgency');
$chart_values_urgency = array_column($urgency_data, 'total');
?>
<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="space-y-6">
    <!-- Header and Filters -->
    <div class="no-print flex flex-col sm:flex-row justify-between items-start mb-4 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">รายงานผลการปฏิบัติงาน</h2>
            <p class="text-gray-500">สรุปผลและวิเคราะห์ภาพรวม <?php echo $report_title_month_year; ?></p>
        </div>
        <div class="flex items-center gap-4 bg-white p-2 rounded-lg shadow-sm">
            <form method="GET" action="admin_monthly_report.php" class="flex items-center gap-2">
                <select name="month" class="border-gray-300 rounded-md shadow-sm text-sm"><?php foreach ($thai_months as $m_num => $m_name): ?><option value="<?php echo $m_num; ?>" <?php echo ($m_num == $month) ? 'selected' : ''; ?>><?php echo $m_name; ?></option><?php endforeach; ?></select>
                <select name="year" class="border-gray-300 rounded-md shadow-sm text-sm"><?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?><option value="<?php echo $y; ?>" <?php echo ($y == $year) ? 'selected' : ''; ?>><?php echo $y + 543; ?></option><?php endfor; ?></select>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 text-sm">แสดงผล</button>
            </form>
            <button onclick="window.print()" class="px-4 py-2 bg-gray-600 text-white font-semibold rounded-md hover:bg-gray-700 text-sm"><i class="fa-solid fa-print mr-2"></i>พิมพ์</button>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 no-print">
        <div class="bg-white p-6 rounded-lg shadow-md"><h3 class="font-semibold text-gray-700">เรื่องแจ้งเข้าทั้งหมด</h3><p class="text-3xl font-bold text-gray-900 mt-1"><?php echo $overall_stats['total_assigned'] ?? 0; ?></p></div>
        <div class="bg-white p-6 rounded-lg shadow-md"><h3 class="font-semibold text-gray-700">เสร็จสิ้นในเดือนนี้</h3><p class="text-3xl font-bold text-green-600 mt-1"><?php echo $overall_stats['total_completed'] ?? 0; ?></p></div>
        <div class="bg-white p-6 rounded-lg shadow-md"><h3 class="font-semibold text-gray-700">เวลาแก้ไขเฉลี่ย</h3><p class="text-2xl font-bold text-blue-600 mt-1"><?php echo formatDuration($overall_stats['avg_minutes']); ?></p></div>
        <div class="bg-white p-6 rounded-lg shadow-md"><h3 class="font-semibold text-gray-700">คะแนนเฉลี่ยรวม</h3><p class="text-3xl font-bold text-amber-500 mt-1"><?php echo $overall_stats['avg_rating'] ? number_format($overall_stats['avg_rating'], 2) : 'N/A'; ?></p></div>
    </div>

    <!-- Performance Table -->
    <div class="bg-white p-6 rounded-lg shadow-md">
         <h3 class="text-xl font-bold text-gray-800 mb-4 print-only">ตารางสรุปผลการปฏิบัติงาน <?php echo $report_title_month_year; ?></h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 border">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">เจ้าหน้าที่</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">งานที่รับผิดชอบ</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">งานที่เสร็จสิ้น</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">เวลาเฉลี่ย</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">คะแนนเฉลี่ย</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($staff_performance_data) > 0): ?>
                        <?php foreach($staff_performance_data as $staff): ?>
                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap"><p class="font-medium text-gray-900"><?php echo htmlspecialchars($staff['fullname']); ?></p><p class="text-sm text-gray-500"><?php echo htmlspecialchars($staff['position']); ?></p></td>
                            <td class="px-4 py-3 text-center text-gray-700"><?php echo (int)$staff['total_assigned']; ?></td>
                            <td class="px-4 py-3 text-center text-green-600 font-semibold"><?php echo (int)$staff['total_done']; ?></td>
                            <td class="px-4 py-3 text-center text-blue-600"><?php echo formatDuration($staff['avg_minutes']); ?></td>
                            <td class="px-4 py-3 text-center text-amber-600 font-bold"><?php echo $staff['avg_rating'] ? number_format($staff['avg_rating'], 2) : 'N/A'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-10 text-gray-500">ไม่พบข้อมูลผลการปฏิบัติงานในเดือนที่เลือก</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Performance Chart -->
    <div class="bg-white p-6 rounded-lg shadow-md no-print">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">กราฟแท่งเปรียบเทียบผลการปฏิบัติงาน</h3>
        <div class="h-80"><canvas id="performanceChart"></canvas></div>
    </div>
    
    <!-- Analytics Section -->
    <div class="pt-6 border-t no-print">
        <h2 class="text-2xl font-bold text-gray-800 mb-4 text-center">การวิเคราะห์ภาพรวมประจำเดือน</h2>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Monthly Trend -->
            <div class="bg-white p-6 rounded-lg shadow-md"><h3 class="text-lg font-semibold text-gray-800">แนวโน้มปริมาณงาน 6 เดือนล่าสุด</h3><div class="mt-4 h-64"><canvas id="trendChart"></canvas></div></div>
            <!-- Urgency Breakdown -->
            <div class="bg-white p-6 rounded-lg shadow-md"><h3 class="text-lg font-semibold text-gray-800">สัดส่วนความเร่งด่วนของงาน</h3><div class="mt-4 h-64"><canvas id="urgencyChart"></canvas></div></div>
            <!-- Top Problems -->
            <div class="bg-white p-6 rounded-lg shadow-md"><h3 class="text-lg font-semibold text-gray-800">5 อันดับปัญหาสูงสุด</h3><ul class="mt-4 space-y-2 text-sm"><?php while($row = $top_problems_result->fetch_assoc()): ?><li class="flex justify-between"><span><?php echo htmlspecialchars($row['title']); ?></span> <span class="font-bold"><?php echo $row['total']; ?> ครั้ง</span></li><?php endwhile; ?></ul></div>
            <!-- Top Departments -->
            <div class="bg-white p-6 rounded-lg shadow-md"><h3 class="text-lg font-semibold text-gray-800">5 อันดับหน่วยงานที่แจ้งปัญหาสูงสุด</h3><ul class="mt-4 space-y-2 text-sm"><?php while($row = $top_departments_result->fetch_assoc()): ?><li class="flex justify-between"><span><?php echo htmlspecialchars($row['reporter_department']); ?></span> <span class="font-bold"><?php echo $row['total']; ?> ครั้ง</span></li><?php endwhile; ?></ul></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const sarabunFont = { family: 'Sarabun' };
    // Performance Chart
    new Chart(document.getElementById('performanceChart').getContext('2d'), {
        type: 'bar', data: { labels: <?php echo json_encode($chart_labels_staff); ?>, datasets: [ { label: 'งานที่ทำเสร็จ', data: <?php echo json_encode($chart_data_done); ?>, backgroundColor: 'rgba(16, 185, 129, 0.7)' }, { label: 'งานที่รับผิดชอบ', data: <?php echo json_encode($chart_data_assigned); ?>, backgroundColor: 'rgba(107, 114, 128, 0.5)' } ] },
        options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } }, x: { ticks: { font: sarabunFont } } }, plugins: { legend: { labels: { font: sarabunFont } } } }
    });
    // Trend Chart
    new Chart(document.getElementById('trendChart').getContext('2d'), {
        type: 'line', data: { labels: <?php echo json_encode($chart_labels_trend); ?>, datasets: [{ label: 'จำนวนเรื่อง', data: <?php echo json_encode($chart_values_trend); ?>, fill: true, borderColor: 'rgb(79, 70, 229)', tension: 0.1, backgroundColor: 'rgba(79, 70, 229, 0.1)' }] },
        options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } }, x: { ticks: { font: sarabunFont } } }, plugins: { legend: { display: false } } }
    });
    // Urgency Chart
    new Chart(document.getElementById('urgencyChart').getContext('2d'), {
        type: 'doughnut', data: { labels: <?php echo json_encode($chart_labels_urgency); ?>, datasets: [{ data: <?php echo json_encode($chart_values_urgency); ?>, backgroundColor: ['#EF4444', '#F59E0B', '#10B981'], hoverOffset: 4 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top', labels: { font: sarabunFont } } } }
    });
});
</script>

<?php
$conn->close();
require_once 'includes/footer.php';
?>
