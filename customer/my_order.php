<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/header.php';

if (empty($_SESSION['user_id'])) {
    header("Location: " . $rootDir . "customer/login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// Tự động bổ sung các cột cần thiết vào bảng binhluan nếu chưa có
$cols = $conn->query("SHOW COLUMNS FROM binhluan")->fetch_all(MYSQLI_ASSOC);
$colNames = array_column($cols, 'Field');
if (!in_array('MaDH', $colNames))        $conn->query("ALTER TABLE binhluan ADD COLUMN MaDH INT NULL AFTER MaSP");
if (!in_array('SoSao', $colNames))       $conn->query("ALTER TABLE binhluan ADD COLUMN SoSao TINYINT(1) NOT NULL DEFAULT 5 AFTER MaDH");
if (!in_array('HinhAnh', $colNames))     $conn->query("ALTER TABLE binhluan ADD COLUMN HinhAnh VARCHAR(255) NULL AFTER SoSao");
if (!in_array('TraLoiAdmin', $colNames)) $conn->query("ALTER TABLE binhluan ADD COLUMN TraLoiAdmin TEXT NULL");

// Lấy chi tiết đánh giá của khách theo từng mã đơn hàng (MaDH)
$danhGiaTheoDon = [];
$stmtRated = $conn->prepare("
    SELECT MaDH, SoSao, NoiDung, HinhAnh, TraLoiAdmin, NgayDanhGia 
    FROM binhluan 
    WHERE MaND = ? AND MaDH IS NOT NULL 
    ORDER BY MaBinhLuan DESC
");
$stmtRated->bind_param("i", $user_id);
$stmtRated->execute();
$resRated = $stmtRated->get_result();
while ($r = $resRated->fetch_assoc()) {
    $madhKey = (int)$r['MaDH'];
    if (!isset($danhGiaTheoDon[$madhKey])) {
        $danhGiaTheoDon[$madhKey] = [
            'SoSao'       => (int)($r['SoSao'] ?? 5),
            'NoiDung'     => $r['NoiDung'] ?? '',
            'HinhAnh'     => $r['HinhAnh'] ?? '',
            'TraLoiAdmin' => $r['TraLoiAdmin'] ?? '',
            'NgayDanhGia' => !empty($r['NgayDanhGia']) ? date('d/m/Y H:i', strtotime($r['NgayDanhGia'])) : '',
        ];
    }
}

// Lấy danh sách đơn hàng của Khách hàng hiện tại
$sql = "SELECT * FROM donhang WHERE MaND = ? ORDER BY NgayDat DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$ds_donhang = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Lấy chi tiết sản phẩm trong các đơn hàng của khách
$chiTietTheoDon = [];
$checkTable = $conn->query("SHOW TABLES LIKE 'chitietdonhang'");
if ($checkTable->num_rows > 0 && !empty($ds_donhang)) {
    $ctCols   = array_column($conn->query("SHOW COLUMNS FROM chitietdonhang")->fetch_all(MYSQLI_ASSOC), 'Field');
    $fkCol    = in_array('MaDonHang', $ctCols) ? 'MaDonHang' : 'MaDH';
    $qtyCol   = in_array('SoLuong', $ctCols) ? 'SoLuong' : 'soluong';
    $priceCol = in_array('DonGia', $ctCols) ? 'DonGia' : (in_array('Gia', $ctCols) ? 'Gia' : 'GiaBan');

    $orderIds = array_map(fn($d) => (int)($d['MaDonHang'] ?? $d['id'] ?? 0), $ds_donhang);
    $orderIds = array_filter($orderIds, fn($id) => $id > 0);

    if (!empty($orderIds)) {
        $inClause = implode(',', $orderIds);
        $sqlCT = "SELECT ct.$fkCol AS MaDon, ct.MaSP, ct.$qtyCol AS SoLuong, ct.$priceCol AS DonGia,
                         COALESCE(s.TenSP, CONCAT('Sản phẩm #', ct.MaSP)) AS TenSP,
                         COALESCE(s.DonViTinh, '') AS DonViTinh
                  FROM chitietdonhang ct
                  LEFT JOIN sanpham s ON ct.MaSP = s.MaSP
                  WHERE ct.$fkCol IN ($inClause)";
        $resCT = $conn->query($sqlCT)->fetch_all(MYSQLI_ASSOC);

        foreach ($resCT as $item) {
            $idDon = (int)$item['MaDon'];
            $chiTietTheoDon[$idDon][] = [
                'TenSP'     => $item['TenSP'],
                'DonViTinh' => $item['DonViTinh'],
                'SoLuong'   => (int)$item['SoLuong'],
                'DonGia'    => (float)$item['DonGia'],
                'ThanhTien' => (int)$item['SoLuong'] * (float)$item['DonGia'],
            ];
        }
    }
}

// Trạng thái dạng chữ thuần
$trang_thai_label = [
    'Chờ xác nhận'  => ['text' => 'Chờ xác nhận',         'color' => '#f59e0b'],
    'Chờ xử lý'     => ['text' => 'Chờ xử lý',            'color' => '#f59e0b'],
    'Đang xử lý'    => ['text' => 'Đang xử lý',           'color' => '#6b7280'],
    'Đã thanh toán' => ['text' => 'Đã thanh toán',        'color' => '#3b82f6'],
    'Đang giao'     => ['text' => 'Đang giao hàng',       'color' => '#8b5cf6'],
    'Đã giao'       => ['text' => 'Đã giao',              'color' => '#525866'],
    'Đã hoàn thành' => ['text' => 'Giao hàng thành công', 'color' => '#10b981'],
    'Đã hủy'        => ['text' => 'Đơn hàng đã hủy',      'color' => '#ef4444'],
];
?>

<style>
    .star-rating { display: flex; gap: 6px; cursor: pointer; user-select: none; }
    .star-rating .star { color: #d1d5db; transition: color 0.15s, transform 0.15s; display: inline-flex; }
    .star-rating .star svg { width: 28px; height: 28px; fill: currentColor; }
    .star-rating .star.active,
    .star-rating .star.hover { color: #f59e0b; transform: scale(1.08); }
    
    .upload-box {
        border: 1.5px dashed #cbd5e1; border-radius: 10px; padding: 16px;
        text-align: center; cursor: pointer; background: #f8fafc; transition: 0.2s;
    }
    .upload-box:hover { border-color: #16834b; background: #f0fdf4; }

    .order-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 36px;
        padding: 0 18px;
        border-radius: 999px;
        font-family: inherit;
        font-size: 13px;
        font-weight: 600;
        line-height: 1;
        box-sizing: border-box;
        white-space: nowrap;
    }

    .btn-order-detail {
        background: #fff;
        color: #374151;
        border: 1px solid #d1d5db;
        cursor: pointer;
        transition: 0.2s;
    }
    .btn-order-detail:hover {
        background: #f3f4f6;
        border-color: #9ca3af;
        color: #111827;
    }
    
    .btn-review-open {
        background: #16834b;
        color: #fff;
        border: none;
        cursor: pointer;
        transition: 0.2s;
    }
    .btn-review-open:hover { background: #126b3d; }

    .btn-reviewed-view {
        background: #e5f2e9;
        color: #16834b;
        border: 1px solid #bbf7d0;
        cursor: pointer;
        transition: 0.2s;
    }
    .btn-reviewed-view:hover { background: #d1fae5; }

    .auto-hide-alert {
        transition: opacity 0.4s ease, transform 0.4s ease;
    }

    .cust-detail-row {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        font-size: 13.5px;
        margin-bottom: 8px;
    }
    .cust-detail-row span:first-child { color: #6b7280; white-space: nowrap; }
    .cust-detail-row span:last-child { color: #111827; font-weight: 600; text-align: right; }

    .cust-items-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        overflow: hidden;
    }
    .cust-items-table th {
        background: #f3f7f4;
        color: #1e3a29;
        padding: 8px 10px;
        text-align: left;
        font-weight: 600;
        border-bottom: 1px solid #e5e7eb;
    }
    .cust-items-table td {
        padding: 8px 10px;
        border-bottom: 1px solid #f1f5f9;
        color: #1f2937;
    }
    .cust-items-table tr:last-child td { border-bottom: none; }
</style>

<main class="container page" style="margin-top: 30px; margin-bottom: 50px;">
    <h2 style="display:flex; align-items:center; gap:10px; margin-bottom:6px;">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#16834b" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
            <path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>
        </svg>
        <span>Danh sách đơn hàng của bạn</span>
    </h2>
    <p style="color: #666; margin-bottom: 20px;">Theo dõi tiến độ xử lý và giao nhận nông sản</p>

    <?php if (!empty($_GET['msg'])): ?>
        <div class="auto-hide-alert" style="background:#dcfce7; color:#166534; padding:12px 16px; border-radius:8px; margin-bottom:16px; font-weight:600; display:flex; align-items:center; gap:8px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            <span><?= htmlspecialchars($_GET['msg']) ?></span>
        </div>
    <?php endif; ?>
    <?php if (!empty($_GET['err'])): ?>
        <div class="auto-hide-alert" style="background:#fee2e2; color:#991b1b; padding:12px 16px; border-radius:8px; margin-bottom:16px; font-weight:600; display:flex; align-items:center; gap:8px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span><?= htmlspecialchars($_GET['err']) ?></span>
        </div>
    <?php endif; ?>

    <?php if (empty($ds_donhang)): ?>
        <div style="background: #fff; padding: 40px; text-align: center; border-radius: 12px; border: 1px solid #e5e7eb;">
            <p>Bạn chưa có đơn hàng nào.</p>
            <a href="<?= $rootDir ?>index1.php" style="color: #16834b; font-weight: bold;">Đi mua sắm ngay →</a>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 15px;">
            <?php foreach ($ds_donhang as $dh): ?>
                <?php 
                    $maDon = (int)($dh['MaDonHang'] ?? $dh['id'] ?? 0);
                    $maDonText = 'DH' . str_pad((string)$maDon, 4, '0', STR_PAD_LEFT);
                    $trangThai = trim($dh['TrangThai']);
                    $tt = $trang_thai_label[$trangThai] ?? ['text' => $trangThai, 'color' => '#666'];
                    $choPhepBinhLuan = in_array($trangThai, ['Đã giao', 'Đã hoàn thành'], true);
                    $daReview = isset($danhGiaTheoDon[$maDon]);

                    $ngayGiaoText = !empty($dh['NgayGiao']) ? date('H:i — d/m/Y', strtotime($dh['NgayGiao'])) : '-';

                    $orderDetailPayload = [
                        'MaDonText'   => '#' . $maDonText,
                        'NgayDat'     => date('d/m/Y H:i', strtotime($dh['NgayDat'])),
                        'NgayGiao'    => $ngayGiaoText,
                        'TrangThai'   => $tt['text'],
                        'NguoiNhan'   => $dh['NguoiNhan'] ?? ($_SESSION['fullname'] ?? 'Khách hàng'),
                        'SoDienThoai' => $dh['SoDienThoai'] ?? '-',
                        'DiaChiGiao'  => $dh['DiaChiGiao'] ?? '-',
                        'TongTien'    => (float)($dh['TongTien'] ?? 0),
                        'Items'       => $chiTietTheoDon[$maDon] ?? [],
                    ];
                ?>
                <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                    <div>
                        <h3 style="margin: 0 0 5px 0;">Mã đơn: #<?= $maDonText ?></h3>
                        <p style="margin: 0; color: #666; font-size: 14px;">Ngày đặt: <?= date('d/m/Y H:i', strtotime($dh['NgayDat'])) ?></p>
                        <p style="margin: 5px 0 0 0; font-weight: bold; color: #15803d;">Tổng tiền: <?= number_format($dh['TongTien'], 0, ',', '.') ?>đ</p>
                    </div>

                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <button type="button" class="order-pill btn-order-detail" 
                                onclick='openOrderDetailModal(<?= htmlspecialchars(json_encode($orderDetailPayload), ENT_QUOTES, "UTF-8") ?>)'>
                            Chi tiết
                        </button>

                        <span class="order-pill" style="color: #fff; background-color: <?= $tt['color'] ?>;">
                            <?= $tt['text'] ?>
                        </span>

                        <?php if ($choPhepBinhLuan): ?>
                            <?php if ($daReview): ?>
                                <button type="button" class="order-pill btn-reviewed-view" 
                                        onclick='openViewReviewModal("#<?= $maDonText ?>", <?= htmlspecialchars(json_encode($danhGiaTheoDon[$maDon]), ENT_QUOTES, "UTF-8") ?>)'>
                                    Xem đánh giá
                                </button>
                            <?php else: ?>
                                <button type="button" class="order-pill btn-review-open" onclick="openReviewModal(<?= $maDon ?>, '#<?= $maDonText ?>')">
                                    Bình luận
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<!-- POPUP 0: XEM CHI TIẾT ĐƠN HÀNG CỦA KHÁCH -->
<div id="orderDetailModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:9999; align-items:center; justify-content:center; padding:16px;">
    <div style="background:#fff; width:100%; max-width:520px; border-radius:14px; padding:24px; box-shadow:0 15px 35px rgba(0,0,0,0.2); position:relative; max-height:90vh; overflow-y:auto;">
        <button type="button" onclick="closeOrderDetailModal()" style="position:absolute; right:16px; top:14px; border:none; background:none; color:#6b7280; cursor:pointer; padding:4px; display:flex;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>

        <h3 style="margin:0 0 4px 0; color:#1e3a29; font-size:18px;">Chi tiết đơn hàng <span id="odCode" style="color:#16834b;"></span></h3>
        <p id="odMeta" style="margin:0 0 16px 0; font-size:13px; color:#6b7280;"></p>

        <div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; padding:12px 14px; margin-bottom:16px;">
            <div class="cust-detail-row">
                <span>Người nhận</span>
                <span id="odReceiver">-</span>
            </div>
            <div class="cust-detail-row">
                <span>Số điện thoại</span>
                <span id="odPhone">-</span>
            </div>
            <div class="cust-detail-row">
                <span>Thời gian giao dự kiến</span>
                <span id="odDeliveryTime" style="color:#16834b;">-</span>
            </div>
            <div class="cust-detail-row" style="margin-bottom:0;">
                <span>Địa chỉ giao</span>
                <span id="odAddress">-</span>
            </div>
        </div>

        <div style="font-size:13px; font-weight:700; color:#1e3a29; margin-bottom:8px; text-transform:uppercase; letter-spacing:0.3px;">
            Sản phẩm đã đặt (<span id="odItemCount">0</span>)
        </div>
        <table class="cust-items-table">
            <thead>
                <tr>
                    <th>Tên nông sản</th>
                    <th style="text-align:center; width:55px;">SL</th>
                    <th style="text-align:right; width:95px;">Đơn giá</th>
                    <th style="text-align:right; width:105px;">Thành tiền</th>
                </tr>
            </thead>
            <tbody id="odItemsBody"></tbody>
        </table>

        <div style="margin-top:12px; background:#f8fafc; border:1px solid #e5e7eb; border-radius:10px; padding:12px 14px; display:flex; flex-direction:column; gap:7px;">
            <div style="display:flex; justify-content:space-between; font-size:13.5px; color:#4b5563;">
                <span>Tạm tính (tiền hàng)</span>
                <span id="odSubtotal" style="font-weight:600; color:#111827;">0đ</span>
            </div>
            <div style="display:flex; justify-content:space-between; font-size:13.5px; color:#4b5563;">
                <span id="odShipLabel">Phí giao hàng</span>
                <span id="odShipVal" style="font-weight:600; color:#111827;">0đ</span>
            </div>
            <div style="display:flex; justify-content:space-between; font-size:15px; font-weight:700; color:#16834b; border-top:1px dashed #cbd5e1; padding-top:8px; margin-top:2px;">
                <span>Tổng thanh toán</span>
                <span id="odTotal">0đ</span>
            </div>
        </div>

        <div style="display:flex; justify-content:flex-end; margin-top:16px;">
            <button type="button" onclick="closeOrderDetailModal()" style="padding:9px 20px; border-radius:8px; border:none; background:#16834b; color:#fff; font-weight:600; cursor:pointer;">Đóng</button>
        </div>
    </div>
</div>

<!-- POPUP 1: GỬI ĐÁNH GIÁ MỚI -->
<div id="reviewModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:9999; align-items:center; justify-content:center; padding:16px;">
    <div style="background:#fff; width:100%; max-width:460px; border-radius:14px; padding:24px; box-shadow:0 15px 35px rgba(0,0,0,0.2); position:relative;">
        <button type="button" onclick="closeReviewModal()" style="position:absolute; right:16px; top:14px; border:none; background:none; color:#6b7280; cursor:pointer; padding:4px; display:flex;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        
        <h3 style="margin:0 0 4px 0; color:#1e3a29; font-size:18px;">Đánh giá đơn hàng <span id="modalOrderCode" style="color:#16834b;"></span></h3>
        <p style="margin:0 0 16px 0; font-size:13px; color:#6b7280;">Chia sẻ trải nghiệm của bạn về chất lượng nông sản</p>

        <form method="POST" action="submit_binhluan.php" enctype="multipart/form-data">
            <input type="hidden" name="madh" id="modalMaDH" value="">
            <input type="hidden" name="sosao" id="inputSoSao" value="5">

            <!-- CHỌN 5 SAO -->
            <div style="margin-bottom: 16px;">
                <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">Chất lượng sản phẩm:</label>
                <div style="display:flex; align-items:center; gap:12px;">
                    <div class="star-rating" id="starContainer">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="star active" data-val="<?= $i ?>">
                                <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            </span>
                        <?php endfor; ?>
                    </div>
                    <span id="starLabel" style="font-size:13px; font-weight:600; color:#f59e0b;">Tuyệt vời</span>
                </div>
            </div>

            <!-- NỘI DUNG BÌNH LUẬN -->
            <div style="margin-bottom: 14px;">
                <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">Nhận xét của bạn:</label>
                <textarea name="noidung" required rows="3" 
                          placeholder="Rau củ tươi ngon, đóng gói sạch sẽ, giao hàng đúng giờ..."
                          style="width:100%; padding:10px; border:1px solid #d1d5db; border-radius:8px; font-family:inherit; font-size:14px; box-sizing:border-box; resize:vertical;"></textarea>
            </div>

            <!-- UP ẢNH CHỤP SẢN PHẨM -->
            <div style="margin-bottom: 18px;">
                <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">Thêm hình ảnh thực tế (không bắt buộc):</label>
                <label class="upload-box" style="display:block;">
                    <input type="file" name="hinhanh" id="inputHinhAnh" accept="image/*" style="display:none;" onchange="previewReviewImage(this)">
                    <div id="uploadPlaceholder">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto; display:block;">
                            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                            <circle cx="12" cy="13" r="4"/>
                        </svg>
                        <div style="font-size:13px; color:#475569; font-weight:500; margin-top:6px;">Bấm để chọn ảnh chụp sản phẩm</div>
                        <div style="font-size:11px; color:#94a3b8; margin-top:2px;">Hỗ trợ JPG, PNG, WEBP (tối đa 5MB)</div>
                    </div>
                    <img id="previewImg" src="" alt="Xem trước" style="display:none; max-height:130px; margin:0 auto; border-radius:8px; object-fit:cover;">
                </label>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" onclick="closeReviewModal()" style="padding:9px 16px; border-radius:8px; border:1px solid #d1d5db; background:#fff; font-weight:600; color:#374151; cursor:pointer;">Hủy</button>
                <button type="submit" style="padding:9px 20px; border-radius:8px; border:none; background:#16834b; color:#fff; font-weight:600; cursor:pointer;">Gửi đánh giá</button>
            </div>
        </form>
    </div>
</div>

<!-- POPUP 2: XEM LẠI ĐÁNH GIÁ CỦA MÌNH -->
<div id="viewReviewModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:9999; align-items:center; justify-content:center; padding:16px;">
    <div style="background:#fff; width:100%; max-width:460px; border-radius:14px; padding:24px; box-shadow:0 15px 35px rgba(0,0,0,0.2); position:relative;">
        <button type="button" onclick="closeViewReviewModal()" style="position:absolute; right:16px; top:14px; border:none; background:none; color:#6b7280; cursor:pointer; padding:4px; display:flex;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>

        <h3 style="margin:0 0 4px 0; color:#1e3a29; font-size:18px;">Đánh giá của bạn — <span id="viewOrderCode" style="color:#16834b;"></span></h3>
        <p id="viewDate" style="margin:0 0 14px 0; font-size:12px; color:#6b7280;"></p>

        <!-- Số sao đã chấm -->
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:14px;">
            <div id="viewStars" style="display:flex; gap:4px;"></div>
            <span id="viewStarText" style="font-size:13px; font-weight:600; color:#f59e0b;"></span>
        </div>

        <!-- Nội dung đánh giá -->
        <div style="margin-bottom:14px;">
            <span style="display:block; font-size:12px; font-weight:600; color:#6b7280; margin-bottom:5px;">Nhận xét của bạn:</span>
            <div id="viewContent" style="background:#f9fafb; padding:12px; border-radius:8px; border:1px solid #e5e7eb; font-size:14px; color:#1f2937; line-height:1.5;"></div>
        </div>

        <!-- Hình ảnh đã tải lên -->
        <div id="viewImageBox" style="margin-bottom:14px; display:none;">
            <span style="display:block; font-size:12px; font-weight:600; color:#6b7280; margin-bottom:6px;">Hình ảnh đính kèm:</span>
            <a id="viewImageLink" href="#" target="_blank">
                <img id="viewImage" src="" alt="Ảnh đánh giá" style="max-height:150px; border-radius:8px; border:1px solid #e5e7eb; object-fit:cover;">
            </a>
        </div>

        <!-- Phản hồi từ Shop (nếu Admin đã trả lời) -->
        <div id="viewReplyBox" style="margin-bottom:16px; display:none; background:#f0fdf4; border:1px solid #bbf7d0; padding:12px; border-radius:8px;">
            <span style="display:block; font-size:12px; font-weight:700; color:#166534; margin-bottom:4px;">Phản hồi từ Nông Sản Xanh:</span>
            <div id="viewReplyContent" style="font-size:13px; color:#1e3a29; line-height:1.5;"></div>
        </div>

        <div style="display:flex; justify-content:flex-end;">
            <button type="button" onclick="closeViewReviewModal()" style="padding:9px 20px; border-radius:8px; border:none; background:#16834b; color:#fff; font-weight:600; cursor:pointer;">Đóng</button>
        </div>
    </div>
</div>

<script>
const starTexts = {
    1: 'Rất tệ',
    2: 'Không hài lòng',
    3: 'Bình thường',
    4: 'Hài lòng',
    5: 'Tuyệt vời'
};

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text ?? '';
    return div.innerHTML;
}

function formatVND(amount) {
    return new Intl.NumberFormat('vi-VN').format(Number(amount) || 0) + 'đ';
}

// Mở Popup xem chi tiết đơn hàng
function openOrderDetailModal(data) {
    document.getElementById('odCode').innerText = data.MaDonText || '';
    document.getElementById('odMeta').innerText = `Ngày đặt: ${data.NgayDat || '-'} • Trạng thái: ${data.TrangThai || '-'}`;
    document.getElementById('odReceiver').innerText = data.NguoiNhan || '-';
    document.getElementById('odPhone').innerText = data.SoDienThoai || '-';
    document.getElementById('odDeliveryTime').innerText = data.NgayGiao || '-';
    document.getElementById('odAddress').innerText = data.DiaChiGiao || '-';

    const items = Array.isArray(data.Items) ? data.Items : [];
    document.getElementById('odItemCount').innerText = items.length;
    const tbody = document.getElementById('odItemsBody');

    let subtotal = 0;
    if (items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; color:#6b7280; padding:14px;">Chưa có dữ liệu chi tiết sản phẩm cho đơn hàng này</td></tr>';
        subtotal = Number(data.TongTien) || 0;
    } else {
        tbody.innerHTML = items.map(item => {
            subtotal += Number(item.ThanhTien) || 0;
            const dvt = item.DonViTinh ? ` <span style="color:#6b7280; font-size:12px;">(${escapeHtml(item.DonViTinh)})</span>` : '';
            return `
                <tr>
                    <td><strong>${escapeHtml(item.TenSP)}</strong>${dvt}</td>
                    <td style="text-align:center; font-weight:600;">x${item.SoLuong}</td>
                    <td style="text-align:right; color:#4b5563;">${formatVND(item.DonGia)}</td>
                    <td style="text-align:right; font-weight:600; color:#16834b;">${formatVND(item.ThanhTien)}</td>
                </tr>
            `;
        }).join('');
    }

    const total = Number(data.TongTien) || 0;
    const diff = total - subtotal;
    const shipLabel = document.getElementById('odShipLabel');
    const shipVal = document.getElementById('odShipVal');

    document.getElementById('odSubtotal').innerText = formatVND(subtotal);
    document.getElementById('odTotal').innerText = formatVND(total);

    if (diff > 0) {
        shipLabel.innerText = 'Phí giao hàng';
        shipVal.innerText = '+' + formatVND(diff);
        shipVal.style.color = '#111827';
    } else if (diff < 0) {
        shipLabel.innerText = 'Giảm giá / Khuyến mãi';
        shipVal.innerText = '-' + formatVND(Math.abs(diff));
        shipVal.style.color = '#dc2626';
    } else {
        shipLabel.innerText = 'Phí giao hàng';
        shipVal.innerText = 'Miễn phí (0đ)';
        shipVal.style.color = '#16834b';
    }

    document.getElementById('orderDetailModal').style.display = 'flex';
}

function closeOrderDetailModal() {
    document.getElementById('orderDetailModal').style.display = 'none';
}

const stars = document.querySelectorAll('#starContainer .star');
const inputSoSao = document.getElementById('inputSoSao');
const starLabel = document.getElementById('starLabel');

function renderStars(rating) {
    stars.forEach(s => {
        const val = parseInt(s.getAttribute('data-val'));
        s.classList.toggle('active', val <= rating);
    });
    starLabel.innerText = starTexts[rating] || '';
}

stars.forEach(star => {
    star.addEventListener('mouseenter', function() {
        const hoverVal = parseInt(this.getAttribute('data-val'));
        stars.forEach(s => {
            const val = parseInt(s.getAttribute('data-val'));
            s.classList.toggle('hover', val <= hoverVal);
        });
        starLabel.innerText = starTexts[hoverVal] || '';
    });

    star.addEventListener('mouseleave', function() {
        stars.forEach(s => s.classList.remove('hover'));
        renderStars(parseInt(inputSoSao.value));
    });

    star.addEventListener('click', function() {
        const selectedVal = parseInt(this.getAttribute('data-val'));
        inputSoSao.value = selectedVal;
        renderStars(selectedVal);
    });
});

function openReviewModal(madh, orderCode) {
    document.getElementById('modalMaDH').value = madh;
    document.getElementById('modalOrderCode').innerText = orderCode;
    inputSoSao.value = 5;
    renderStars(5);
    document.getElementById('inputHinhAnh').value = '';
    document.getElementById('previewImg').style.display = 'none';
    document.getElementById('uploadPlaceholder').style.display = 'block';
    document.getElementById('reviewModal').style.display = 'flex';
}

function closeReviewModal() {
    document.getElementById('reviewModal').style.display = 'none';
}

function previewReviewImage(input) {
    const preview = document.getElementById('previewImg');
    const placeholder = document.getElementById('uploadPlaceholder');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            placeholder.style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function openViewReviewModal(orderCode, data) {
    document.getElementById('viewOrderCode').innerText = orderCode;
    document.getElementById('viewDate').innerText = data.NgayDanhGia ? ('Đã gửi lúc: ' + data.NgayDanhGia) : '';

    const rating = Math.max(1, Math.min(5, parseInt(data.SoSao) || 5));
    let starHtml = '';
    for (let i = 1; i <= 5; i++) {
        const fill = i <= rating ? '#f59e0b' : '#d1d5db';
        starHtml += `<svg width="22" height="22" viewBox="0 0 24 24" fill="${fill}"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>`;
    }
    document.getElementById('viewStars').innerHTML = starHtml;
    document.getElementById('viewStarText').innerText = starTexts[rating] || '';
    document.getElementById('viewContent').innerText = data.NoiDung || '';

    const imgBox = document.getElementById('viewImageBox');
    if (data.HinhAnh) {
        const imgUrl = '../uploads/binhluan/' + data.HinhAnh;
        document.getElementById('viewImage').src = imgUrl;
        document.getElementById('viewImageLink').href = imgUrl;
        imgBox.style.display = 'block';
    } else {
        imgBox.style.display = 'none';
    }

    const replyBox = document.getElementById('viewReplyBox');
    if (data.TraLoiAdmin && data.TraLoiAdmin.trim() !== '') {
        document.getElementById('viewReplyContent').innerText = data.TraLoiAdmin;
        replyBox.style.display = 'block';
    } else {
        replyBox.style.display = 'none';
    }

    document.getElementById('viewReviewModal').style.display = 'flex';
}

function closeViewReviewModal() {
    document.getElementById('viewReviewModal').style.display = 'none';
}

// Tự ẩn thông báo sau 3 giây và làm sạch URL để F5 không hiện lại
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.auto-hide-alert');
    if (alerts.length > 0) {
        if (window.history.replaceState) {
            const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
            window.history.replaceState({ path: cleanUrl }, '', cleanUrl);
        }

        setTimeout(() => {
            alerts.forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(-6px)';
                setTimeout(() => el.remove(), 400);
            });
        }, 3000);
    }
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>