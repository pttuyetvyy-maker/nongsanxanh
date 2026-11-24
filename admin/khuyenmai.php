<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

function e($val) { return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8'); }

// Tự tạo cột TrangThai nếu CSDL thiếu
$checkCol = $conn->query("SHOW COLUMNS FROM khuyenmai LIKE 'TrangThai'");
if ($checkCol && $checkCol->num_rows == 0) {
    $conn->query("ALTER TABLE khuyenmai ADD COLUMN TrangThai TINYINT(1) DEFAULT 1");
}

$msg = ""; $err = "";

// XỬ LÝ LƯU TRỰC TIẾP VÀO DATABASE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $makm  = strtoupper(trim($_POST['makm'] ?? ''));
        $tenkm = trim($_POST['tenkm'] ?? '');
        $giam  = (int)($_POST['phantramgiam'] ?? 0);
        $from  = $_POST['ngaybatdau'] ?? '';
        $to    = $_POST['ngayketthuc'] ?? '';
        $tt    = (int)($_POST['trangthai'] ?? 1);

        if (empty($makm) || empty($tenkm) || $giam <= 0 || empty($from) || empty($to)) {
            $err = "Vui lòng nhập đầy đủ thông tin!";
        } else {
            $check = $conn->prepare("SELECT 1 FROM khuyenmai WHERE MaKM = ? LIMIT 1");
            $check->bind_param("s", $makm);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $err = "Mã KM này đã tồn tại!";
            } else {
                $stmt = $conn->prepare("INSERT INTO khuyenmai (MaKM, TenKM, PhanTramGiam, NgayBatDau, NgayKetThuc, TrangThai) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssissi", $makm, $tenkm, $giam, $from, $to, $tt);
                if ($stmt->execute()) { $msg = "Thêm mã khuyến mãi thành công!"; }
                else { $err = "Lỗi CSDL: " . $conn->error; }
            }
        }
    }
    elseif ($action === 'edit') {
        $makm  = trim($_POST['makm'] ?? '');
        $tenkm = trim($_POST['tenkm'] ?? '');
        $giam  = (int)($_POST['phantramgiam'] ?? 0);
        $from  = $_POST['ngaybatdau'] ?? '';
        $to    = $_POST['ngayketthuc'] ?? '';
        $tt    = (int)($_POST['trangthai'] ?? 1);

        $stmt = $conn->prepare("UPDATE khuyenmai SET TenKM=?, PhanTramGiam=?, NgayBatDau=?, NgayKetThuc=?, TrangThai=? WHERE MaKM=?");
        $stmt->bind_param("sissis", $tenkm, $giam, $from, $to, $tt, $makm);
        if ($stmt->execute()) { $msg = "Cập nhật khuyến mãi thành công!"; }
        else { $err = "Lỗi: " . $conn->error; }
    }
    elseif ($action === 'delete') {
        $makm = trim($_POST['makm'] ?? '');
        $stmt = $conn->prepare("DELETE FROM khuyenmai WHERE MaKM=?");
        $stmt->bind_param("s", $makm);
        if ($stmt->execute()) { $msg = "Đã xóa khuyến mãi thành công!"; }
        else { $err = "Lỗi xóa: " . $conn->error; }
    }
}

