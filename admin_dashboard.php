<?php
$page_title = "แดชบอร์ดผู้ดูแลระบบ";
require_once 'includes/functions.php';
check_auth(['admin']); // ตรวจสอบสิทธิ์ ต้องเป็น 'admin' เท่านั้น
require_once 'includes/header.php'; 

// --- คำนวณสถิติ ---
// จำนวนเรื่องทั้งหมด
$total_issues_q = $conn->query("SELECT COUNT(id) as total FROM issues");
$total_issues = $total_issues_q ? $total_issues_q->fetch_assoc()['total'] : 0;

// จำนวนเรื่องที่กำลังดำเนินการ
$inprogress_issues_q = $conn->query("SELECT COUNT(id) as total FROM issues WHERE status = 'in_progress'");
$inprogress_issues = $inprogress_issues_q ? $inprogress_issues_q->fetch_assoc()['total'] : 0;

// จำนวนเรื่องที่รอตรวจสอบ
$pending_issues_q = $conn->query("SELECT COUNT(id) as total FROM issues WHERE status = 'pending'");
$pending_issues = $pending_issues_q ? $pending_issues_q->fetch_assoc()['total'] : 0;

?>

<div class="space-y-8">
    <!-- Stat Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-lg shadow-md flex items-center">
            <div class="bg-indigo-100 p-4 rounded-full">
                <i class="fa-solid fa-layer-group text-2xl text-indigo-600"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-semibold text-gray-700">เรื่องทั้งหมด</h3>
                <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo $total_issues; ?></p>
            </div>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md flex items-center">
            <div class="bg-blue-100 p-4 rounded-full">
                <i class="fa-solid fa-spinner text-2xl text-blue-600"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-semibold text-gray-700">กำลังดำเนินการ</h3>
                <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo $inprogress_issues; ?></p>
            </div>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md flex items-center">
            <div class="bg-amber-100 p-4 rounded-full">
                <i class="fa-solid fa-hourglass-start text-2xl text-amber-600"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-semibold text-gray-700">รอตรวจสอบ</h3>
                <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo $pending_issues; ?></p>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h3 class="text-xl font-semibold text-gray-800">ทางลัด (Quick Actions)</h3>
        <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="admin_users.php" class="block p-4 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                <i class="fa-solid fa-users mr-2 text-indigo-500"></i> จัดการผู้ใช้งานระบบ
            </a>
            <a href="it_dashboard.php" class="block p-4 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                <i class="fa-solid fa-list-check mr-2 text-blue-500"></i> ดูรายการปัญหาทั้งหมด
            </a>
            <a href="knowledge_base.php" class="block p-4 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                <i class="fa-solid fa-book mr-2 text-green-500"></i> เปิดฐานความรู้
            </a>
        </div>
    </div>
</div>

<?php 
$conn->close();
require_once 'includes/footer.php'; 
?>

