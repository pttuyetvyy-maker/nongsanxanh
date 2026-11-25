<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

function e($val) { return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8'); }

$msg = ""; $err = "";

// 1. XỬ LÝ CẬP NHẬT TRẠNG THÁI ĐƠN HÀNG VÀO MYSQL
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $madh   = (int)($_POST['madonhang'] ?? 0);

    if ($madh > 0) {
        if ($action === 'update_status') {
            $trangthai = trim($_POST['trangthai'] ?? '');
            $stmt = $conn->prepare("UPDATE donhang SET TrangThai = ? WHERE MaDonHang = ?");
            $stmt->bind_param("si", $trangthai, $madh);
            if ($stmt->execute()) {
                $msg = "Cập nhật trạng thái đơn hàng #DH" . str_pad($madh, 4, '0', STR_PAD_LEFT) . " thành công!";
            } else {
                $err = "Lỗi khi cập nhật!";
            }
        } elseif ($action === 'cancel_order') {
            $stmt = $conn->prepare("UPDATE donhang SET TrangThai = 'Đã hủy' WHERE MaDonHang = ?");
            $stmt->bind_param("i", $madh);
            if ($stmt->execute()) {
                $msg = "Đã hủy đơn hàng #DH" . str_pad($madh, 4, '0', STR_PAD_LEFT) . " thành công!";
            } else {
                $err = "Lỗi khi hủy đơn hàng!";
            }
        }
    }
}

// 2. LẤY DANH SÁCH ĐƠN HÀNG TỪ CSDL
$sql = "SELECT d.*, u.fullname AS TenKhachHang 
        FROM donhang d 
        LEFT JOIN users u ON d.MaND = u.MaND 
        ORDER BY d.MaDonHang DESC";
