<?php
$pageTitle = 'Nông Sản Xanh - Trang chủ';
require_once __DIR__ . '/customer/header.php';
require_once __DIR__ . '/config/db.php';
?>

<!-- CẤU TRÚC HERO CHUẨN ĐỂ ĂN THEO CSS CÓ SẴN -->
<section class="hero">
    <div class="hero-content">
        <p class="hero-small">Nông sản tươi mỗi ngày</p>
        <h1>
            Nông sản sạch<br>
            <span>Từ nông trại đến bàn ăn</span>
        </h1>
        <p>
            Cung cấp các sản phẩm nông sản tươi,
            an toàn và chất lượng cho khách hàng.
        </p>
        <a href="#san-pham" class="btn">Mua ngay</a>
    </div>
</section>

<!-- THANH TIM KIEM -->
<section class="search-section">
    <div class="container">
        <form class="search-box" method="get" action="<?= $rootDir ?>customer/timkiem.php">
            <input type="text" name="q" placeholder="Tìm kiếm sản phẩm nông sản..." required>
            <button type="submit">Tìm kiếm</button>
        </form>
    </div>
</section>

<!-- SẢN PHẨM NỔI BẬT -->
<section class="section product-section" id="san-pham">
    <div class="container">
        <div class="section-title">
            <p>SẢN PHẨM BÁN CHẠY</p>
            <h2>Sản phẩm nổi bật</h2>
        </div>

        <div class="product-list">
            <?php
            // Lấy 4 sản phẩm mới nhất từ CSDL
            $query_sp = $conn->query("SELECT * FROM `sanpham` ORDER BY `MaSP` DESC LIMIT 4");
            if ($query_sp && $query_sp->num_rows > 0):
                while ($sp = $query_sp->fetch_assoc()):
                    $anh = trim((string)$sp['Hinhanh']);
                    $laUrl = preg_match('#^https?://#', $anh) === 1;
                    $src = $laUrl ? $anh : $rootDir . 'themes/images/' . rawurlencode(basename($anh));
            ?>
                <div class="product-card">
                    <div class="product-image">
                        <img src="<?= htmlspecialchars($src) ?>" alt="<?= htmlspecialchars($sp['TenSP']) ?>" onerror="this.src='<?= $rootDir ?>themes/images/placeholder.jpg'">
                    </div>
                    <div class="product-info">
                        <span class="product-category">Nông sản</span>
                        <h3><?= htmlspecialchars($sp['TenSP']) ?></h3>
                        <p class="description"><?= htmlspecialchars((string)($sp['Mota'] ?? '')) ?></p>
                        <div class="product-bottom">
                            <span class="price"><?= number_format($sp['Gia'], 0, ',', '.') ?>đ</span>
                            <div class="product-actions" style="display:flex; gap:6px;">
                                <button type="button" onclick="checkLoginBeforeAction(() => addToCart(<?= (int)$sp['MaSP'] ?>))">Thêm</button>
                                <button type="button" onclick="checkLoginBeforeAction(() => buyNow(<?= (int)$sp['MaSP'] ?>))" style="background:#f59e0b;">Mua ngay</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php 
                endwhile;
            else:
            ?>
                <p>Hiện chưa có sản phẩm nào.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- GIỚI THIỆU -->
<section class="section about-section" id="gioi-thieu">
    <div class="container">
        <div class="section-title">
            <p>GIỚI THIỆU</p>
            <h2>Về chúng tôi</h2>
        </div>

        <div class="about-content">
            <div class="about-image">
                <img src="<?= $rootDir ?>themes/imagess/info.jpg" alt="Nông sản sạch" onerror="this.src='<?= $rootDir ?>themes/images/placeholder.jpg'">
            </div>

            <div class="about-text">
                <h2>Nông sản sạch cho cuộc sống xanh</h2>
                <p>Nông Sản Xanh là cửa hàng chuyên cung cấp các sản phẩm nông sản tươi ngon, an toàn và có nguồn gốc rõ ràng.</p>
                <p>Chúng tôi mong muốn kết nối người tiêu dùng với những sản phẩm chất lượng từ các nông trại Việt Nam.</p>

                <div class="about-features">
                    <div class="about-feature">
                        <div>
                            <h3>Sản phẩm tươi</h3>
                            <p>Nông sản được lựa chọn kỹ càng.</p>
                        </div>
                    </div>
                    <div class="about-feature">
                        <div>
                            <h3>Giao hàng nhanh</h3>
                            <p>Giao hàng nhanh chóng và tiện lợi.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- KHUYẾN MÃI -->
<section class="promo" id="khuyen-mai">
    <div class="container">
        <div>
            <h2>ƯU ĐÃI ĐẶC BIỆT</h2>
            <p>Giảm 20% cho đơn hàng đầu tiên</p>
            <p>Áp dụng cho khách hàng mới khi mua hàng tại Nông Sản Xanh.</p>
        </div>
        <a href="#san-pham" class="btn">Mua ngay</a>
    </div>
</section>



<?php require_once __DIR__ . '/customer/footer.php'; ?>