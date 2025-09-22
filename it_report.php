<?php
require_once 'includes/functions.php';
check_auth(['it']); 
require_once 'includes/header.php'; 

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

// --- Fetch all data needed for the page ---
// Personal Stats
$stats_sql = "
    SELECT
        (SELECT COUNT(id) FROM issues WHERE assigned_to = ? AND DATE(created_at) BETWEEN ? AND ?) as total_assigned,
        COUNT(id) as total_completed,
        AVG(TIMESTAMPDIFF(MINUTE, created_at, completed_at)) as avg_minutes,
        AVG(satisfaction_rating) as avg_rating
    FROM issues
    WHERE assigned_to = ? AND status = 'done' AND completed_at IS NOT NULL AND DATE(completed_at) BETWEEN ? AND ?
";
$stats_q = $conn->prepare($stats_sql);
$stats_q->bind_param("isssis", $current_user_id, $start_date, $end_date, $current_user_id, $start_date, $end_date);
$stats_q->execute();
$stats = $stats_q->get_result()->fetch_assoc();
$stats_q->close();

// Team Average Stats
$team_avg_sql = "
    SELECT
        AVG(stats.total_completed) as avg_team_completed,
        AVG(stats.avg_minutes) as avg_team_minutes,
        AVG(stats.avg_rating) as avg_team_rating
    FROM (
        SELECT COUNT(id) as total_completed, AVG(TIMESTAMPDIFF(MINUTE, created_at, completed_at)) as avg_minutes, AVG(satisfaction_rating) as avg_rating
        FROM issues
        WHERE status = 'done' AND completed_at IS NOT NULL AND DATE(completed_at) BETWEEN ? AND ? AND assigned_to IN (SELECT id FROM users WHERE role IN ('it', 'admin'))
        GROUP BY assigned_to
    ) as stats
";
$team_avg_q = $conn->prepare($team_avg_sql);
$team_avg_q->bind_param("ss", $start_date, $end_date);
$team_avg_q->execute();
$team_avg_stats = $team_avg_q->get_result()->fetch_assoc();
$team_avg_q->close();

// Prepare chart data
$issues_for_charts_sql = "SELECT category, urgency FROM issues WHERE assigned_to = ? AND DATE(created_at) BETWEEN ? AND ?";
$charts_stmt = $conn->prepare($issues_for_charts_sql);
$charts_stmt->bind_param("iss", $current_user_id, $start_date, $end_date);
$charts_stmt->execute();
$issues_for_charts_result = $charts_stmt->get_result();
$all_issues_for_charts = $issues_for_charts_result->fetch_all(MYSQLI_ASSOC);
$charts_stmt->close();

$category_data = [];
$urgency_data = [];
foreach($all_issues_for_charts as $issue) {
    @$category_data[$issue['category']]++;
    @$urgency_data[$issue['urgency']]++;
}
arsort($category_data);
$category_labels_json = json_encode(array_keys($category_data));
$category_values_json = json_encode(array_values($category_data));
$urgency_labels_json = json_encode(array_keys($urgency_data));
$urgency_values_json = json_encode(array_values($urgency_data));

