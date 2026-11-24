<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

function e($val) { return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8'); }

function getAvatarText($name) {
    $parts = explode(' ', trim($name));
    if (count($parts) >= 2) {
        $first = mb_substr($parts[0], 0, 1, 'UTF-8');
        $last  = mb_substr(end($parts), 0, 1, 'UTF-8');
        return mb_strtoupper($first . $last, 'UTF-8');
    }
    return mb_strtoupper(mb_substr($name, 0, 2, 'UTF-8'), 'UTF-8');
}

$msg = ""; $err = "";

// 1. XỬ LÝ LƯU TRỰC TIẾP VÀO CSDL (KHÔNG CẦN CHỈNH CODE BẢNG USER NỮA)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // THÊM NGƯỜI DÙNG MỚI
    if ($action === 'add') {
        $fullname     = trim($_POST['fullname'] ?? '');
        $email        = trim($_POST['email'] ?? '');
        $phone        = trim($_POST['phone'] ?? '');
        $password_raw = trim($_POST['password'] ?? '');
        $account_type = trim($_POST['account_type'] ?? 'customer');
        $status       = trim($_POST['status'] ?? 'Hoạt động');

        if (empty($fullname) || empty($email) || empty($password_raw)) {
            $err = "Vui lòng nhập đầy đủ các thông tin bắt buộc!";
        } else {
            // Kiểm tra trùng Email
            $check = $conn->prepare("SELECT 1 FROM users WHERE email = ? LIMIT 1");
            $check->bind_param("s", $email);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $err = "Email này đã được sử dụng!";
            } else {
                $password = hash('sha256', $password_raw);
                $stmt = $conn->prepare("INSERT INTO users (fullname, email, phone, password, account_type, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $fullname, $email, $phone, $password, $account_type, $status);
                if ($stmt->execute()) {
                    $msg = "Thêm người dùng mới thành công!";
                } else {
                    $err = "Lỗi CSDL: " . $conn->error;
                }
            }
        }
    }
    // CẬP NHẬT THÔNG TIN NGƯỜI DÙNG
    elseif ($action === 'edit') {
        $mand         = (int)($_POST['mand'] ?? 0);
        $fullname     = trim($_POST['fullname'] ?? '');
        $email        = trim($_POST['email'] ?? '');
        $phone        = trim($_POST['phone'] ?? '');
        $account_type = trim($_POST['account_type'] ?? 'customer');
        $status       = trim($_POST['status'] ?? 'Hoạt động');

        if ($mand > 0) {
            $stmt = $conn->prepare("UPDATE users SET fullname=?, email=?, phone=?, account_type=?, status=? WHERE MaND=?");
            $stmt->bind_param("sssssi", $fullname, $email, $phone, $account_type, $status, $mand);
            if ($stmt->execute()) {
                $msg = "Cập nhật tài khoản #ND" . str_pad($mand, 4, '0', STR_PAD_LEFT) . " thành công!";
            } else {
                $err = "Lỗi khi cập nhật!";
            }
        }
    }
    // XÓA NGƯỜI DÙNG
    elseif ($action === 'delete') {
        $mand = (int)($_POST['mand'] ?? 0);
        if ($mand > 0) {
            $stmt = $conn->prepare("DELETE FROM users WHERE MaND=?");
            $stmt->bind_param("i", $mand);
            if ($stmt->execute()) {
                $msg = "Đã xóa người dùng thành công!";
            } else {
                $err = "Lỗi khi xóa (Có thể người dùng này đã từng đặt hàng)!";
            }
        }
    }
}

