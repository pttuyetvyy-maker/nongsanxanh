<?php
// Trang kết quả tìm kiếm sản phẩm: customer/timkiem.php?q=...
$pageTitle = 'Tìm kiếm - Nông Sản Xanh';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../config/db.php';

$q = trim((string)($_GET['q'] ?? ''));

$ds = null;
$tong = 0;
if ($q !== '') {
    // Escape ký tự đặc biệt của LIKE để người dùng gõ % hoặc _ không bị sai kết quả
    $like = '%' . addcslashes($q, '%_\\') . '%';

    // Chỉ lọc theo an_hien nếu bảng sanpham thực sự có cột này
    $co_an_hien = ($r = $conn->query("SHOW COLUMNS FROM sanpham LIKE 'an_hien'")) && $r->num_rows > 0;
    $loc_hien   = $co_an_hien ? "AND (an_hien = 1 OR an_hien IS NULL)" : "";

    $sql = "SELECT MaSP, TenSP, Gia, Donvitinh, Hinhanh, Mota
            FROM sanpham
            WHERE (TenSP LIKE ? OR Mota LIKE ?)
              $loc_hien
            ORDER BY (TenSP LIKE ?) DESC, MaSP DESC
            LIMIT 40";
    $stmt = $conn->prepare($sql);
    if (!$stmt) { die('Lỗi SQL: ' . $conn->error); }
    $stmt->bind_param('sss', $like, $like, $like);
    $stmt->execute();
    $ds = $stmt->get_result();
    $tong = $ds->num_rows;
}
?>
<link rel="stylesheet" href="<?= $rootDir ?>themes/stylectsp.css">

<section class="search-section">
    <div class="container">
        <form class="search-box" method="get" action="<?= $rootDir ?>customer/timkiem.php">
            <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Tìm kiếm sản phẩm nông sản..." required>
            <button type="submit">Tìm kiếm</button>
        </form>
    </div>
</section>

<section class="section product-section">
    <div class="container">
        <div class="section-title">
            <p>KẾT QUẢ TÌM KIẾM</p>
            <h2>
                <?php if ($q === ''): ?>
                    Hãy nhập từ khóa để tìm sản phẩm
                <?php else: ?>
                    <?= $tong ?> kết quả cho "<?= htmlspecialchars($q) ?>"
                <?php endif; ?>
            </h2>
        </div>

        <?php if ($ds && $tong > 0): ?>
            <div class="product-list">
                <?php while ($sp = $ds->fetch_assoc()):
                    $anh   = trim((string)$sp['Hinhanh']);
                    $src   = preg_match('#^https?://#', $anh) === 1 ? $anh : $rootDir . 'themes/images/' . rawurlencode(basename($anh));
                    $link  = $rootDir . 'chitietsp/chitietsanpham.php?id=' . (int)$sp['MaSP'];
                ?>
                    <div class="product-card">
                        <a class="product-image" href="<?= $link ?>">
                            <img src="<?= htmlspecialchars($src) ?>" alt="<?= htmlspecialchars($sp['TenSP']) ?>"
                                 onerror="this.src='<?= $rootDir ?>themes/images/placeholder.jpg'">
                        </a>
                        <div class="product-info">
                            <h3><a href="<?= $link ?>" style="color:inherit;text-decoration:none;"><?= htmlspecialchars($sp['TenSP']) ?></a></h3>
                            <p class="description"><?= htmlspecialchars((string)($sp['Mota'] ?? '')) ?></p>
                            <div class="product-bottom">
                                <span class="price"><?= number_format($sp['Gia'], 0, ',', '.') ?>đ<?php if ($sp['Donvitinh']): ?><small>/<?= htmlspecialchars($sp['Donvitinh']) ?></small><?php endif; ?></span>
                                <div style="display:flex; gap:6px;">
                                    <button type="button" onclick="checkLoginBeforeAction(() => addToCart(<?= (int)$sp['MaSP'] ?>))">Thêm</button>
                                    <button type="button" style="background:#f59e0b;" onclick="checkLoginBeforeAction(() => buyNow(<?= (int)$sp['MaSP'] ?>))">Mua ngay</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php elseif ($q !== ''): ?>
            <p style="text-align:center; padding:30px 0;">Không tìm thấy sản phẩm phù hợp. Hãy thử từ khóa khác như "cà chua", "gạo", "xoài"...</p>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/footer.php'; ?>