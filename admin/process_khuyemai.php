<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';

// Kiểm tra quyền Admin
if (empty($_SESSION['user_id']) || ($_SESSION['account_type'] ?? '') !== 'admin') {
    die("Bạn không có quyền thực hiện thao tác này!");
}

// Tự động kiểm tra và thêm cột TrangThai nếu CSDL chưa có
$checkCol = $conn->query("SHOW COLUMNS FROM khuyenmai LIKE 'TrangThai'");
if ($checkCol && $checkCol->num_rows == 0) {
    $conn->query("ALTER TABLE khuyenmai ADD COLUMN TrangThai TINYINT(1) DEFAULT 1");
}

$action = $_POST['action'] ?? '';   

// 1. THÊM HOẶC SỬA KHUYẾN MÃI
if ($action === 'add' || $action === 'edit') {
    $makm  = strtoupper(trim($_POST['makm'] ?? ''));
    $tenkm = trim($_POST['tenkm'] ?? '');
    $giam  = (int)($_POST['phantramgiam'] ?? 0);
    $from  = $_POST['ngaybatdau'] ?? '';
    $to    = $_POST['ngayketthuc'] ?? '';
    $tt    = (int)($_POST['trangthai'] ?? 1);

    if (empty($makm) || empty($tenkm) || $giam <= 0 || empty($from) || empty($to)) {
        header("Location: khuyenmai.php?err=" . urlencode("Vui lòng nhập đầy đủ thông tin khuyến mãi!"));
        exit();
    }

    if ($action === 'add') {
        // Kiểm tra trùng Mã KM
        $check = $conn->prepare("SELECT 1 FROM khuyenmai WHERE MaKM = ? LIMIT 1");
        $check->bind_param("s", $makm);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            header("Location: khuyenmai.php?err=" . urlencode("Mã khuyến mãi này đã tồn tại!"));
            exit();
        }

        $stmt = $conn->prepare("INSERT INTO khuyenmai (MaKM, TenKM, PhanTramGiam, NgayBatDau, NgayKetThuc, TrangThai) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssissi", $makm, $tenkm, $giam, $from, $to, $tt);
        $res = $stmt->execute();
        $msg = "Thêm mã khuyến mãi thành công!";
    } else {
        $stmt = $conn->prepare("UPDATE khuyenmai SET TenKM=?, PhanTramGiam=?, NgayBatDau=?, NgayKetThuc=?, TrangThai=? WHERE MaKM=?");
        $stmt->bind_param("sissis", $tenkm, $giam, $from, $to, $tt, $makm);
        $res = $stmt->execute();
        $msg = "Cập nhật khuyến mãi thành công!";
    }

    if ($res) {
        header("Location: khuyenmai.php?msg=" . urlencode($msg));
    } else {
        header("Location: khuyenmai.php?err=" . urlencode("Lỗi CSDL: " . $conn->error));
    }
    exit();
}

// 2. XÓA KHUYẾN MÃI
if ($action === 'delete') {
    $makm = trim($_POST['makm'] ?? '');
    if (!empty($makm)) {
        $stmt = $conn->prepare("DELETE FROM khuyenmai WHERE MaKM=?");
        $stmt->bind_param("s", $makm);
        if ($stmt->execute()) {
            header("Location: khuyenmai.php?msg=" . urlencode("Xóa mã khuyến mãi thành công!"));
        } else {
            header("Location: khuyenmai.php?err=" . urlencode("Không thể xóa mã khuyến mãi này!"));
        }
    }
    exit();
}

header("Location: khuyenmai.php");
exit();