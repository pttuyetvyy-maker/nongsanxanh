<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = $_GET['error'] ?? '';
$reset = $_GET['reset'] ?? '';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Đăng nhập - Nông Sản Xanh</title>

<link rel="stylesheet" href="/nongsanxanh/themes/kieu.css?v=<?= time() ?>"></head>

<body class="auth-page">

<div class="auth-container">

    <!-- CỘT TRÁI -->
    <div class="auth-left">

        <div class="auth-logo">Nông Sản Xanh</div>

        <div class="welcome-content">
            <h1>Chào mừng trở lại!</h1>

            <p>Đăng nhập để tiếp tục mua sắm</p>
            <p>những sản phẩm nông sản tươi ngon.</p>
        </div>

    </div>

    <!-- CỘT PHẢI -->
    <div class="auth-form">

        <a href="../index1.php" class="back-home">
            ← Về trang chủ
        </a>

        <h2>Đăng nhập</h2>

        <p class="form-description">
            Nhập thông tin tài khoản của bạn
        </p>

        <!-- THÔNG BÁO LỖI -->
        <?php if ($error === 'invalid' || $error === 'empty'): ?>

            <div class="error-message">
                <?php
                if ($error === 'invalid') {
                    echo '✕ Sai email/họ tên hoặc mật khẩu!';
                } else {
                    echo '⚠ Vui lòng nhập đầy đủ thông tin!';
                }
                ?>
            </div>

        <?php endif; ?>

        <!-- THÔNG BÁO THÀNH CÔNG KHI ĐỔI MẬT KHẨU -->
        <?php if ($reset === 'success'): ?>
            <div style="background: #d1e7dd; border: 1px solid #badbcc; color: #0f5132; padding: 14px; border-radius: 8px; font-size: 14px; text-align: center; margin-bottom: 24px;">
                ✓ Đặt lại mật khẩu thành công! Vui lòng đăng nhập lại.
            </div>
        <?php endif; ?>

        <!-- FORM -->
        <form action="process_login.php" method="POST">

            <div class="form-group">

                <label for="email">Email / Họ và tên</label>

                <input
                    type="text"
                    id="email"
                    name="email"
                    placeholder="Nhập email hoặc họ tên"
                    autocomplete="username"
                    required
                >

            </div>

            <div class="form-group">

                <label for="password">Mật khẩu</label>

                <div class="password-wrapper">

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Nhập mật khẩu"
                        autocomplete="current-password"
                        required
                    >

                    <button
                        type="button"
                        class="btn-toggle-eye"
                        id="togglePassword"
                        aria-label="Hiện hoặc ẩn mật khẩu"
                    >
                        <svg id="eyeOpen"
                             xmlns="http://www.w3.org/2000/svg"
                             width="21"
                             height="21"
                             viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>

                        <svg id="eyeClosed"
                             xmlns="http://www.w3.org/2000/svg"
                             width="21"
                             height="21"
                             viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="2"
                             style="display:none">
                            <path d="M17.94 17.94A10 10 0 0 1 12 20c-7 0-11-8-11-8a18 18 0 0 1 5-6"/>
                            <path d="M9.9 4.2A10 10 0 0 1 12 4c7 0 11 8 11 8a18 18 0 0 1-3 4"/>
                            <line x1="1" y1="1" x2="23" y2="23"/>
                        </svg>
                    </button>

                </div>

            </div>

            <div class="form-options">

                <label class="remember">
                    <input type="checkbox" name="remember" value="1">
                    Remember me
                </label>

                <!-- ĐÃ CẬP NHẬT LINK QUÊN MẬT KHẨU TẠI ĐÂY -->
                <a href="forgot_password.php">Forgot password?</a>

            </div>

            <button type="submit" class="auth-button">
                Login
            </button>

            <p class="register-link">
                Chưa có tài khoản?
                <a href="../customer/register.php">Đăng ký ngay</a>
            </p>

        </form>

    </div>

</div>

<script>
const togglePassword = document.getElementById('togglePassword');
const passwordInput = document.getElementById('password');
const eyeOpen = document.getElementById('eyeOpen');
const eyeClosed = document.getElementById('eyeClosed');

togglePassword.addEventListener('click', function () {

    const show = passwordInput.type === 'password';

    passwordInput.type = show ? 'text' : 'password';

    eyeOpen.style.display = show ? 'none' : 'block';
    eyeClosed.style.display = show ? 'block' : 'none';

});
</script>

</body>
</html>