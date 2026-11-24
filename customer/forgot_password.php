<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu - Nông Sản Xanh</title>
    <link rel="stylesheet" href="/nongsanxanh/themes/auth_forgot.css?v=reset">
</head>
<body class="forgot-page">

<div class="forgot-box">
    <div class="forgot-left">
        <div class="forgot-logo">Nông Sản Xanh</div>
        <h1>Khôi phục mật khẩu</h1>
        <p>Nhập email tài khoản của bạn để tiến hành đặt lại mật khẩu mới.</p>
    </div>

    <div class="forgot-right">
        <a href="login.php" class="forgot-back-link">← Quay lại đăng nhập</a>
        
        <h2>Quên mật khẩu</h2>
        <p class="forgot-desc">Nhập địa chỉ email đăng ký hệ thống</p>

        <?php if ($error === 'not_found'): ?>
            <div class="forgot-alert-danger">✕ Email không tồn tại trong hệ thống!</div>
        <?php elseif ($error === 'empty'): ?>
            <div class="forgot-alert-danger">⚠ Vui lòng nhập email!</div>
        <?php endif; ?>

        <form action="process_forgot.php" method="POST">
            <div class="forgot-field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Nhập email của bạn" required>
            </div>

            <button type="submit" class="forgot-submit-btn">Tiếp tục</button>
        </form>
    </div>
</div>

</body>
</html>