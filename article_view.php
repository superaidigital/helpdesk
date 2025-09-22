<?php
require_once 'includes/functions.php';

$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    header("Location: news.php");
    exit();
}

$article = getArticleBySlug($conn, $slug);

if (!$article) {
    // Optionally create a 404 page
    redirect_with_message('news.php', 'error', 'ไม่พบบทความที่คุณต้องการ');
}

$page_title = $article['title'];
require_once 'includes/header.php';
?>

<div class="bg-white rounded-lg shadow-xl max-w-4xl mx-auto overflow-hidden">
    <img src="<?php echo htmlspecialchars($article['featured_image_url'] ?? 'https://placehold.co/1200x600/E2E8F0/4A5568?text=IT+News'); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>" class="w-full h-64 md:h-96 object-cover">
    
    <div class="p-6 md:p-10">
        <h1 class="text-3xl md:text-4xl font-extrabold text-gray-900"><?php echo htmlspecialchars($article['title']); ?></h1>
        
        <div class="flex items-center mt-4 text-sm text-gray-500">
            <img class="h-10 w-10 rounded-full object-cover mr-4" src="<?php echo htmlspecialchars(get_user_avatar($article['author_avatar'])); ?>" alt="Avatar">
            <div>
                <p class="font-semibold text-gray-700"><?php echo htmlspecialchars($article['author_name']); ?></p>
                <p>เผยแพร่เมื่อ: <?php echo formatDate($article['published_at']); ?></p>
            </div>
        </div>

        <article class="prose lg:prose-xl max-w-none mt-8 text-gray-800 leading-relaxed">
            <?php
            // --- SECURITY UPDATE: Prevent XSS ---
            // Allow a safe list of HTML tags for formatting.
            // For the best security, consider using a library like HTML Purifier in the future.
            $allowed_tags = '<p><b><i><u><strong><em><ul><ol><li><br><a><img><h2><h3><h4><h5><h6><blockquote>';
            echo strip_tags($article['content'], $allowed_tags);
            ?>
        </article>

        <?php if (!empty($article['tags'])): ?>
            <div class="mt-8 pt-6 border-t">
                <p class="text-sm font-semibold text-gray-600">Tags:</p>
                <div class="flex flex-wrap gap-2 mt-2">
                    <?php 
                        $tags = explode(',', $article['tags']);
                        foreach($tags as $tag):
                    ?>
                        <span class="bg-gray-200 text-gray-800 text-xs font-medium px-3 py-1 rounded-full"><?php echo htmlspecialchars(trim($tag)); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="text-center mt-8">
    <a href="news.php" class="text-indigo-600 hover:text-indigo-800 font-semibold"><i class="fa-solid fa-arrow-left mr-2"></i>กลับไปหน้ารวมบทความ</a>
</div>


<?php
$conn->close();
require_once 'includes/footer.php';
?>
