<?php
// includes/sidebar_content.php
// This file contains the shared sidebar HTML structure
// It's included by header.php to avoid code duplication
?>
<div class="flex-1 flex flex-col pt-5 pb-4 overflow-y-auto">
    <div class="flex items-center flex-shrink-0 px-4">
        <i class="fa-solid fa-headset text-3xl text-white"></i>
        <span class="ml-3 font-bold text-xl text-white">IT HELP DESK</span>
    </div>
    <nav class="mt-5 flex-1 px-2 space-y-1">
        <?php
        $baseLinkClass = "group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors duration-200";
        $inactiveLinkClass = "text-indigo-100 hover:bg-indigo-600";
        $activeLinkClass = "bg-indigo-800 text-white";

        foreach ($menu_items as $url => $item) {
            $isActive = ($current_page === $url) || ($url === 'news.php' && $current_page === 'article_view.php');
            $class = $isActive ? $activeLinkClass : $inactiveLinkClass;

            echo "<a href='$url' class='$baseLinkClass $class'>";
            echo "<i class='fa-solid " . $item['icon'] . " mr-3 flex-shrink-0 h-6 w-6 text-indigo-300'></i>";
            echo $item['title'];

            if ($url === 'admin_users.php' && $role === 'admin') {
                $new_user_count = get_new_user_count($conn);
                if ($new_user_count > 0) {
                    echo "<span class='ml-auto inline-block py-0.5 px-2 text-xs font-bold text-white bg-red-500 rounded-full'>$new_user_count</span>";
                }
            }
            echo "</a>";
        }
        ?>
    </nav>
</div>
<div class="flex-shrink-0 flex border-t border-indigo-800 p-4">
    <a href="profile.php" class="flex-shrink-0 w-full group block">
        <div class="flex items-center">
            <div>
                <img class="inline-block h-10 w-10 rounded-full object-cover" 
                     src="<?php echo htmlspecialchars(get_user_avatar($user_data['image_url'] ?? null)); ?>" alt="">
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-white"><?php echo htmlspecialchars($_SESSION['fullname']); ?></p>
                <p class="text-xs font-medium text-indigo-200 group-hover:text-white">ดูโปรไฟล์</p>
            </div>
        </div>
    </a>
</div>
