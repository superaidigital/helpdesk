<?php
// register_process.php
session_start();
require_once 'includes/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and retrieve form data
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // --- Validation ---
    if (empty($fullname) || empty($email) || empty($password) || empty($confirm_password)) {
        header("Location: register.php?error=กรุณากรอกข้อมูลให้ครบทุกช่อง");
        exit();
    }

    if ($password !== $confirm_password) {
        header("Location: register.php?error=รหัสผ่านไม่ตรงกัน");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: register.php?error=รูปแบบอีเมลไม่ถูกต้อง");
        exit();
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        header("Location: register.php?error=มีผู้ใช้งานอีเมลนี้ในระบบแล้ว");
        $stmt->close();
        $conn->close();
        exit();
    }
    $stmt->close();

    // --- Insert new user ---
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    // The 'role' will default to 'user' as per the table definition

    $stmt = $conn->prepare("INSERT INTO users (fullname, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $fullname, $email, $hashed_password);

    if ($stmt->execute()) {
        header("Location: index.php?success=สมัครสมาชิกสำเร็จ กรุณาเข้าสู่ระบบ");
        exit();
    } else {
        header("Location: register.php?error=เกิดข้อผิดพลาดในการสมัครสมาชิก");
        exit();
    }

    $stmt->close();
    $conn->close();

} else {
    // Redirect if not a POST request
    header("Location: register.php");
    exit();
}
