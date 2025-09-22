<?php
// $page_title = "รายการปัญหาทั้งหมด";
require_once 'includes/functions.php';

// ตรวจสอบสิทธิ์: อนุญาตให้เฉพาะ 'it' และ 'admin' เข้าถึงหน้านี้
check_auth(['it', 'admin']); 

// --- Pagination & Filtering Logic ---
$items_per_page = 5;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) {
    $current_page = 1;
}
$offset = ($current_page - 1) * $items_per_page;

$view = $_GET['view'] ?? 'active';
$search_term = trim($_GET['search'] ?? '');

// --- Build SQL Query Securely using Prepared Statements ---
$sql_conditions = [];
$params = [];
$types = "";

if ($view === 'done') {
    $page_title = "รายการปัญหาที่เสร็จสิ้น";
    $sql_conditions[] = "status = 'done'";
    // ถ้าผู้ใช้เป็น 'it', ให้แสดงเฉพาะงานของตัวเอง
    if ($_SESSION['role'] === 'it') {
        $sql_conditions[] = "assigned_to = ?";
        $params[] = $_SESSION['user_id'];
        $types .= "i";
    }
} else {
    $page_title = "รายการปัญหา (ที่ยังไม่เสร็จสิ้น)";
    $sql_conditions[] = "status != 'done'";
}

// SECURITY: Add search condition with placeholders to prevent SQL Injection
if (!empty($search_term)) {
    $search_query = "%" . $search_term . "%";
    $sql_conditions[] = "(title LIKE ? OR description LIKE ? OR reporter_name LIKE ?)";
    array_push($params, $search_query, $search_query, $search_query);
    $types .= "sss";
}

$where_clause = !empty($sql_conditions) ? " WHERE " . implode(" AND ", $sql_conditions) : "";

// Get total count for pagination
$total_count_sql = "SELECT COUNT(id) as total FROM issues" . $where_clause;
$stmt_count = $conn->prepare($total_count_sql);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_items = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_items / $items_per_page);
$stmt_count->close();

// Get paginated results
$sql = "SELECT * FROM issues" . $where_clause . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
array_push($params, $items_per_page, $offset);
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

require_once 'includes/header.php'; 

// Prepare maps for status display
$status_text_map = [
    'pending' => 'รอตรวจสอบ',
    'in_progress' => 'กำลังดำเนินการ',
    'done' => 'เสร็จสิ้น',
    'cannot_resolve' => 'แก้ไขไม่ได้'
];
$status_color_map = [
    'pending' => 'bg-yellow-100 text-yellow-800',
    'in_progress' => 'bg-blue-100 text-blue-800',
    'done' => 'bg-green-100 text-green-800',
    'cannot_resolve' => 'bg-red-100 text-red-800'
];
?>

<div class="bg-white rounded-lg shadow-md">
    <div class="p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b">
       <h3 class="font-semibold text-lg"><?php echo htmlspecialchars($page_title); ?></h3>
       <div class="flex items-center space-x-2 w-full sm:w-auto">
            <!-- Search Form -->
            <form method="GET" action="it_dashboard.php" class="flex-grow sm:flex-grow-0">
                <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
                <div class="relative">
                    <input type="text" name="search" placeholder="ค้นหา..." value="<?php echo htmlspecialchars($search_term); ?>" class="w-full pl-8 pr-4 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>
            </form>

            <?php if ($_SESSION['role'] === 'it'): ?>
                <a href="export_report.php?user_id=<?php echo $_SESSION['user_id']; ?>" class="px-4 py-2 bg-green-600 text-white rounded-md text-sm hover:bg-green-700 whitespace-nowrap">
                    <i class="fa-solid fa-file-excel mr-2"></i>ส่งออกรายงาน
                </a>
            <?php endif; ?>
            
            <!-- View Toggle Button -->
            <?php if ($view === 'done'): ?>
                <a href="it_dashboard.php" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700 whitespace-nowrap">
                    <i class="fa-solid fa-list-check mr-2"></i>ดูงานที่ยังไม่เสร็จสิ้น
                </a>
            <?php else: ?>
                <a href="it_dashboard.php?view=done" class="px-4 py-2 bg-green-500 text-white rounded-md text-sm hover:bg-green-600 whitespace-nowrap">
                    <i class="fa-solid fa-check-double mr-2"></i>ดูงานที่เสร็จสิ้นแล้ว
                </a>
            <?php endif; ?>
       </div>
   </div>
   <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">หัวข้อ</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">ผู้แจ้ง</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">สถานะ</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">ผู้รับผิดชอบ</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">จัดการ</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($issue = $result->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?php echo $issue['id']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo htmlspecialchars($issue['title']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($issue['reporter_name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?php echo $status_color_map[$issue['status']] ?? 'bg-gray-100 text-gray-800'; ?>">
                                    <?php echo $status_text_map[$issue['status']] ?? htmlspecialchars($issue['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo getUserNameById($issue['assigned_to'], $conn); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <?php if ($issue['status'] === 'pending' && is_null($issue['assigned_to']) && $_SESSION['role'] === 'it'): ?>
                                    <a href="issue_action.php?action=accept&id=<?php echo $issue['id']; ?>" class="text-white bg-blue-500 hover:bg-blue-600 px-3 py-1 rounded-md text-xs font-semibold">รับงาน</a>
                                <?php else: ?>
                                    <a href="issue_view.php?id=<?php echo $issue['id']; ?>" class="text-indigo-600 hover:text-indigo-900 font-semibold"><i class="fa-solid fa-magnifying-glass mr-1"></i>ดูรายละเอียด</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-10 text-gray-500">
                             <?php echo !empty($search_term) ? 'ไม่พบข้อมูลที่ตรงกับคำค้นหา' : ($view === 'done' ? 'ไม่พบข้อมูลงานที่เสร็จสิ้น' : 'ไม่พบข้อมูลงานที่ยังไม่เสร็จสิ้น'); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
   <!-- Pagination -->
   <?php 
   $pagination_params = ['view' => $view];
   if (!empty($search_term)) {
       $pagination_params['search'] = $search_term;
   }
   echo generate_pagination_links($total_pages, $current_page, 'it_dashboard.php', $pagination_params); 
   ?>
</div>

<?php 
$stmt->close();
if(isset($conn)) $conn->close();
require_once 'includes/footer.php'; 
?>

