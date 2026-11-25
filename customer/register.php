<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - Nông Sản Xanh</title>
    <link rel="stylesheet" href="/nongsanxanh/themes/styledk.css?v=<?= time() ?>">
    <style>
        .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-wrapper input {
            width: 100%;
            padding-right: 42px;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            transition: color 0.2s ease;
        }

        .toggle-password:hover {
            color: #238b45;
        }

        .toggle-password svg {
            width: 20px;
            height: 20px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .error-message {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px 14px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }

        .success-box {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
        }

        .success-box a {
            color: #155724;
            font-weight: bold;
            text-decoration: underline;
            margin-left: 5px;
        }
    </style>
</head>
<body class="auth-page">

<div class="auth-container">
    <div class="auth-left">
        <div class="auth-logo">Nông Sản Xanh</div>
        <h1>Tạo tài khoản</h1>
        <p>Đăng ký ngay để nhận nhiều ưu đãi hấp dẫn từ Nông Sản Xanh.</p>
    </div>

    <div class="auth-form">
        <a href="../index1.php" class="back-home">
            ← Về trang chủ
        </a>

        <h2>Đăng ký</h2>
        <p class="form-description">Tạo tài khoản mới của bạn</p>

        <!-- Đọc thông báo lỗi từ URL nếu có -->
        <?php if (isset($_GET['error'])): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Đọc thông báo thành công từ URL nếu có -->
        <?php if (isset($_GET['success'])): ?>
            <div class="success-box">
                Đăng ký thành công! <a href="login.php">Đăng nhập ngay</a>
            </div>
        <?php endif; ?>

        <form method="POST" action="process_register.php">
            <div class="form-group">
                <label for="fullname">Họ tên</label>
                <input type="text" id="fullname" name="fullname" placeholder="Nhập họ và tên" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="example@gmail.com" required>
            </div>

            <div class="form-group">
                <label for="phone">Số điện thoại</label>
                <input type="tel" id="phone" name="phone" placeholder="Nhập số điện thoại" required>
            </div>

            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" minlength="8" placeholder="Nhập mật khẩu" required>
                    <span class="toggle-password" onclick="togglePass('password', this)">
                        <svg viewBox="0 0 24 24">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </span>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm-password">Xác nhận mật khẩu</label>
                <div class="password-wrapper">
                    <input type="password" id="confirm-password" name="confirm-password" minlength="8" placeholder="Nhập lại mật khẩu" required>
                    <span class="toggle-password" onclick="togglePass('confirm-password', this)">
                        <svg viewBox="0 0 24 24">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </span>
                </div>
            </div>

            <button type="submit" class="auth-button">Đăng ký</button>

            <p class="register-link">
                Đã có tài khoản?
                <a href="login.php">Đăng nhập</a>
            </p>
        </form>
    </div>
</div>

<script>
function togglePass(inputId, btn) {
    const input = document.getElementById(inputId);
    const isPass = input.type === "password";
    
    input.type = isPass ? "text" : "password";
    
    btn.innerHTML = isPass 
        ? `<svg viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>`
        : `<svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`;
}
</script>

</body>
</html>