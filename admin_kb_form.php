<?php
require_once 'includes/functions.php';
check_auth(['it', 'admin']);

$kb_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_editing = $kb_id > 0;
$kb_data = [ 'question' => '', 'answer' => '', 'category' => '' ];

if ($is_editing) {
    $stmt = $conn->prepare("SELECT * FROM knowledge_base WHERE id = ?");
    $stmt->bind_param("i", $kb_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $kb_data = $result->fetch_assoc();
    $stmt->close();
    if (!$kb_data) {
        redirect_with_message('admin_kb.php', 'error', 'ไม่พบบทความที่ต้องการแก้ไข');
    }
}

// Fetch categories for dropdown
$categories_result = $conn->query("SELECT name FROM categories WHERE is_active = 1 ORDER BY name ASC");
// Fetch recent KB articles for reference card
$recent_kb_result = $conn->query("SELECT id, question FROM knowledge_base ORDER BY created_at DESC LIMIT 10");

$page_title = $is_editing ? "แก้ไขบทความ" : "เพิ่มบทความใหม่";
require_once 'includes/header.php';
?>

<div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
    <!-- Left Column: Form -->
    <div class="lg:col-span-3">
        <form action="admin_kb_action.php" method="POST" class="bg-white p-8 rounded-xl shadow-md space-y-6">
            <?php echo generate_csrf_token(); ?>
            <input type="hidden" name="kb_id" value="<?php echo $kb_id; ?>">
            <input type="hidden" name="action" value="<?php echo $is_editing ? 'edit_kb' : 'add_kb'; ?>">
            
            <div>
                <label for="question" class="block text-sm font-medium text-gray-700">หัวข้อปัญหา (Question)</label>
                <input type="text" name="question" id="question" value="<?php echo htmlspecialchars($kb_data['question']); ?>" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label for="category" class="block text-sm font-medium text-gray-700">หมวดหมู่</label>
                <select name="category" id="category" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <?php while($cat = $categories_result->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($cat['name']); ?>" <?php echo ($kb_data['category'] === $cat['name']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div>
                <label for="answer" class="block text-sm font-medium text-gray-700">วิธีแก้ไข (Answer)</label>
                <textarea name="answer" id="answer" rows="15" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-500"><?php echo htmlspecialchars($kb_data['answer']); ?></textarea>
            </div>

            <div class="flex justify-end space-x-3 pt-4 border-t">
                <a href="admin_kb.php" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">ยกเลิก</a>
                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700">
                    <?php echo $is_editing ? 'บันทึกการเปลี่ยนแปลง' : 'เพิ่มบทความ'; ?>
                </button>
            </div>
        </form>
    </div>

    <!-- Right Column: Reference Card -->
    <div class="lg:col-span-2">
        <div class="bg-white p-6 rounded-lg shadow-md sticky top-24">
            <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">รายการล่าสุดในฐานความรู้</h3>
            <ul class="mt-4 space-y-2 text-sm">
                <?php while($item = $recent_kb_result->fetch_assoc()): ?>
                <li>
                    <a href="admin_kb_form.php?id=<?php echo $item['id']; ?>" class="text-indigo-600 hover:underline truncate block">
                        <?php echo htmlspecialchars($item['question']); ?>
                    </a>
                </li>
                <?php endwhile; ?>
            </ul>
        </div>
    </div>
</div>

<?php 
$conn->close();
require_once 'includes/footer.php'; 
?>

