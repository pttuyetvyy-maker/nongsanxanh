<?php
require_once __DIR__ . '/../config/db.php';

// Tính đường dẫn gốc hệ thống (/nongsanxanh/)
$rootDir = '/' . trim(explode('/', trim($_SERVER['SCRIPT_NAME'], '/'))[0], '/') . '/';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

/* Lấy sản phẩm trong bảng `sanpham` + tên loại */
$stmt =$conn->prepare(
    "SELECT s.MaSP, s.TenSP, s.Gia, s.Donvitinh, s.Soluongton, s.Hinhanh, s.Mota,
            s.maloaisanpham, l.tenloaisanpham
     FROM sanpham s
     LEFT JOIN loaisanpham l ON l.maloaisanpham = s.maloaisanpham
     WHERE s.MaSP = ?"
);
if (!$stmt) { 
    die("Lỗi SQL: " . $conn->error); 
}
$stmt->bind_param("i", $id);$stmt->execute();
$sp =$stmt->get_result()->fetch_assoc();

$sp_lienquan = null;
$ds_binhluan = [];$tong_danhgia = 0;
$diem_trungbinh = 0;

if ($sp) {
    /* 1. Sản phẩm cùng loại trong bảng `sanpham` (trừ sản phẩm đang xem) */
    $stmt =$conn->prepare(
        "SELECT MaSP, TenSP, Gia, Donvitinh, Hinhanh
         FROM sanpham
         WHERE maloaisanpham = ? AND MaSP <> ?
         ORDER BY MaSP DESC LIMIT 4"
    );
    $stmt->bind_param("ii", $sp['maloaisanpham'], $sp['MaSP']);$stmt->execute();
    $sp_lienquan =$stmt->get_result();

    /* 2. Lấy danh sách bình luận đang hiển thị (TrangThai = 1) của sản phẩm */
    $stmtBL =$conn->prepare(
        "SELECT b.MaBinhLuan, b.SoSao, b.HinhAnh, b.NoiDung, b.TraLoiAdmin, b.NgayDanhGia,
                COALESCE(NULLIF(u.fullname, ''), 'Khách hàng') AS TenNguoiDung
         FROM binhluan b
         LEFT JOIN users u ON b.MaND = u.MaND
         WHERE b.MaSP = ? AND (b.TrangThai = 1 OR b.TrangThai IS NULL)
         ORDER BY b.NgayDanhGia DESC, b.MaBinhLuan DESC"
    );
    if ($stmtBL) {$stmtBL->bind_param("i", $sp['MaSP']);$stmtBL->execute();
        $ds_binhluan =$stmtBL->get_result()->fetch_all(MYSQLI_ASSOC);

        $tong_danhgia = count($ds_binhluan);
        if ($tong_danhgia > 0) {$tong_sao = 0;
            foreach ($ds_binhluan as$bl) {
                $tong_sao += max(1, min(5, (int)$bl['SoSao']));
            }
            $diem_trungbinh = round($tong_sao / $tong_danhgia, 1);
        }
    }
}

/* Hàm lấy đường dẫn ảnh chuẩn với $rootDir */
function lay_anh($anh) {
    global $rootDir;
    $anh = trim((string)$anh);
    if (preg_match('#^https?://#', $anh) === 1) return$anh;
    
    if ($anh !== '') {
        $filename = basename($anh);
        if (file_exists(__DIR__ . '/../themes/images/' . $filename)) {
            return $rootDir . 'themes/images/' . rawurlencode($filename);
        } elseif (file_exists(__DIR__ . '/../themes/image/' . $filename)) {
            return $rootDir . 'themes/image/' . rawurlencode($filename);
        } elseif (file_exists(__DIR__ . '/images/' . $filename)) {
            return 'images/' . rawurlencode($filename);
        }
    }
    return $rootDir . 'themes/images/placeholder.jpg';
}

/* Hàm lấy ảnh đánh giá của khách */
function lay_anh_binhluan($anh) {
    global $rootDir;
    $anh = trim((string)$anh);
    if ($anh === '') return '';
    if (preg_match('#^https?://#', $anh) === 1) return$anh;

    $filename = basename($anh);$paths = [
        __DIR__ . '/../themes/images/reviews/' . $filename => $rootDir . 'themes/images/reviews/' . rawurlencode($filename),
        __DIR__ . '/../themes/images/' . $filename         => $rootDir . 'themes/images/' . rawurlencode($filename),
        __DIR__ . '/../themes/image/' . $filename          => $rootDir . 'themes/image/' . rawurlencode($filename),
        __DIR__ . '/images/' . $filename                   => 'images/' . rawurlencode($filename),
    ];
    foreach ($paths as $diskPath =>$webPath) {
        if (file_exists($diskPath)) return$webPath;
    }
    return $rootDir . 'themes/images/' . rawurlencode($filename);
}

$v_css = file_exists(__DIR__ . '/chitietmoi.css') ? filemtime(__DIR__ . '/chitietmoi.css') : 1;

/* Nạp giao diện HTML hiển thị */
require_once __DIR__ . '/chitietsanpham_view.php';
?>