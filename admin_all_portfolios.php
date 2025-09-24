<?php
// $page_title = "ผลงานทั้งหมด";
require_once 'includes/functions.php';
check_auth(['admin']); // เฉพาะ Admin เท่านั้นที่เข้าถึงได้
require_once 'includes/header.php';

$all_portfolio_items = getAllPortfolios($conn);
?>

<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">ผลงานทั้งหมดของเจ้าหน้าที่</h1>
    </div>

    <?php if (empty($all_portfolio_items)): ?>
        <div class="text-center bg-white p-12 rounded-lg shadow-md">
            <i class="fa-solid fa-folder-open text-5xl text-gray-400"></i>
            <h3 class="mt-4 text-xl font-semibold text-gray-700">ยังไม่มีผลงานในระบบ</h3>
            <p class="text-gray-500 mt-2">ยังไม่มีเจ้าหน้าที่คนใดเพิ่มผลงานเข้ามา</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($all_portfolio_items as $item): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <img src="<?php echo htmlspecialchars($item['main_image_url'] ?? 'https://placehold.co/600x400/E2E8F0/4A5568?text=Project'); ?>" alt="<?php echo htmlspecialchars($item['project_title']); ?>" class="w-full h-48 object-cover">
                    <div class="p-4">
                        <h3 class="font-bold text-lg text-gray-800"><?php echo htmlspecialchars($item['project_title']); ?></h3>
                        <div class="flex items-center mt-2">
                             <img class="h-8 w-8 rounded-full object-cover mr-3" src="<?php echo htmlspecialchars(get_user_avatar($item['author_avatar'])); ?>" alt="Avatar">
                             <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($item['author_name']); ?></span>
                        </div>
                         <p class="text-gray-600 mt-2 text-sm h-16 overflow-hidden"><?php echo htmlspecialchars(substr($item['project_description'], 0, 100)); ?>...</p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
$conn->close();
require_once 'includes/footer.php';
?>
