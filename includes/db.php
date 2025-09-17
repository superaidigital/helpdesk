<?php
// includes/db.php

// เรียกใช้ไฟล์ config จากโฟลเดอร์แม่ (../)
require_once __DIR__ . '/../config.php';

// Create a database connection using defined constants
$conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Set character set to utf8mb4
if (!$conn->set_charset("utf8mb4")) {
    // In a production environment, you might log this error instead of printing it
    error_log("Error loading character set utf8mb4: " . $conn->error);
    // For the user, show a generic error message
    die("เกิดข้อผิดพลาดในการเชื่อมต่อกับฐานข้อมูล");
}

// Check the connection
if ($conn->connect_error) {
    // Log the detailed error for developers
    error_log("Connection failed: " . $conn->connect_error);
    // Show a generic error to the user
    die("เกิดข้อผิดพลาดในการเชื่อมต่อกับฐานข้อมูล");
}

?>

