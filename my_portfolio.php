<?php
// $page_title = "ผลงานของฉัน";
require_once 'includes/functions.php';
check_auth(['it', 'admin']); // อนุญาตให้ IT และ Admin เข้าถึงหน้านี้
require_once 'includes/header.php';

// ดึง ID ของผู้ใช้ที่ล็อกอินอยู่ปัจจุบันจาก Session
$current_user_id = $_SESSION['user_id'];

// เรียกใช้ฟังก์ชันที่ดึงข้อมูล Portfolio เฉพาะของ user ID นี้เท่านั้น
$portfolio_items = getPortfolioByUserId($current_user_id, $conn);
?>

<div x-data="{ isDeleteModalOpen: false, itemToDeleteId: null, itemToDeleteTitle: '' }">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800"> </h1>
        <a href="portfolio_form.php" class="px-4 py-2 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 transition-colors">
            <i class="fa-solid fa-plus-circle mr-2"></i>เพิ่มผลงานใหม่
        </a>
    </div>

    <?php if (empty($portfolio_items)): ?>
        <div class="text-center bg-white p-12 rounded-lg shadow-md">
            <i class="fa-solid fa-folder-open text-5xl text-gray-400"></i>
            <h3 class="mt-4 text-xl font-semibold text-gray-700">ยังไม่มีผลงาน</h3>
            <p class="text-gray-500 mt-2">คลิก "เพิ่มผลงานใหม่" เพื่อเริ่มสร้าง Portfolio ของคุณ</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($portfolio_items as $item): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden transform hover:-translate-y-1 transition-transform duration-300">
                    <img src="<?php echo htmlspecialchars($item['main_image_url'] ?? 'https://placehold.co/600x400/E2E8F0/4A5568?text=Project'); ?>" alt="<?php echo htmlspecialchars($item['project_title']); ?>" class="w-full h-48 object-cover">
                    <div class="p-4">
                        <?php if ($item['project_category']): ?>
                            <span class="inline-block bg-indigo-100 text-indigo-800 text-xs font-semibold px-2.5 py-0.5 rounded-full mb-2"><?php echo htmlspecialchars($item['project_category']); ?></span>
                        <?php endif; ?>
                        <h3 class="font-bold text-lg text-gray-800"><?php echo htmlspecialchars($item['project_title']); ?></h3>
                        <p class="text-sm text-gray-500 mt-1">
                            <?php echo $item['start_date'] ? date('d M Y', strtotime($item['start_date'])) : ''; ?> - <?php echo $item['end_date'] ? date('d M Y', strtotime($item['end_date'])) : 'ปัจจุบัน'; ?>
                        </p>
                        <p class="text-gray-600 mt-2 text-sm h-16 overflow-hidden"><?php echo htmlspecialchars(substr($item['project_description'], 0, 100)); ?>...</p>
                        <div class="mt-4 flex justify-end space-x-2 border-t pt-3">
                            <a href="portfolio_form.php?id=<?php echo $item['id']; ?>" class="text-sm text-indigo-600 hover:text-indigo-800 font-semibold">แก้ไข</a>
                            <button @click="isDeleteModalOpen = true; itemToDeleteId = <?php echo $item['id']; ?>; itemToDeleteTitle = '<?php echo htmlspecialchars(addslashes($item['project_title'])); ?>';" class="text-sm text-red-600 hover:text-red-800 font-semibold">ลบ</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Delete Modal -->
     <div x-show="isDeleteModalOpen" @keydown.escape.window="isDeleteModalOpen = false" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50" x-cloak>
        <div class="bg-white rounded-lg shadow-2xl max-w-sm w-full" @click.away="isDeleteModalOpen = false">
            <div class="p-6 text-center">
                <i class="fa-solid fa-triangle-exclamation text-4xl text-red-500 mx-auto mb-4"></i>
                <h3 class="font-semibold text-lg text-gray-800">ยืนยันการลบผลงาน</h3>
                <p class="mt-2 text-gray-600">คุณแน่ใจหรือไม่ว่าต้องการลบโปรเจกต์ "<strong x-text="itemToDeleteTitle"></strong>"?</p>
            </div>
            <form action="portfolio_action.php" method="POST" class="bg-gray-50 px-6 py-3 flex justify-end space-x-3 rounded-b-lg">
                <?php echo generate_csrf_token(); ?>
                <input type="hidden" name="action" value="delete_portfolio">
                <input type="hidden" name="item_id" :value="itemToDeleteId">
                <button type="button" @click="isDeleteModalOpen = false" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">ยกเลิก</button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">ยืนยันการลบ</button>
            </form>
        </div>
    </div>
</div>

<?php
$conn->close();
require_once 'includes/footer.php';
?>

