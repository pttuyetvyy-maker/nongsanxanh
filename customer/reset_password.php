<?php
$token = $_GET['token'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đặt lại mật khẩu - Nông Sản Xanh</title>
    <link rel="stylesheet" href="/nongsanxanh/themes/auth_forgot.css?v=reset">
</head>
<body class="forgot-page">

<div class="forgot-box">
    <div class="forgot-left">
        <div class="forgot-logo">Nông Sản Xanh</div>
        <h1>Đặt lại mật khẩu</h1>
        <p>Vui lòng nhập mật khẩu mới cho tài khoản của bạn.</p>
    </div>

    <div class="forgot-right">
        <h2>Mật khẩu mới</h2>

        <?php if ($error === 'empty'): ?>
            <div class="forgot-alert-danger">⚠ Vui lòng nhập đầy đủ thông tin!</div>
        <?php elseif ($error === 'short'): ?>
            <div class="forgot-alert-danger">⚠ Mật khẩu phải từ 6 ký tự trở lên!</div>
        <?php elseif ($error === 'mismatch'): ?>
            <div class="forgot-alert-danger">✕ Mật khẩu nhập lại không khớp!</div>
        <?php endif; ?>

        <form action="process_reset.php" method="POST">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            
            <div class="forgot-field">
                <label>Mật khẩu mới</label>
                <input type="password" name="password" placeholder="Nhập mật khẩu mới" required minlength="6">
            </div>

            <div class="forgot-field">
                <label>Xác nhận mật khẩu</label>
                <input type="password" name="confirm_password" placeholder="Nhập lại mật khẩu mới" required minlength="6">
            </div>

            <button type="submit" class="forgot-submit-btn">Cập nhật mật khẩu</button>
        </form>
    </div>
</div>

</body>
</html>