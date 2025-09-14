<?php
// login_process.php
session_start();
require_once 'includes/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        header("Location: index.php?error=กรุณากรอกอีเมลและรหัสผ่าน");
        exit();
    }

    // ใช้ Prepared Statement เพื่อป้องกัน SQL Injection
    $stmt = $conn->prepare("SELECT id, fullname, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // ตรวจสอบรหัสผ่านที่เข้ารหัสไว้
        if (password_verify($password, $user['password'])) {
            // ถ้ารหัสผ่านถูกต้อง, สร้าง session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['role'] = $user['role'];

            // Redirect ไปยังหน้า Dashboard ตามสิทธิ์
            if ($user['role'] === 'admin') {
                header("Location: admin_dashboard.php");
            } else if ($user['role'] === 'it') {
                 header("Location: it_dashboard.php");
            } else if ($user['role'] === 'user') {
                 header("Location: user_dashboard.php");
            } else {
                 header("Location: index.php?error=Invalid role");
            }
            exit();

        } else {
            // รหัสผ่านไม่ถูกต้อง
            header("Location: index.php?error=อีเมลหรือรหัสผ่านไม่ถูกต้อง");
            exit();
        }
    } else {
        // ไม่พบผู้ใช้งาน
        header("Location: index.php?error=อีเมลหรือรหัสผ่านไม่ถูกต้อง");
        exit();
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: index.php");
    exit();
}

