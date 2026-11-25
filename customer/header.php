<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rootDir = '/' . trim(explode('/', trim($_SERVER['SCRIPT_NAME'], '/'))[0], '/') . '/';

$cartCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += is_array($item) ? ($item['quantity'] ?? $item['soluong'] ?? 1) : (int)$item;
    }
}
?>
<!DOCTYPE html> 
<html lang="vi"> 
<head> 
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Nông Sản Xanh' ?></title> 
    <link rel="stylesheet" href="<?= $rootDir ?>themes/styleindex1.css?v=<?= time() ?>">
    <style>
        .user-dropdown {
            position: relative;
            display: inline-flex;
            align-items: center;
        }
        .user-dropdown-trigger {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            cursor: default;
            user-select: none;
            padding: 6px 0;
        }
        .user-dropdown-trigger svg {
            transition: transform 0.2s ease;
        }
        .user-dropdown:hover .user-dropdown-trigger svg {
            transform: rotate(180deg);
        }
        .user-dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            min-width: 225px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            padding: 6px;
            z-index: 9999;
        }
        .user-dropdown:hover .user-dropdown-menu {
            display: block;
        }
        .navbar .user-dropdown-menu a {
            display: flex !important;
            align-items: center;
            gap: 8px;
            padding: 10px 12px !important;
            margin: 0 !important;
            border-radius: 7px;
            font-size: 13.5px !important;
            font-weight: 500;
            color: #1f2937 !important;
            text-decoration: none;
            white-space: nowrap;
            transition: background 0.15s ease, color 0.15s ease;
        }
        .navbar .user-dropdown-menu a:hover {
            background: #eaf5e7 !important;
            color: #16834b !important;
        }
    </style>
</head> 
<body>

<header class="header">
    <div class="container header-content">

        <div class="logo">
            <span>Nông Sản Xanh</span>
        </div>

        <nav class="navbar">
            <a href="<?= $rootDir ?>index1.php">Trang chủ</a>
            <a href="<?= $rootDir ?>customer/danhmuc.php">Danh mục</a>
            <a href="<?= $rootDir ?>customer/sanpham_theoloai.php">Sản phẩm</a>
            
            <a href="<?= $rootDir ?>customer/giohang.php" class="nav-cart">
                Giỏ hàng (<span id="header-cart-count"><?= $cartCount ?></span>)
            </a>

            <?php if (isset($_SESSION["user_id"])) { ?>
                <a href="<?= $rootDir ?>customer/my_order.php" class="<?= (basename($_SERVER['SCRIPT_NAME']) == 'my_order.php') ? 'active' : '' ?>">
                    Đơn hàng của tôi
                </a>

                <div class="user-dropdown">
                    <span class="nav-user user-dropdown-trigger">
                        <span>Xin chào, <?= htmlspecialchars($_SESSION["fullname"] ?? $_SESSION["user_name"] ?? $_SESSION["username"] ?? 'Khách'); ?></span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </span>
                    <div class="user-dropdown-menu">
                        <a href="<?= $rootDir ?>customer/profile.php">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                            <span>Chỉnh sửa thông tin cá nhân</span>
                        </a>
                    </div>
                </div>

                <a href="<?= $rootDir ?>customer/logout.php">Đăng xuất</a>
            <?php } else { ?>
                <a href="<?= $rootDir ?>customer/login.php">Đăng nhập</a>
                <a href="<?= $rootDir ?>customer/register.php">Đăng ký</a>
            <?php } ?>
        </nav>

    </div>
</header>

<!-- ====== JS DÙNG CHUNG: GIỎ HÀNG / MUA NGAY (nạp 1 lần ở header) ====== -->
<script>
const ROOT_DIR     = '<?= $rootDir ?>';
const IS_LOGGED_IN = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;

/* Chưa đăng nhập -> báo + chuyển sang trang login. Đã đăng nhập -> chạy callback */
function checkLoginBeforeAction(callback) {
    if (!IS_LOGGED_IN) {
        alert('Vui lòng đăng nhập để tiếp tục!');
        window.location.href = ROOT_DIR + 'customer/login.php';
        return;
    }
    if (typeof callback === 'function') callback();
}

function updateCartCount(n) {
    const el = document.getElementById('header-cart-count');
    if (el) el.textContent = n;
}

function callCartApi(payload) {
    return fetch(ROOT_DIR + 'customer/api_giohang.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    }).then(r => r.json());
}

/* THÊM VÀO GIỎ (không reload trang, chỉ cập nhật số trên header) */
function addToCart(maSP, soluong) {
    soluong = soluong || 1;
    callCartApi({ action: 'them_gio_hang', id: maSP, soluong: soluong })
        .then(data => {
            if (data.require_login) { IS_LOGGED_IN_FALLBACK(); return; }
            if (data.success) {
                updateCartCount(data.cart_count);
                alert('Đã thêm sản phẩm vào giỏ hàng!');
            } else {
                alert(data.message || 'Thêm vào giỏ hàng thất bại!');
            }
        })
        .catch(() => alert('Lỗi kết nối máy chủ, vui lòng thử lại!'));
}

/* MUA NGAY: bỏ qua giỏ hàng, đi thẳng tới trang thanh toán */
function buyNow(maSP, soluong) {
    soluong = soluong || 1;
    callCartApi({ action: 'mua_ngay', id: maSP, soluong: soluong })
        .then(data => {
            if (data.require_login) { IS_LOGGED_IN_FALLBACK(); return; }
            if (data.success) {
                window.location.href = ROOT_DIR + 'customer/orderdetail.php?mode=buynow';
            } else {
                alert(data.message || 'Không thể mua ngay!');
            }
        })
        .catch(() => alert('Lỗi kết nối máy chủ, vui lòng thử lại!'));
}

function IS_LOGGED_IN_FALLBACK() {
    alert('Phiên đăng nhập đã hết, vui lòng đăng nhập lại!');
    window.location.href = ROOT_DIR + 'customer/login.php';
}
</script>