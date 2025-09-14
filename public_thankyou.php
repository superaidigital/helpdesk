<?php
$page_title = "ส่งข้อมูลสำเร็จ";
$issue_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
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
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-10 rounded-lg shadow-xl text-center">
            <i class="fa-solid fa-check-circle text-6xl text-green-500"></i>
            <h1 class="text-2xl font-bold text-green-600 mt-4">ส่งข้อมูลสำเร็จ!</h1>
            <p class="text-gray-600 mt-2">เราได้รับเรื่องของท่านเรียบร้อยแล้ว ระบบจะแจ้งเตือนเจ้าหน้าที่ต่อไป</p>
            <?php if ($issue_id > 0): ?>
            <div class="mt-4 bg-gray-50 p-4 rounded-lg">
                <p>หมายเลขเรื่องของท่านคือ:</p>
                <p class="text-3xl font-bold text-indigo-600">#<?php echo $issue_id; ?></p>
                <p class="text-sm text-gray-500 mt-1">กรุณาจดหมายเลขนี้ไว้เพื่อใช้อ้างอิงและติดตามสถานะ</p>
            </div>
            <a href="track_issue.php?id=<?php echo $issue_id; ?>" class="mt-4 inline-block w-full px-6 py-3 bg-blue-500 text-white font-semibold rounded-md hover:bg-blue-600">
                <i class="fa-solid fa-search-location mr-2"></i>ติดตามสถานะเรื่องนี้
            </a>
            <?php endif; ?>
            <div class="mt-6">
                <a href="public_form.php" class="text-sm text-gray-600 hover:text-indigo-600">กลับสู่หน้าแจ้งปัญหา</a>
            </div>
        </div>
    </div>
</body>
</html>

