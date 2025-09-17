<?php
// includes/db.php

// !!! ATTENTION !!!
// PLEASE CHECK YOUR DATABASE CREDENTIALS HERE.
// THIS IS THE MOST COMMON CAUSE OF "CANNOT CONNECT TO SERVER" ERRORS.
// Make sure the database server is running and the credentials are correct.

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root'); // <-- Double-check your username
define('DB_PASSWORD', ''); // <-- Double-check your password
define('DB_NAME', 'helpdesk_db'); // <-- Double-check your database name

// Create a database connection
$conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Set character set to utf8mb4
if (!$conn->set_charset("utf8mb4")) {
    printf("Error loading character set utf8mb4: %s\n", $conn->error);
    exit();
}

// Check the connection
if ($conn->connect_error) {
    // This will stop script execution and show an error.
    // The new JavaScript code in issue_view.php will display this error message.
    die("Connection failed: " . $conn->connect_error);
}

?>
