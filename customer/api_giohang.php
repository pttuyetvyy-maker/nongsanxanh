<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

// Tắt hiển thị lỗi HTML rác ra output
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

function gui_json($success, $message = '', $extra = []) {
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $extra));
    exit;
}

// 1. Kiểm tra đăng nhập
if (empty($_SESSION['user_id'])) {
    gui_json(false, 'Vui lòng đăng nhập!', ['require_login' => true]);
}

$input  = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? '';

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/* =====================================================
   1. LẤY DANH SÁCH SẢN PHẨM TRONG GIỎ HÀNG
===================================================== */
if ($action === 'lay_gio_hang' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    $cart = $_SESSION['cart'];
    $items = [];
    $tong_tien = 0;

    if (!empty($cart)) {
        $ids = array_map('intval', array_keys($cart));
        $in_clause = implode(',', array_filter($ids));

        if (!empty($in_clause)) {
            $sql = "SELECT MaSP, TenSP, Gia, Donvitinh, Hinhanh FROM sanpham WHERE MaSP IN ($in_clause)";
            $res = $conn->query($sql);

            if ($res) {
                while ($sp = $res->fetch_assoc()) {
                    $id_sp    = (int)$sp['MaSP'];
                    $soluong  = (int)($cart[$id_sp] ?? 1);
                    $don_gia  = (float)$sp['Gia'];
                    $subtotal = $don_gia * $soluong;
                    $tong_tien += $subtotal;

                    $anh = trim((string)$sp['Hinhanh']);
                    $src = (preg_match('#^https?://#', $anh) === 1) 
                        ? $anh 
                        : '/' . trim(explode('/', trim($_SERVER['SCRIPT_NAME'], '/'))[0], '/') . '/themes/images/' . rawurlencode(basename($anh));

                    $items[] = [
                        'id'        => $id_sp,
                        'name'      => $sp['TenSP'],
                        'price'     => $don_gia,
                        'unit'      => $sp['Donvitinh'],
                        'image'     => $src,
                        'quantity'  => $soluong,
                        'subtotal'  => $subtotal
                    ];
                }
            }
        }
    }

    $tong_sp_dem = array_sum($_SESSION['cart']);

    gui_json(true, 'Thành công', [
        'items'      => $items,
        'cart_count' => $tong_sp_dem,
        'total'      => $tong_tien
    ]);
}

/* =====================================================
   2. THÊM SẢN PHẨM VÀO GIỎ HÀNG
===================================================== */
if ($action === 'them_gio_hang') {
    $id_sp   = (int)($input['id'] ?? 0);
    $soluong = max(1, (int)($input['soluong'] ?? 1));

    if ($id_sp <= 0) {
        gui_json(false, 'Sản phẩm không hợp lệ!');
    }

    // Kiểm tra sản phẩm tồn tại + còn hàng
    $stmt = $conn->prepare("SELECT Soluongton FROM sanpham WHERE MaSP = ? LIMIT 1");
    $stmt->bind_param("i", $id_sp);
    $stmt->execute();
    $sp = $stmt->get_result()->fetch_assoc();
    if (!$sp) {
        gui_json(false, 'Sản phẩm không tồn tại!');
    }

    $da_co = (int)($_SESSION['cart'][$id_sp] ?? 0);
    if ($da_co + $soluong > (int)$sp['Soluongton']) {
        gui_json(false, 'Số lượng vượt quá tồn kho (còn ' . (int)$sp['Soluongton'] . ')!');
    }

    $_SESSION['cart'][$id_sp] = $da_co + $soluong;

    gui_json(true, 'Đã thêm sản phẩm vào giỏ hàng!', [
        'cart_count' => array_sum($_SESSION['cart'])
    ]);
}

/* =====================================================
   2b. MUA NGAY (không đụng vào giỏ hàng)
   Lưu tạm 1 sản phẩm vào $_SESSION['buy_now'],
   trang orderdetail.php?mode=buynow sẽ đọc từ đây.
===================================================== */
if ($action === 'mua_ngay') {
    $id_sp   = (int)($input['id'] ?? 0);
    $soluong = max(1, (int)($input['soluong'] ?? 1));

    if ($id_sp <= 0) {
        gui_json(false, 'Sản phẩm không hợp lệ!');
    }

    $stmt = $conn->prepare("SELECT Soluongton FROM sanpham WHERE MaSP = ? LIMIT 1");
    $stmt->bind_param("i", $id_sp);
    $stmt->execute();
    $sp = $stmt->get_result()->fetch_assoc();
    if (!$sp) {
        gui_json(false, 'Sản phẩm không tồn tại!');
    }
    if ($soluong > (int)$sp['Soluongton']) {
        gui_json(false, 'Số lượng vượt quá tồn kho (còn ' . (int)$sp['Soluongton'] . ')!');
    }

    $_SESSION['buy_now'] = [$id_sp => $soluong];
    gui_json(true, 'OK');
}

/* =====================================================
   3. CẬP NHẬT SỐ LƯỢNG SẢN PHẨM
===================================================== */
if ($action === 'cap_nhat_so_luong') {
    $id_sp   = (int)($input['id'] ?? 0);
    $soluong = (int)($input['soluong'] ?? 1);

    if ($id_sp > 0) {
        if ($soluong <= 0) {
            unset($_SESSION['cart'][$id_sp]);
        } else {
            $_SESSION['cart'][$id_sp] = $soluong;
        }
    }

    gui_json(true, 'Cập nhật thành công!');
}

/* =====================================================
   4. XÓA SẢN PHẨM KHỎI GIỎ HÀNG
===================================================== */
if ($action === 'xoa_san_pham') {
    $id_sp = (int)($input['id'] ?? 0);
    if ($id_sp > 0 && isset($_SESSION['cart'][$id_sp])) {
        unset($_SESSION['cart'][$id_sp]);
    }

    gui_json(true, 'Xóa sản phẩm thành công!');
}

gui_json(false, 'Yêu cầu không hợp lệ!');