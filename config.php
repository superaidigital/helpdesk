<?php
// config.php
// **สำคัญ: ไฟล์นี้เป็นเพียงตัวอย่าง ไม่ควรเก็บข้อมูลจริงในนี้**
// แนะนำให้ใช้ Environment Variables (.env) ในการเก็บข้อมูลเหล่านี้

// --- 1. Database Configuration ---
// ค่าเหล่านี้ควรอ่านมาจาก Environment Variables
// define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
// define('DB_USERNAME', $_ENV['DB_USERNAME'] ?? 'root');
// define('DB_PASSWORD', $_ENV['DB_PASSWORD'] ?? '');
// define('DB_NAME', $_ENV['DB_NAME'] ?? 'helpdesk_db');

// --- สำหรับการทดสอบ (Fallback) ---
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'helpdesk_db');


// --- 2. SMTP Email Configuration ---
// แนะนำให้อ่านค่าเหล่านี้จาก Environment Variables เช่นกัน
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'your.email@gmail.com');
define('SMTP_PASS', 'your_app_password');
define('SMTP_PORT', 465);
define('SMTP_FROM_EMAIL', 'your.email@gmail.com');
define('SMTP_FROM_NAME', 'IT Helpdesk อบจ.ศรีสะเกษ');

?>
