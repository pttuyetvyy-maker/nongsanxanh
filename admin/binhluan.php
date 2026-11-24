<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

function e($val) { return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8'); }

// 1. TỰ ĐỘNG THÊM CỘT TraLoiAdmin VÀ TrangThai NẾU CSDL THIẾU
$cols = $conn->query("SHOW COLUMNS FROM binhluan")->fetch_all(MYSQLI_ASSOC);
$hasReply = false; $hasStatus = false;
foreach ($cols as $c) {
    if ($c['Field'] === 'TraLoiAdmin') $hasReply = true;
    if ($c['Field'] === 'TrangThai') $hasStatus = true;
}
if (!$hasReply)  $conn->query("ALTER TABLE binhluan ADD COLUMN TraLoiAdmin TEXT NULL");
if (!$hasStatus) $conn->query("ALTER TABLE binhluan ADD COLUMN TrangThai TINYINT(1) DEFAULT 1");

$msg = ""; $err = "";

// 2. XỬ LÝ CẬP NHẬT TRỰC TIẾP VÀO MYSQL
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $mabl   = (int)($_POST['mabinhluan'] ?? 0);

    if ($mabl > 0) {
        if ($action === 'save_reply') {
            $reply  = trim($_POST['traloiadmin'] ?? '');
            $status = (int)($_POST['trangthai'] ?? 1);
            
            $stmt = $conn->prepare("UPDATE binhluan SET TraLoiAdmin = ?, TrangThai = ? WHERE MaBinhLuan = ?");
            $stmt->bind_param("sii", $reply, $status, $mabl);
            if ($stmt->execute()) {
                $msg = "Cập nhật phản hồi bình luận #BL" . str_pad($mabl, 4, '0', STR_PAD_LEFT) . " thành công!";
            } else {
                $err = "Lỗi khi lưu phản hồi!";
            }
        } 
        elseif ($action === 'delete_comment') {
            $stmt = $conn->prepare("DELETE FROM binhluan WHERE MaBinhLuan = ?");
            $stmt->bind_param("i", $mabl);
            if ($stmt->execute()) {
                $msg = "Đã xóa bình luận #BL" . str_pad($mabl, 4, '0', STR_PAD_LEFT) . " thành công!";
            } else {
                $err = "Lỗi khi xóa bình luận!";
            }
        }
    }
}

// 3. LẤY DANH SÁCH BÌNH LUẬN TỪ CSDL
$sql = "SELECT b.MaBinhLuan, b.NoiDung, b.TraLoiAdmin, b.TrangThai, b.NgayDanhGia, 
               COALESCE(u.fullname, 'Khách hàng') AS TenKhach, 
               s.TenSP 
        FROM binhluan b 
        LEFT JOIN users u ON b.MaND = u.MaND 
        LEFT JOIN sanpham s ON b.MaSP = s.MaSP 
        ORDER BY b.MaBinhLuan DESC";
