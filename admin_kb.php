<?php
$page_title = "จัดการฐานความรู้";
require_once 'includes/functions.php';
check_auth(['it', 'admin']);
require_once 'includes/header.php';

// --- Pagination & Search Logic ---
$items_per_page = 15;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

$search_term = trim($_GET['search'] ?? '');
$search_query = "%" . $search_term . "%";

$sql_conditions = "";
$params = [];
$types = "";

if (!empty($search_term)) {
    $sql_conditions = " WHERE (kb.question LIKE ? OR kb.answer LIKE ?)";
    $params = [$search_query, $search_query];
    $types = "ss";
}

// Get total count for pagination
$total_count_sql = "SELECT COUNT(kb.id) as total FROM knowledge_base kb" . $sql_conditions;
$stmt_count = $conn->prepare($total_count_sql);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_items = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_items / $items_per_page);
$stmt_count->close();

// Get paginated results
$sql = "SELECT kb.*, u.fullname as creator_name 
        FROM knowledge_base kb
        JOIN users u ON kb.created_by = u.id" . $sql_conditions . " 
        ORDER BY kb.created_at DESC LIMIT ? OFFSET ?";
array_push($params, $items_per_page, $offset);
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$kb_result = $stmt->get_result();

?>
<div x-data="{ isDeleteModalOpen: false, kbToDeleteId: null, kbToDeleteQuestion: '' }">
    <div class="bg-white rounded-lg shadow-md">
       <div class="p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b">
           <div class="flex-shrink-0">
                <h3 class="font-semibold text-lg">รายการในฐานความรู้</h3>
           </div>
            <div class="flex items-center space-x-2 w-full sm:w-auto">
                <form method="GET" action="admin_kb.php" class="flex-grow">
                    <div class="relative">
                        <input type="text" name="search" placeholder="ค้นหาหัวข้อหรือวิธีแก้..." value="<?php echo htmlspecialchars($search_term); ?>" class="w-full pl-8 pr-4 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                </form>
                <a href="admin_kb_form.php" class="px-4 py-2 bg-green-500 text-white rounded-md text-sm hover:bg-green-600 whitespace-nowrap">
                   <i class="fa-solid fa-plus-circle mr-2"></i>เพิ่มบทความใหม่
               </a>
           </div>
       </div>
       <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">หัวข้อปัญหา</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">หมวดหมู่</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">ผู้บันทึก</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if ($kb_result->num_rows > 0): ?>
                        <?php while($item = $kb_result->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['question']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($item['category']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($item['creator_name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="admin_kb_form.php?id=<?php echo $item['id']; ?>" class="text-indigo-600 hover:text-indigo-900">แก้ไข</a>
                                <button @click="isDeleteModalOpen = true; kbToDeleteId = <?php echo $item['id']; ?>; kbToDeleteQuestion = '<?php echo htmlspecialchars(addslashes($item['question'])); ?>';" class="text-red-600 hover:text-red-900 ml-4">ลบ</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-10 text-gray-500">ไม่พบข้อมูลในฐานความรู้</td>
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
       echo generate_pagination_links($total_pages, $current_page, 'admin_kb.php', $pagination_params); 
       ?>
    </div>

    <!-- Delete Confirmation Modal -->
    <div x-show="isDeleteModalOpen" @keydown.escape.window="isDeleteModalOpen = false" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50" x-cloak>
        <div class="bg-white rounded-lg shadow-2xl max-w-sm w-full" @click.away="isDeleteModalOpen = false">
            <div class="p-6 text-center">
                <i class="fa-solid fa-triangle-exclamation text-4xl text-red-500 mx-auto mb-4"></i>
                <h3 class="font-semibold text-lg text-gray-800">ยืนยันการลบ</h3>
                <p class="mt-2 text-gray-600">คุณแน่ใจหรือไม่ว่าต้องการลบบทความ "<strong x-text="kbToDeleteQuestion"></strong>"?</p>
            </div>
            <form :action="'admin_kb_action.php'" method="POST" class="bg-gray-50 px-6 py-3 flex justify-end space-x-3 rounded-b-lg">
                <?php echo generate_csrf_token(); ?>
                <input type="hidden" name="action" value="delete_kb">
                <input type="hidden" name="kb_id" :value="kbToDeleteId">
                <button type="button" @click="isDeleteModalOpen = false" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">ยกเลิก</button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">ยืนยันการลบ</button>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<?php
$conn->close();
require_once 'includes/footer.php'; 
?>

