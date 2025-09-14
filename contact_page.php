<?php
$page_title = "ติดต่อเจ้าหน้าที่";
require_once 'includes/functions.php'; // ใช้ functions.php เพื่อดึงข้อมูล

// ดึงข้อมูลเจ้าหน้าที่ IT ทั้งหมด
$it_staff_result = $conn->query("SELECT * FROM users WHERE role = 'it' ORDER BY fullname");
$it_staff = [];
if ($it_staff_result) {
    while($row = $it_staff_result->fetch_assoc()) {
        $it_staff[] = $row;
    }
}

// หา ID ของเจ้าหน้าที่ที่งานเยอะที่สุด (อาจมีหลายคน)
$busiest_ids = getBusiestITStaffIds($conn);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - อบจ.ศรีสะเกษ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-gray-100 font-sans">
    <main class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">ติดต่อเจ้าหน้าที่ฝ่ายเทคโนโลยีสารสนเทศ</h1>
                <p class="mt-2 text-gray-600">ท่านสามารถติดต่อเจ้าหน้าที่ได้โดยตรงตามข้อมูลด้านล่าง</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if (!empty($it_staff)): ?>
                    <?php foreach ($it_staff as $staff): ?>
                    <div class="bg-white rounded-lg shadow-md p-6 text-center transform hover:-translate-y-1 transition-transform flex flex-col">
                        <div class="relative w-24 h-24 mx-auto">
                            <img class="w-24 h-24 rounded-full object-cover border-2 border-gray-200" src="<?php echo htmlspecialchars(get_user_avatar($staff['image_url'])); ?>" alt="Profile image of <?php echo htmlspecialchars($staff['fullname']); ?>">
                            <?php if (in_array($staff['id'], $busiest_ids)): ?>
                            <div class="absolute -top-2 -right-2 transform rotate-12">
                                <i class="fa-solid fa-crown text-3xl text-yellow-400" style="filter: drop-shadow(0 2px 2px rgba(0,0,0,0.3));"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                        <h3 class="mt-4 text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($staff['fullname']); ?></h3>
                        <p class="text-gray-500"><?php echo htmlspecialchars($staff['position']); ?></p>
                        <p class="text-sm text-gray-500 flex-grow"><?php echo htmlspecialchars($staff['department']); ?></p>
                        <div class="mt-4 pt-4 border-t space-y-2">
                            <p class="text-sm text-gray-600 truncate"><i class="fa-solid fa-envelope w-5 text-gray-400"></i> <?php echo htmlspecialchars($staff['email']); ?></p>
                            <div class="flex space-x-2 pt-2">
                                 <a href="tel:<?php echo htmlspecialchars($staff['phone']); ?>" class="flex-1 text-center px-3 py-2 bg-blue-500 text-white rounded-md text-sm btn hover:bg-blue-600 flex items-center justify-center">
                                    <i class="fa-solid fa-phone mr-2"></i> โทร
                                </a>
                                <a href="https://line.me/ti/p/~<?php echo htmlspecialchars($staff['line_id']); ?>" target="_blank" class="flex-1 text-center px-3 py-2 bg-green-500 text-white rounded-md text-sm btn hover:bg-green-600 flex items-center justify-center">
                                    <i class="fab fa-line mr-2"></i> แอดไลน์
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center text-gray-500 col-span-3">ไม่พบข้อมูลเจ้าหน้าที่ฝ่าย IT</p>
                <?php endif; ?>
            </div>
             <div class="text-center mt-8">
                 <a href="index.php" class="text-sm text-gray-600 hover:text-indigo-600">กลับหน้าแรก</a>
             </div>
        </div>
    </main>
</body>
</html>

