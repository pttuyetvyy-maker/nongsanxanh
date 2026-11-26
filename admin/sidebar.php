<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="brand">
        <div class="brand-mark">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/>
                <path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>
            </svg>
        </div>
        <div class="brand-text"><strong>Nông sản Xanh</strong><span>Quản trị hệ thống</span></div>
    </div>

    <div class="nav-group-label">TỔNG QUAN</div>
    <ul class="nav">
        <li>
            <a class="nav-btn <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                <span class="icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7" rx="1"/>
                        <rect x="14" y="3" width="7" height="7" rx="1"/>
                        <rect x="14" y="14" width="7" height="7" rx="1"/>
                        <rect x="3" y="14" width="7" height="7" rx="1"/>
                    </svg>
                </span> Dashboard
            </a>
        </li>
        <li>
            <a class="nav-btn <?= $currentPage === 'baocao.php' ? 'active' : '' ?>" href="baocao.php">
                <span class="icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="20" x2="18" y2="10"/>
                        <line x1="12" y1="20" x2="12" y2="4"/>
                        <line x1="6" y1="20" x2="6" y2="14"/>
                    </svg>
                </span> Báo cáo doanh số
            </a>
        </li>
    </ul>

    <div class="nav-group-label">QUẢN LÝ</div>
    <ul class="nav">
        <li>
            <a class="nav-btn <?= $currentPage === 'sanpham.php' ? 'active' : '' ?>" href="sanpham.php">
                <span class="icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                        <line x1="12" y1="22.08" x2="12" y2="12"/>
                    </svg>
                </span> Sản phẩm
            </a>
        </li>
        <li>
            <a class="nav-btn <?= $currentPage === 'danhmuc.php' ? 'active' : '' ?>" href="danhmuc.php">
                <span class="icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                    </svg>
                </span> Danh mục
            </a>
        </li>
        <li>
            <a class="nav-btn <?= $currentPage === 'orders.php' ? 'active' : '' ?>" href="orders.php">
                <span class="icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/>
                        <path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/>
                        <path d="M12 17.5v-11"/>
                    </svg>
                </span> Đơn hàng
            </a>
        </li>
        <li>
            <a class="nav-btn <?= $currentPage === 'nguoidung.php' ? 'active' : '' ?>" href="nguoidung.php">
                <span class="icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </span> Người dùng
            </a>
        </li>
        <li>
            <a class="nav-btn <?= $currentPage === 'binhluan.php' ? 'active' : '' ?>" href="binhluan.php">
                <span class="icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                </span> Bình luận
            </a>
        </li>
        <li>
            <a class="nav-btn <?= $currentPage === 'khuyenmai.php' ? 'active' : '' ?>" href="khuyenmai.php">
                <span class="icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/>
                        <path d="M13 5v2"/>
                        <path d="M13 17v2"/>
                        <path d="M13 11v2"/>
                    </svg>
                </span> Mã khuyến mãi
            </a>
        </li>
    </ul>

    <!-- NÚT ĐĂNG XUẤT VỀ INDEX -->
    <div style="margin-top: auto; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.1);">
        <ul class="nav">
            <li>
                <a class="nav-btn" href="logout.php" onclick="return confirm('Bạn có chắc muốn đăng xuất?');" style="color: #fca5a5;">
                    <span class="icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                    </span> Đăng xuất
                </a>
            </li>
        </ul>
    </div>
</aside>