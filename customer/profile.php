<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$msg = "";
$err = "";

// Xử lý cập nhật thông tin cá nhân & đổi mật khẩu theo đúng cột bảng users
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $fullname = trim($_POST['fullname'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');

        if ($fullname === '') {
            $err = "Họ và tên không được để trống!";
        } elseif ($phone === '') {
            $err = "Số điện thoại không được để trống!";
        } else {
            $stmtUp = $conn->prepare("UPDATE users SET fullname = ?, phone = ? WHERE MaND = ?");
            $stmtUp->bind_param("ssi", $fullname, $phone, $user_id);
            if ($stmtUp->execute()) {
                $_SESSION['fullname']  = $fullname;
                $_SESSION['user_name'] = $fullname;
                $msg = "Cập nhật thông tin tài khoản thành công!";
            } else {
                $err = "Có lỗi xảy ra khi cập nhật thông tin!";
            }
            $stmtUp->close();
        }
    } elseif ($action === 'change_password') {
        $old_pass     = trim($_POST['old_password'] ?? '');
        $new_pass     = trim($_POST['new_password'] ?? '');
        $confirm_pass = trim($_POST['confirm_password'] ?? '');

        $stmtCheck = $conn->prepare("SELECT password FROM users WHERE MaND = ? LIMIT 1");
        $stmtCheck->bind_param("i", $user_id);
        $stmtCheck->execute();
        $rowUser = $stmtCheck->get_result()->fetch_assoc();
        $stmtCheck->close();

        $dbPass = $rowUser['password'] ?? '';
        $isOldValid = (hash('sha256', $old_pass) === $dbPass) || ($old_pass === $dbPass);

        if (!$rowUser || !$isOldValid) {
            $err = "Mật khẩu hiện tại không chính xác!";
        } elseif (strlen($new_pass) < 8) {
            $err = "Mật khẩu mới phải có ít nhất 8 ký tự!";
        } elseif ($new_pass !== $confirm_pass) {
            $err = "Mật khẩu xác nhận không khớp!";
        } else {
            $newHash = hash('sha256', $new_pass);
            $stmtPass = $conn->prepare("UPDATE users SET password = ? WHERE MaND = ?");
            $stmtPass->bind_param("si", $newHash, $user_id);
            if ($stmtPass->execute()) {
                $msg = "Đổi mật khẩu thành công!";
            } else {
                $err = "Lỗi khi đổi mật khẩu!";
            }
            $stmtPass->close();
        }
    }
}

// Lấy thông tin thật từ bảng users
$stmtUser = $conn->prepare("SELECT MaND, fullname, email, phone, account_type, created_at, status FROM users WHERE MaND = ? LIMIT 1");
$stmtUser->bind_param("i", $user_id);
$stmtUser->execute();
$user = $stmtUser->get_result()->fetch_assoc();
$stmtUser->close();

if (!$user) {
    header("Location: logout.php");
    exit();
}

// Lấy địa chỉ giao hàng gần nhất từ bảng donhang (nếu khách từng đặt hàng)
$diaChiGanNhat = "Chưa có địa chỉ (sẽ lưu khi bạn đặt đơn hàng đầu tiên)";
$stmtAddr = $conn->prepare("SELECT DiaChiGiao FROM donhang WHERE MaND = ? AND DiaChiGiao IS NOT NULL AND DiaChiGiao != '' ORDER BY MaDonHang DESC LIMIT 1");
if ($stmtAddr) {
    $stmtAddr->bind_param("i", $user_id);
    $stmtAddr->execute();
    $resAddr = $stmtAddr->get_result()->fetch_assoc();
    if (!empty($resAddr['DiaChiGiao'])) {
        $diaChiGanNhat = $resAddr['DiaChiGiao'];
    }
    $stmtAddr->close();
}

