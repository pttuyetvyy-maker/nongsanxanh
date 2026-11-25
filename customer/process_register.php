<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname         = trim($_POST['fullname'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $phone            = trim($_POST['phone'] ?? '');
    $password         = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm-password'] ?? '');
    $account_type     = 'customer'; // Mặc định luôn là khách hàng

    if (empty($fullname) || empty($email) || empty($password)) {
        header('Location: register.php?error=' . urlencode('Vui lòng điền đầy đủ thông tin bắt buộc!'));
        exit;
    }

    if ($password !== $confirm_password) {
        header('Location: register.php?error=' . urlencode('Mật khẩu xác nhận không khớp!'));
        exit;
    }

    // 1. Kiểm tra Email đã tồn tại chưa
    $check = $conn->prepare("SELECT `email` FROM `users` WHERE `email` = ? LIMIT 1");
    $check->bind_param("s", $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $check->close();
        header('Location: register.php?error=' . urlencode('Email này đã được sử dụng!'));
        exit;
    }
    $check->close();

    // 2. MÃ HÓA MẬT KHẨU BẰNG SHA-256
    $hashed_password = hash('sha256', $password);

    // 3. THÊM VÀO CSDL VỚI QUYỀN MẶC ĐỊNH LÀ CUSTOMER
    $stmt = $conn->prepare("INSERT INTO `users` (`fullname`, `email`, `phone`, `password`, `account_type`) VALUES (?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        header('Location: register.php?error=' . urlencode('Lỗi hệ thống CSDL!'));
        exit;
    }

    $stmt->bind_param(
        "sssss",
        $fullname,
        $email,
        $phone,
        $hashed_password,
        $account_type
    );

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header('Location: register.php?success=1');
        exit;
    } else {
        $stmt->close();
        $conn->close();
        header('Location: register.php?error=' . urlencode('Lỗi đăng ký, vui lòng thử lại!'));
        exit;
    }
}
?>