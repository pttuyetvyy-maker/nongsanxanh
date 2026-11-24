<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname     = trim($_POST['fullname'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $phone        = trim($_POST['phone'] ?? '');
    $password     = trim($_POST['password'] ?? '');
    $account_type = $_POST['account_type'] ?? 'customer';

    if (empty($fullname) || empty($email) || empty($password)) {
        echo "<script>alert('Vui lòng điền đầy đủ thông tin bắt buộc!'); window.history.back();</script>";
        exit;
    }

    // 1. Kiểm tra Email đã tồn tại chưa
    $check = $conn->prepare("SELECT `email` FROM `users` WHERE `email` = ? LIMIT 1");
    $check->bind_param("s", $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo "<script>alert('Email này đã được sử dụng!'); window.history.back();</script>";
        exit;
    }
    $check->close();

    // 2. MÃ HÓA MAT KHẨU BẰNG SHA-256
    $hashed_password = hash('sha256', $password);

    // 3. THÊM VÀO CSDL (Dùng đúng 5 tham số "sssss" tương ứng 5 dấu ?)
    $stmt = $conn->prepare("INSERT INTO `users` (`fullname`, `email`, `phone`, `password`, `account_type`) VALUES (?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        die("Lỗi SQL: " . $conn->error);
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
        echo "<script>
            alert('Đăng ký tài khoản thành công! Vui lòng đăng nhập.');
            window.location.href = 'login.php';
        </script>";
        exit;
    } else {
        echo "<script>alert('Lỗi đăng ký: " . addslashes($conn->error) . "'); window.history.back();</script>";
        exit;
    }
}
?>