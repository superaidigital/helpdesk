<?php
$page_title = "จัดการผู้ใช้งานระบบ";
require_once 'includes/functions.php';
check_auth(['admin']);
require_once 'includes/header.php';

// --- Pagination & Search Logic ---
$items_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) {
    $current_page = 1;
}
$offset = ($current_page - 1) * $items_per_page;

$search_term = trim($_GET['search'] ?? '');

// --- Build SQL Query Securely using Prepared Statements ---
$sql_conditions = "";
$params = [];
$types = "";

// SECURITY: Add search condition with placeholders to prevent SQL Injection
if (!empty($search_term)) {
    $search_query = "%" . $search_term . "%";
    $sql_conditions = " WHERE (fullname LIKE ? OR email LIKE ? OR position LIKE ? OR department LIKE ?)";
    $params = [$search_query, $search_query, $search_query, $search_query];
    $types = "ssss";
}

// Get total count for pagination
$total_count_sql = "SELECT COUNT(id) as total FROM users" . $sql_conditions;
$stmt_count = $conn->prepare($total_count_sql);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_items = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_items / $items_per_page);
$stmt_count->close();

// Get paginated results
$sql = "SELECT * FROM users" . $sql_conditions . " ORDER BY role, fullname LIMIT ? OFFSET ?";
$params[] = $items_per_page;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$users_result = $stmt->get_result();

// ค้นหา ID ของเจ้าหน้าที่ที่มีงานเยอะที่สุด (อาจมีหลายคน)
$busiest_ids = getBusiestITStaffIds($conn); 
?>
<div x-data="{ isDeleteModalOpen: false, userToDeleteId: null, userToDeleteName: '' }">
    <div class="bg-white rounded-lg shadow-md">
       <div class="p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b">
           <div class="flex-shrink-0">
                <h3 class="font-semibold text-lg">รายชื่อผู้ใช้งานในระบบ</h3>
           </div>
            <div class="flex items-center space-x-2 w-full sm:w-auto">
                <form method="GET" action="admin_users.php" class="flex-grow">
                    <div class="relative">
                        <input type="text" name="search" placeholder="ค้นหาชื่อ, อีเมล, ตำแหน่ง..." value="<?php echo htmlspecialchars($search_term); ?>" class="w-full pl-8 pr-4 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                </form>
                <a href="admin_user_form.php" class="px-4 py-2 bg-green-500 text-white rounded-md text-sm hover:bg-green-600 whitespace-nowrap">
                   <i class="fa-solid fa-plus-circle mr-2"></i>เพิ่มผู้ใช้ใหม่
               </a>
           </div>
       </div>
       <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">ผู้ใช้งาน</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">ตำแหน่ง</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">ติดต่อ</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">สิทธิ์</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if ($users_result->num_rows > 0): ?>
                        <?php while($user = $users_result->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 relative">
                                        <img class="h-10 w-10 rounded-full object-cover" src="<?php echo htmlspecialchars(get_user_avatar($user['image_url'])); ?>" alt="Profile image of <?php echo htmlspecialchars($user['fullname']); ?>">
                                        <?php if ($user['role'] === 'it' && in_array($user['id'], $busiest_ids)): ?>
                                            <div class="absolute -top-1 -right-1 transform rotate-12">
                                                <i class="fa-solid fa-crown text-yellow-400 text-lg" style="filter: drop-shadow(0 1px 1px rgba(0,0,0,0.4));"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['fullname']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($user['position']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($user['department']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div><i class="fa-solid fa-phone w-4 text-gray-400"></i> <?php echo htmlspecialchars($user['phone']); ?></div>
                                <div><i class="fab fa-line w-4 text-green-500"></i> <?php echo htmlspecialchars($user['line_id']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800"><?php echo htmlspecialchars($user['role']); ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="admin_user_form.php?id=<?php echo $user['id']; ?>" class="text-indigo-600 hover:text-indigo-900">แก้ไข</a>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <button @click="isDeleteModalOpen = true; userToDeleteId = <?php echo $user['id']; ?>; userToDeleteName = '<?php echo htmlspecialchars(addslashes($user['fullname'])); ?>';" class="text-red-600 hover:text-red-900 ml-4">ลบ</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-10 text-gray-500">ไม่พบข้อมูลผู้ใช้งานที่ตรงกับคำค้นหา</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
       </div>
       <!-- Pagination -->
       <?php 
       $pagination_params = [];
       if (!empty($search_term)) {
           $pagination_params['search'] = $search_term;
       }
       echo generate_pagination_links($total_pages, $current_page, 'admin_users.php', $pagination_params); 
       ?>
    </div>

    <!-- Delete Confirmation Modal -->
    <div x-show="isDeleteModalOpen" @keydown.escape.window="isDeleteModalOpen = false" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50" x-cloak>
        <div class="bg-white rounded-lg shadow-2xl max-w-sm w-full" @click.away="isDeleteModalOpen = false">
            <div class="p-6 text-center">
                <i class="fa-solid fa-triangle-exclamation text-4xl text-red-500 mx-auto mb-4"></i>
                <h3 class="font-semibold text-lg text-gray-800">ยืนยันการลบ</h3>
                <p class="mt-2 text-gray-600">คุณแน่ใจหรือไม่ว่าต้องการลบผู้ใช้ <strong x-text="userToDeleteName"></strong>? การกระทำนี้ไม่สามารถย้อนกลับได้</p>
            </div>
            <form :action="'admin_user_action.php'" method="POST" class="bg-gray-50 px-6 py-3 flex justify-end space-x-3 rounded-b-lg">
                <?php echo generate_csrf_token(); ?>
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="user_id" :value="userToDeleteId">
                <button type="button" @click="isDeleteModalOpen = false" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">ยกเลิก</button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">ยืนยันการลบ</button>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<?php
$stmt->close();
$conn->close();
require_once 'includes/footer.php'; 
?>

