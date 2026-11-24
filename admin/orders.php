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
        
        .order-details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
        .detail-card { background: #fff; padding: 20px; border-radius: 10px; border: 1px solid #edf2f0; box-shadow: 0 2px 8px rgba(0,0,0,0.03); }
        .detail-card h3 { margin-top: 0; color: #1e3a29; font-size: 16px; border-bottom: 2px solid #eaf4ed; padding-bottom: 10px; }
        .detail-item { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14px; }
        .detail-item span:first-child { color: #6b7280; font-weight: 500; }
        .detail-item span:last-child { font-weight: 600; color: #111827; }
        
        .form-select-status { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; margin-bottom: 15px; }
        .btn-update { background: #16834b; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .btn-cancel { background: #dc2626; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; }
        
        .icon-view-btn { padding: 6px 10px; border: 1px solid #d1d5db; background: #fff; border-radius: 6px; cursor: pointer; }
        .icon-view-btn:hover { background: #f3f7f4; }
        
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
                            if ($stt === 'Đã giao') $badge = 'badge-green';
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
                                    <button type="button" class="icon-view-btn" onclick='selectOrder(<?= json_encode($dh) ?>)' title="Xem chi tiết">👁️</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- KHUNG BẢNG CHI TIẾT & BẢNG XỬ LÝ PHÍA DƯỚI -->
                <div class="order-details-grid">
                    <!-- BẢNG TRÁI: CHI TIẾT ĐƠN HÀNG -->
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
                            <span>Tổng tiền</span>
                            <span id="detail-tongtien" style="color:#16834b; font-size:16px;">-</span>
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
// Hàm xử lý đổ dữ liệu khi bấm nút 👁️
function selectOrder(dh) {
    const formattedId = '#DH' + String(dh.MaDonHang).padStart(4, '0');
    document.getElementById('lbl-madh').innerText = formattedId;
    document.getElementById('detail-khach').innerText = dh.NguoiNhan || dh.TenKhachHang || 'Khách lẻ';
    document.getElementById('detail-sdt').innerText = dh.SoDienThoai || '-';
    document.getElementById('detail-diachi').innerText = dh.DiaChiGiao || '-';
    
    const tongTien = new Intl.NumberFormat('vi-VN').format(dh.TongTien) + 'đ';
    document.getElementById('detail-tongtien').innerText = tongTien;
    
    document.getElementById('detail-ngaydat').innerText = dh.NgayDat || '-';
    
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
        alert('Vui lòng chọn một đơn hàng bằng cách bấm nút 👁️ trước!');
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