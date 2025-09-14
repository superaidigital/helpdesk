<?php
$page_title = "รายงานสรุปผล";
require_once 'includes/functions.php';
check_auth(['admin']);
require_once 'includes/header.php';

// --- Date Filtering Logic ---
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// --- SQL Queries for Stats ---
$date_condition = "WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date'";
$completed_date_condition = "WHERE status = 'done' AND completed_at IS NOT NULL AND DATE(completed_at) BETWEEN '$start_date' AND '$end_date'";

// Stat Cards
$total_issues = $conn->query("SELECT COUNT(id) as total FROM issues $date_condition")->fetch_assoc()['total'] ?? 0;
$done_issues = $conn->query("SELECT COUNT(id) as total FROM issues $completed_date_condition")->fetch_assoc()['total'] ?? 0;
$avg_time_q = $conn->query("SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, completed_at)) as avg_min FROM issues $completed_date_condition");
$avg_minutes = $avg_time_q->fetch_assoc()['avg_min'];

$avg_satisfaction_q = $conn->query("SELECT AVG(satisfaction_rating) as avg_rating FROM issues WHERE satisfaction_rating IS NOT NULL AND DATE(completed_at) BETWEEN '$start_date' AND '$end_date'");
$avg_satisfaction = $avg_satisfaction_q->fetch_assoc()['avg_rating'];


$avg_time_str = "N/A";
if ($avg_minutes) {
    $avg_days = floor($avg_minutes / 1440);
    $rem_minutes = $avg_minutes % 1440;
    $avg_hours = floor($rem_minutes / 60);
    $rem_minutes = $rem_minutes % 60;
    $avg_time_str = ($avg_days > 0 ? "{$avg_days} วัน " : "") . "{$avg_hours} ชม. {$rem_minutes} นาที";
}

// Issues by Status (for Pie Chart)
$status_report_q = $conn->query("SELECT status, COUNT(id) as total FROM issues $date_condition GROUP BY status");
$status_data = [];
while ($row = $status_report_q->fetch_assoc()) {
    $status_data[$row['status']] = $row['total'];
}
$status_labels_json = json_encode(array_keys($status_data));
$status_values_json = json_encode(array_values($status_data));


// Issues by Category (for Bar Chart)
$category_report_q = $conn->query("SELECT category, COUNT(id) as total FROM issues $date_condition GROUP BY category ORDER BY total DESC");
$category_data = [];
while ($row = $category_report_q->fetch_assoc()) {
    $category_data[] = $row;
}
$category_labels_json = json_encode(array_column($category_data, 'category'));
$category_values_json = json_encode(array_column($category_data, 'total'));


// Issues by IT Staff
$staff_report_q = $conn->query("SELECT u.id, u.fullname, COUNT(i.id) as total_assigned, SUM(CASE WHEN i.status = 'done' THEN 1 ELSE 0 END) as total_done FROM users u LEFT JOIN issues i ON u.id = i.assigned_to AND DATE(i.created_at) BETWEEN '$start_date' AND '$end_date' WHERE u.role = 'it' GROUP BY u.id, u.fullname ORDER BY total_assigned DESC");
?>
<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="space-y-6">
    <!-- Filter Form -->
    <div class="bg-white p-4 rounded-lg shadow-md">
        <form id="report-filter-form" method="GET" action="admin_reports.php" class="flex flex-col sm:flex-row items-center gap-4">
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
        <div class="bg-white p-6 rounded-lg shadow-md"><h3 class="font-semibold text-gray-700">เรื่องแจ้งเข้าทั้งหมด</h3><p class="text-3xl font-bold text-gray-900 mt-1"><?php echo $total_issues; ?></p></div>
        <div class="bg-white p-6 rounded-lg shadow-md"><h3 class="font-semibold text-gray-700">แก้ไขเสร็จสิ้น</h3><p class="text-3xl font-bold text-green-600 mt-1"><?php echo $done_issues; ?></p></div>
        <div class="bg-white p-6 rounded-lg shadow-md"><h3 class="font-semibold text-gray-700">เวลาเฉลี่ยในการแก้ไข</h3><p class="text-2xl font-bold text-blue-600 mt-1"><?php echo $avg_time_str; ?></p></div>
        <div class="bg-white p-6 rounded-lg shadow-md"><h3 class="font-semibold text-gray-700">คะแนนความพึงพอใจ</h3><p class="text-3xl font-bold text-amber-500 mt-1"><?php echo $avg_satisfaction ? number_format($avg_satisfaction, 2) . ' / 5' : 'N/A'; ?></p></div>
    </div>

    <!-- Charts and Tables -->
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
        <!-- Issues by Category (Bar Chart) -->
        <div class="lg:col-span-3 bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">สรุปปัญหาตามหมวดหมู่</h3>
            <canvas id="categoryChart"></canvas>
        </div>
        <!-- Issues by Status (Pie Chart) -->
        <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">สัดส่วนสถานะงาน</h3>
            <canvas id="statusChart"></canvas>
        </div>
    </div>

    <!-- Issues by Staff Table -->
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">ปริมาณงานของเจ้าหน้าที่</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase">เจ้าหน้าที่</th>
                        <th class="px-4 py-2 text-center text-xs font-bold text-gray-500 uppercase">รับผิดชอบ</th>
                        <th class="px-4 py-2 text-center text-xs font-bold text-gray-500 uppercase">เสร็จสิ้น</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php while($staff = $staff_report_q->fetch_assoc()): ?>
                    <tr>
                        <td class="px-4 py-2 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($staff['fullname']); ?></td>
                        <td class="px-4 py-2 text-center text-sm text-gray-500"><?php echo (int)$staff['total_assigned']; ?></td>
                        <td class="px-4 py-2 text-center text-sm text-gray-500"><?php echo (int)$staff['total_done']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
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
        options: { responsive: true, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 }}}, plugins: { legend: { display: false }}}
    });

    // Pie Chart for Statuses
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo $status_labels_json; ?>,
            datasets: [{
                data: <?php echo $status_values_json; ?>,
                backgroundColor: [ 'rgba(251, 146, 60, 0.8)', 'rgba(59, 130, 246, 0.8)', 'rgba(16, 185, 129, 0.8)', 'rgba(239, 68, 68, 0.8)'],
                borderColor: [ 'rgba(251, 146, 60, 1)', 'rgba(59, 130, 246, 1)', 'rgba(16, 185, 129, 1)', 'rgba(239, 68, 68, 1)'],
                borderWidth: 1
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'top' }}}
    });

    // Script for dynamic export link
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const exportBtn = document.getElementById('export-btn');
    function updateExportLink() {
        exportBtn.href = `export_report.php?start_date=${startDateInput.value}&end_date=${endDateInput.value}`;
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

