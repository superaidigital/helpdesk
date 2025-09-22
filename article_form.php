<?php
require_once 'includes/functions.php';
check_auth(['it', 'admin']);

$article_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_editing = $article_id > 0;
$article_data = [
    'title' => '', 'content' => '', 'excerpt' => '', 'tags' => '', 'status' => 'draft', 'featured_image_url' => ''
];

if ($is_editing) {
    // Only allow editing own articles for 'it' role, admin can edit any
    $sql = "SELECT * FROM articles WHERE id = ?";
    $params = [$article_id];
    $types = "i";
    if ($_SESSION['role'] === 'it') {
        $sql .= " AND author_id = ?";
        $params[] = $_SESSION['user_id'];
        $types .= "i";
    }
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $article_data = $result->fetch_assoc();
    $stmt->close();
    if (!$article_data) {
        redirect_with_message('admin_articles.php', 'error', 'ไม่พบบทความ หรือคุณไม่มีสิทธิ์แก้ไข');
    }
}

$page_title = $is_editing ? "แก้ไขบทความ" : "สร้างบทความใหม่";
require_once 'includes/header.php';
?>
<div class="max-w-4xl mx-auto">
    <form action="article_action.php" method="POST" enctype="multipart/form-data" class="bg-white p-8 rounded-xl shadow-md space-y-6">
        <?php echo generate_csrf_token(); ?>
        <input type="hidden" name="article_id" value="<?php echo $article_id; ?>">
        <input type="hidden" name="action" value="<?php echo $is_editing ? 'edit_article' : 'add_article'; ?>">

        <div>
            <label for="title" class="block text-sm font-medium text-gray-700">หัวข้อเรื่อง</label>
            <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($article_data['title']); ?>" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500">
        </div>

        <div>
            <label for="content" class="block text-sm font-medium text-gray-700">เนื้อหา</label>
            <textarea name="content" id="content" rows="15" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500"><?php echo htmlspecialchars($article_data['content']); ?></textarea>
            <p class="text-xs text-gray-500 mt-1">สามารถใช้โค้ด HTML พื้นฐานได้ เช่น &lt;b&gt;, &lt;i&gt;, &lt;ul&gt;, &lt;li&gt;, &lt;p&gt;</p>
        </div>

        <div>
            <label for="excerpt" class="block text-sm font-medium text-gray-700">เรื่องย่อ (สำหรับแสดงในหน้ารายการ)</label>
            <textarea name="excerpt" id="excerpt" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3"><?php echo htmlspecialchars($article_data['excerpt']); ?></textarea>
        </div>
        
        <div>
            <label for="tags" class="block text-sm font-medium text-gray-700">Tags</label>
            <input type="text" name="tags" id="tags" value="<?php echo htmlspecialchars($article_data['tags']); ?>" placeholder="คั่นด้วยจุลภาค (,) เช่น ประกาศ, คู่มือ" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
        </div>
        
        <div>
            <label for="featured_image" class="block text-sm font-medium text-gray-700">รูปภาพหน้าปก</label>
            <input type="file" name="featured_image" id="featured_image" accept="image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
            <?php if ($is_editing && !empty($article_data['featured_image_url'])): ?>
                <p class="text-xs text-gray-500 mt-2">รูปปัจจุบัน: <a href="<?php echo htmlspecialchars($article_data['featured_image_url']); ?>" target="_blank" class="text-indigo-600">ดูรูปภาพ</a> (การอัปโหลดใหม่จะแทนที่รูปเดิม)</p>
            <?php endif; ?>
        </div>
        
        <div>
            <label for="status" class="block text-sm font-medium text-gray-700">สถานะ</label>
            <select name="status" id="status" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                <option value="draft" <?php echo ($article_data['status'] === 'draft') ? 'selected' : ''; ?>>ฉบับร่าง</option>
                <option value="published" <?php echo ($article_data['status'] === 'published') ? 'selected' : ''; ?>>เผยแพร่</option>
                <option value="archived" <?php echo ($article_data['status'] === 'archived') ? 'selected' : ''; ?>>เก็บเข้าคลัง</option>
            </select>
        </div>

        <div class="flex justify-end space-x-3 pt-4 border-t">
            <a href="admin_articles.php" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">ยกเลิก</a>
            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700">
                <i class="fa-solid fa-save mr-2"></i><?php echo $is_editing ? 'บันทึกการเปลี่ยนแปลง' : 'บันทึกบทความ'; ?>
            </button>
        </div>
    </form>
</div>

<?php
$conn->close();
require_once 'includes/footer.php';
?>
