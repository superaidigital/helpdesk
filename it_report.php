<?php
$page_title = "รายงานของฉัน";
require_once 'includes/functions.php';
// อนุญาตให้ 'it' และ 'admin' เข้าถึงได้ (เผื่อ admin ต้องการดูรายงานของแต่ละคน)
check_auth(['it', 'admin']); 
require_once 'includes/header.php'; 

// --- Date Filtering Logic ---
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$current_user_id = $_SESSION['user_id'];

// --- SQL Queries for Stats (Filtered by Logged-in User) ---
$user_condition = "AND assigned_to = ?";

// Stat Cards
$total_assigned_q = $conn->prepare("SELECT COUNT(id) as total FROM issues WHERE DATE(created_at) BETWEEN ? AND ? $user_condition");
$total_assigned_q->bind_param("ssi", $start_date, $end_date, $current_user_id);
$total_assigned_q->execute();
$total_assigned = $total_assigned_q->get_result()->fetch_assoc()['total'] ?? 0;

$done_issues_q = $conn->prepare("SELECT COUNT(id) as total FROM issues WHERE status = 'done' AND DATE(completed_at) BETWEEN ? AND ? $user_condition");
$done_issues_q->bind_param("ssi", $start_date, $end_date, $current_user_id);
$done_issues_q->execute();
$done_issues = $done_issues_q->get_result()->fetch_assoc()['total'] ?? 0;

$avg_time_q = $conn->prepare("SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, completed_at)) as avg_min FROM issues WHERE status = 'done' AND completed_at IS NOT NULL AND DATE(completed_at) BETWEEN ? AND ? $user_condition");
$avg_time_q->bind_param("ssi", $start_date, $end_date, $current_user_id);
$avg_time_q->execute();
$avg_minutes = $avg_time_q->get_result()->fetch_assoc()['avg_min'];

$avg_satisfaction_q = $conn->prepare("SELECT AVG(satisfaction_rating) as avg_rating FROM issues WHERE satisfaction_rating IS NOT NULL AND DATE(completed_at) BETWEEN ? AND ? $user_condition");
$avg_satisfaction_q->bind_param("ssi", $start_date, $end_date, $current_user_id);
$avg_satisfaction_q->execute();
$avg_satisfaction = $avg_satisfaction_q->get_result()->fetch_assoc()['avg_rating'];

$avg_time_str = "N/A";
if ($avg_minutes) {
    $avg_days = floor($avg_minutes / 1440);
    $rem_minutes = $avg_minutes % 1440;
    $avg_hours = floor($rem_minutes / 60);
    $rem_minutes = floor($rem_minutes % 60);
    $avg_time_str = ($avg_days > 0 ? "{$avg_days} วัน " : "") . "{$avg_hours} ชม. {$rem_minutes} นาที";
}

// Issues by Category (for Bar Chart)
$category_report_q = $conn->prepare("SELECT category, COUNT(id) as total FROM issues WHERE DATE(created_at) BETWEEN ? AND ? $user_condition GROUP BY category ORDER BY total DESC");
$category_report_q->bind_param("ssi", $start_date, $end_date, $current_user_id);
$category_report_q->execute();
$category_result = $category_report_q->get_result();
$category_data = [];
while ($row = $category_result->fetch_assoc()) {
    $category_data[] = $row;
}
$category_labels_json = json_encode(array_column($category_data, 'category'));
$category_values_json = json_encode(array_column($category_data, 'total'));

?>
<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="space-y-6">
    <!-- Filter Form -->
    <div class="bg-white p-4 rounded-lg shadow-md">
        <form id="report-filter-form" method="GET" action="it_report.php" class="flex flex-col sm:flex-row items-center gap-4">
            <div>
                <label for="start_date" class="text-sm font-medium text-gray-700">วันที่เริ่มต้น:</label>
                <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($start_date); ?>" class="mt-1 block border border-gray-300 rounded-md shadow-sm py-2 px-3">
            </div>
            <div>
                <label for="end_date" class="text-sm font-medium text-gray-700">วันที่สิ้นสุด:</label>
                <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($end_date); ?>" class="mt-1 block border border-gray-300 rounded-md shadow-sm py-2 px-3">
            </div>
            <div class="flex items-center space-x-2">
                <button type="submit" class="w-full sm:w-auto mt-6 px-4 py-2 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700">
                    <i class="fa-solid fa-filter mr-2"></i>กรองข้อมูล
                </button>
                <a id="export-btn" href="#" class="w-full sm:w-auto mt-6 px-4 py-2 bg-green-600 text-white font-semibold rounded-md hover:bg-green-700">
                    <i class="fa-solid fa-file-excel mr-2"></i>ส่งออกรายงาน
                </a>
            </div>
        </form>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white p-6 rounded-lg shadow-md"><h3 class="font-semibold text-gray-700">งานที่รับผิดชอบ</h3><p class="text-3xl font-bold text-gray-900 mt-1"><?php echo $total_assigned; ?></p></div>
        <div class="bg-white p-6 rounded-lg shadow-md"><h3 class="font-semibold text-gray-700">แก้ไขเสร็จสิ้น</h3><p class="text-3xl font-bold text-green-600 mt-1"><?php echo $done_issues; ?></p></div>
        <div class="bg-white p-6 rounded-lg shadow-md"><h3 class="font-semibold text-gray-700">เวลาเฉลี่ยในการแก้ไข</h3><p class="text-2xl font-bold text-blue-600 mt-1"><?php echo $avg_time_str; ?></p></div>
        <div class="bg-white p-6 rounded-lg shadow-md"><h3 class="font-semibold text-gray-700">คะแนนความพึงพอใจ</h3><p class="text-3xl font-bold text-amber-500 mt-1"><?php echo $avg_satisfaction ? number_format($avg_satisfaction, 2) . ' / 5' : 'N/A'; ?></p></div>
    </div>

    <!-- Charts and Tables -->
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">สรุปปัญหาตามหมวดหมู่ (เฉพาะงานของคุณ)</h3>
        <div class="h-80">
             <canvas id="categoryChart"></canvas>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Bar Chart for Categories
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    new Chart(categoryCtx, {
        type: 'bar',
        data: {
            labels: <?php echo $category_labels_json; ?>,
            datasets: [{
                label: 'จำนวนเรื่อง',
                data: <?php echo $category_values_json; ?>,
                backgroundColor: 'rgba(79, 70, 229, 0.8)',
                borderColor: 'rgba(79, 70, 229, 1)',
                borderWidth: 1
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 }}}, plugins: { legend: { display: false }}}
    });

    // Script for dynamic export link
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const exportBtn = document.getElementById('export-btn');
    function updateExportLink() {
        exportBtn.href = `export_report.php?start_date=${startDateInput.value}&end_date=${endDateInput.value}&user_id=<?php echo $current_user_id; ?>`;
    }
    startDateInput.addEventListener('change', updateExportLink);
    endDateInput.addEventListener('change', updateExportLink);
    updateExportLink(); // Set initial link
});
</script>

<?php 
$conn->close();
require_once 'includes/footer.php'; 
?>
