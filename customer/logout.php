<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Xóa sạch Session
$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Tính đường dẫn gốc dự án để quay về index1.php
$rootDir = '/' . trim(explode('/', trim($_SERVER['SCRIPT_NAME'], '/'))[0], '/') . '/';

header("Location: " . $rootDir . "index1.php");
exit();
?>