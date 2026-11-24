<?php
// 1. Định nghĩa tiêu đề trang
$pageTitle = 'Danh mục nông sản - Nông Sản Xanh';

// 2. Nhúng Header chuẩn từ cùng thư mục customer/
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../config/db.php';

// Đảm bảo $rootDir luôn sẵn sàng
if (!isset($rootDir)) {
    $rootDir = '/' . trim(explode('/', trim($_SERVER['SCRIPT_NAME'], '/'))[0], '/') . '/';
}

$icon_loai = [1 => '🥬', 2 => '🍊', 3 => '🌾', 4 => '🥚', 5 => '🍯', 6 => '🥫'];
$mota_loai = [
    1 => 'Rau củ tươi hái mỗi sáng, trồng theo quy trình sạch, giao nhanh trong ngày.',
    2 => 'Trái cây chín tự nhiên từ các vùng nổi tiếng, ngọt thơm, không chất bảo quản.',
    3 => 'Gạo và ngũ cốc chọn lọc, hạt mẩy, nấu lên dẻo thơm, tốt cho sức khỏe.',
    4 => 'Thịt, trứng, hải sản tươi sống, có nguồn gốc rõ ràng, đảm bảo vệ sinh.',
    5 => 'Đặc sản vùng miền và đồ khô chất lượng, thích hợp làm quà biếu.',
    6 => 'Sản phẩm chế biến từ nông sản sạch, tiện lợi, đóng gói cẩn thận.',
];

// Lấy danh sách loại + số lượng sản phẩm mỗi loại
$ds_loai = $conn->query(
    "SELECT l.maloaisanpham AS MaLoai, l.tenloaisanpham AS TenLoai, COUNT(s.MaSP) AS SoSP
     FROM loaisanpham l
     LEFT JOIN sanpham s ON s.maloaisanpham = l.maloaisanpham
     GROUP BY l.maloaisanpham, l.tenloaisanpham
     ORDER BY l.maloaisanpham"
);
if (!$ds_loai) { 
    die("Lỗi SQL: " . $conn->error); 
}
$tong_loai = $ds_loai->num_rows;

$v_css = file_exists(__DIR__ . '/../themes/sanphamtheoloai.css') ? filemtime(__DIR__ . '/../themes/sanphamtheoloai.css') : 1;
?>

<!-- Link CSS riêng cho giao diện danh mục -->
<link rel="stylesheet" href="<?= $rootDir ?>themes/sanphamtheoloai.css?v=<?= $v_css ?>">

<main class="container main-danhmuc" style="margin-top: 30px; margin-bottom: 50px;">

    <div class="breadcrumb">
        <a href="<?= $rootDir ?>index1.php">Trang chủ</a> <i>›</i>
        <span>Danh mục</span>
    </div>

    <h1 style="color: #243b24; margin-bottom: 20px;">Danh mục nông sản</h1>

    <?php if ($tong_loai > 0): ?>
        <div class="cat-grid">
            <?php while ($l = $ds_loai->fetch_assoc()): ?>
                <?php
                    $icon = $icon_loai[$l['MaLoai']] ?? '🌿';
                    $mota = $mota_loai[$l['MaLoai']] ?? 'Nông sản sạch, tươi ngon, giao tận nhà.';
                    $link = $rootDir . 'customer/sanpham_theoloai.php?id=' . $l['MaLoai'];
                ?>
                <a class="cat-card" href="<?= $link ?>">
                    <div class="cat-icon"><?= $icon ?></div>
                    <h3><?= htmlspecialchars($l['TenLoai']) ?></h3>
                    <p><?= htmlspecialchars($mota) ?></p>
                    <span class="cat-count"><?= (int)$l['SoSP'] ?> sản phẩm</span>
                    <span class="cat-btn">Xem sản phẩm →</span>
                </a>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-box">
            <div class="empty-icon">🌱</div>
            <p>Hiện chưa có danh mục nào.</p>
        </div>
    <?php endif; ?>

</main>

<?php 
// 3. Nhúng Footer chuẩn từ customer/
require_once __DIR__ . '/footer.php'; 
?>