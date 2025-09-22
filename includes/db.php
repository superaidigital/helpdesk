<?php
// includes/db.php

// --- RECOMMENDED: Using Environment Variables ---

// 1. Install dotenv library:
// In your project root, run: composer require vlucas/phpdotenv

// 2. Load environment variables from .env file
// You need to place this line in a central starting point of your app, like a bootstrap.php or at the top of functions.php
// require_once __DIR__ . '/../vendor/autoload.php';
// $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
// $dotenv->load();

// --- Assume .env file is loaded ---

// Get credentials from environment variables with default fallbacks
$db_host = $_ENV['DB_HOST'] ?? 'localhost';
$db_username = $_ENV['DB_USERNAME'] ?? 'root';
$db_password = $_ENV['DB_PASSWORD'] ?? '';
$db_name = $_ENV['DB_NAME'] ?? 'helpdesk_db';

// Create a database connection
$conn = new mysqli($db_host, $db_username, $db_password, $db_name);

// Set character set to utf8mb4
if (!$conn->set_charset("utf8mb4")) {
    // In production, log this error instead of displaying it
    error_log("Error loading character set utf8mb4: " . $conn->error);
    // Provide a user-friendly error message
    die("เกิดข้อผิดพลาดในการเชื่อมต่อกับระบบฐานข้อมูล");
}

// Check the connection
if ($conn->connect_error) {
    // In production, log the detailed error
    error_log("Database connection failed: " . $conn->connect_error);
    // Show a generic, user-friendly error message. Do not expose connection details.
    die("ไม่สามารถเชื่อมต่อฐานข้อมูลได้ กรุณาติดต่อผู้ดูแลระบบ");
}

?>
