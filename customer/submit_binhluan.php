<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['user_id'])) {
    header('Location: my_order.php');
    exit();
}

$mand    = (int)$_SESSION['user_id'];
$madh    = (int)($_POST['madh'] ?? 0);
$sosao   = (int)($_POST['sosao'] ?? 5);
$noidung = trim($_POST['noidung'] ?? '');

if ($sosao < 1 || $sosao > 5) {
    $sosao = 5;
}

if ($madh <= 0 || $noidung === '') {
    header('Location: my_order.php?err=' . urlencode('Vui lòng nhập nội dung đánh giá!'));
    exit();
}

try {
    // 1. Kiểm tra đơn hàng thuộc về khách và đã giao thành công
    $checkOrder = $conn->prepare("
        SELECT MaDonHang FROM donhang 
        WHERE MaDonHang = ? AND MaND = ? AND TrangThai IN ('Đã giao', 'Đã hoàn thành')
    ");
    $checkOrder->bind_param('ii', $madh, $mand);
    $checkOrder->execute();
    if ($checkOrder->get_result()->num_rows === 0) {
        header('Location: my_order.php?err=' . urlencode('Chỉ đơn hàng đã giao mới được bình luận!'));
        exit();
    }

    // 2. Xử lý upload ảnh chụp thực tế (nếu khách có chọn ảnh)
    $tenFileAnh = null;
    if (!empty($_FILES['hinhanh']['name']) && $_FILES['hinhanh']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['hinhanh']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        
        if (!in_array($ext, $allowed, true)) {
            header('Location: my_order.php?err=' . urlencode('Chỉ chấp nhận file ảnh (JPG, PNG, WEBP)!'));
            exit();
        }
        if ($_FILES['hinhanh']['size'] > 5 * 1024 * 1024) {
            header('Location: my_order.php?err=' . urlencode('Dung lượng ảnh tối đa 5MB!'));
            exit();
        }

        $uploadDir = __DIR__ . '/../uploads/binhluan/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $tenFileAnh = 'rv_' . $madh . '_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['hinhanh']['tmp_name'], $uploadDir . $tenFileAnh);
    }

    // 3. Lấy danh sách sản phẩm trong đơn hàng
    $colCheck = $conn->query("SHOW COLUMNS FROM chitietdonhang LIKE 'MaDonHang'");
    $fkCol = ($colCheck->num_rows > 0) ? 'MaDonHang' : 'MaDH';

    $stmtSP = $conn->prepare("SELECT MaSP FROM chitietdonhang WHERE $fkCol = ?");
    $stmtSP->bind_param('i', $madh);
    $stmtSP->execute();
    $dsSP = $stmtSP->get_result()->fetch_all(MYSQLI_ASSOC);

    // 4. Lưu vào bảng binhluan
    $insert = $conn->prepare("
        INSERT INTO binhluan (MaND, MaSP, MaDH, SoSao, HinhAnh, NoiDung, NgayDanhGia, TrangThai) 
        VALUES (?, ?, ?, ?, ?, ?, NOW(), 1)
    ");

    if (!empty($dsSP)) {
        foreach ($dsSP as $sp) {
            $masp = (int)$sp['MaSP'];
            $insert->bind_param('iiiiss', $mand, $masp, $madh, $sosao, $tenFileAnh, $noidung);
            $insert->execute();
        }
    } else {
        $nullSp = null;
        $insert->bind_param('iiiiss', $mand, $nullSp, $madh, $sosao, $tenFileAnh, $noidung);
        $insert->execute();
    }

    header('Location: my_order.php?msg=' . urlencode('Cảm ơn bạn đã gửi đánh giá đơn hàng!'));
    exit();

} catch (Throwable $e) {
    error_log('submit_binhluan.php: ' . $e->getMessage());
    header('Location: my_order.php?err=' . urlencode('Lỗi hệ thống khi gửi bình luận.'));
    exit();
}