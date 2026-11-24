<?php
/** @var array|null $sp */
/** @var mysqli_result|null $sp_lienquan */
/** @var mixed $v_css */

// Đảm bảo $rootDir sẵn sàng
if (!isset($rootDir)) {
    $rootDir = '/' . trim(explode('/', trim($_SERVER['SCRIPT_NAME'], '/'))[0], '/') . '/';
}

$pageTitle = $sp ? htmlspecialchars($sp['TenSP']) . ' - Nông Sản Xanh' : 'Nông Sản Xanh';

// Nhúng Header chung từ customer/
require_once __DIR__ . '/../customer/header.php';
?>

<link rel="stylesheet" href="<?= $rootDir ?>themes/chitietmoi.css?v=reset">

<?php if (!$sp): ?>

    <main class="container ct-page">
        <div class="ct-empty">
            <div class="ct-empty-icon">🔍</div>
            <h2>Không tìm thấy sản phẩm</h2>
            <p>Sản phẩm không tồn tại hoặc đã bị xóa.</p>
            <a class="ct-btn" href="<?= $rootDir ?>customer/sanpham_theoloai.php">← Về danh mục</a>
        </div>
    </main>

<?php else: ?>
    <?php
        $anh       = lay_anh($sp['Hinhanh']);
        $con_hang  = (int)$sp['Soluongton'] > 0;
        $link_loai = $rootDir . 'customer/sanpham_theoloai.php?id=' . (int)$sp['maloaisanpham'];
    ?>

    <main class="container ct-page">

        <div class="ct-breadcrumb">
            <a href="<?= $rootDir ?>index1.php">Trang chủ</a> <i>›</i>
            <a href="<?= $rootDir ?>customer/danhmuc.php">Danh mục</a> <i>›</i>
            <a href="<?= $link_loai ?>"><?= htmlspecialchars((string)$sp['tenloaisanpham']) ?></a> <i>›</i>
            <span><?= htmlspecialchars($sp['TenSP']) ?></span>
        </div>

        <section class="ct-main">

            <div class="ct-image">
                <?php if ($anh): ?>
                    <img src="<?= htmlspecialchars($anh) ?>" alt="<?= htmlspecialchars($sp['TenSP']) ?>">
                <?php else: ?>
                    <div class="ct-image-empty">🌿</div>
                <?php endif; ?>
            </div>

            <div class="ct-info">
                <span class="ct-tag"><?= htmlspecialchars((string)$sp['tenloaisanpham']) ?></span>
                <h1><?= htmlspecialchars($sp['TenSP']) ?></h1>

                <p class="ct-price">
                    <?= number_format($sp['Gia'], 0, ',', '.') ?>đ<?php if ($sp['Donvitinh']): ?><small> / <?= htmlspecialchars($sp['Donvitinh']) ?></small><?php endif; ?>
                </p>

                <p class="ct-stock <?= $con_hang ? 'ok' : 'out' ?>">
                    <?= $con_hang ? 'Còn hàng (' . (int)$sp['Soluongton'] . ')' : 'Hết hàng' ?>
                </p>

                <h3 class="ct-sub">Đặc điểm nổi bật</h3>
                <p class="ct-desc"><?= nl2br(htmlspecialchars((string)$sp['Mota'])) ?></p>

                <?php if ($con_hang): ?>
                    <div class="ct-buy">
                        <label for="soluong">Số lượng đặt:</label>
                        <input type="number" id="soluong" name="soluong" value="1" min="1" max="<?= (int)$sp['Soluongton'] ?>">

                        <div class="ct-buttons">
                            <!-- NÚT THÊM GIỎ HÀNG -->
                            <button type="button" class="ct-btn" onclick="xuLyDatHang('them_gio_hang', <?= (int)$sp['MaSP'] ?>)">
                                🛒 Thêm giỏ hàng
                            </button>
                            <!-- NÚT MUA NGAY -->
                            <button type="button" class="ct-btn ct-btn-alt" onclick="xuLyDatHang('mua_ngay', <?= (int)$sp['MaSP'] ?>)">
                                ⚡ Mua ngay
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <button class="ct-btn ct-btn-off" disabled>Tạm hết hàng</button>
                <?php endif; ?>

                <ul class="ct-perks">
                    <li>🚚 Giao trong ngày</li>
                    <li>🛡️ Đảm bảo an toàn</li>
                    <li>🔄 Đổi trả tận nơi</li>
                </ul>

                <a class="ct-back" href="<?= $link_loai ?>">← Quay lại danh sách</a>
            </div>
        </section>

        <?php if ($sp_lienquan && $sp_lienquan->num_rows > 0): ?>
            <section class="ct-related">
                <h2>Gợi ý nông sản cùng loại</h2>
                <div class="ct-related-grid">
                    <?php while ($r = $sp_lienquan->fetch_assoc()): ?>
                        <?php $ranh = lay_anh($r['Hinhanh']); ?>
                        <a class="ct-card" href="chitietsanpham.php?id=<?= (int)$r['MaSP'] ?>">
                            <div class="ct-card-img">
                                <?php if ($ranh): ?>
                                    <img src="<?= htmlspecialchars($ranh) ?>" alt="<?= htmlspecialchars($r['TenSP']) ?>" loading="lazy">
                                <?php else: ?>
                                    <span>🌿</span>
                                <?php endif; ?>
                            </div>
                            <h3><?= htmlspecialchars($r['TenSP']) ?></h3>
                            <p><?= number_format($r['Gia'], 0, ',', '.') ?>đ<?php if ($r['Donvitinh']): ?> / <?= htmlspecialchars($r['Donvitinh']) ?><?php endif; ?></p>
                        </a>
                    <?php endwhile; ?>
                </div>
            </section>
        <?php endif; ?>

    </main>
<?php endif; ?>

<script>
// Dùng hàm addToCart / buyNow có sẵn trong header.php
function xuLyDatHang(actionType, maSP) {
    const input   = document.getElementById('soluong');
    const max     = input ? parseInt(input.max) || 1 : 1;
    let soluong   = input ? (parseInt(input.value) || 1) : 1;
    if (soluong < 1) soluong = 1;
    if (soluong > max) { alert('Chỉ còn ' + max + ' sản phẩm trong kho!'); return; }

    checkLoginBeforeAction(() => {
        if (actionType === 'mua_ngay') buyNow(maSP, soluong);
        else addToCart(maSP, soluong);
    });
}
</script>

<?php require_once __DIR__ . '/../customer/footer.php'; ?>