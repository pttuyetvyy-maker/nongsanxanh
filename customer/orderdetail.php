<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

// XỬ LÝ POST AJAX LƯU ĐƠN HÀNG
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');

    if (empty($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để thanh toán!']);
        exit();
    }

    $user_id = (int)$_SESSION['user_id'];
    $input   = json_decode(file_get_contents('php://input'), true);
    $mode    = trim($input['mode'] ?? 'cart');
    
    // Tự động xử lý MaKM tương thích với bảng donhang hiện tại (dùng chuỗi rỗng thay vì NULL)
    $makm    = !empty($input['makm']) ? trim($input['makm']) : '';

    // Kiểm tra xem bảng khuyenmai có mã mặc định hoặc chấp nhận mã rỗng không
    $chk_km = $conn->query("SELECT MaKM FROM khuyenmai LIMIT 1");
    if ($chk_km && $row_km = $chk_km->fetch_assoc()) {
        // Nếu truyền rỗng bị vướng FK, lấy mã hợp lệ đầu tiên hoặc giữ chuỗi rỗng
        if ($makm === '') {
            $makm = $row_km['MaKM']; 
        }
    }

    $mapttt = 1; // Mặc định 1: Thanh toán COD
    $cart   = ($mode === 'buynow') ? ($_SESSION['buy_now'] ?? []) : ($_SESSION['cart'] ?? []);

    if (empty($cart)) {
        echo json_encode(['success' => false, 'message' => 'Giỏ hàng trống hoặc đã hết hạn!']);
        exit();
    }

    // 1. Tính tổng tiền từ CSDL
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

    // 2. Lấy thông tin người nhận từ bảng users
    $stmt_u = $conn->prepare("SELECT fullname, phone FROM users WHERE MaND = ? LIMIT 1");
    $stmt_u->bind_param("i", $user_id);
    $stmt_u->execute();
    $usr = $stmt_u->get_result()->fetch_assoc();

    $nguoi_nhan = $usr['fullname'] ?? $_SESSION['user_name'] ?? 'Khách hàng';
    $sdt        = $usr['phone'] ?? '0946378461';
    $dia_chi    = 'Thành phố Hồ Chí Minh, Việt Nam';

    // 3. Thêm đơn hàng vào bảng donhang
    $stmt_dh = $conn->prepare("INSERT INTO donhang (NgayDat, NgayGiao, TongTien, TrangThai, DiaChiGiao, MaND, MaPTTT, MaKM, SoDienThoai, NguoiNhan) VALUES (NOW(), NOW(), ?, 'Đang xử lý', ?, ?, ?, ?, ?, ?)");

    if ($stmt_dh) {
        $stmt_dh->bind_param("dsiisss", $tong_thanhtoan, $dia_chi, $user_id, $mapttt, $makm, $sdt, $nguoi_nhan);
        if ($stmt_dh->execute()) {
            $madh = $conn->insert_id;

            // 4. Thêm chi tiết đơn hàng vào bảng chitietdonhang
            $stmt_ct = $conn->prepare("INSERT INTO chitietdonhang (MaDonHang, MaSP, SoLuong, DonGia) VALUES (?, ?, ?, ?)");
            foreach ($items_info as $item) {
                $stmt_ct->bind_param("iiid", $madh, $item['id'], $item['sl'], $item['gia']);
                $stmt_ct->execute();
            }

            // Dọn sạch session giỏ hàng
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

    echo json_encode(['success' => false, 'message' => 'Lỗi khởi tạo câu lệnh SQL!']);
    exit();
}

// BẮT ĐẦU HIỂN THỊ GIAO DIỆN (GET)
require_once __DIR__ . '/header.php';

$rootDir = '/' . trim(explode('/', trim($_SERVER['SCRIPT_NAME'], '/'))[0], '/') . '/';

if (empty($_SESSION['user_id'])) {
    echo "<script>alert('Vui lòng đăng nhập để thanh toán!'); window.location.href='" . $rootDir . "customer/login.php';</script>";
    exit();
}

$buyNow = (($_GET['mode'] ?? '') === 'buynow');
$cart   = $buyNow ? ($_SESSION['buy_now'] ?? []) : ($_SESSION['cart'] ?? []);
if (empty($cart)) {
    echo "<script>alert('Không có sản phẩm nào để thanh toán!'); window.location.href='" . $rootDir . "customer/giohang.php';</script>";
    exit();
}

$ids = array_map('intval', array_keys($cart));
$in_clause = implode(',', array_filter($ids));

$ds_sanpham = [];
$tam_tinh = 0;

if (!empty($in_clause)) {
    $sql = "SELECT MaSP, TenSP, Gia, Donvitinh, Hinhanh FROM sanpham WHERE MaSP IN ($in_clause)";
    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $id_sp = (int)$row['MaSP'];
            $sl = (int)($cart[$id_sp] ?? 1);
            $thanh_tien = $row['Gia'] * $sl;
            $tam_tinh += $thanh_tien;
            
            $row['SoLuong'] = $sl;
            $row['ThanhTien'] = $thanh_tien;
            $ds_sanpham[] = $row;
        }
    }
}

