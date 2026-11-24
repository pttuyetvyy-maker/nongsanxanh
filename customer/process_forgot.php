<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
require_once __DIR__ . '/../config/db.php';

$email = trim($_POST['email'] ?? '');

if ($email === '') {
    header("Location: forgot_password.php?error=empty");
    exit();
}

// 1. Kiểm tra Email có tồn tại không
$stmt = $conn->prepare("SELECT `MaND` FROM `users` WHERE `email` = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    header("Location: forgot_password.php?error=not_found");
    exit();
}

// 2. Tạo Token và thời gian hết hạn (tính bằng NOW() + 1 HOUR của MySQL để không bao giờ bị lệch giờ)
$token = bin2hex(random_bytes(32));

// 3. Cập nhật Token vào Database
$update = $conn->prepare("UPDATE `users` SET `reset_token` = ?, `reset_expires` = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE `email` = ?");
$update->bind_param("ss", $token, $email);
$update->execute();

$stmt->close();
$update->close();
$conn->close();

// 4. Chuyển hướng sang trang nhập mật khẩu mới
header("Location: reset_password.php?token=" . urlencode($token));
exit();
?>