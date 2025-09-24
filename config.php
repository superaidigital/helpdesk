<?php
// config.php
// ไฟล์สำหรับเก็บข้อมูลการตั้งค่าที่สำคัญของระบบ
// **สำคัญ: ห้ามอัปโหลดไฟล์นี้ขึ้นบน Git Repository ที่เป็นสาธารณะ**

// -- 1. Database Configuration --
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root'); // <-- เปลี่ยนเป็น username ของคุณ
define('DB_PASSWORD', '');     // <-- เปลี่ยนเป็น password ของคุณ
define('DB_NAME', 'helpdesk_db');

// -- 2. SMTP Email Configuration --
// ตั้งค่าสำหรับส่งอีเมลแจ้งเตือนผ่าน Gmail
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'your.email@gmail.com'); // <-- ใส่อีเมล Gmail ของคุณ
define('SMTP_PASS', 'your_app_password');     // <-- ใส่ App Password ที่สร้างจาก Google Account
define('SMTP_PORT', 465);
define('SMTP_FROM_EMAIL', 'your.email@gmail.com'); // อีเมลผู้ส่ง
define('SMTP_FROM_NAME', 'IT Helpdesk อบจ.ศรีสะเกษ'); // ชื่อผู้ส่ง

?>