$phi_ship = 25000;
$giam_gia = 10000;
$tong_tien = max(0, $tam_tinh + $phi_ship - $giam_gia);

$user_id = $_SESSION['user_id'];
$pk_user = ($rc = $conn->query("SHOW COLUMNS FROM users LIKE 'MaND'")) && $rc->num_rows > 0 ? 'MaND' : 'id';
$stmt_u  = $conn->prepare("SELECT fullname, email, phone FROM users WHERE `$pk_user` = ? LIMIT 1");
$stmt_u->bind_param("i", $user_id);
$stmt_u->execute();
$u_info  = $stmt_u->get_result()->fetch_assoc();

$fullname = $u_info['fullname'] ?? $_SESSION['user_name'] ?? 'Khách hàng';
$phone    = $u_info['phone'] ?? '0946378461';
?>

<link rel="stylesheet" href="<?= $rootDir ?>themes/order.css?v=<?= time() ?>">

<main class="container">

    <p class="breadcrumb">
        <a href="<?= $rootDir ?>index1.php">Trang chủ</a> / <a href="<?= $rootDir ?>customer/giohang.php">Giỏ hàng</a> / <span>Thanh toán</span>
    </p>

    <h1>Chi tiết đơn hàng</h1>
    <p class="subtitle">Kiểm tra đơn hàng và chọn phương thức thanh toán</p>

    <div class="order-layout">

        <!-- CỘT TRÁI -->
        <div class="order-main">

            <!-- TRẠNG THÁI ĐƠN HÀNG -->
            <section class="card">
                <div class="order-head">
                    <div>
                        <h2>Đơn hàng mới</h2>
                        <p>Ngày đặt: <?= date('d/m/Y') ?></p>
                    </div>
                    <span class="status pending">⏳ Chờ thanh toán</span>
                </div>

                <ol class="timeline">
                    <li class="done">
                        <span class="dot">✓</span>
                        <strong>Đã đặt hàng</strong>
                        <small><?= date('d/m - H:i') ?></small>
                    </li>
                    <li class="current">
                        <span class="dot">2</span>
                        <strong>Thanh toán</strong>
                        <small>Đang chờ bạn</small>
                    </li>
                    <li>
                        <span class="dot">3</span>
                        <strong>Đang giao</strong>
                        <small>Dự kiến ngày mai</small>
                    </li>
                    <li>
                        <span class="dot">4</span>
                        <strong>Đã giao</strong>
                        <small>Dự kiến 2 ngày tới</small>
                    </li>
                </ol>
            </section>

            <!-- DANH SÁCH SẢN PHẨM TRONG GIỎ -->
            <section class="card">
                <div class="card-title">
                    <h2>Sản phẩm đã chọn</h2>
                    <span><?= count($ds_sanpham) ?> sản phẩm</span>
                </div>

                <div class="item-header">
                    <span>Sản phẩm</span>
                    <span>Đơn giá</span>
                    <span>Số lượng</span>
                    <span>Thành tiền</span>
                </div>

                <?php foreach ($ds_sanpham as $item): ?>
                    <?php 
                        $anh = trim((string)$item['Hinhanh']);
                        $src = (preg_match('#^https?://#', $anh) === 1) 
                            ? $anh 
                            : $rootDir . 'themes/images/' . rawurlencode(basename($anh));
                    ?>
                    <div class="item">
                        <div class="item-info">
                            <img src="<?= htmlspecialchars($src) ?>" alt="<?= htmlspecialchars($item['TenSP']) ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;" onerror="this.src='<?= $rootDir ?>themes/images/placeholder.jpg'">
                            <div>
                                <h3><?= htmlspecialchars($item['TenSP']) ?></h3>
                                <p><?= htmlspecialchars($item['Donvitinh'] ?? 'kg') ?></p>
                            </div>
                        </div>
                        <div class="price"><?= number_format($item['Gia'], 0, ',', '.') ?>đ</div>
                        <div class="qty">x <?= $item['SoLuong'] ?></div>
                        <div class="total"><?= number_format($item['ThanhTien'], 0, ',', '.') ?>đ</div>
                    </div>
                <?php endforeach; ?>
            </section>

            <!-- PHƯƠNG THỨC THANH TOÁN -->
            <section class="card">
                <h2>Phương thức thanh toán</h2>
                <div class="pay-list">
                    <div class="pay-box selected" style="cursor: default;">
                        <span class="pay-icon">💵</span>
                        <span class="pay-text">
                            <strong>Thanh toán khi nhận hàng (COD)</strong>
                            <small>Trả tiền mặt cho shipper khi nhận hàng</small>
                        </span>
                        <span class="pay-check"></span>
                    </div>
                </div>
            </section>

        </div>

        <!-- CỘT PHẢI -->
        <aside class="order-side">

            <!-- THÔNG TIN GIAO HÀNG -->
            <section class="card">
                <h2>Thông tin giao hàng</h2>
                <div class="info-row">
                    <span>Người nhận</span>
                    <strong><?= htmlspecialchars($fullname) ?></strong>
                </div>
                <div class="info-row">
                    <span>Điện thoại</span>
                    <strong><?= htmlspecialchars($phone) ?></strong>
                </div>
                <div class="info-row">
                    <span>Địa chỉ</span>
                    <strong>Thành phố Hồ Chí Minh, Việt Nam</strong>
                </div>
            </section>

            <!-- TÓM TẮT THANH TOÁN -->
            <section class="card">
                <h2>Tóm tắt thanh toán</h2>
                <div class="sum-row">
                    <span>Tạm tính</span>
                    <strong><?= number_format($tam_tinh, 0, ',', '.') ?>đ</strong>
                </div>
                <div class="sum-row">
                    <span>Phí vận chuyển</span>
                    <strong><?= number_format($phi_ship, 0, ',', '.') ?>đ</strong>
                </div>
                <div class="sum-row discount-text">
                    <span>Giảm giá</span>
                    <strong>-<?= number_format($giam_gia, 0, ',', '.') ?>đ</strong>
                </div>

                <hr>

                <div class="sum-total">
                    <span>Tổng tiền</span>
                    <strong><?= number_format($tong_tien, 0, ',', '.') ?>đ</strong>
                </div>

                <button class="btn-primary" onclick="xacNhanDatHang()">Xác nhận thanh toán →</button>
                <button class="btn-outline" onclick="window.location.href='<?= $rootDir ?><?= $buyNow ? 'index1.php' : 'customer/giohang.php' ?>'">Hủy đơn hàng</button>

                <p class="secure">🔒 Thanh toán an toàn và bảo mật</p>
            </section>

        </aside>

    </div>

</main>

<script>
function xacNhanDatHang() {
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            mode: '<?= $buyNow ? 'buynow' : 'cart' ?>'
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Đặt hàng thành công! Mã đơn: ' + data.ma_dh);
            window.location.href = '<?= $rootDir ?>index1.php';
        } else {
            alert('Lỗi: ' + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Có lỗi kết nối máy chủ!');
    });
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>