// Thống kê số đơn hàng & tổng chi tiêu thật từ bảng donhang
$stats = ['tong_don' => 0, 'don_hoan_thanh' => 0, 'tong_chi' => 0];
$stmtStat = $conn->prepare("
    SELECT 
        COUNT(*) AS tong_don,
        SUM(CASE WHEN TrangThai IN ('Đã giao', 'Đã hoàn thành') THEN 1 ELSE 0 END) AS don_hoan_thanh,
        COALESCE(SUM(CASE WHEN TrangThai != 'Đã hủy' THEN TongTien ELSE 0 END), 0) AS tong_chi
    FROM donhang 
    WHERE MaND = ?
");
if ($stmtStat) {
    $stmtStat->bind_param("i", $user_id);
    $stmtStat->execute();
    $resStat = $stmtStat->get_result()->fetch_assoc();
    if ($resStat) {
        $stats = $resStat;
    }
    $stmtStat->close();
}

require_once __DIR__ . '/header.php';
?>

<style>
    .pf-container {
        width: 88%;
        max-width: 1160px;
        margin: 105px auto 60px;
    }
    .pf-title {
        font-size: 28px;
        color: #1e3a29;
        margin: 0 0 6px 0;
        font-weight: 700;
    }
    .pf-subtitle {
        color: #6b7280;
        font-size: 14px;
        margin: 0 0 26px 0;
    }
    .pf-layout {
        display: grid;
        grid-template-columns: 270px 1fr;
        gap: 24px;
        align-items: start;
    }
    .pf-sidebar {
        background: #fff;
        padding: 28px 20px;
        border-radius: 12px;
        text-align: center;
        border: 1px solid #e5e7eb;
        box-shadow: 0 2px 10px rgba(0,0,0,0.03);
    }
    .pf-avatar {
        width: 84px;
        height: 84px;
        margin: 0 auto;
        background: #eaf5e7;
        color: #16834b;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .pf-sidebar h2 {
        margin: 14px 0 6px 0;
        font-size: 18px;
        color: #111827;
    }
    .pf-role-tag {
        display: inline-block;
        color: #16834b;
        background: #eaf5e7;
        font-size: 12px;
        font-weight: 600;
        padding: 4px 12px;
        border-radius: 999px;
        margin-bottom: 22px;
    }
    .pf-menu {
        display: flex;
        flex-direction: column;
        gap: 6px;
        text-align: left;
    }
    .pf-menu button,
    .pf-menu a {
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
        padding: 11px 14px;
        border-radius: 8px;
        border: none;
        background: transparent;
        color: #4b5563;
        font-family: inherit;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        cursor: pointer;
        transition: 0.15s;
        box-sizing: border-box;
    }
    .pf-menu button:hover,
    .pf-menu a:hover,
    .pf-menu .active {
        background: #eaf5e7;
        color: #16834b;
        font-weight: 600;
    }
    .pf-menu .logout {
        color: #dc2626;
        margin-top: 8px;
        border-top: 1px solid #f1f5f9;
        padding-top: 14px;
        border-radius: 0 0 8px 8px;
    }
    .pf-menu .logout:hover {
        background: #fef2f2;
        color: #b91c1c;
    }
    .pf-content {
        background: #fff;
        padding: 28px;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 2px 10px rgba(0,0,0,0.03);
    }
    .pf-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #eee;
        padding-bottom: 18px;
        gap: 12px;
        flex-wrap: wrap;
    }
    .pf-header h2 {
        margin: 0 0 4px 0;
        font-size: 19px;
        color: #1e3a29;
    }
    .pf-header p {
        margin: 0;
        color: #6b7280;
        font-size: 13.5px;
    }
    .pf-edit-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 9px 16px;
        background: #fff;
        border: 1px solid #16834b;
        color: #16834b;
        border-radius: 8px;
        font-family: inherit;
        font-size: 13.5px;
        font-weight: 600;
        cursor: pointer;
        transition: 0.15s;
    }
    .pf-edit-btn:hover {
        background: #16834b;
        color: #fff;
    }
    .pf-info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-top: 22px;
    }
    .pf-info-card {
        background: #f7f9f5;
        padding: 16px 18px;
        border-radius: 8px;
        border: 1px solid #edf2ea;
    }
    .pf-info-card.full {
        grid-column: 1 / 3;
    }
    .pf-info-card span {
        display: block;
        color: #6b7280;
        font-size: 12.5px;
        margin-bottom: 6px;
    }
    .pf-info-card strong {
        font-size: 15px;
        color: #111827;
    }
    .pf-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-top: 24px;
    }
    .pf-stat-card {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 18px;
        background: #f7f9f5;
        border-radius: 8px;
        border: 1px solid #edf2ea;
    }
    .pf-stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        background: #eaf5e7;
        color: #16834b;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .pf-stat-vnd {
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.3px;
        color: #16834b;
    }
    .pf-stat-card strong {
        display: block;
        font-size: 18px;
        color: #111827;
    }
    .pf-stat-card span {
        font-size: 12.5px;
        color: #6b7280;
    }
    .pf-form-group {
        margin-bottom: 16px;
    }
    .pf-form-group label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 6px;
    }
    .pf-form-group input {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-family: inherit;
        font-size: 14px;
        box-sizing: border-box;
    }
    .pf-form-group input:focus {
        outline: none;
        border-color: #16834b;
    }
    .pf-form-group input[readonly] {
        background: #f3f4f6;
        color: #6b7280;
        cursor: not-allowed;
    }
    .pf-btn-save {
        padding: 10px 22px;
        background: #16834b;
        color: #fff;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
    }
    .pf-btn-save:hover { background: #126b3d; }
    .pf-btn-cancel {
        padding: 10px 18px;
        background: #fff;
        color: #374151;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
    }
    .auto-hide-alert { transition: opacity 0.4s ease, transform 0.4s ease; }

    @media (max-width: 850px) {
        .pf-layout { grid-template-columns: 1fr; }
        .pf-info-grid, .pf-stats { grid-template-columns: 1fr; }
        .pf-info-card.full { grid-column: auto; }
    }
</style>

<main class="pf-container">
    <h1 class="pf-title">Tài khoản của tôi</h1>
    <p class="pf-subtitle">Quản lý thông tin cá nhân và hoạt động mua hàng tại Nông Sản Xanh</p>

    <?php if ($msg !== ''): ?>
        <div class="auto-hide-alert" style="background:#dcfce7; color:#166534; padding:12px 16px; border-radius:8px; margin-bottom:18px; font-weight:600; display:flex; align-items:center; gap:8px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            <span><?= htmlspecialchars($msg) ?></span>
        </div>
    <?php endif; ?>
    <?php if ($err !== ''): ?>
        <div class="auto-hide-alert" style="background:#fee2e2; color:#991b1b; padding:12px 16px; border-radius:8px; margin-bottom:18px; font-weight:600; display:flex; align-items:center; gap:8px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span><?= htmlspecialchars($err) ?></span>
        </div>
    <?php endif; ?>

    <div class="pf-layout">
        <!-- SIDEBAR -->
        <aside class="pf-sidebar">
            <div class="pf-avatar">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
            <h2><?= htmlspecialchars($user['fullname']) ?></h2>
            <span class="pf-role-tag"><?= htmlspecialchars($user['status'] ?: 'Hoạt động') ?></span>

            <div class="pf-menu">
                <button type="button" id="tab-btn-info" class="active" onclick="switchProfileTab('info')">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <span>Thông tin cá nhân</span>
                </button>

                <button type="button" id="tab-btn-pass" onclick="switchProfileTab('pass')">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <span>Đổi mật khẩu</span>
                </button>

                <a href="logout.php" class="logout">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    <span>Đăng xuất</span>
                </a>
            </div>
        </aside>

        <!-- NỘI DUNG PHẢI -->
        <section class="pf-content">
            <!-- TAB 1: THÔNG TIN CÁ NHÂN -->
            <div id="tab-info">
                <div class="pf-header">
                    <div>
                        <h2>Thông tin cá nhân</h2>
                        <p>Xem và cập nhật họ tên, số điện thoại liên hệ của bạn</p>
                    </div>
                    <button type="button" class="pf-edit-btn" id="btnToggleEdit" onclick="toggleEditMode(true)">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        <span>Chỉnh sửa</span>
                    </button>
                </div>

                <!-- CHẾ ĐỘ XEM THÔNG TIN -->
                <div id="viewModeBox">
                    <div class="pf-info-grid">
                        <div class="pf-info-card">
                            <span>Họ và tên</span>
                            <strong><?= htmlspecialchars($user['fullname']) ?></strong>
                        </div>
                        <div class="pf-info-card">
                            <span>Email đăng nhập</span>
                            <strong><?= htmlspecialchars($user['email']) ?></strong>
                        </div>
                        <div class="pf-info-card">
                            <span>Số điện thoại</span>
                            <strong><?= htmlspecialchars($user['phone'] ?: 'Chưa cập nhật') ?></strong>
                        </div>
                        <div class="pf-info-card">
                            <span>Ngày tham gia</span>
                            <strong><?= !empty($user['created_at']) ? date('d/m/Y', strtotime($user['created_at'])) : '-' ?></strong>
                        </div>
                        <div class="pf-info-card full">
                            <span>Địa chỉ giao hàng gần nhất</span>
                            <strong><?= htmlspecialchars($diaChiGanNhat) ?></strong>
                        </div>
                    </div>

                    <!-- THỐNG KÊ HOẠT ĐỘNG THẬT -->
                    <div class="pf-stats">
                        <div class="pf-stat-card">
                            <div class="pf-stat-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                            </div>
                            <div>
                                <strong><?= (int)($stats['tong_don'] ?? 0) ?></strong>
                                <span>Tổng đơn hàng</span>
                            </div>
                        </div>

                        <div class="pf-stat-card">
                            <div class="pf-stat-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            </div>
                            <div>
                                <strong><?= (int)($stats['don_hoan_thanh'] ?? 0) ?></strong>
                                <span>Đơn đã giao</span>
                            </div>
                        </div>

                        <div class="pf-stat-card">
                            <div class="pf-stat-icon">
                                <span class="pf-stat-vnd">VNĐ</span>
                            </div>
                            <div>
                                <strong><?= number_format((float)($stats['tong_chi'] ?? 0), 0, ',', '.') ?>đ</strong>
                                <span>Tổng chi tiêu</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CHẾ ĐỘ CHỈNH SỬA THÔNG TIN -->
                <form id="editModeForm" method="POST" action="" style="display:none; margin-top:22px;">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="pf-info-grid" style="margin-top:0;">
                        <div class="pf-form-group" style="margin-bottom:0;">
                            <label for="pf_fullname">Họ và tên *</label>
                            <input type="text" id="pf_fullname" name="fullname" value="<?= htmlspecialchars($user['fullname']) ?>" required>
                        </div>
                        <div class="pf-form-group" style="margin-bottom:0;">
                            <label for="pf_phone">Số điện thoại *</label>
                            <input type="tel" id="pf_phone" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required placeholder="Nhập số điện thoại">
                        </div>
                        <div class="pf-form-group" style="margin-bottom:0;">
                            <label for="pf_email">Email (không thể thay đổi)</label>
                            <input type="email" id="pf_email" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                        </div>
                        <div class="pf-form-group" style="margin-bottom:0;">
                            <label for="pf_created">Ngày tham gia</label>
                            <input type="text" id="pf_created" value="<?= !empty($user['created_at']) ? date('d/m/Y H:i', strtotime($user['created_at'])) : '' ?>" readonly>
                        </div>
                    </div>
                    <div style="display:flex; gap:10px; margin-top:20px;">
                        <button type="submit" class="pf-btn-save">Lưu thay đổi</button>
                        <button type="button" class="pf-btn-cancel" onclick="toggleEditMode(false)">Hủy</button>
                    </div>
                </form>
            </div>

            <!-- TAB 2: ĐỔI MẬT KHẨU -->
            <div id="tab-pass" style="display:none;">
                <div class="pf-header">
                    <div>
                        <h2>Đổi mật khẩu</h2>
                        <p>Cập nhật mật khẩu mới để bảo mật tài khoản của bạn</p>
                    </div>
                </div>

                <form method="POST" action="" style="margin-top:22px; max-width:440px;">
                    <input type="hidden" name="action" value="change_password">
                    <div class="pf-form-group">
                        <label for="old_password">Mật khẩu hiện tại *</label>
                        <input type="password" id="old_password" name="old_password" required placeholder="Nhập mật khẩu đang dùng">
                    </div>
                    <div class="pf-form-group">
                        <label for="new_password">Mật khẩu mới (tối thiểu 8 ký tự) *</label>
                        <input type="password" id="new_password" name="new_password" minlength="8" required placeholder="Nhập mật khẩu mới">
                    </div>
                    <div class="pf-form-group">
                        <label for="confirm_password">Xác nhận mật khẩu mới *</label>
                        <input type="password" id="confirm_password" name="confirm_password" minlength="8" required placeholder="Nhập lại mật khẩu mới">
                    </div>
                    <button type="submit" class="pf-btn-save">Cập nhật mật khẩu</button>
                </form>
            </div>
        </section>
    </div>
</main>

<script>
function toggleEditMode(isEdit) {
    document.getElementById('viewModeBox').style.display = isEdit ? 'none' : 'block';
    document.getElementById('editModeForm').style.display = isEdit ? 'block' : 'none';
    document.getElementById('btnToggleEdit').style.display = isEdit ? 'none' : 'inline-flex';
}

function switchProfileTab(tab) {
    const isInfo = (tab === 'info');
    document.getElementById('tab-info').style.display = isInfo ? 'block' : 'none';
    document.getElementById('tab-pass').style.display = isInfo ? 'none' : 'block';
    document.getElementById('tab-btn-info').classList.toggle('active', isInfo);
    document.getElementById('tab-btn-pass').classList.toggle('active', !isInfo);
    if (isInfo) toggleEditMode(false);
}

setTimeout(() => {
    document.querySelectorAll('.auto-hide-alert').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(-6px)';
        setTimeout(() => el.remove(), 400);
    });
}, 3000);
</script>

<?php require_once __DIR__ . '/footer.php'; ?>