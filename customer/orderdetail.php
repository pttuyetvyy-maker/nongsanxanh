<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';
date_default_timezone_set('Asia/Ho_Chi_Minh');

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
    $makm    = !empty($input['makm']) ? trim($input['makm']) : '';

    // Kiểm tra mã khuyến mãi mặc định nếu vướng FK
    $chk_km = $conn->query("SELECT MaKM FROM khuyenmai LIMIT 1");
    if ($chk_km && $row_km = $chk_km->fetch_assoc()) {
        if ($makm === '') {
            $makm = $row_km['MaKM'];
        }
    }

    // Xử lý gói giao hàng & phí vận chuyển
    $deliveryMode = trim($input['delivery_mode'] ?? 'now'); // 'now' | 'schedule'
    $deliveryTier = trim($input['delivery_tier'] ?? 'standard'); // 'priority' | 'standard' | 'saver'
    $scheduleDate = trim($input['schedule_date'] ?? '');
    $scheduleTime = trim($input['schedule_time'] ?? '09:00');

    $phi_ship = 25000;
    $ngay_giao_sql = date('Y-m-d H:i:s', strtotime('+3 hours'));

    if ($deliveryMode === 'schedule' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $scheduleDate)) {
        $phi_ship = 25000;
        $gio_hop_le = preg_match('/^\d{2}:\d{2}$/', $scheduleTime) ? $scheduleTime . ':00' : '09:00:00';
        $ngay_giao_sql = $scheduleDate . ' ' . $gio_hop_le;
    } else {
        if ($deliveryTier === 'priority') {
            $phi_ship = 29000; // +4.000đ
            $ngay_giao_sql = date('Y-m-d H:i:s', strtotime('+90 minutes'));
        } elseif ($deliveryTier === 'saver') {
            $phi_ship = 22000; // -3.000đ
            $ngay_giao_sql = date('Y-m-d H:i:s', strtotime('+6 hours'));
        } else {
            $phi_ship = 25000; // Tiêu chuẩn
            $ngay_giao_sql = date('Y-m-d H:i:s', strtotime('+3 hours'));
        }
    }

    $mapttt = 1; // Thanh toán COD
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

    $giam_gia = 10000;
    $tong_thanhtoan = max(0, $tam_tinh + $phi_ship - $giam_gia);

    // 2. Lấy thông tin người nhận từ bảng users
    $stmt_u = $conn->prepare("SELECT fullname, phone FROM users WHERE MaND = ? LIMIT 1");
    $stmt_u->bind_param("i", $user_id);
    $stmt_u->execute();
    $usr = $stmt_u->get_result()->fetch_assoc();

    $nguoi_nhan = $usr['fullname'] ?? $_SESSION['user_name'] ?? 'Khách hàng';
    $sdt        = $usr['phone'] ?? '0946378461';
    $dia_chi    = 'Thành phố Hồ Chí Minh, Việt Nam';

    // 3. Thêm đơn hàng vào bảng donhang (lưu NgayGiao theo lựa chọn của khách)
    $stmt_dh = $conn->prepare("INSERT INTO donhang (NgayDat, NgayGiao, TongTien, TrangThai, DiaChiGiao, MaND, MaPTTT, MaKM, SoDienThoai, NguoiNhan) VALUES (NOW(), ?, ?, 'Đang xử lý', ?, ?, ?, ?, ?, ?)");

    if ($stmt_dh) {
        $stmt_dh->bind_param("sdsiisss", $ngay_giao_sql, $tong_thanhtoan, $dia_chi, $user_id, $mapttt, $makm, $sdt, $nguoi_nhan);
        if ($stmt_dh->execute()) {
            $madh = $conn->insert_id;

            // 4. Thêm chi tiết đơn hàng vào bảng chitietdonhang
            $stmt_ct = $conn->prepare("INSERT INTO chitietdonhang (MaDonHang, MaSP, SoLuong, DonGia) VALUES (?, ?, ?, ?)");
            foreach ($items_info as $item) {
                $stmt_ct->bind_param("iiid", $madh, $item['id'], $item['sl'], $item['gia']);
                $stmt_ct->execute();
            }

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

$timePriority = date('H:i', strtotime('+90 minutes'));
$timeStandard = date('H:i', strtotime('+3 hours'));
$timeSaver    = date('H:i', strtotime('+6 hours'));
?>

<link rel="stylesheet" href="<?= $rootDir ?>themes/order.css?v=<?= time() ?>">

<style>
    /* Khối chọn thời gian giao hàng giống mẫu */
    .deliv-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 14px;
    }
    .deliv-title {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 16px;
        font-weight: 700;
        color: #111827;
    }
    .deliv-switch-btn {
        background: none;
        border: none;
        color: #16834b;
        font-family: inherit;
        font-size: 13.5px;
        font-weight: 600;
        cursor: pointer;
        padding: 4px 6px;
        border-radius: 6px;
        transition: 0.15s;
    }
    .deliv-switch-btn:hover {
        background: #eaf5e7;
    }
    .deliv-options {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .deliv-opt {
        position: relative;
        border: 1.5px solid #e5e7eb;
        border-radius: 10px;
        padding: 13px 16px;
        cursor: pointer;
        transition: all 0.15s ease;
        background: #fff;
        overflow: hidden;
    }
    .deliv-opt:hover {
        border-color: #a7f3d0;
    }
    .deliv-opt.active {
        border-color: #16834b;
        background: #f6fbf8;
    }
    .deliv-opt .corner-check {
        display: none;
        position: absolute;
        top: 0;
        left: 0;
        width: 24px;
        height: 24px;
        background: #16834b;
        color: #fff;
        border-bottom-right-radius: 8px;
        align-items: center;
        justify-content: center;
    }
    .deliv-opt.active .corner-check {
        display: flex;
    }
    .deliv-opt-title {
        font-size: 14.5px;
        font-weight: 600;
        color: #111827;
        transition: padding-left 0.15s;
    }
    .deliv-opt.active .deliv-opt-title {
        color: #16834b;
        padding-left: 16px;
    }
    .deliv-opt-sub {
        font-size: 13px;
        color: #6b7280;
        margin-top: 4px;
    }
    .deliv-opt.active .deliv-opt-sub {
        padding-left: 16px;
    }
    .deliv-opt-sub .hl-plus {
        color: #dc2626;
        font-weight: 600;
    }
    .deliv-opt-sub .hl-minus {
        color: #16834b;
        font-weight: 600;
    }

    /* Hẹn ngày giờ cụ thể */
    .schedule-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 14px;
    }
    .schedule-field label {
        display: block;
        font-size: 12.5px;
        font-weight: 600;
        color: #4b5563;
        margin-bottom: 6px;
    }
    .schedule-field input,
    .schedule-field select {
        width: 100%;
        padding: 9px 11px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-family: inherit;
        font-size: 13.5px;
        background: #fff;
        box-sizing: border-box;
    }
    .schedule-field input:focus,
    .schedule-field select:focus {
        outline: none;
        border-color: #16834b;
    }
    @media (max-width: 600px) {
        .schedule-grid { grid-template-columns: 1fr; }
    }
</style>

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
                    <span class="status pending" style="display:inline-flex; align-items:center; gap:6px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <span>Chờ thanh toán</span>
                    </span>
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
                        <small id="tl-deliv-sub">Dự kiến hôm nay</small>
                    </li>
                    <li>
                        <span class="dot">4</span>
                        <strong>Đã giao</strong>
                        <small>Hoàn tất đơn</small>
                    </li>
                </ol>
            </section>

            <!-- CHỌN THỜI GIAN GIAO HÀNG (GIAO NGAY / HẸN GIỜ) -->
            <section class="card">
                <div class="deliv-head">
                    <div class="deliv-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16834b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
                        </svg>
                        <span id="deliv-mode-heading">Giao ngay</span>
                    </div>
                    <button type="button" class="deliv-switch-btn" id="btnSwitchDeliveryMode" onclick="toggleDeliveryMode()">
                        Đổi sang hẹn giờ
                    </button>
                </div>

                <!-- CHẾ ĐỘ 1: GIAO NGAY (3 GÓI TỐC ĐỘ) -->
                <div class="deliv-options" id="deliveryNowBox">
                    <div class="deliv-opt" data-tier="priority" data-ship="29000" data-label="Hôm nay, <?= $timePriority ?>" onclick="selectDeliveryTier(this)">
                        <span class="corner-check">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        </span>
                        <div class="deliv-opt-title">Ưu tiên - <?= $timePriority ?></div>
                        <div class="deliv-opt-sub">Thêm <span class="hl-plus">4.000đ</span> để nhận nông sản sớm hơn</div>
                    </div>

                    <div class="deliv-opt active" data-tier="standard" data-ship="25000" data-label="Hôm nay, <?= $timeStandard ?>" onclick="selectDeliveryTier(this)">
                        <span class="corner-check">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        </span>
                        <div class="deliv-opt-title">Tiêu chuẩn - <?= $timeStandard ?></div>
                    </div>

                    <div class="deliv-opt" data-tier="saver" data-ship="22000" data-label="Hôm nay, <?= $timeSaver ?>" onclick="selectDeliveryTier(this)">
                        <span class="corner-check">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        </span>
                        <div class="deliv-opt-title">Tiết kiệm - <?= $timeSaver ?></div>
                        <div class="deliv-opt-sub">Phí giao hàng <span class="hl-minus">giảm 3.000đ</span></div>
                    </div>
                </div>

                <!-- CHẾ ĐỘ 2: HẸN NGÀY GIỜ GIAO -->
                <div id="deliveryScheduleBox" style="display:none;">
                    <div class="schedule-grid">
                        <div class="schedule-field">
                            <label for="scheduleDate">Chọn ngày nhận hàng</label>
                            <input type="date" id="scheduleDate" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+14 days')) ?>" onchange="updateScheduleSummary()">
                        </div>
                        <div class="schedule-field">
                            <label for="scheduleTime">Chọn khung giờ giao</label>
                            <select id="scheduleTime" onchange="updateScheduleSummary()">
                                <option value="08:00">08:00 - 10:00 (Buổi sáng)</option>
                                <option value="10:00">10:00 - 12:00 (Trưa)</option>
                                <option value="14:00">14:00 - 16:00 (Buổi chiều)</option>
                                <option value="17:00">17:00 - 19:00 (Chiều tối)</option>
                            </select>
                        </div>
                    </div>
                    <p style="margin:10px 0 0 0; font-size:12.5px; color:#6b7280;">Phí giao hàng tiêu chuẩn áp dụng: <strong>25.000đ</strong>. Nông sản sẽ được đóng gói tươi mới sát giờ giao.</p>
                </div>
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
                        <span class="pay-icon" style="display:inline-flex; align-items:center; justify-content:center; color:#16834b;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
                        </span>
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
                    <span>Thời gian nhận</span>
                    <strong id="summary-delivery-time" style="color:#16834b;">Hôm nay, <?= $timeStandard ?></strong>
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
                    <strong id="summary-ship-fee"><?= number_format($phi_ship, 0, ',', '.') ?>đ</strong>
                </div>
                <div class="sum-row discount-text">
                    <span>Giảm giá</span>
                    <strong>-<?= number_format($giam_gia, 0, ',', '.') ?>đ</strong>
                </div>

                <hr>

                <div class="sum-total">
                    <span>Tổng tiền</span>
                    <strong id="summary-grand-total"><?= number_format($tong_tien, 0, ',', '.') ?>đ</strong>
                </div>

                <button class="btn-primary" onclick="xacNhanDatHang()">Xác nhận thanh toán →</button>
                <button class="btn-outline" onclick="window.location.href='<?= $rootDir ?><?= $buyNow ? 'index1.php' : 'customer/giohang.php' ?>'">Hủy đơn hàng</button>

                <p class="secure" style="display:flex; align-items:center; justify-content:center; gap:6px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <span>Thanh toán an toàn và bảo mật</span>
                </p>
            </section>

        </aside>

    </div>

</main>

<script>
const TAM_TINH = <?= (float)$tam_tinh ?>;
const GIAM_GIA = <?= (float)$giam_gia ?>;

let deliveryMode = 'now';      // 'now' hoặc 'schedule'
let deliveryTier = 'standard'; // 'priority', 'standard', 'saver'
let currentShipFee = 25000;

function formatVND(num) {
    return new Intl.NumberFormat('vi-VN').format(num) + 'đ';
}

function updateTotalUI(shipFee, timeText) {
    currentShipFee = shipFee;
    const grandTotal = Math.max(0, TAM_TINH + shipFee - GIAM_GIA);
    document.getElementById('summary-ship-fee').innerText = formatVND(shipFee);
    document.getElementById('summary-grand-total').innerText = formatVND(grandTotal);
    document.getElementById('summary-delivery-time').innerText = timeText;
    document.getElementById('tl-deliv-sub').innerText = timeText;
}

function selectDeliveryTier(el) {
    document.querySelectorAll('#deliveryNowBox .deliv-opt').forEach(opt => opt.classList.remove('active'));
    el.classList.add('active');

    deliveryTier = el.getAttribute('data-tier');
    const shipFee = parseInt(el.getAttribute('data-ship'), 10) || 25000;
    const label = el.getAttribute('data-label') || '';
    updateTotalUI(shipFee, label);
}

function toggleDeliveryMode() {
    const nowBox = document.getElementById('deliveryNowBox');
    const schBox = document.getElementById('deliveryScheduleBox');
    const heading = document.getElementById('deliv-mode-heading');
    const btn = document.getElementById('btnSwitchDeliveryMode');

    if (deliveryMode === 'now') {
        deliveryMode = 'schedule';
        nowBox.style.display = 'none';
        schBox.style.display = 'block';
        heading.innerText = 'Hẹn giờ giao hàng';
        btn.innerText = 'Đổi sang giao ngay';
        updateScheduleSummary();
    } else {
        deliveryMode = 'now';
        schBox.style.display = 'none';
        nowBox.style.display = 'flex';
        heading.innerText = 'Giao ngay';
        btn.innerText = 'Đổi sang hẹn giờ';
        const activeOpt = document.querySelector('#deliveryNowBox .deliv-opt.active');
        if (activeOpt) selectDeliveryTier(activeOpt);
    }
}

function updateScheduleSummary() {
    const dateVal = document.getElementById('scheduleDate').value;
    const timeSelect = document.getElementById('scheduleTime');
    const timeLabel = timeSelect.options[timeSelect.selectedIndex].text.split(' (')[0];

    let formattedDate = dateVal;
    if (dateVal && dateVal.includes('-')) {
        const parts = dateVal.split('-');
        formattedDate = `${parts[2]}/${parts[1]}/${parts[0]}`;
    }
    updateTotalUI(25000, `${timeLabel}, ngày ${formattedDate}`);
}

function xacNhanDatHang() {
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            mode: '<?= $buyNow ? 'buynow' : 'cart' ?>',
            delivery_mode: deliveryMode,
            delivery_tier: deliveryTier,
            schedule_date: document.getElementById('scheduleDate').value,
            schedule_time: document.getElementById('scheduleTime').value
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