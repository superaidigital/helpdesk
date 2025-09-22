<?php
$page_title = "ฐานความรู้ (Knowledge Base)";
require_once 'includes/functions.php';
// check_auth(['it', 'admin']); // Removed authentication check
require_once 'includes/header.php';

// รับค่าคำค้นหาจาก URL (GET request)
$search_term = trim($_GET['search'] ?? '');
$search_query = "%" . $search_term . "%";

// เตรียมคำสั่ง SQL เพื่อค้นหาข้อมูลจากตาราง knowledge_base
$sql = "SELECT kb.*, u.fullname as creator_name 
        FROM knowledge_base kb
        JOIN users u ON kb.created_by = u.id
        WHERE kb.question LIKE ? OR kb.answer LIKE ? 
        ORDER BY kb.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $search_query, $search_query);
$stmt->execute();
$kb_result = $stmt->get_result();

// Map for category icons
$category_icon_map = [
    'ฮาร์ดแวร์' => 'fa-desktop',
    'ซอฟต์แวร์' => 'fa-window-maximize',
    'ระบบเครือข่าย' => 'fa-wifi',
    'ระบบสารบรรณ/ERP' => 'fa-file-invoice',
    'อีเมล' => 'fa-envelope-open-text',
    'อื่นๆ' => 'fa-question-circle'
];
?>
<style>
    /* For smooth accordion transition */
    .accordion-content {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease-in-out;
    }
</style>

<div class="max-w-3xl mx-auto" x-data="{ activeAccordion: null }">
    <!-- Search Form -->
    <form method="GET" action="knowledge_base.php" class="mb-6 relative">
        <input type="text" name="search" placeholder="ค้นหาปัญหาที่พบบ่อย..." value="<?php echo htmlspecialchars($search_term); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg pl-10 focus:outline-none focus:ring-2 focus:ring-indigo-500">
        <i class="fa-solid fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
        <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 px-4 py-1.5 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700">ค้นหา</button>
    </form>

    <!-- Knowledge Base List -->
    <div id="kb-list" class="space-y-4">
        <?php if ($kb_result->num_rows > 0): ?>
            <?php while ($kb_item = $kb_result->fetch_assoc()): 
                $category_icon = $category_icon_map[$kb_item['category']] ?? 'fa-question-circle';
            ?>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div @click="activeAccordion = activeAccordion === <?php echo $kb_item['id']; ?> ? null : <?php echo $kb_item['id']; ?>" class="cursor-pointer flex justify-between items-center p-4 hover:bg-gray-50 transition-colors">
                    <div class="flex items-center gap-4">
                        <div class="bg-indigo-100 text-indigo-600 rounded-lg w-10 h-10 flex-shrink-0 flex items-center justify-center">
                            <i class="fa-solid <?php echo $category_icon; ?>"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($kb_item['question']); ?></h3>
                            <p class="text-xs text-gray-500 mt-1">
                                <span>บันทึกโดย: <?php echo htmlspecialchars($kb_item['creator_name']); ?></span>
                            </p>
                        </div>
                    </div>
                    <i class="fa-solid fa-chevron-down transition-transform text-gray-400" :class="activeAccordion === <?php echo $kb_item['id']; ?> && 'rotate-180'"></i>
                </div>
                <div class="accordion-content" x-ref="accordion_<?php echo $kb_item['id']; ?>" :style="activeAccordion === <?php echo $kb_item['id']; ?> ? { maxHeight: $refs.accordion_<?php echo $kb_item['id']; ?>.scrollHeight + 'px' } : {}">
                    <div class="px-4 pb-4 border-t pt-4 text-gray-700 leading-relaxed">
                        <?php echo nl2br(htmlspecialchars($kb_item['answer'])); ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="text-center text-gray-500 py-10 bg-white rounded-lg shadow-sm">
                <i class="fa-solid fa-book-open text-4xl text-gray-400"></i>
                <p class="mt-4 font-semibold">ไม่พบข้อมูลในฐานความรู้</p>
                <?php if (!empty($search_term)): ?>
                    <p class="text-sm">ไม่พบผลลัพธ์ที่ตรงกับคำค้นหา "<?php echo htmlspecialchars($search_term); ?>"</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="text-center mt-8">
        <a href="index.php" class="text-sm text-gray-600 hover:text-indigo-600">
            <i class="fa-solid fa-arrow-left mr-1"></i> กลับหน้าแรก
        </a>
    </div>

</div>
<!-- Add defer attribute to ensure Alpine.js runs after the DOM is fully parsed -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<?php 
$stmt->close();
$conn->close();
require_once 'includes/footer.php'; 
?>

