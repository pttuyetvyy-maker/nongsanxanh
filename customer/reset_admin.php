<?php

require_once __DIR__ . '/../config/db.php';

// Email tài khoản Admin
$email = "2531540047@gmail.com";

// Mật khẩu mới
$password = "admin123";

// Tạo mật khẩu băm BCrypt
$hashed_password = password_hash(
    $password,
    PASSWORD_BCRYPT
);

// Cập nhật mật khẩu trong database
$sql = "UPDATE users
        SET password = ?
        WHERE email = ?
        AND account_type = 'admin'";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Lỗi SQL: " . $conn->error);
}

$stmt->bind_param("ss", $hashed_password, $email);

if ($stmt->execute()) {

    // Đọc lại mật khẩu vừa lưu
    $check = $conn->prepare(
        "SELECT password FROM users WHERE email = ?"
    );

    $check->bind_param("s", $email);
    $check->execute();

    $result = $check->get_result();
    $user = $result->fetch_assoc();

    if (
        $user &&
        password_verify("admin123", $user['password'])
    ) {
        echo "THÀNH CÔNG!<br>";
        echo "Đã cập nhật mật khẩu Admin.<br>";
        echo "Email: " . htmlspecialchars($email) . "<br>";
        echo "Mật khẩu: admin123<br>";
        echo "Kiểm tra password_verify: TRUE";
    } else {
        echo "LỖI: Không xác thực được mật khẩu vừa lưu.";
    }

    $check->close();

} else {

    echo "Cập nhật thất bại: " . $stmt->error;

}

$stmt->close();
$conn->close();

?>