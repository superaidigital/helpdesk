<?php
// includes/db.php

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root'); // <-- เปลี่ยนเป็น username ของคุณ
define('DB_PASSWORD', ''); // <-- เปลี่ยนเป็น password ของคุณ
define('DB_NAME', 'helpdesk_db');

// Create a database connection
$conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Set character set to utf8mb4
if (!$conn->set_charset("utf8mb4")) {
    printf("Error loading character set utf8mb4: %s\n", $conn->error);
    exit();
}

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>
