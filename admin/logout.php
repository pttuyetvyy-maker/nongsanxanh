<?php
// File xử lý Đăng xuất Admin (admin/logout.php)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Xóa sạch toàn bộ dữ liệu Session
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

// Chuyển hướng thẳng về Trang chủ (gốc dự án)
header("Location: ../index1.php");
exit;