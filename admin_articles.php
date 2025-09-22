<?php
// $page_title = "จัดการบทความ";
require_once 'includes/functions.php';
check_auth(['it', 'admin']);
require_once 'includes/header.php';

$articles = getAllArticles($conn); // Get all articles regardless of status
?>

<div x-data="{ isDeleteModalOpen: false, articleToDeleteId: null, articleToDeleteTitle: '' }">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">จัดการบทความและข่าวสาร</h1>
        <a href="article_form.php" class="px-4 py-2 bg-green-500 text-white font-semibold rounded-md hover:bg-green-600 transition-colors">
            <i class="fa-solid fa-plus-circle mr-2"></i>สร้างบทความใหม่
        </a>
    </div>
    
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">หัวข้อ</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">ผู้เขียน</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">สถานะ</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">วันที่สร้าง</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($articles)): ?>
                        <tr><td colspan="5" class="text-center py-10 text-gray-500">ยังไม่มีบทความในระบบ</td></tr>
                    <?php else: ?>
                        <?php foreach($articles as $article): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($article['title']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($article['author_name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <?php 
                                        $status_classes = [
                                            'published' => 'bg-green-100 text-green-800',
                                            'draft' => 'bg-yellow-100 text-yellow-800',
                                            'archived' => 'bg-gray-100 text-gray-800'
                                        ];
                                        $status_text = [
                                            'published' => 'เผยแพร่แล้ว',
                                            'draft' => 'ฉบับร่าง',
                                            'archived' => 'เก็บเข้าคลัง'
                                        ];
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_classes[$article['status']] ?? ''; ?>">
                                        <?php echo $status_text[$article['status']] ?? htmlspecialchars($article['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo formatDate($article['created_at']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="article_form.php?id=<?php echo $article['id']; ?>" class="text-indigo-600 hover:text-indigo-900">แก้ไข</a>
                                    <button @click="isDeleteModalOpen = true; articleToDeleteId = <?php echo $article['id']; ?>; articleToDeleteTitle = '<?php echo htmlspecialchars(addslashes($article['title'])); ?>';" class="text-red-600 hover:text-red-900 ml-4">ลบ</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Delete Modal -->
    <div x-show="isDeleteModalOpen" @keydown.escape.window="isDeleteModalOpen = false" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50" x-cloak>
        <div class="bg-white rounded-lg shadow-2xl max-w-sm w-full" @click.away="isDeleteModalOpen = false">
            <div class="p-6 text-center">
                <i class="fa-solid fa-triangle-exclamation text-4xl text-red-500 mx-auto mb-4"></i>
                <h3 class="font-semibold text-lg text-gray-800">ยืนยันการลบบทความ</h3>
                <p class="mt-2 text-gray-600">คุณแน่ใจหรือไม่ว่าต้องการลบบทความ "<strong x-text="articleToDeleteTitle"></strong>"?</p>
            </div>
            <form action="article_action.php" method="POST" class="bg-gray-50 px-6 py-3 flex justify-end space-x-3 rounded-b-lg">
                <?php echo generate_csrf_token(); ?>
                <input type="hidden" name="action" value="delete_article">
                <input type="hidden" name="article_id" :value="articleToDeleteId">
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
