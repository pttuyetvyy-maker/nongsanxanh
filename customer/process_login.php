<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

// Đường dẫn gốc hệ thống (/nongsanxanh/)
$rootDir = '/' . trim(explode('/', trim($_SERVER['SCRIPT_NAME'], '/'))[0], '/') . '/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? $_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        echo "<script>alert('Vui lòng nhập đầy đủ tên/email/SĐT và mật khẩu!'); window.history.back();</script>";
        exit;
    }

    // 1. Mã hóa SHA-256 mật khẩu nhập vào để so sánh
    $hashed_input = hash('sha256', $password);

    // 2. Truy vấn tìm theo: fullname OR email OR phone
    $stmt = $conn->prepare("SELECT * FROM `users` WHERE `fullname` = ? OR `email` = ? OR `phone` = ? LIMIT 1");
    
    if (!$stmt) {
        die("Lỗi SQL: " . $conn->error);
    }

    $stmt->bind_param("sss", $username, $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        // 3. So sánh mật khẩu SHA-256
        if ($user['password'] === $hashed_input) {
            
            $displayName = !empty($user['fullname']) ? $user['fullname'] : $user['email'];

            // Lưu Session
            $_SESSION['user_id']      = $user['MaND'] ?? $user['id'] ?? 0;
            $_SESSION['user_name']    = $displayName;
            $_SESSION['fullname']     = $displayName;
            $_SESSION['account_type'] = $user['account_type'];

            // 4. CHUYỂN HƯỚNG THEO PHÂN QUYỀN (account_type)
            if ($user['account_type'] === 'admin') {
                echo "<script>
                    alert('Đăng nhập thành công với quyền Admin!');
                    window.location.href = '" . $rootDir . "admin/dashboard.php'; 
                </script>";
            } else {
                echo "<script>
                    alert('Đăng nhập thành công!');
                    window.location.href = '" . $rootDir . "index1.php';
                </script>";
            }
            exit;
        }
    }

    echo "<script>alert('Tên đăng nhập hoặc mật khẩu không chính xác!'); window.history.back();</script>";
    exit;
} else {
    header("Location: " . $rootDir . "customer/login.php");
    exit;
}
?>