$ds_binhluan = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Nông sản Xanh — Quản lý bình luận</title>
    <link rel="stylesheet" href="../themes/styleadmin.css?v=1">
    <link rel="stylesheet" href="../themes/category-fix.css?v=1">
    <style>
        .category-table { width:100%; border-collapse:collapse; font-size:14px; }
        .category-table th { padding:14px 16px; text-align:left; background:#eaf4ed; color:#1e3a29; font-weight:700; }
        .category-table td { padding:14px 16px; border-bottom:1px solid #edf2f0; vertical-align:middle; }
        
        .category-badge { display:inline-block; padding:4px 10px; border-radius:20px; font-size:12px; font-weight:600; }
        .badge-green { background:#e5f2e9; color:#087443; }
        .badge-danger { background:#fee2e2; color:#991b1b; }
        
        .comment-details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
        .detail-card { background: #fff; padding: 20px; border-radius: 10px; border: 1px solid #edf2f0; box-shadow: 0 2px 8px rgba(0,0,0,0.03); }
        .detail-card h3 { margin-top: 0; color: #1e3a29; font-size: 16px; border-bottom: 2px solid #eaf4ed; padding-bottom: 10px; }
        .detail-item { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14px; }
        .detail-item span:first-child { color: #6b7280; font-weight: 500; }
        .detail-item span:last-child { font-weight: 600; color: #111827; }
        
        .form-textarea { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; box-sizing: border-box; min-height: 90px; margin-bottom: 12px; font-family: inherit; }
        .form-select-status { width: 100%; padding: 9px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; margin-bottom: 15px; }
        
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
            <div class="topbar-title">Bình luận<span>Phản hồi & duyệt đánh giá sản phẩm</span></div>
            <div class="topbar-right"><div class="admin-chip"><div class="avatar-dot">TV</div><span>Admin</span></div></div>
        </div>

        <div class="content">
            <section class="panel active">
                
                <!-- BẢNG DANH SÁCH BÌNH LUẬN -->
                <div class="table-card">
                    <table class="category-table">
                        <thead>
                            <tr>
                                <th>Mã BL</th>
                                <th>Khách hàng</th>
                                <th>Sản phẩm</th>
                                <th>Nội dung đánh giá</th>
                                <th style="text-align:center;">Trạng thái</th>
                                <th style="text-align:center;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($ds_binhluan as $bl): 
                            $tt = ($bl['TrangThai'] ?? 1) == 1;
                        ?>
                            <tr>
                                <td><strong>#BL<?= str_pad($bl['MaBinhLuan'], 4, '0', STR_PAD_LEFT) ?></strong></td>
                                <td><?= e($bl['TenKhach']) ?></td>
                                <td><strong style="color:#16834b;"><?= e($bl['TenSP'] ?? 'Sản phẩm đã xóa') ?></strong></td>
                                <td><?= e(mb_strimwidth($bl['NoiDung'], 0, 45, '...')) ?></td>
                                <td style="text-align:center;">
                                    <span class="category-badge <?= $tt ? 'badge-green' : 'badge-danger' ?>">
                                        <?= $tt ? 'Hiển thị' : 'Đã ẩn' ?>
                                    </span>
                                </td>
                                <td style="text-align:center;">
                                    <button type="button" class="icon-view-btn" onclick='selectComment(<?= json_encode($bl) ?>)' title="Xem & Trả lời">👁️</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- BẢNG CHI TIẾT & BẢNG XỬ LÝ NẰM PHÍA DƯỚI -->
                <div class="comment-details-grid">
                    <!-- BẢNG TRÁI: CHI TIẾT BÌNH LUẬN -->
                    <div class="detail-card">
                        <h3>Chi tiết bình luận <span id="lbl-mabl" style="color:#16834b;"></span></h3>
                        <div class="detail-item">
                            <span>Khách hàng</span>
                            <span id="detail-khach">-</span>
                        </div>
                        <div class="detail-item">
                            <span>Sản phẩm</span>
                            <span id="detail-sanpham" style="color:#16834b;">-</span>
                        </div>
                        <div class="detail-item">
                            <span>Ngày đánh giá</span>
                            <span id="detail-ngay">-</span>
                        </div>
                        <div style="margin-top:10px;">
                            <span style="color: #6b7280; font-weight: 500; font-size:14px; display:block; margin-bottom:4px;">Nội dung bình luận:</span>
                            <div id="detail-noidung" style="background:#f9fafb; padding:10px; border-radius:6px; border:1px solid #e5e7eb; font-size:14px; color:#1f2937;">-</div>
                        </div>
                    </div>

                    <!-- BẢNG PHẢI: XỬ LÝ & PHẢN HỒI -->
                    <div class="detail-card">
                        <h3>Xử lý & Trả lời bình luận</h3>
                        <form method="post" action="">
                            <input type="hidden" name="action" value="save_reply">
                            <input type="hidden" name="mabinhluan" id="form-mabl" value="">
                            
                            <div style="margin-bottom: 10px;">
                                <label style="display:block; font-size:13px; font-weight:600; margin-bottom:6px;">Nội dung phản hồi (Admin)</label>
                                <textarea name="traloiadmin" id="form-traloi" class="form-textarea" placeholder="Nhập câu trả lời của Admin..."></textarea>
                            </div>
                            <div>
                                <label style="display:block; font-size:13px; font-weight:600; margin-bottom:6px;">Trạng thái hiển thị</label>
                                <select name="trangthai" id="form-trangthai" class="form-select-status">
                                    <option value="1">Hiển thị bình luận</option>
                                    <option value="0">Ẩn bình luận này</option>
                                </select>
                            </div>
                            <div style="display:flex; gap:10px; margin-top:10px;">
                                <button type="submit" class="btn-update">Lưu phản hồi</button>
                                <button type="button" class="btn-cancel" onclick="deleteCurrentComment()">Xóa bình luận</button>
                            </div>
                        </form>

                        <!-- FORM ẨN DÙNG ĐỂ XÓA -->
                        <form id="form-delete-hidden" method="post" action="">
                            <input type="hidden" name="action" value="delete_comment">
                            <input type="hidden" name="mabinhluan" id="delete-mabl" value="">
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
// Hàm chọn bình luận khi bấm nút 👁️
function selectComment(bl) {
    const formattedId = '#BL' + String(bl.MaBinhLuan).padStart(4, '0');
    document.getElementById('lbl-mabl').innerText = formattedId;
    document.getElementById('detail-khach').innerText = bl.TenKhach || 'Khách hàng';
    document.getElementById('detail-sanpham').innerText = bl.TenSP || 'Sản phẩm đã xóa';
    document.getElementById('detail-ngay').innerText = bl.NgayDanhGia || '-';
    document.getElementById('detail-noidung').innerText = bl.NoiDung || '';
    
    // Đưa thông tin sang Form bên phải
    document.getElementById('form-mabl').value = bl.MaBinhLuan;
    document.getElementById('delete-mabl').value = bl.MaBinhLuan;
    document.getElementById('form-traloi').value = bl.TraLoiAdmin || '';
    document.getElementById('form-trangthai').value = bl.TrangThai ?? 1;
}

// Tự đóng thông báo Toast
setTimeout(() => {
    document.querySelectorAll('.toast-msg').forEach(toast => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(8px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    });
}, 3000);

// Xử lý khi bấm nút Xóa bình luận
function deleteCurrentComment() {
    const mabl = document.getElementById('form-mabl').value;
    if (!mabl) {
        alert('Vui lòng bấm nút 👁️ để chọn bình luận cần xóa!');
        return;
    }
    if (confirm('Bạn có chắc muốn xóa vĩnh viễn bình luận này?')) {
        document.getElementById('form-delete-hidden').submit();
    }
}

// Tự chọn bình luận đầu tiên khi vừa tải trang
document.addEventListener('DOMContentLoaded', function() {
    const firstComment = <?= !empty($ds_binhluan) ? json_encode($ds_binhluan[0]) : 'null' ?>;
    if (firstComment) {
        selectComment(firstComment);
    }
});
</script>
</body>
</html>