<?php
require_once __DIR__ . '/../config/db.php';

// Tính đường dẫn gốc hệ thống (/nongsanxanh/)
$rootDir = '/' . trim(explode('/', trim($_SERVER['SCRIPT_NAME'], '/'))[0], '/') . '/';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

/* Lấy sản phẩm trong bảng `sanpham` + tên loại */
$stmt = $conn->prepare(
    "SELECT s.MaSP, s.TenSP, s.Gia, s.Donvitinh, s.Soluongton, s.Hinhanh, s.Mota,
            s.maloaisanpham, l.tenloaisanpham
     FROM sanpham s
     LEFT JOIN loaisanpham l ON l.maloaisanpham = s.maloaisanpham
     WHERE s.MaSP = ?"
);
if (!$stmt) { 
    die("Lỗi SQL: " . $conn->error); 
}
$stmt->bind_param("i", $id);
$stmt->execute();
$sp = $stmt->get_result()->fetch_assoc();

$sp_lienquan = null;
if ($sp) {
    /* Sản phẩm cùng loại trong bảng `sanpham` (trừ sản phẩm đang xem) */
    $stmt = $conn->prepare(
        "SELECT MaSP, TenSP, Gia, Donvitinh, Hinhanh
         FROM sanpham
         WHERE maloaisanpham = ? AND MaSP <> ?
         ORDER BY MaSP DESC LIMIT 4"
    );
    $stmt->bind_param("ii", $sp['maloaisanpham'], $sp['MaSP']);
    $stmt->execute();
    $sp_lienquan = $stmt->get_result();
}

/* Hàm lấy đường dẫn ảnh chuẩn với $rootDir */
function lay_anh($anh) {
    global $rootDir;
    $anh = trim((string)$anh);
    if (preg_match('#^https?://#', $anh) === 1) return $anh;
    
    if ($anh !== '') {
        $filename = basename($anh);
        // Kiểm tra xem ảnh nằm trong themes/images hay thư mục local
        if (file_exists(__DIR__ . '/../themes/images/' . $filename)) {
            return $rootDir . 'themes/images/' . rawurlencode($filename);
        } elseif (file_exists(__DIR__ . '/images/' . $filename)) {
            return 'images/' . rawurlencode($filename);
        }
    }
    return $rootDir . 'themes/images/placeholder.jpg';
}

$v_css = file_exists(__DIR__ . '/chitietmoi.css') ? filemtime(__DIR__ . '/chitietmoi.css') : 1;

/* Nạp giao diện HTML hiển thị */
require_once __DIR__ . '/chitietsanpham_view.php';
?>