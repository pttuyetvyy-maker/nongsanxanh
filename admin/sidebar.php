<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="brand">
        <div class="brand-mark">🌾</div>
        <div class="brand-text"><strong>Nông sản Xanh</strong><span>Quản trị hệ thống</span></div>
    </div>

    <div class="nav-group-label">TỔNG QUAN</div>
    <ul class="nav">
        <li>
            <a class="nav-btn <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                <span class="icon">▦</span> Dashboard
            </a>
        </li>
        <li>
            <a class="nav-btn <?= $currentPage === 'baocao.php' ? 'active' : '' ?>" href="baocao.php">
                <span class="icon">📊</span> Báo cáo doanh số
            </a>
        </li>
    </ul>

    <div class="nav-group-label">QUẢN LÝ</div>
    <ul class="nav">
        <li>
            <a class="nav-btn <?= $currentPage === 'sanpham.php' ? 'active' : '' ?>" href="sanpham.php">
                <span class="icon">🥬</span> Sản phẩm
            </a>
        </li>
        <li>
            <a class="nav-btn <?= $currentPage === 'danhmuc.php' ? 'active' : '' ?>" href="danhmuc.php">
                <span class="icon">🗂</span> Danh mục
            </a>
        </li>
        <li>
            <a class="nav-btn <?= $currentPage === 'orders.php' ? 'active' : '' ?>" href="orders.php">
                <span class="icon">🧾</span> Đơn hàng
            </a>
        </li>
        <li>
            <a class="nav-btn <?= $currentPage === 'nguoidung.php' ? 'active' : '' ?>" href="nguoidung.php">
                <span class="icon">👥</span> Người dùng
            </a>
        </li>
        <li>
            <a class="nav-btn <?= $currentPage === 'binhluan.php' ? 'active' : '' ?>" href="binhluan.php">
                <span class="icon">💬</span> Bình luận
            </a>
        </li>
        <li>
            <a class="nav-btn <?= $currentPage === 'khuyenmai.php' ? 'active' : '' ?>" href="khuyenmai.php">
                <span class="icon">🎟️</span> Mã khuyến mãi
            </a>
        </li>
    </ul>

    <!-- NÚT ĐĂNG XUẤT VỀ INDEX -->
    <div style="margin-top: auto; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.1);">
        <ul class="nav">
            <li>
                <a class="nav-btn" href="logout.php" onclick="return confirm('Bạn có chắc muốn đăng xuất?');" style="color: #fca5a5;">
                    <span class="icon">🚪</span> Đăng xuất
                </a>
            </li>
        </ul>
    </div>
</aside>