// 2. LẤY DANH SÁCH NGƯỜI DÙNG CHUẨN THEO KHÓA MaND
$sql = "SELECT * FROM users ORDER BY MaND DESC";
$ds_users = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Nông sản Xanh — Quản lý người dùng</title>
    <link rel="stylesheet" href="../themes/styleadmin.css?v=1">
    <link rel="stylesheet" href="../themes/category-fix.css?v=1">
    <style>
        .category-table { width:100%; border-collapse:collapse; font-size:14px; }
        .category-table th { padding:14px 16px; text-align:left; background:#eaf4ed; color:#1e3a29; font-weight:700; }
        .category-table td { padding:14px 16px; border-bottom:1px solid #edf2f0; vertical-align:middle; }
        
        .user-avatar { width: 36px; height: 36px; border-radius: 50%; color: #fff; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; background: #15803d; }
        .category-badge { display:inline-block; padding:4px 10px; border-radius:20px; font-size:12px; font-weight:600; }
        .badge-green { background:#e5f2e9; color:#087443; } .badge-danger { background:#fee2e2; color:#991b1b; }
        
        .add-card-box { background: #fff; border: 2px solid #16834b; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .form-grid-inputs { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 12px; margin-top: 10px; }
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
            <div class="topbar-title">Người dùng<span>Quản lý danh sách người dùng</span></div>
            <div class="topbar-right"><div class="admin-chip"><div class="avatar-dot">TV</div><span>Admin</span></div></div>
        </div>

        <div class="content">
            <section class="panel active">
                
                <!-- KHUNG THÊM NGƯỜI DÙNG MỚI -->
                <div class="add-card-box">
                    <h3 style="margin:0; color:#16834b;">👤 Thêm người dùng mới</h3>
                    <form method="post" action="">
                        <input type="hidden" name="action" value="add">
                        <div class="form-grid-inputs">
                            <div>
                                <label style="font-size:12px; font-weight:600;">Họ tên *</label>
                                <input type="text" name="fullname" placeholder="Nhập họ tên" required>
                            </div>
                            <div>
                                <label style="font-size:12px; font-weight:600;">Email *</label>
                                <input type="email" name="email" placeholder="Nhập email" required>
                            </div>
                            <div>
                                <label style="font-size:12px; font-weight:600;">Số điện thoại</label>
                                <input type="text" name="phone" placeholder="Nhập SĐT">
                            </div>
                            <div>
                                <label style="font-size:12px; font-weight:600;">Mật khẩu *</label>
                                <input type="password" name="password" placeholder="Nhập mật khẩu" required>
                            </div>
                            <div>
                                <label style="font-size:12px; font-weight:600;">Vai trò *</label>
                                <select name="account_type" required>
                                    <option value="customer">Khách hàng (customer)</option>
                                    <option value="admin">Quản trị viên (admin)</option>
                                </select>
                            </div>
                            <div>
                                <label style="font-size:12px; font-weight:600;">Trạng thái *</label>
                                <select name="status">
                                    <option value="Hoạt động">Hoạt động</option>
                                    <option value="Khoá">Khoá</option>
                                </select>
                            </div>
                        </div>
                        <div style="margin-top:14px; text-align:right;">
                            <button type="submit" class="btn-save-db">+ Lưu Người Dùng Vào MySQL</button>
                        </div>
                    </form>
                </div>

                <!-- BẢNG DANH SÁCH NGƯỜI DÙNG SỬA TRỰC TIẾP -->
                <div class="table-card">
                    <table class="category-table">
                        <thead>
                            <tr>
                                <th style="width:6%; text-align:center;">ID</th>
                                <th style="width:6%; text-align:center;">Ảnh</th>
                                <th style="width:20%">Họ tên</th>
                                <th style="width:22%">Email</th>
                                <th style="width:14%">Số điện thoại</th>
                                <th style="width:12%">Vai trò</th>
                                <th style="text-align:center;">Trạng thái</th>
                                <th style="text-align:center;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($ds_users as $u): 
                            $mand     = $u['MaND'];
                            $fullname = $u['fullname'] ?? '';
                            $email    = $u['email'] ?? '';
                            $phone    = $u['phone'] ?? '';
                            $role     = $u['account_type'] ?? 'customer';
                            $status   = $u['status'] ?? 'Hoạt động';
                        ?>
                            <tr>
                                <td style="text-align:center;"><strong>#<?= $mand ?></strong></td>
                                <td style="text-align:center;"><div class="user-avatar"><?= getAvatarText($fullname) ?></div></td>
                                
                                <form method="post" action="" style="display:contents;">
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="mand" value="<?= $mand ?>">
                                    <td><input type="text" name="fullname" value="<?= e($fullname) ?>" style="padding:4px 8px; border:1px solid #ccc; border-radius:4px; width:90%;" required></td>
                                    <td><input type="email" name="email" value="<?= e($email) ?>" style="padding:4px 8px; border:1px solid #ccc; border-radius:4px; width:90%;" required></td>
                                    <td><input type="text" name="phone" value="<?= e($phone) ?>" style="padding:4px 8px; border:1px solid #ccc; border-radius:4px; width:90%;"></td>
                                    <td>
                                        <select name="account_type" style="padding:4px; border-radius:4px;">
                                            <option value="customer" <?= $role === 'customer' ? 'selected' : '' ?>>customer</option>
                                            <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>admin</option>
                                        </select>
                                    </td>
                                    <td style="text-align:center;">
                                        <select name="status" style="padding:4px; border-radius:4px;">
                                            <option value="Hoạt động" <?= $status === 'Hoạt động' ? 'selected' : '' ?>>Hoạt động</option>
                                            <option value="Khoá" <?= $status === 'Khoá' ? 'selected' : '' ?>>Khoá</option>
                                        </select>
                                    </td>
                                    <td style="text-align:center;">
                                        <button type="submit" style="padding:5px 10px; background:#3b82f6; color:#fff; border:none; border-radius:4px; cursor:pointer; font-weight:bold;">Lưu</button>
                                </form>
                                        <form method="post" action="" onsubmit="return confirm('Bạn chắc chắn muốn xóa người dùng này?');" style="display:inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="mand" value="<?= $mand ?>">
                                            <button type="submit" style="border:none; background:none; cursor:pointer; font-size:16px; margin-left:4px;" title="Xóa">🗑️</button>
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