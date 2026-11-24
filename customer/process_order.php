<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

// 1. Kiểm tra đăng nhập
if (empty($_SESSION['user_id']) && empty($_SESSION['MaND'])) {
    echo json_encode([
        'success' => false,
        'require_login' => true,
        'message' => 'Vui lòng đăng nhập để thực hiện thao tác này!'
    ]);
    exit();
}

$user_id = (int)($_SESSION['user_id'] ?? $_SESSION['MaND']);
$input   = json_decode(file_get_contents('php://input'), true);
$action  = $input['action'] ?? '';
$id_sp   = (int)($input['id'] ?? 0);
$soluong = max(1, (int)($input['soluong'] ?? 1));

if ($id_sp <= 0) {
    echo json_encode(['success' => false, 'message' => 'Sản phẩm không hợp lệ!']);
    exit();
}

// 2. Lấy thông tin sản phẩm từ CSDL
$stmt = $conn->prepare("SELECT MaSP, TenSP, Gia, SoLuongTon FROM sanpham WHERE MaSP = ? LIMIT 1");
$stmt->bind_param("i", $id_sp);
$stmt->execute();
$sp = $stmt->get_result()->fetch_assoc();

if (!$sp) {
    echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại!']);
    exit();
}

/* ====================================================
   TRƯỜNG HỢP 1: THÊM VÀO GIỎ HÀNG
==================================================== */
if ($action === 'them_gio_hang') {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    if (isset($_SESSION['cart'][$id_sp])) {
        $_SESSION['cart'][$id_sp] += $soluong;
    } else {
        $_SESSION['cart'][$id_sp] = $soluong;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Đã thêm sản phẩm vào giỏ hàng thành công!'
    ]);
    exit();
}

/* ====================================================
   TRƯỜNG HỢP 2: MUA NGAY (TẠO ĐƠN HÀNG VÀO DATABASE)
==================================================== */
if ($action === 'mua_ngay') {
    $tong_tien = $sp['Gia'] * $soluong;
    $phi_ship = 25000;
    $tong_thanhtoan = $tong_tien + $phi_ship;

    // Lấy thông tin người dùng từ bảng users để điền người nhận/SĐT
    $stmt_u = $conn->prepare("SELECT fullname, phone FROM users WHERE MaND = ? LIMIT 1");
    $stmt_u->bind_param("i", $user_id);
    $stmt_u->execute();
    $usr = $stmt_u->get_result()->fetch_assoc();
    
    $nguoi_nhan = $usr['fullname'] ?? 'Khách hàng';
    $sdt        = $usr['phone'] ?? '0900000000';
    $dia_chi    = 'Địa chỉ mặc định';
    $ma_pttt    = 1; // Mặc định 1: Thanh toán COD (Thỏa mãn khóa ngoại phuongthucthanhtoan)

    // Lưu vào bảng donhang (dùng đúng cấu trúc cột MaDonHang, MaND, MaPTTT...)
    $stmt_dh = $conn->prepare("INSERT INTO donhang (NgayDat, TongTien, TrangThai, DiaChiGiao, MaND, MaPTTT, SoDienThoai, NguoiNhan) VALUES (NOW(), ?, 'Đang xử lý', ?, ?, ?, ?, ?)");
    
    if ($stmt_dh) {
        $stmt_dh->bind_param("dsiiss", $tong_thanhtoan, $dia_chi, $user_id, $ma_pttt, $sdt, $nguoi_nhan);
        if ($stmt_dh->execute()) {
            $id_dh = $conn->insert_id; // Lấy ID vừa tạo AUTO_INCREMENT

            // Lưu vào bảng chitietdonhang
            $stmt_ct = $conn->prepare("INSERT INTO chitietdonhang (MaDonHang, MaSP, SoLuong, DonGia) VALUES (?, ?, ?, ?)");
            if ($stmt_ct) {
                $stmt_ct->bind_param("iiid", $id_dh, $id_sp, $soluong, $sp['Gia']);
                $stmt_ct->execute();
            }

            echo json_encode([
                'success' => true,
                'redirect_url' => 'orderdetail.php?id=' . $id_dh
            ]);
            exit();
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi tạo đơn hàng: ' . $conn->error]);
            exit();
        }
    }
}

echo json_encode(['success' => false, 'message' => 'Thao tác không hợp lệ!']);
exit();