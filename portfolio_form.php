<?php
require_once 'includes/functions.php';
check_auth(['it', 'admin']);

$item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_editing = $item_id > 0;
$item_data = [
    'project_title' => '', 'project_description' => '', 'project_category' => '',
    'start_date' => '', 'end_date' => '', 'technologies_used' => '',
    'project_url' => '', 'main_image_url' => '', 'user_id' => $_SESSION['user_id']
];

if ($is_editing) {
    $stmt = $conn->prepare("SELECT * FROM it_portfolio WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $item_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $item_data = $result->fetch_assoc();
    $stmt->close();
    if (!$item_data) {
        redirect_with_message('my_portfolio.php', 'error', 'ไม่พบผลงานที่ต้องการแก้ไข หรือคุณไม่มีสิทธิ์');
    }
}

$page_title = $is_editing ? "แก้ไขผลงาน" : "เพิ่มผลงานใหม่";
require_once 'includes/header.php';
?>

<div class="max-w-3xl mx-auto">
    <form action="portfolio_action.php" method="POST" enctype="multipart/form-data" class="bg-white p-8 rounded-xl shadow-md space-y-6">
        <?php echo generate_csrf_token(); ?>
        <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
        <input type="hidden" name="action" value="<?php echo $is_editing ? 'edit_portfolio' : 'add_portfolio'; ?>">

        <div>
            <label for="project_title" class="block text-sm font-medium text-gray-700">ชื่อโปรเจกต์ / ผลงาน</label>
            <input type="text" name="project_title" id="project_title" value="<?php echo htmlspecialchars($item_data['project_title']); ?>" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
        </div>

        <div>
            <label for="project_description" class="block text-sm font-medium text-gray-700">รายละเอียด</label>
            <textarea name="project_description" id="project_description" rows="5" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3"><?php echo htmlspecialchars($item_data['project_description']); ?></textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700">วันที่เริ่ม</label>
                <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($item_data['start_date']); ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
            </div>
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700">วันที่สิ้นสุด</label>
                <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($item_data['end_date']); ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
            </div>
        </div>

        <div>
            <label for="project_category" class="block text-sm font-medium text-gray-700">หมวดหมู่</label>
            <input type="text" name="project_category" id="project_category" value="<?php echo htmlspecialchars($item_data['project_category']); ?>" placeholder="เช่น Infrastructure, Software Development" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
        </div>

        <div>
            <label for="technologies_used" class="block text-sm font-medium text-gray-700">ทักษะ / เทคโนโลยีที่ใช้</label>
            <input type="text" name="technologies_used" id="technologies_used" value="<?php echo htmlspecialchars($item_data['technologies_used']); ?>" placeholder="คั่นด้วยเครื่องหมายจุลภาค (,) เช่น PHP, MySQL, Photoshop" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
        </div>
        
        <div>
            <label for="project_url" class="block text-sm font-medium text-gray-700">ลิงก์ที่เกี่ยวข้อง (ถ้ามี)</label>
            <input type="url" name="project_url" id="project_url" value="<?php echo htmlspecialchars($item_data['project_url']); ?>" placeholder="https://example.com" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
        </div>

        <div>
            <label for="main_image" class="block text-sm font-medium text-gray-700">รูปภาพหลัก</label>
            <input type="file" name="main_image" id="main_image" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
            <?php if ($is_editing && !empty($item_data['main_image_url'])): ?>
                <p class="text-xs text-gray-500 mt-2">รูปภาพปัจจุบัน: <a href="<?php echo htmlspecialchars($item_data['main_image_url']); ?>" target="_blank" class="text-indigo-600">ดูรูปภาพ</a> (การอัปโหลดรูปใหม่จะเขียนทับรูปเดิม)</p>
            <?php endif; ?>
        </div>

        <div class="flex justify-end space-x-3 pt-4 border-t">
            <a href="my_portfolio.php" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">ยกเลิก</a>
            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700">
                <?php echo $is_editing ? 'บันทึกการเปลี่ยนแปลง' : 'เพิ่มผลงาน'; ?>
            </button>
        </div>
    </form>
</div>

<?php
$conn->close();
require_once 'includes/footer.php';
?>