$ds_donhang = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// 3. LẤY CHI TIẾT SẢN PHẨM CỦA TẤT CẢ ĐƠN HÀNG ĐỂ CHUẨN BỊ HÀNG
$chiTietTheoDon = [];
$checkTable = $conn->query("SHOW TABLES LIKE 'chitietdonhang'");
if ($checkTable->num_rows > 0) {
    $ctCols = array_column($conn->query("SHOW COLUMNS FROM chitietdonhang")->fetch_all(MYSQLI_ASSOC), 'Field');
    $fkCol  = in_array('MaDonHang', $ctCols) ? 'MaDonHang' : 'MaDH';
    $qtyCol = in_array('SoLuong', $ctCols) ? 'SoLuong' : 'soluong';
    $priceCol = in_array('DonGia', $ctCols) ? 'DonGia' : (in_array('Gia', $ctCols) ? 'Gia' : 'GiaBan');

    $sqlCT = "SELECT ct.$fkCol AS MaDon, ct.MaSP, ct.$qtyCol AS SoLuong, ct.$priceCol AS DonGia,
                     COALESCE(s.TenSP, CONCAT('Sản phẩm #', ct.MaSP)) AS TenSP,
                     COALESCE(s.DonViTinh, '') AS DonViTinh
              FROM chitietdonhang ct
              LEFT JOIN sanpham s ON ct.MaSP = s.MaSP
              ORDER BY ct.$fkCol DESC";
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

// Gắn mảng sản phẩm vào từng đơn hàng
foreach ($ds_donhang as &$dhItem) {
    $idDon = (int)$dhItem['MaDonHang'];
    $dhItem['items'] = $chiTietTheoDon[$idDon] ?? [];
}
unset($dhItem);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Nông sản Xanh — Quản lý đơn hàng</title>
    <link rel="stylesheet" href="../themes/styleadmin.css?v=1">
    <link rel="stylesheet" href="../themes/category-fix.css?v=1">
    <style>
        .category-table { width:100%; border-collapse:collapse; font-size:14px; }
        .category-table th { padding:14px 16px; text-align:left; background:#eaf4ed; color:#1e3a29; font-weight:700; }
        .category-table td { padding:14px 16px; border-bottom:1px solid #edf2f0; vertical-align:middle; }
        
        .category-badge { display:inline-block; padding:4px 10px; border-radius:20px; font-size:12px; font-weight:600; }
        .badge-green { background:#e5f2e9; color:#087443; }
        .badge-warning { background:#fef3c7; color:#92400e; }
        .badge-danger { background:#fee2e2; color:#991b1b; }
        
        .order-details-grid { display: grid; grid-template-columns: 1.35fr 0.85fr; gap: 20px; margin-top: 20px; align-items: start; }
        .detail-card { background: #fff; padding: 20px; border-radius: 10px; border: 1px solid #edf2f0; box-shadow: 0 2px 8px rgba(0,0,0,0.03); }
        .detail-card h3 { margin-top: 0; color: #1e3a29; font-size: 16px; border-bottom: 2px solid #eaf4ed; padding-bottom: 10px; }
        .detail-item { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14px; gap: 16px; }
        .detail-item span:first-child { color: #6b7280; font-weight: 500; white-space: nowrap; }
        .detail-item span:last-child { font-weight: 600; color: #111827; text-align: right; }

        /* Bảng sản phẩm cần chuẩn bị */
        .items-box { margin-top: 16px; padding-top: 14px; border-top: 1px dashed #d1d5db; }
        .items-box-title { font-size: 13px; font-weight: 700; color: #1e3a29; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.3px; }
        .items-mini-table { width: 100%; border-collapse: collapse; font-size: 13px; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; }
        .items-mini-table th { background: #f3f7f4; color: #1e3a29; padding: 8px 10px; text-align: left; font-weight: 600; border-bottom: 1px solid #e5e7eb; }
        .items-mini-table td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; color: #1f2937; }
        .items-mini-table tr:last-child td { border-bottom: none; }

        /* Khung tóm tắt tiền hàng + phí ship */
        .money-summary {
            margin-top: 12px;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px 14px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .money-row { display: flex; justify-content: space-between; align-items: center; font-size: 13.5px; color: #4b5563; }
        .money-row.total {
            border-top: 1px dashed #cbd5e1;
            padding-top: 8px;
            margin-top: 2px;
            font-size: 15px;
            font-weight: 700;
            color: #16834b;
        }
        
        .form-select-status { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; margin-bottom: 15px; }
        .btn-update { background: #16834b; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .btn-cancel { background: #dc2626; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; }
        
        .icon-view-btn {
            width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center;
            border: 1px solid #d1d5db; background: #fff; border-radius: 8px; cursor: pointer; color: #4b5563; transition: 0.15s;
        }
        .icon-view-btn:hover { background: #f3f7f4; border-color: #9ca3af; color: #111827; }
        
        .toast-container { position:fixed; right:24px; bottom:24px; z-index:10001; }
        .toast-msg { padding:13px 18px; border-radius:10px; color:#fff; font-weight:600; box-shadow:0 8px 25px rgba(0,0,0,.15); }
        .toast-msg.success { background:#16834b; } .toast-msg.error { background:#bd3b32; }
    </style>
</head>
<body>
<div class="shell">
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <div class="main">
        <div class="topbar">
            <div class="topbar-title">Đơn hàng<span>Quản lý đơn hàng</span></div>
            <div class="topbar-right"><div class="admin-chip"><div class="avatar-dot">TV</div><span>Admin</span></div></div>
        </div>

        <div class="content">
            <section class="panel active">
                
                <!-- BẢNG DANH SÁCH ĐƠN HÀNG -->
                <div class="table-card">
                    <table class="category-table">
                        <thead>
                            <tr>
                                <th>Mã đơn</th>
                                <th>Khách hàng</th>
                                <th>Ngày đặt</th>
                                <th>Tổng tiền</th>
                                <th style="text-align:center;">Trạng thái</th>
                                <th style="text-align:center;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($ds_donhang as $dh): 
                            $stt = $dh['TrangThai'];
                            $badge = 'badge-warning';
                            if ($stt === 'Đã giao' || $stt === 'Đã hoàn thành') $badge = 'badge-green';
                            elseif ($stt === 'Đã hủy') $badge = 'badge-danger';
                            
                            $tenKhach = $dh['NguoiNhan'] ?: ($dh['TenKhachHang'] ?: 'Khách lẻ');
                        ?>
                            <tr>
                                <td><strong>#DH<?= str_pad($dh['MaDonHang'], 4, '0', STR_PAD_LEFT) ?></strong></td>
                                <td><?= e($tenKhach) ?></td>
                                <td><?= date('d/m/Y', strtotime($dh['NgayDat'])) ?></td>
                                <td><strong style="color:#16834b;"><?= number_format($dh['TongTien'], 0, ',', '.') ?>đ</strong></td>
                                <td style="text-align:center;">
                                    <span class="category-badge <?= $badge ?>"><?= e($stt) ?></span>
                                </td>
                                <td style="text-align:center;">
                                    <button type="button" class="icon-view-btn" onclick='selectOrder(<?= htmlspecialchars(json_encode($dh), ENT_QUOTES, "UTF-8") ?>)' title="Xem chi tiết">
                                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- KHUNG BẢNG CHI TIẾT & BẢNG XỬ LÝ PHÍA DƯỚI -->
                <div class="order-details-grid">
                    <!-- BẢNG TRÁI: CHI TIẾT ĐƠN HÀNG & SẢN PHẨM ĐÃ ĐẶT -->
                    <div class="detail-card">
                        <h3>Chi tiết đơn hàng <span id="lbl-madh" style="color:#16834b;"></span></h3>
                        <div class="detail-item">
                            <span>Khách hàng</span>
                            <span id="detail-khach">-</span>
                        </div>
                        <div class="detail-item">
                            <span>Điện thoại</span>
                            <span id="detail-sdt">-</span>
                        </div>
                        <div class="detail-item">
                            <span>Địa chỉ giao</span>
                            <span id="detail-diachi">-</span>
                        </div>
                        <div class="detail-item">
                            <span>Thời gian giao dự kiến</span>
                            <span id="detail-ngaygiao" style="color:#16834b;">-</span>
                        </div>

                        <!-- DANH SÁCH SẢN PHẨM CẦN CHUẨN BỊ -->
                        <div class="items-box">
                            <div class="items-box-title">Sản phẩm trong đơn (<span id="detail-item-count">0</span>)</div>
                            <table class="items-mini-table">
                                <thead>
                                    <tr>
                                        <th>Tên nông sản</th>
                                        <th style="text-align:center; width:70px;">SL</th>
                                        <th style="text-align:right; width:100px;">Đơn giá</th>
                                        <th style="text-align:right; width:110px;">Thành tiền</th>
                                    </tr>
                                </thead>
                                <tbody id="detail-items-body">
                                    <tr>
                                        <td colspan="4" style="text-align:center; color:#6b7280; padding:14px;">Chọn một đơn hàng để xem sản phẩm</td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- TÁCH RÕ TẠM TÍNH, PHÍ SHIP VÀ TỔNG THANH TOÁN -->
                            <div class="money-summary">
                                <div class="money-row">
                                    <span>Tạm tính (tiền hàng)</span>
                                    <span id="detail-tamtinh" style="font-weight:600; color:#111827;">-</span>
                                </div>
                                <div class="money-row">
                                    <span id="detail-phiship-label">Phí giao hàng</span>
                                    <span id="detail-phiship" style="font-weight:600; color:#111827;">-</span>
                                </div>
                                <div class="money-row total">
                                    <span>Tổng thanh toán</span>
                                    <span id="detail-tongtien">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- BẢNG PHẢI: XỬ LÝ ĐƠN HÀNG -->
                    <div class="detail-card">
                        <h3>Xử lý đơn hàng</h3>
                        <form method="post" action="">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="madonhang" id="form-madh" value="">
                            
                            <div class="detail-item">
                                <span>Ngày đặt</span>
                                <span id="detail-ngaydat">-</span>
                            </div>
                            <div style="margin-top: 15px;">
                                <label style="display:block; font-size:13px; font-weight:600; margin-bottom:6px;">Trạng thái đơn hàng</label>
                                <select name="trangthai" id="form-trangthai" class="form-select-status">
                                    <option value="Đang xử lý">Đang xử lý</option>
                                    <option value="Chờ xác nhận">Chờ xác nhận</option>
                                    <option value="Đang giao">Đang giao</option>
                                    <option value="Đã giao">Đã giao</option>
                                    <option value="Đã hủy">Đã hủy</option>
                                </select>
                            </div>
                            <div style="display:flex; gap:10px; margin-top:10px;">
                                <button type="submit" class="btn-update">Cập nhật</button>
                                <button type="button" class="btn-cancel" onclick="cancelCurrentOrder()">Hủy đơn</button>
                            </div>
                        </form>

                        <!-- FORM ẨN DÙNG ĐỂ HỦY ĐƠN -->
                        <form id="form-cancel-hidden" method="post" action="">
                            <input type="hidden" name="action" value="cancel_order">
                            <input type="hidden" name="madonhang" id="cancel-madh" value="">
                        </form>
                    </div>
                </div>

            </section>
        </div>
    </div>
</div>

<div class="toast-container">
    <?php if ($msg !== ''): ?><div class="toast-msg success"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($err !== ''): ?><div class="toast-msg error"><?= e($err) ?></div><?php endif; ?>
</div>

<script>
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text ?? '';
    return div.innerHTML;
}

function formatVND(amount) {
    return new Intl.NumberFormat('vi-VN').format(Number(amount) || 0) + 'đ';
}

function formatNgayGiao(rawDate) {
    if (!rawDate) return '-';
    const parts = String(rawDate).split(' ');
    if (parts.length < 2) return rawDate;
    const d = parts[0].split('-');
    const t = parts[1].substring(0, 5);
    if (d.length === 3) {
        return `${t} — ${d[2]}/${d[1]}/${d[0]}`;
    }
    return rawDate;
}

// Hàm xử lý đổ dữ liệu khi bấm nút xem chi tiết
function selectOrder(dh) {
    const formattedId = '#DH' + String(dh.MaDonHang).padStart(4, '0');
    document.getElementById('lbl-madh').innerText = formattedId;
    document.getElementById('detail-khach').innerText = dh.NguoiNhan || dh.TenKhachHang || 'Khách lẻ';
    document.getElementById('detail-sdt').innerText = dh.SoDienThoai || '-';
    document.getElementById('detail-diachi').innerText = dh.DiaChiGiao || '-';
    document.getElementById('detail-ngaygiao').innerText = formatNgayGiao(dh.NgayGiao);
    document.getElementById('detail-ngaydat').innerText = dh.NgayDat || '-';

    // Đổ danh sách sản phẩm trong đơn hàng & tính tạm tính
    const items = Array.isArray(dh.items) ? dh.items : [];
    document.getElementById('detail-item-count').innerText = items.length;
    const tbody = document.getElementById('detail-items-body');

    let tamTinh = 0;
    if (items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; color:#6b7280; padding:14px;">Đơn hàng này chưa có dữ liệu chi tiết sản phẩm</td></tr>';
        tamTinh = Number(dh.TongTien) || 0;
    } else {
        tbody.innerHTML = items.map(item => {
            tamTinh += Number(item.ThanhTien) || 0;
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

    // Tính chênh lệch phí giao hàng / giảm giá
    const tongTien = Number(dh.TongTien) || 0;
    const chenhLech = tongTien - tamTinh;
    const shipLabelEl = document.getElementById('detail-phiship-label');
    const shipValEl   = document.getElementById('detail-phiship');

    document.getElementById('detail-tamtinh').innerText = formatVND(tamTinh);
    document.getElementById('detail-tongtien').innerText = formatVND(tongTien);

    if (chenhLech > 0) {
        shipLabelEl.innerText = 'Phí giao hàng';
        shipValEl.innerText = '+' + formatVND(chenhLech);
        shipValEl.style.color = '#111827';
    } else if (chenhLech < 0) {
        shipLabelEl.innerText = 'Giảm giá / Khuyến mãi';
        shipValEl.innerText = '-' + formatVND(Math.abs(chenhLech));
        shipValEl.style.color = '#dc2626';
    } else {
        shipLabelEl.innerText = 'Phí giao hàng';
        shipValEl.innerText = 'Miễn phí (0đ)';
        shipValEl.style.color = '#16834b';
    }
    
    // Đưa ID và Trạng thái vào Form bên phải
    document.getElementById('form-madh').value = dh.MaDonHang;
    document.getElementById('cancel-madh').value = dh.MaDonHang;
    document.getElementById('form-trangthai').value = dh.TrangThai;
}

// Đóng thông báo Toast sau 3s
setTimeout(() => {
    document.querySelectorAll('.toast-msg').forEach(toast => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(8px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    });
}, 3000);

// Xử lý khi bấm nút Hủy đơn
function cancelCurrentOrder() {
    const madh = document.getElementById('form-madh').value;
    if (!madh) {
        alert('Vui lòng chọn một đơn hàng trước!');
        return;
    }
    if (confirm('Bạn có chắc chắn muốn hủy đơn hàng này không?')) {
        document.getElementById('form-cancel-hidden').submit();
    }
}

// Tự động chọn đơn hàng đầu tiên khi vừa vào trang
document.addEventListener('DOMContentLoaded', function() {
    const firstOrder = <?= !empty($ds_donhang) ? json_encode($ds_donhang[0]) : 'null' ?>;
    if (firstOrder) {
        selectOrder(firstOrder);
    }
});
</script>
</body>
</html>