<?php
$page_title = "จัดการระบบ - หมวดหมู่";
require_once 'includes/functions.php';
check_auth(['admin']);
require_once 'includes/header.php';

// --- Search Logic ---
$search_term = trim($_GET['search'] ?? '');
$search_query = "%" . $search_term . "%";

$sql = "SELECT * FROM categories";
if (!empty($search_term)) {
    $sql .= " WHERE (name LIKE ? OR description LIKE ?)";
}
$sql .= " ORDER BY id ASC";

$stmt = $conn->prepare($sql);
if (!empty($search_term)) {
    $stmt->bind_param("ss", $search_query, $search_query);
}
$stmt->execute();
$categories_result = $stmt->get_result();
?>
<div x-data="{
    isEditing: false,
    isDeleteModalOpen: false,
    formTitle: 'เพิ่มหมวดหมู่ใหม่',
    formAction: 'add_category',
    categoryId: null,
    categoryName: '',
    categoryIcon: '',
    categoryDescription: '',
    deleteCategoryId: null,
    deleteCategoryName: '',
    resetForm() {
        this.isEditing = false;
        this.formTitle = 'เพิ่มหมวดหมู่ใหม่';
        this.formAction = 'add_category';
        this.categoryId = null;
        this.categoryName = '';
        this.categoryIcon = '';
        this.categoryDescription = '';
    }
}">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column: Add/Edit Form -->
        <div class="lg:col-span-1">
            <div class="bg-white p-6 rounded-lg shadow-md sticky top-24">
                <h3 class="text-lg font-semibold text-gray-800" x-text="formTitle"></h3>
                <form action="admin_system_action.php" method="POST" class="mt-4 space-y-4">
                    <?php echo generate_csrf_token(); ?>
                    <input type="hidden" name="action" :value="formAction">
                    <input type="hidden" name="category_id" :value="categoryId">
                    
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">ชื่อหมวดหมู่</label>
                        <input type="text" name="name" id="name" x-model="categoryName" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                     <div>
                        <label for="icon" class="block text-sm font-medium text-gray-700">คลาสไอคอน (Font Awesome)</label>
                        <input type="text" name="icon" id="icon" x-model="categoryIcon" placeholder="เช่น fa-desktop" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                     <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">คำอธิบาย</label>
                        <textarea name="description" id="description" x-model="categoryDescription" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700" :class="{'bg-green-600 hover:bg-green-700': isEditing}">
                            <i class="fa-solid" :class="isEditing ? 'fa-save' : 'fa-plus-circle'"></i>
                            <span x-text="isEditing ? 'บันทึกการเปลี่ยนแปลง' : 'เพิ่มหมวดหมู่'"></span>
                        </button>
                        <button type="button" x-show="isEditing" @click="resetForm()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">ยกเลิก</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Column: Category List -->
        <div class="lg:col-span-2">
             <div class="bg-white rounded-lg shadow-md">
                <div class="p-4 border-b">
                     <form method="GET" action="admin_system.php">
                        <div class="relative">
                            <input type="text" name="search" placeholder="ค้นหาหมวดหมู่..." value="<?php echo htmlspecialchars($search_term); ?>" class="w-full pl-8 pr-4 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        </div>
                    </form>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">หมวดหมู่</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">คำอธิบาย</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if ($categories_result->num_rows > 0): ?>
                                <?php while($cat = $categories_result->fetch_assoc()): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center text-sm">
                                            <i class="fa-solid <?php echo htmlspecialchars($cat['icon']); ?> w-6 text-center text-indigo-500 mr-3"></i>
                                            <span class="font-medium text-gray-900"><?php echo htmlspecialchars($cat['name']); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($cat['description']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button @click="isEditing = true; formTitle = 'แก้ไขหมวดหมู่'; formAction = 'edit_category'; categoryId = <?php echo $cat['id']; ?>; categoryName = '<?php echo htmlspecialchars(addslashes($cat['name'])); ?>'; categoryIcon = '<?php echo htmlspecialchars(addslashes($cat['icon'])); ?>'; categoryDescription = '<?php echo htmlspecialchars(addslashes($cat['description'])); ?>';" class="text-indigo-600 hover:text-indigo-900">แก้ไข</button>
                                        <button @click="isDeleteModalOpen = true; deleteCategoryId = <?php echo $cat['id']; ?>; deleteCategoryName = '<?php echo htmlspecialchars(addslashes($cat['name'])); ?>';" class="text-red-600 hover:text-red-900 ml-4">ลบ</button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center py-10 text-gray-500">ไม่พบข้อมูลหมวดหมู่ที่ตรงกับคำค้นหา</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
             </div>
        </div>
    </div>

     <!-- Delete Confirmation Modal -->
    <div x-show="isDeleteModalOpen" @keydown.escape.window="isDeleteModalOpen = false" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50" x-cloak>
        <div class="bg-white rounded-lg shadow-2xl max-w-sm w-full" @click.away="isDeleteModalOpen = false">
            <div class="p-6 text-center">
                <i class="fa-solid fa-triangle-exclamation text-4xl text-red-500 mx-auto mb-4"></i>
                <h3 class="font-semibold text-lg text-gray-800">ยืนยันการลบ</h3>
                <p class="mt-2 text-gray-600">คุณแน่ใจหรือไม่ว่าต้องการลบหมวดหมู่ <strong x-text="deleteCategoryName"></strong>? การกระทำนี้ไม่สามารถย้อนกลับได้</p>
            </div>
            <form :action="'admin_system_action.php'" method="POST" class="bg-gray-50 px-6 py-3 flex justify-end space-x-3 rounded-b-lg">
                <?php echo generate_csrf_token(); ?>
                <input type="hidden" name="action" value="delete_category">
                <input type="hidden" name="category_id" :value="deleteCategoryId">
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

