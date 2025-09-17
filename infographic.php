<?php
    // สร้าง URL สำหรับ QR Code ที่จะลิงก์ไปยังหน้าแจ้งปัญหา
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    // หา path ของ directory ปัจจุบันให้ถูกต้อง
    $path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    $form_url = $protocol . "://" . $host . $path . "/public_form.php";
    $qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . urlencode($form_url);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ขั้นตอนการใช้งานระบบ IT Helpdesk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.15); }
            100% { transform: scale(1); }
        }
        @keyframes gradient-animation {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        body { 
            font-family: 'Sarabun', sans-serif; 
            background: linear-gradient(-45deg, #667eea, #764ba2, #8ec5fc, #e0c3fc);
            background-size: 400% 400%;
            animation: gradient-animation 18s ease infinite;
        }

        .animated-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            opacity: 0;
            animation: fadeInUp 0.7s ease-out forwards;
        }
        .animated-card:hover {
            transform: translateY(-12px) scale(1.05);
            box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.25);
        }
        .animated-card:hover .icon-container {
            transform: scale(1.1);
        }
        .animated-card:hover .icon-container i {
             animation: pulse 1.2s ease-in-out;
        }
        .step-arrow {
            color: rgba(255, 255, 255, 0.6);
            text-shadow: 0 1px 3px rgba(0,0,0,0.2);
            opacity: 0;
            animation: fadeInUp 0.7s ease-out forwards;
        }
        .icon-container {
            transition: transform 0.3s ease;
        }
        .text-shadow {
             text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="container mx-auto px-4 py-16 sm:py-24">
        <div class="text-center mb-16" style="opacity: 0; animation: fadeInUp 0.7s ease-out forwards;">
            <h1 class="text-4xl md:text-5xl font-extrabold text-white text-shadow">ขั้นตอนการใช้งาน</h1>
            <h2 class="text-2xl md:text-3xl font-semibold text-indigo-200 mt-2 text-shadow">ระบบแจ้งปัญหาและให้คำปรึกษาด้าน IT</h2>
            <p class="mt-4 text-indigo-100 max-w-3xl mx-auto">เพียง 4 ขั้นตอนง่ายๆ ในการแจ้งปัญหาและติดตามสถานะการแก้ไขผ่านระบบของเรา</p>
        </div>

        <div class="flex flex-col md:flex-row items-center justify-center gap-8 md:gap-4">
            <!-- Step Cards -->
            <div class="animated-card bg-white/80 backdrop-blur-sm p-8 rounded-2xl shadow-lg border-t-4 border-indigo-500 text-center max-w-sm" style="animation-delay: 0.2s;"> <div class="icon-container bg-indigo-500 text-white rounded-full w-20 h-20 flex items-center justify-center mx-auto shadow-indigo-300/50 shadow-lg"><i class="fa-solid fa-bullhorn text-4xl"></i></div> <h3 class="text-2xl font-bold text-gray-800 mt-6">1. แจ้งปัญหา</h3> <p class="mt-2 text-gray-700">เลือกหมวดหมู่, กรอกรายละเอียดปัญหา และข้อมูลติดต่อของคุณผ่านหน้าเว็บ ระบบจะสร้างหมายเลข Ticket สำหรับติดตาม</p> </div>
            <div class="step-arrow text-4xl hidden md:block" style="animation-delay: 0.4s;"><i class="fa-solid fa-chevron-right"></i></div> <div class="step-arrow text-4xl md:hidden" style="animation-delay: 0.4s;"><i class="fa-solid fa-chevron-down"></i></div>
            <div class="animated-card bg-white/80 backdrop-blur-sm p-8 rounded-2xl shadow-lg border-t-4 border-blue-500 text-center max-w-sm" style="animation-delay: 0.6s;"> <div class="icon-container bg-blue-500 text-white rounded-full w-20 h-20 flex items-center justify-center mx-auto shadow-blue-300/50 shadow-lg"><i class="fa-solid fa-user-check text-4xl"></i></div> <h3 class="text-2xl font-bold text-gray-800 mt-6">2. เจ้าหน้าที่รับเรื่อง</h3> <p class="mt-2 text-gray-700">เจ้าหน้าที่ IT จะได้รับการแจ้งเตือนและเข้ามาตรวจสอบข้อมูล จากนั้นจะรับงานเพื่อเริ่มดำเนินการแก้ไข</p> </div>
            <div class="step-arrow text-4xl hidden md:block" style="animation-delay: 0.8s;"><i class="fa-solid fa-chevron-right"></i></div> <div class="step-arrow text-4xl md:hidden" style="animation-delay: 0.8s;"><i class="fa-solid fa-chevron-down"></i></div>
            <div class="animated-card bg-white/80 backdrop-blur-sm p-8 rounded-2xl shadow-lg border-t-4 border-amber-500 text-center max-w-sm" style="animation-delay: 1.0s;"> <div class="icon-container bg-amber-500 text-white rounded-full w-20 h-20 flex items-center justify-center mx-auto shadow-amber-300/50 shadow-lg"><i class="fa-solid fa-magnifying-glass-chart text-4xl"></i></div> <h3 class="text-2xl font-bold text-gray-800 mt-6">3. ติดตามสถานะ</h3> <p class="mt-2 text-gray-700">นำหมายเลข Ticket ที่ได้รับมาตรวจสอบความคืบหน้า และดูประวัติการดำเนินการของเจ้าหน้าที่ได้ตลอดเวลา</p> </div>
            <div class="step-arrow text-4xl hidden md:block" style="animation-delay: 1.2s;"><i class="fa-solid fa-chevron-right"></i></div> <div class="step-arrow text-4xl md:hidden" style="animation-delay: 1.2s;"><i class="fa-solid fa-chevron-down"></i></div>
            <div class="animated-card bg-white/80 backdrop-blur-sm p-8 rounded-2xl shadow-lg border-t-4 border-green-500 text-center max-w-sm" style="animation-delay: 1.4s;"> <div class="icon-container bg-green-500 text-white rounded-full w-20 h-20 flex items-center justify-center mx-auto shadow-green-300/50 shadow-lg"><i class="fa-solid fa-signature text-4xl"></i></div> <h3 class="text-2xl font-bold text-gray-800 mt-6">4. ปิดงานและประเมินผล</h3> <p class="mt-2 text-gray-700">เมื่อปัญหาได้รับการแก้ไขแล้ว ผู้ใช้บริการลงลายมือชื่อเพื่อยืนยันการปิดงาน และให้คะแนนความพึงพอใจ</p> </div>
        </div>
        
        <!-- Call to Action Section -->
        <div class="mt-24 animated-card bg-white/80 backdrop-blur-sm p-8 md:p-12 rounded-2xl shadow-lg border-t-4 border-indigo-500" style="animation-delay: 1.6s;">
             <div class="grid md:grid-cols-2 gap-8 items-center">
                <div class="text-center md:text-left">
                    <h2 class="text-3xl font-extrabold text-gray-800">พร้อมเริ่มใช้งานแล้วใช่ไหม?</h2>
                    <p class="mt-3 text-gray-600">
                        คุณสามารถแจ้งปัญหาหรือขอคำปรึกษาด้าน IT ได้ทันทีผ่านช่องทางที่สะดวกที่สุดสำหรับคุณ
                    </p>
                    <div class="mt-6 flex flex-col sm:flex-row gap-4 justify-center md:justify-start">
                        <a href="public_form.php" class="px-8 py-3 bg-indigo-600 text-white font-bold rounded-lg hover:bg-indigo-700 transition-transform hover:scale-105 shadow-lg">
                           <i class="fa-solid fa-paper-plane mr-2"></i> แจ้งปัญหาทันที
                        </a>
                        <a href="contact_page.php" class="px-8 py-3 bg-gray-200 text-gray-800 font-bold rounded-lg hover:bg-gray-300 transition-colors">
                            ติดต่อเจ้าหน้าที่
                        </a>
                    </div>
                </div>
                 <div class="text-center bg-gray-50 p-4 rounded-lg">
                     <h4 class="font-bold text-gray-700">หรือสแกนผ่านมือถือ</h4>
                     <p class="text-sm text-gray-500 mb-2">เพื่อเปิดหน้าฟอร์มแจ้งปัญหา</p>
                     <img src="<?php echo $qr_code_url; ?>" alt="QR Code to report an issue" class="mx-auto rounded-lg border p-1">
                </div>
            </div>
        </div>

        <!-- Back to Home Button -->
        <div class="text-center mt-16 animated-card" style="animation-delay: 1.8s;">
            <a href="index.php" class="inline-block px-6 py-3 bg-white/80 backdrop-blur-sm text-indigo-700 font-bold rounded-lg hover:bg-white transition-colors shadow-lg">
                <i class="fa-solid fa-home mr-2"></i> กลับสู่หน้าแรก
            </a>
        </div>

    </div>
</body>
</html>

