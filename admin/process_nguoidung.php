<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

// Kiểm tra quyền Admin
if (empty($_SESSION['user_id']) || ($_SESSION['account_type'] ?? '') !== 'admin') {
    die("Bạn không có quyền thực hiện thao tác này!");
}

$action = $_POST['action'] ?? '';

// 1. XỬ LÝ THÊM NGƯỜI DÙNG MỚI
if ($action === 'add') {
    $fullname     = trim($_POST['fullname'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $phone        = trim($_POST['phone'] ?? '');
    $password_raw = trim($_POST['password'] ?? '');
    $account_type = trim($_POST['account_type'] ?? 'customer');
    $status       = trim($_POST['status'] ?? 'Hoạt động');

    if (empty($fullname) || empty($email) || empty($password_raw)) {
        header("Location: nguoidung.php?err=" . urlencode("Vui lòng nhập đầy đủ Họ tên, Email và Mật khẩu!"));
        exit();
    }

    // Mã hóa mật khẩu SHA-256
    $password = hash('sha256', $password_raw);

    // Kiểm tra xem email đã tồn tại chưa
    $check = $conn->prepare("SELECT 1 FROM users WHERE email = ? LIMIT 1");
    $check->bind_param("s", $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        header("Location: nguoidung.php?err=" . urlencode("Email này đã được sử dụng!"));
        exit();
    }

    // Câu lệnh SQL thêm vào MySQL
    $stmt = $conn->prepare("INSERT INTO users (fullname, email, phone, password, account_type, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $fullname, $email, $phone, $password, $account_type, $status);

    if ($stmt->execute()) {
        header("Location: nguoidung.php?msg=" . urlencode("Thêm người dùng thành công!"));
    } else {
        header("Location: nguoidung.php?err=" . urlencode("Lỗi CSDL: " . $conn->error));
    }
    exit();
}

// 2. XỬ LÝ XÓA NGƯỜI DÙNG
if ($action === 'delete') {
    $userId = (int)($_POST['id'] ?? 0);
    if ($userId > 0) {
        // Kiểm tra cột khóa chính là MaND hay id
        $colCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'MaND'")->num_rows > 0 ? 'MaND' : 'id';
        
        $stmt = $conn->prepare("DELETE FROM users WHERE {$colCheck} = ?");
        $stmt->bind_param("i", $userId);
        if ($stmt->execute()) {
            header("Location: nguoidung.php?msg=" . urlencode("Xóa người dùng thành công!"));
        } else {
            header("Location: nguoidung.php?err=" . urlencode("Không thể xóa người dùng này!"));
        }
    }
    exit();
}

header("Location: nguoidung.php");
exit();