$ds_km = $conn->query("SELECT * FROM khuyenmai ORDER BY NgayBatDau DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Nông sản Xanh — Quản lý khuyến mãi</title>
    <link rel="stylesheet" href="../themes/styleadmin.css?v=1">
    <link rel="stylesheet" href="../themes/category-fix.css?v=1">
    <style>
        .category-table { width:100%; border-collapse:collapse; font-size:14px; }
        .category-table th { padding:14px 16px; text-align:left; background:#eaf4ed; color:#1e3a29; font-weight:700; }
        .category-table td { padding:14px 16px; border-bottom:1px solid #edf2f0; vertical-align:middle; }
        .add-card-box { background: #fff; border: 2px solid #16834b; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .form-grid-inputs { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-top: 10px; }
        .form-grid-inputs input, .form-grid-inputs select { 
            width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; box-sizing: border-box; background: #fff !important;
        }
        .btn-save-db { background: #16834b; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; }
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
            <div class="topbar-title">Khuyến mãi<span>Quản lý chương trình & mã ưu đãi</span></div>
            <div class="topbar-right"><div class="admin-chip"><div class="avatar-dot">TV</div><span>Admin</span></div></div>
        </div>

        <div class="content">
            <section class="panel active">
                
                <!-- KHUNG TẠO MỚI -->
                <div class="add-card-box">
                    <h3 style="margin:0; color:#16834b;">🎁 Thêm khuyến mãi mới</h3>
                    <form method="post" action="">
                        <input type="hidden" name="action" value="add">
                        <div class="form-grid-inputs">
                            <div>
                                <label style="font-size:12px; font-weight:600;">Mã KM *</label>
                                <input type="text" name="makm" placeholder="VD: KM10" required>
                            </div>
                            <div>
                                <label style="font-size:12px; font-weight:600;">Tên KM *</label>
                                <input type="text" name="tenkm" placeholder="Tên chương trình" required>
                            </div>
                            <div>
                                <label style="font-size:12px; font-weight:600;">% Giảm *</label>
                                <input type="number" name="phantramgiam" min="1" max="100" placeholder="% giảm" required>
                            </div>
                            <div>
                                <label style="font-size:12px; font-weight:600;">Từ ngày *</label>
                                <input type="datetime-local" name="ngaybatdau" required>
                            </div>
                            <div>
                                <label style="font-size:12px; font-weight:600;">Đến ngày *</label>
                                <input type="datetime-local" name="ngayketthuc" required>
                            </div>
                            <div>
                                <label style="font-size:12px; font-weight:600;">Trạng thái *</label>
                                <select name="trangthai">
                                    <option value="1">Hiển thị</option>
                                    <option value="0">Ẩn mã</option>
                                </select>
                            </div>
                        </div>
                        <div style="margin-top:14px; text-align:right;">
                            <button type="submit" class="btn-save-db">+ Lưu Khuyến Mãi Vào CSDL</button>
                        </div>
                    </form>
                </div>

                <!-- BẢNG DANH SÁCH -->
                <div class="table-card">
                    <table class="category-table">
                        <thead>
                            <tr>
                                <th>Mã KM</th>
                                <th>Tên KM</th>
                                <th>% Giảm</th>
                                <th>Ngày bắt đầu</th>
                                <th>Ngày kết thúc</th>
                                <th style="text-align:center;">Trạng thái</th>
                                <th style="text-align:center;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($ds_km as $km): 
                            $tt = ($km['TrangThai'] ?? 1) == 1;
                        ?>
                            <tr>
                                <td><strong><?= e($km['MaKM']) ?></strong></td>
                                
                                <form method="post" action="" style="display:contents;">
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="makm" value="<?= e($km['MaKM']) ?>">
                                    <td><input type="text" name="tenkm" value="<?= e($km['TenKM']) ?>" style="padding:4px 8px; border:1px solid #ccc; border-radius:4px;" required></td>
                                    <td><input type="number" name="phantramgiam" value="<?= $km['PhanTramGiam'] ?>" style="width:55px; padding:4px 8px; border:1px solid #ccc; border-radius:4px;" required>%</td>
                                    <td><input type="datetime-local" name="ngaybatdau" value="<?= date('Y-m-d\TH:i', strtotime($km['NgayBatDau'])) ?>" style="padding:4px;" required></td>
                                    <td><input type="datetime-local" name="ngayketthuc" value="<?= date('Y-m-d\TH:i', strtotime($km['NgayKetThuc'])) ?>" style="padding:4px;" required></td>
                                    <td style="text-align:center;">
                                        <select name="trangthai" style="padding:4px; border-radius:4px;">
                                            <option value="1" <?= $tt ? 'selected' : '' ?>>Hiển thị</option>
                                            <option value="0" <?= !$tt ? 'selected' : '' ?>>Ẩn mã</option>
                                        </select>
                                    </td>
                                    <td style="text-align:center;">
                                        <button type="submit" style="padding:5px 10px; background:#3b82f6; color:#fff; border:none; border-radius:4px; cursor:pointer; font-weight:bold;">Lưu</button>
                                </form>
                                        <form method="post" action="" onsubmit="return confirm('Bạn có chắc muốn xóa mã KM này?');" style="display:inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="makm" value="<?= e($km['MaKM']) ?>">
                                            <button type="submit" style="border:none; background:none; cursor:pointer; font-size:16px;" title="Xóa">🗑️</button>
                                        </form>
                                    </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</div>

<!-- KÍNH THÔNG BÁO THÀNH CÔNG HỆ THỐNG -->
<div class="toast-container">
    <?php if ($msg !== ''): ?><div class="toast-msg success"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($err !== ''): ?><div class="toast-msg error"><?= e($err) ?></div><?php endif; ?>
</div>

<script>
setTimeout(() => {
    document.querySelectorAll('.toast-msg').forEach(toast => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(8px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    });
}, 3000);
</script>
</body>
</html>