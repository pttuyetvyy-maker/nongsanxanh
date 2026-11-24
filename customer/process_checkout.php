<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để thanh toán!']);
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$input   = json_decode(file_get_contents('php://input'), true);

$method = trim($input['payment_method'] ?? 'COD');
$mode   = trim($input['mode'] ?? 'cart');

// Ép MaPTTT tồn tại trong bảng phuongthucthanhtoan (1: COD, 2: MOMO)
$mapttt = ($method === 'MOMO') ? 2 : 1;

$cart = ($mode === 'buynow') ? ($_SESSION['buy_now'] ?? []) : ($_SESSION['cart'] ?? []);

if (empty($cart)) {
    echo json_encode(['success' => false, 'message' => 'Giỏ hàng trống!']);
    exit();
}

// 1. Tính tổng tiền
$ids = array_map('intval', array_keys($cart));
$in_clause = implode(',', array_filter($ids));

$tam_tinh = 0;
$items_info = [];

if (!empty($in_clause)) {
    $res = $conn->query("SELECT MaSP, Gia FROM sanpham WHERE MaSP IN ($in_clause)");
    while ($row = $res->fetch_assoc()) {
        $sp_id = (int)$row['MaSP'];
        $sl = (int)($cart[$sp_id] ?? 1);
        $tam_tinh += $row['Gia'] * $sl;
        $items_info[] = ['id' => $sp_id, 'sl' => $sl, 'gia' => $row['Gia']];
    }
}

$tong_thanhtoan = max(0, $tam_tinh + 25000 - 10000);

// 2. Lấy thông tin người nhận
$stmt_u = $conn->prepare("SELECT fullname, phone FROM users WHERE MaND = ? LIMIT 1");
$stmt_u->bind_param("i", $user_id);
$stmt_u->execute();
$usr = $stmt_u->get_result()->fetch_assoc();

$nguoi_nhan = $usr['fullname'] ?? 'Khách hàng';
$sdt        = $usr['phone'] ?? '0901234567';
$dia_chi    = 'Thành phố Hồ Chí Minh, Việt Nam';

// 3. Thêm vào donhang
$stmt_dh = $conn->prepare("INSERT INTO donhang (NgayDat, NgayGiao, TongTien, TrangThai, DiaChiGiao, MaND, MaPTTT, SoDienThoai, NguoiNhan) VALUES (NOW(), NOW(), ?, 'Đang xử lý', ?, ?, ?, ?, ?)");

if ($stmt_dh) {
    $stmt_dh->bind_param("dsiiss", $tong_thanhtoan, $dia_chi, $user_id, $mapttt, $sdt, $nguoi_nhan);
    if ($stmt_dh->execute()) {
        $madh = $conn->insert_id;

        // 4. Thêm chitietdonhang
        $stmt_ct = $conn->prepare("INSERT INTO chitietdonhang (MaDonHang, MaSP, SoLuong, DonGia) VALUES (?, ?, ?, ?)");
        foreach ($items_info as $item) {
            $stmt_ct->bind_param("iiid", $madh, $item['id'], $item['sl'], $item['gia']);
            $stmt_ct->execute();
        }

        // Dọn sạch giỏ hàng
        if ($mode === 'buynow') {
            unset($_SESSION['buy_now']);
        } else {
            unset($_SESSION['cart']);
        }

        echo json_encode([
            'success' => true,
            'ma_dh'   => '#DH' . str_pad($madh, 4, '0', STR_PAD_LEFT)
        ]);
        exit();
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi CSDL: ' . $conn->error]);
        exit();
    }
}

echo json_encode(['success' => false, 'message' => 'Lỗi xử lý!']);