<?php
// includes/header.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Include the central functions file on every page that uses this header.
if (file_exists('includes/functions.php')) {
    require_once 'includes/functions.php';
}

// Get the current page filename for active menu styling
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="th" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ระบบแจ้งปัญหาฯ' : 'ระบบแจ้งปัญหาและให้คำปรึกษา - อบจ.ศรีสะเกษ'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100 font-sans h-full">
    <div id="app" class="flex flex-col min-h-full">
        <div id="app-container" class="flex-grow">
            <?php if (isset($_SESSION['user_id'])) : ?>
            <nav class="bg-white shadow-sm sticky top-0 z-10">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between h-16">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 font-bold text-xl text-indigo-600 flex items-center">
                                <i class="fa-solid fa-headset mr-2"></i>
                                IT HELP DESK
                            </div>
                            <div class="hidden md:block">
                                <div class="ml-10 flex items-baseline space-x-2">
                                    <?php 
                                        $role = $_SESSION['role'];
                                        $baseLinkClass = "px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200";
                                        $inactiveLinkClass = "text-gray-500 hover:bg-gray-100 hover:text-gray-900";
                                        $activeLinkClass = "text-indigo-700 bg-indigo-100 font-bold";
                                        
                                        if ($role === 'admin') {
                                            $new_user_count = get_new_user_count($conn);
                                            $admin_menu = [
                                                'admin_dashboard.php' => 'แดชบอร์ด',
                                                'admin_users.php' => 'จัดการผู้ใช้งาน',
                                                'reports' => [ // Submenu for reports
                                                    'title' => 'รายงาน',
                                                    'pages' => [
                                                        'admin_reports.php' => 'รายงานสรุปผล',
                                                        'admin_analytics.php' => 'รายงานวิเคราะห์ภาพรวม'
                                                    ]
                                                ],
                                                'admin_system.php' => 'จัดการระบบ',
                                                'admin_kb.php' => 'ฐานความรู้',
                                                'it_dashboard.php' => 'รายการปัญหาทั้งหมด'
                                            ];

                                            foreach($admin_menu as $key => $value) {
                                                if (is_array($value)) { // Handle dropdown
                                                    $report_pages = array_keys($value['pages']);
                                                    $is_active = in_array($current_page, $report_pages);
                                                    $class = $is_active ? $activeLinkClass : $inactiveLinkClass;
                                                    
                                                    echo "<div x-data='{ open: false }' @click.away='open = false' class='relative'>";
                                                    echo "<button @click='open = !open' class='$baseLinkClass $class flex items-center'>{$value['title']} <i class='fa-solid fa-chevron-down ml-2 text-xs'></i></button>";
                                                    echo "<div x-show='open' x-transition class='absolute mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-20'>";
                                                    echo "<div class='py-1'>";
                                                    foreach($value['pages'] as $url => $title) {
                                                        echo "<a href='$url' class='block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100'>$title</a>";
                                                    }
                                                    echo "</div></div></div>";
                                                } else {
                                                    $url = $key;
                                                    $title = $value;
                                                    $class = ($current_page === $url) ? $activeLinkClass : $inactiveLinkClass;
                                                    echo "<a href='$url' class='$baseLinkClass $class flex items-center'>";
                                                    echo $title;
                                                    if ($url === 'admin_users.php' && $new_user_count > 0) {
                                                        echo "<span class='ml-2 inline-block py-0.5 px-2 text-xs font-bold text-white bg-red-500 rounded-full'>$new_user_count</span>";
                                                    }
                                                    echo "</a>";
                                                }
                                            }
                                        } elseif ($role === 'it') {
                                            $it_menu = [
                                                'it_dashboard.php' => 'แดชบอร์ด',
                                                'it_report.php' => 'รายงานของฉัน',
                                                'admin_kb.php' => 'ฐานความรู้'
                                            ];
                                            foreach($it_menu as $url => $title) {
                                                 $class = ($current_page === $url) ? $activeLinkClass : $inactiveLinkClass;
                                                 echo "<a href='$url' class='$baseLinkClass $class'>$title</a>";
                                            }
                                        } elseif ($role === 'user') {
                                            $user_menu = [
                                                'user_dashboard.php' => 'แดชบอร์ด',
                                                'public_form.php' => 'แจ้งปัญหาใหม่'
                                            ];
                                             foreach($user_menu as $url => $title) {
                                                 $class = ($current_page === $url) ? $activeLinkClass : $inactiveLinkClass;
                                                 echo "<a href='$url' class='$baseLinkClass $class'>$title</a>";
                                            }
                                        }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <div class="hidden md:block">
                            <div class="ml-4 flex items-center md:ml-6">
                                <span class="text-gray-700 mr-3 font-medium">สวัสดี, <?php echo htmlspecialchars($_SESSION['fullname']); ?></span>
                                <a href="profile.php" class="text-gray-500 hover:text-indigo-600 p-2 rounded-full <?php echo ($current_page === 'profile.php') ? 'text-indigo-600 bg-indigo-50' : ''; ?>" title="แก้ไขโปรไฟล์">
                                    <i class="fa-solid fa-cog"></i>
                                </a>
                                <a href="logout.php" class="ml-3 px-3 py-2 rounded-md text-sm font-medium text-white bg-red-500 hover:bg-red-600" title="ออกจากระบบ">
                                    <i class="fa-solid fa-right-from-bracket"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>
            <?php endif; ?>

            <?php if (isset($page_title)): ?>
            <header class="bg-white">
                 <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <h1 class="text-3xl font-bold text-gray-900"><?php echo htmlspecialchars($page_title); ?></h1>
                </div>
            </header>
            <?php endif; ?>

            <main class="py-6">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <?php 
                    display_flash_message(); 
                ?>

