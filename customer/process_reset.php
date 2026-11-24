<?php
require_once __DIR__ . '/../config/db.php';

$token = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (empty($token) || empty($password) || empty($confirm_password)) {
    header("Location: reset_password.php?token=" . urlencode($token) . "&error=empty");
    exit();
}

if (strlen($password) < 6) {
    header("Location: reset_password.php?token=" . urlencode($token) . "&error=short");
    exit();
}

if ($password !== $confirm_password) {
    header("Location: reset_password.php?token=" . urlencode($token) . "&error=mismatch");
    exit();
}

// 1. Kiểm tra Token hợp lệ theo múi giờ MySQL
$stmt = $conn->prepare("SELECT `MaND` FROM `users` WHERE `reset_token` = ? AND `reset_expires` > NOW() LIMIT 1");
$stmt->bind_param("s", $token);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    die("Token không hợp lệ hoặc đã hết hạn!");
}

// 2. Mã hóa SHA-256
$hashed_password = hash('sha256', $password);

// 3. Cập nhật mật khẩu mới và xóa Token theo cột MaND
$update = $conn->prepare("UPDATE `users` SET `password` = ?, `reset_token` = NULL, `reset_expires` = NULL WHERE `MaND` = ?");
$update->bind_param("si", $hashed_password, $user['MaND']);

if ($update->execute()) {
    $stmt->close();
    $update->close();
    $conn->close();
    
    echo "<script>
        alert('Đổi mật khẩu thành công! Vui lòng đăng nhập lại.');
        window.location.href = 'login.php?reset=success';
    </script>";
    exit();
} else {
    die("Lỗi cập nhật CSDL: " . $conn->error);
}
?>