?>
<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="space-y-6">
    <!-- Header and Filters -->
    <div class="flex flex-col sm:flex-row justify-between items-start mb-4 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">สรุปผลการปฏิบัติงานของคุณ <?php echo $report_title_month_year; ?></h2>
        </div>
        <div class="flex items-center gap-4 bg-white p-2 rounded-lg shadow-sm">
            <form method="GET" action="it_report.php" class="flex items-center gap-2">
                <select name="month" class="border-gray-300 rounded-md shadow-sm text-sm">
                    <?php foreach ($thai_months as $m_num => $m_name): ?>
                        <option value="<?php echo $m_num; ?>" <?php echo ($m_num == $month) ? 'selected' : ''; ?>><?php echo $m_name; ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="year" class="border-gray-300 rounded-md shadow-sm text-sm">
                     <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo ($y == $year) ? 'selected' : ''; ?>><?php echo $y + 543; ?></option>
                    <?php endfor; ?>
                </select>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 text-sm">แสดงผล</button>
            </form>
            <a href="it_report_print.php?month=<?php echo $month; ?>&year=<?php echo $year; ?>" target="_blank" class="px-4 py-2 bg-gray-600 text-white font-semibold rounded-md hover:bg-gray-700 text-sm">
                <i class="fa-solid fa-print mr-2"></i>พิมพ์
            </a>
        </div>
    </div>
    
    <!-- Stat Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white p-6 rounded-lg shadow-md"><h3 class="font-semibold text-gray-700">เรื่องที่คุณรับผิดชอบ</h3><p class="text-3xl font-bold text-gray-900 mt-1"><?php echo $stats['total_assigned'] ?? 0; ?></p></div>
        <div class="bg-white p-6 rounded-lg shadow-md"><h3 class="font-semibold text-gray-700">เสร็จสิ้นในเดือนนี้</h3><p class="text-3xl font-bold text-green-600 mt-1"><?php echo $stats['total_completed'] ?? 0; ?></p><p class="text-xs text-gray-500">เฉลี่ยทีม: <?php echo number_format($team_avg_stats['avg_team_completed'] ?? 0, 1); ?></p></div>
        <div class="bg-white p-6 rounded-lg shadow-md"><h3 class="font-semibold text-gray-700">เวลาแก้ไขเฉลี่ย</h3><p class="text-2xl font-bold text-blue-600 mt-1"><?php echo formatDuration($stats['avg_minutes']); ?></p><p class="text-xs text-gray-500">เฉลี่ยทีม: <?php echo formatDuration($team_avg_stats['avg_team_minutes']); ?></p></div>
        <div class="bg-white p-6 rounded-lg shadow-md"><h3 class="font-semibold text-gray-700">คะแนนเฉลี่ยของคุณ</h3><p class="text-3xl font-bold text-amber-500 mt-1"><?php echo $stats['avg_rating'] ? number_format($stats['avg_rating'], 2) : 'N/A'; ?></p><p class="text-xs text-gray-500">เฉลี่ยทีม: <?php echo number_format($team_avg_stats['avg_team_rating'] ?? 0, 2); ?></p></div>
    </div>
    
    <!-- Analytics Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">สัดส่วนงานตามหมวดหมู่</h3>
            <div class="h-64"><canvas id="categoryChart"></canvas></div>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">สัดส่วนความเร่งด่วนของงาน</h3>
            <div class="h-64"><canvas id="urgencyChart"></canvas></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const sarabunFont = { family: 'Sarabun' };
    const chartDefaults = { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { font: sarabunFont } } } };
    
    const categoryConfig = {
        type: 'doughnut',
        data: { labels: <?php echo $category_labels_json; ?>, datasets: [{ data: <?php echo $category_values_json; ?>, backgroundColor: ['#4F46E5', '#10B981', '#F59E0B', '#3B82F6', '#EF4444', '#6B7280'], hoverOffset: 4 }] },
        options: { ...chartDefaults, plugins: { legend: { position: 'right', ...chartDefaults.plugins.legend } } }
    };
    
    const urgencyConfig = {
        type: 'doughnut',
        data: { labels: <?php echo $urgency_labels_json; ?>, datasets: [{ data: <?php echo $urgency_values_json; ?>, backgroundColor: ['#DC2626', '#F59E0B', '#10B981'], hoverOffset: 4 }] },
        options: { ...chartDefaults, plugins: { legend: { position: 'right', ...chartDefaults.plugins.legend } } }
    };

    new Chart(document.getElementById('categoryChart').getContext('2d'), categoryConfig);
    new Chart(document.getElementById('urgencyChart').getContext('2d'), urgencyConfig);
});
</script>

<?php 
$conn->close();
require_once 'includes/footer.php'; 
?>
