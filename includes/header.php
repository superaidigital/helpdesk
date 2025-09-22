<?php
// includes/header.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (file_exists(__DIR__ . '/functions.php')) {
    require_once __DIR__ . '/functions.php';
}

$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;
$user_data = $user_id ? getUserById($user_id, $conn) : null;


// --- Menu Definitions ---
$menu_items = [];

$all_users_menu = [
    'news.php' => ['icon' => 'fa-newspaper', 'title' => 'ข่าวสาร IT']
];
$admin_menu = [
    'admin_dashboard.php' => ['icon' => 'fa-tachometer-alt', 'title' => 'แดชบอร์ด'],
    'it_dashboard.php' => ['icon' => 'fa-tasks', 'title' => 'รายการปัญหา'],
    'admin_articles.php' => ['icon' => 'fa-pen-to-square', 'title' => 'จัดการบทความ'],
    'admin_all_portfolios.php' => ['icon' => 'fa-folder-open', 'title' => 'ผลงานทั้งหมด'],
    'admin_users.php' => ['icon' => 'fa-users', 'title' => 'จัดการผู้ใช้'],
    'admin_reports.php' => ['icon' => 'fa-chart-line', 'title' => 'รายงาน'],
    'admin_system.php' => ['icon' => 'fa-cogs', 'title' => 'จัดการระบบ']
];
$it_menu = [
    'it_dashboard.php' => ['icon' => 'fa-tasks', 'title' => 'แดชบอร์ด'],
    'it_report.php' => ['icon' => 'fa-chart-pie', 'title' => 'รายงานของฉัน'],
    'my_portfolio.php' => ['icon' => 'fa-user-tie', 'title' => 'ผลงานของฉัน'],
    'admin_articles.php' => ['icon' => 'fa-pen-to-square', 'title' => 'จัดการบทความ'],
    'admin_kb.php' => ['icon' => 'fa-book', 'title' => 'ฐานความรู้']
];
$user_menu = [
    'user_dashboard.php' => ['icon' => 'fa-home', 'title' => 'แดชบอร์ด'],
    'public_form.php' => ['icon' => 'fa-bullhorn', 'title' => 'แจ้งปัญหาใหม่']
];

if ($role === 'admin') {
    $menu_items = array_merge($all_users_menu, $admin_menu);
} elseif ($role === 'it') {
    $menu_items = array_merge($all_users_menu, $it_menu);
} elseif ($role === 'user') {
    $menu_items = array_merge($all_users_menu, $user_menu);
}
?>
<!DOCTYPE html>
<html lang="th" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ระบบแจ้งปัญหาฯ' : 'ระบบแจ้งปัญหาและให้คำปรึกษา - อบจ.ศรีสะเกษ'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="h-full font-sans">
    <div x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false" class="flex h-full">
        <!-- Off-canvas menu for mobile -->
        <div x-show="sidebarOpen" class="fixed inset-0 flex z-40 md:hidden" x-ref="dialog" aria-modal="true">
            <div x-show="sidebarOpen" 
                 x-transition:enter="transition-opacity ease-linear duration-300" 
                 x-transition:enter-start="opacity-0" 
                 x-transition:enter-end="opacity-100" 
                 x-transition:leave="transition-opacity ease-linear duration-300" 
                 x-transition:leave-start="opacity-100" 
                 x-transition:leave-end="opacity-0" 
                 class="fixed inset-0 bg-gray-600 bg-opacity-75" @click="sidebarOpen = false" aria-hidden="true"></div>

            <div x-show="sidebarOpen" 
                 x-transition:enter="transition ease-in-out duration-300 transform" 
                 x-transition:enter-start="-translate-x-full" 
                 x-transition:enter-end="translate-x-0" 
                 x-transition:leave="transition ease-in-out duration-300 transform" 
                 x-transition:leave-start="translate-x-0" 
                 x-transition:leave-end="-translate-x-full" 
                 class="relative flex-1 flex flex-col max-w-xs w-full bg-indigo-700">
                <div class="absolute top-0 right-0 -mr-12 pt-2">
                    <button type="button" class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white" @click="sidebarOpen = false">
                        <span class="sr-only">Close sidebar</span>
                        <i class="fa-solid fa-xmark text-white"></i>
                    </button>
                </div>
                <!-- Sidebar content -->
                <?php include 'sidebar_content.php'; ?>
            </div>
            <div class="flex-shrink-0 w-14"></div>
        </div>

        <!-- Static sidebar for desktop -->
        <div class="hidden md:flex md:flex-shrink-0">
            <div class="flex flex-col w-64">
                <div class="flex flex-col h-0 flex-1 bg-indigo-700">
                    <?php include 'sidebar_content.php'; ?>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <div class="flex flex-col w-0 flex-1 overflow-hidden">
            <!-- Top bar -->
            <div class="relative z-10 flex-shrink-0 flex h-16 bg-white shadow">
                <button type="button" class="px-4 border-r border-gray-200 text-gray-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500 md:hidden" @click="sidebarOpen = true">
                    <span class="sr-only">Open sidebar</span>
                    <i class="fa-solid fa-bars-staggered"></i>
                </button>
                <div class="flex-1 px-4 flex justify-between">
                    <div class="flex-1 flex">
                        <!-- Search (optional) -->
                    </div>
                    <div class="ml-4 flex items-center md:ml-6">
                        <span class="text-gray-700 mr-3 font-medium text-sm hidden sm:block">สวัสดี, <?php echo htmlspecialchars($_SESSION['fullname']); ?></span>
                        <a href="profile.php" class="p-2 text-gray-400 rounded-full hover:bg-gray-100 hover:text-gray-500" title="แก้ไขโปรไฟล์">
                            <i class="fa-solid fa-cog"></i>
                        </a>
                        <a href="logout.php" class="p-2 text-gray-400 rounded-full hover:bg-gray-100 hover:text-red-500" title="ออกจากระบบ">
                            <i class="fa-solid fa-right-from-bracket"></i>
                        </a>
                    </div>
                </div>
            </div>

            <main class="flex-1 relative overflow-y-auto focus:outline-none">
                <div class="py-6">
                    <?php if (isset($page_title) && !in_array($current_page, ['article_view.php'])): ?>
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
                        <h1 class="text-2xl font-semibold text-gray-900"><?php echo htmlspecialchars($page_title); ?></h1>
                    </div>
                    <?php endif; ?>
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8 mt-4">
                        <?php display_flash_message(); ?>
                        <!-- CONTENT START -->

