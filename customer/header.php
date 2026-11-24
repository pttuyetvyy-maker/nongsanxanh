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
                <!-- THÊM LINK THEO DÕI ĐƠN HÀNG TRÊN HEADER -->
                <a href="<?= $rootDir ?>customer/my_order.php" class="<?= (basename($_SERVER['SCRIPT_NAME']) == 'my_order.php') ? 'active' : '' ?>">
                     Đơn hàng của tôi
                </a>
                <span class="nav-user">Xin chào, <?= htmlspecialchars($_SESSION["user_name"] ?? $_SESSION["username"] ?? 'Khách'); ?></span>
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
