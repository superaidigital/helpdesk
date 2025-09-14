<?php
session_start();
// หากผู้ใช้ล็อกอินอยู่แล้ว ให้ redirect ไปยังหน้าหลัก
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - ระบบแจ้งปัญหาฯ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="font-sans">
    <div class="min-h-screen flex items-center justify-center bg-gray-100 p-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="w-full max-w-md p-8 space-y-6 bg-white rounded-2xl shadow-2xl">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-gray-800">สร้างบัญชีผู้ใช้งานใหม่</h2>
                <p class="text-gray-500 mt-2">สมัครสมาชิกเพื่อเข้าใช้งานระบบแจ้งปัญหา</p>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span><?php echo htmlspecialchars($_GET['error']); ?></span>
                </div>
            <?php endif; ?>

            <form action="register_process.php" method="POST" class="space-y-4">
                <div>
                    <label for="fullname" class="block text-sm font-medium text-gray-700">ชื่อ-สกุล</label>
                    <input id="fullname" name="fullname" type="text" required class="w-full px-4 py-3 mt-1 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">อีเมล</label>
                    <input id="email" name="email" type="email" required class="w-full px-4 py-3 mt-1 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">รหัสผ่าน</label>
                    <input id="password" name="password" type="password" required class="w-full px-4 py-3 mt-1 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                 <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700">ยืนยันรหัสผ่าน</label>
                    <input id="confirm_password" name="confirm_password" type="password" required class="w-full px-4 py-3 mt-1 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <button type="submit" class="w-full px-4 py-3 font-bold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        สมัครสมาชิก
                    </button>
                </div>
            </form>
            <div class="text-center">
                <p class="text-sm text-gray-600">
                    มีบัญชีอยู่แล้ว? <a href="index.php" class="font-medium text-indigo-600 hover:text-indigo-500">เข้าสู่ระบบที่นี่</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
