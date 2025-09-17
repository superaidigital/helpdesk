<?php
$page_title = "รายงานวิเคราะห์ภาพรวม";
require_once 'includes/functions.php';
check_auth(['admin']);
require_once 'includes/header.php';

// --- Date Filtering Logic ---
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$base_condition = "DATE(created_at) BETWEEN ? AND ?";

// --- SQL Queries for Analytics ---

// 1. Top 5 Most Frequent Problems
$top_problems_stmt = $conn->prepare("SELECT title, COUNT(id) as total FROM issues WHERE $base_condition GROUP BY title ORDER BY total DESC, title ASC LIMIT 5");
$top_problems_stmt->bind_param("ss", $start_date, $end_date);
$top_problems_stmt->execute();
$top_problems_q = $top_problems_stmt->get_result();


// 2. Top 5 Busiest Departments (FIXED SQL SYNTAX)
$top_departments_stmt = $conn->prepare("SELECT reporter_department, COUNT(id) as total FROM issues WHERE $base_condition AND reporter_department IS NOT NULL AND reporter_department != '' GROUP BY reporter_department ORDER BY total DESC, reporter_department ASC LIMIT 5");
$top_departments_stmt->bind_param("ss", $start_date, $end_date);
$top_departments_stmt->execute();
$top_departments_q = $top_departments_stmt->get_result();


// 3. Average Resolution Time by Category (in hours)
$avg_time_by_cat_stmt = $conn->prepare("SELECT category, AVG(TIMESTAMPDIFF(HOUR, created_at, completed_at)) as avg_hours FROM issues WHERE status = 'done' AND completed_at IS NOT NULL AND DATE(completed_at) BETWEEN ? AND ? GROUP BY category ORDER BY avg_hours DESC");
$avg_time_by_cat_stmt->bind_param("ss", $start_date, $end_date);
$avg_time_by_cat_stmt->execute();
$avg_time_by_cat_q = $avg_time_by_cat_stmt->get_result();

// 4. Issues by Day of Week (for Bar Chart)
$day_of_week_stmt = $conn->prepare("SELECT DAYNAME(created_at) as day_name, COUNT(id) as total FROM issues WHERE $base_condition GROUP BY DAYOFWEEK(created_at), day_name ORDER BY DAYOFWEEK(created_at) ASC");
$day_of_week_stmt->bind_param("ss", $start_date, $end_date);
$day_of_week_stmt->execute();
$day_of_week_q = $day_of_week_stmt->get_result();

$day_labels = [];
$day_values = [];
$thai_days = ['Sunday'=>'อาทิตย์', 'Monday'=>'จันทร์', 'Tuesday'=>'อังคาร', 'Wednesday'=>'พุธ', 'Thursday'=>'พฤหัสบดี', 'Friday'=>'ศุกร์', 'Saturday'=>'เสาร์'];
while($row = $day_of_week_q->fetch_assoc()){
    $day_labels[] = $thai_days[$row['day_name']];
    $day_values[] = $row['total'];
}
$day_labels_json = json_encode($day_labels);
$day_values_json = json_encode($day_values);

?>
<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="space-y-6">
    <!-- Filter Form -->
    <div class="bg-white p-4 rounded-lg shadow-md">
        <form method="GET" action="admin_analytics.php" class="flex flex-col sm:flex-row items-center gap-4">
            <div>
                <label for="start_date" class="text-sm font-medium text-gray-700">วันที่เริ่มต้น:</label>
                <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($start_date); ?>" class="mt-1 block border border-gray-300 rounded-md shadow-sm py-2 px-3">
            </div>
            <div>
                <label for="end_date" class="text-sm font-medium text-gray-700">วันที่สิ้นสุด:</label>
                <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($end_date); ?>" class="mt-1 block border border-gray-300 rounded-md shadow-sm py-2 px-3">
            </div>
            <div>
                <button type="submit" class="w-full sm:w-auto mt-6 px-4 py-2 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700">
                    <i class="fa-solid fa-filter mr-2"></i>วิเคราะห์ข้อมูล
                </button>
            </div>
        </form>
    </div>

    <!-- Analytics Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top 5 Problems -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-semibold text-gray-800">5 อันดับปัญหาสูงสุด</h3>
            <ul class="mt-4 space-y-2 text-sm">
                <?php while($row = $top_problems_q->fetch_assoc()): ?>
                <li class="flex justify-between"><span><?php echo htmlspecialchars($row['title']); ?></span> <span class="font-bold"><?php echo $row['total']; ?> ครั้ง</span></li>
                <?php endwhile; ?>
            </ul>
        </div>

        <!-- Top 5 Departments -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-semibold text-gray-800">5 อันดับหน่วยงานที่แจ้งปัญหาสูงสุด</h3>
            <ul class="mt-4 space-y-2 text-sm">
                 <?php while($row = $top_departments_q->fetch_assoc()): ?>
                <li class="flex justify-between"><span><?php echo htmlspecialchars($row['reporter_department']); ?></span> <span class="font-bold"><?php echo $row['total']; ?> ครั้ง</span></li>
                <?php endwhile; ?>
            </ul>
        </div>

        <!-- Avg Time by Category -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-semibold text-gray-800">เวลาเฉลี่ยในการแก้ไข (แยกตามหมวดหมู่)</h3>
            <ul class="mt-4 space-y-2 text-sm">
                <?php while($row = $avg_time_by_cat_q->fetch_assoc()): ?>
                <li class="flex justify-between"><span><?php echo htmlspecialchars($row['category']); ?></span> <span class="font-bold"><?php echo round($row['avg_hours'], 1); ?> ชั่วโมง</span></li>
                <?php endwhile; ?>
            </ul>
        </div>
        
        <!-- Issues by Day of Week Chart -->
        <div class="bg-white p-6 rounded-lg shadow-md">
             <h3 class="text-lg font-semibold text-gray-800">ปริมาณงานตามวันในสัปดาห์</h3>
             <div class="mt-4 h-64">
                <canvas id="dayOfWeekChart"></canvas>
             </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const dayCtx = document.getElementById('dayOfWeekChart').getContext('2d');
    new Chart(dayCtx, {
        type: 'bar',
        data: {
            labels: <?php echo $day_labels_json; ?>,
            datasets: [{
                label: 'จำนวนเรื่องที่แจ้ง',
                data: <?php echo $day_values_json; ?>,
                backgroundColor: 'rgba(59, 130, 246, 0.7)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 1
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 }}}, plugins: { legend: { display: false }}}
    });
});
</script>

<?php 
$conn->close();
require_once 'includes/footer.php'; 
?>
