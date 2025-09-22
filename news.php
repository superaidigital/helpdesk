<?php
// $page_title = "ข่าวสารและบทความ IT";
require_once 'includes/functions.php';
// ไม่ต้อง check_auth() เพราะเป็นหน้าสาธารณะ
require_once 'includes/header.php';

// Pagination
$items_per_page = 6;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// Search
$search_term = trim($_GET['search'] ?? '');

// Fetch articles
$articles_data = getPublishedArticles($conn, $current_page, $items_per_page, $search_term);
$articles = $articles_data['articles'];
$total_pages = $articles_data['total_pages'];
?>

<div class="max-w-7xl mx-auto">
    <div class="text-center mb-8">
        <h1 class="text-4xl font-bold text-gray-800">ข่าวสารและบทความ</h1>
        <p class="text-gray-500 mt-2">อัปเดตข่าวสาร, เคล็ดลับ และคู่มือการใช้งานต่างๆ จากฝ่าย IT</p>
    </div>

    <!-- Search Form -->
    <form method="GET" action="news.php" class="mb-8 max-w-lg mx-auto">
        <div class="relative">
            <input type="text" name="search" placeholder="ค้นหาบทความ..." value="<?php echo htmlspecialchars($search_term); ?>" class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <i class="fa-solid fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
        </div>
    </form>

    <?php if (empty($articles)): ?>
        <div class="text-center bg-white p-12 rounded-lg shadow-md">
            <i class="fa-solid fa-newspaper text-5xl text-gray-400"></i>
            <h3 class="mt-4 text-xl font-semibold text-gray-700">ไม่พบบทความ</h3>
            <p class="text-gray-500 mt-2">ยังไม่มีบทความที่เผยแพร่ในขณะนี้ หรือไม่พบผลลัพธ์ที่ตรงกับคำค้นหาของคุณ</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($articles as $article): ?>
                <div class="bg-white rounded-lg shadow-lg overflow-hidden transform hover:-translate-y-2 transition-transform duration-300 flex flex-col">
                    <a href="article_view.php?slug=<?php echo htmlspecialchars($article['slug']); ?>">
                        <img src="<?php echo htmlspecialchars($article['featured_image_url'] ?? 'https://placehold.co/600x400/E2E8F0/4A5568?text=IT+News'); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>" class="w-full h-56 object-cover">
                    </a>
                    <div class="p-6 flex flex-col flex-grow">
                        <p class="text-sm text-gray-500"><?php echo formatDate($article['published_at']); ?></p>
                        <h3 class="font-bold text-xl text-gray-800 mt-2 hover:text-indigo-600">
                            <a href="article_view.php?slug=<?php echo htmlspecialchars($article['slug']); ?>"><?php echo htmlspecialchars($article['title']); ?></a>
                        </h3>
                        <p class="text-gray-600 mt-2 text-sm flex-grow"><?php echo htmlspecialchars($article['excerpt']); ?></p>
                        <div class="mt-4 pt-4 border-t border-gray-100 flex items-center">
                             <img class="h-8 w-8 rounded-full object-cover mr-3" src="<?php echo htmlspecialchars(get_user_avatar($article['author_avatar'])); ?>" alt="Avatar">
                             <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($article['author_name']); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <div class="mt-8">
            <?php echo generate_pagination_links($total_pages, $current_page, 'news.php', ['search' => $search_term]); ?>
        </div>
    <?php endif; ?>
</div>

<?php
$conn->close();
require_once 'includes/footer.php';
?>
