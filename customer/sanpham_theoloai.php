<?php
// Nhúng header chuẩn từ thư mục customer/
$pageTitle = 'Danh mục nông sản - Nông Sản Xanh';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../config/db.php';

// Đảm bảo $rootDir luôn sẵn sàng
if (!isset($rootDir)) {
    $rootDir = '/' . trim(explode('/', trim($_SERVER['SCRIPT_NAME'], '/'))[0], '/') . '/';
}

$B_LOAI  = 'loaisanpham';
$L_MA    = 'maloaisanpham';
$L_TEN   = 'tenloaisanpham';

$B_SP    = 'sanpham';
$S_MA    = 'MaSP';
$S_TEN   = 'TenSP';
$S_GIA   = 'Gia';
$S_DONVI = 'Donvitinh';
$S_MOTA  = 'Mota';
$S_ANH   = 'Hinhanh';
$S_LOAI  = 'maloaisanpham';
/* ===================================================== */

$cot_donvi = $S_DONVI !== '' ? "`$S_DONVI`" : "''";
$cot_mota  = $S_MOTA  !== '' ? "`$S_MOTA`"  : "''";

/* Nhận mã loại từ URL */
$ma_loai = isset($_GET['id']) ? (int)$_GET['id'] : 0;

/* Nếu không có ID trên URL, tự động lấy loại đầu tiên trong database */
if ($ma_loai <= 0) {
    $first_cate = $conn->query("SELECT `$L_MA` FROM `$B_LOAI` ORDER BY `$L_MA` ASC LIMIT 1");
    if ($first_cate && $row_cate = $first_cate->fetch_assoc()) {
        $ma_loai = (int)$row_cate[$L_MA];
    }
}

/* Sắp xếp */
$sapxep = $_GET['sapxep'] ?? '';
switch ($sapxep) {
    case 'gia_tang': $order = "`$S_GIA` ASC";  break;
    case 'gia_giam': $order = "`$S_GIA` DESC"; break;
    default:         $sapxep = '';
                     $order = "`$S_MA` DESC";
}

/* Phân trang */
$so_sp_moi_trang = 8;
$trang = isset($_GET['trang']) ? max(1, (int)$_GET['trang']) : 1;

function tao_link($id, $sapxep, $trang = 1) {
    $url = "sanpham_theoloai.php?id=" . $id;
    if ($sapxep !== '') $url .= "&sapxep=" . urlencode($sapxep);
    if ($trang > 1)     $url .= "&trang=" . $trang;
    return $url;
}

/* Biểu tượng và mô tả theo loại */
$icon_loai = [1 => '🥬', 2 => '🍊', 3 => '🌾', 4 => '🥚', 5 => '🍯', 6 => '🥫'];
$mota_loai = [
    1 => 'Rau củ tươi hái mỗi sáng, trồng theo quy trình sạch, giao nhanh trong ngày.',
    2 => 'Trái cây chín tự nhiên từ các vùng nổi tiếng, ngọt thơm, không chất bảo quản.',
    3 => 'Gạo và ngũ cốc chọn lọc, hạt mẩy, nấu lên dẻo thơm, tốt cho sức khỏe.',
    4 => 'Thịt, trứng, hải sản tươi sống, có nguồn gốc rõ ràng, đảm bảo vệ sinh.',
    5 => 'Đặc sản vùng miền và đồ khô chất lượng, thích hợp làm quà biếu.',
    6 => 'Sản phẩm chế biến từ nông sản sạch, tiện lợi, đóng gói cẩn thận.',
];
$icon        = $icon_loai[$ma_loai] ?? '🌿';
$mo_ta_loai  = $mota_loai[$ma_loai] ?? 'Nông sản sạch, tươi ngon, giao tận nhà.';

/* Lấy tên loại, kiểm tra loại tồn tại */
$stmt = $conn->prepare("SELECT `$L_TEN` AS TenLoai FROM `$B_LOAI` WHERE `$L_MA` = ?");
if (!$stmt) { die("Lỗi SQL: " . $conn->error); }
$stmt->bind_param("i", $ma_loai);
$stmt->execute();
$loai = $stmt->get_result()->fetch_assoc();

if ($loai) {
    /* Đếm sản phẩm, tính tổng số trang */
    $stmt = $conn->prepare("SELECT COUNT(*) AS tong FROM `$B_SP` WHERE `$S_LOAI` = ?");
    if (!$stmt) { die("Lỗi SQL: " . $conn->error); }
    $stmt->bind_param("i", $ma_loai);
    $stmt->execute();
    $tong_sp    = (int)$stmt->get_result()->fetch_assoc()['tong'];
    $tong_trang = max(1, (int)ceil($tong_sp / $so_sp_moi_trang));

    if ($trang > $tong_trang) $trang = $tong_trang;
    $offset = ($trang - 1) * $so_sp_moi_trang;

    /* Lấy sản phẩm của trang hiện tại */
    $sql = "SELECT `$S_MA` AS MaSanPham, `$S_TEN` AS TenSanPham, `$S_GIA` AS DonGia,
                   $cot_donvi AS DonViTinh, $cot_mota AS MoTa, `$S_ANH` AS HinhAnh
            FROM `$B_SP`
            WHERE `$S_LOAI` = ?
            ORDER BY $order
            LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) { die("Lỗi SQL: " . $conn->error); }
    $stmt->bind_param("iii", $ma_loai, $so_sp_moi_trang, $offset);
    $stmt->execute();
    $ds_sp = $stmt->get_result();

    /* Tất cả loại cho menu bên trái */
    $ds_loai = $conn->query("SELECT `$L_MA` AS MaLoai, `$L_TEN` AS TenLoai
                             FROM `$B_LOAI` ORDER BY `$L_MA`");
}

$v_css = file_exists(__DIR__ . '/sanphamtheoloai.css') ? filemtime(__DIR__ . '/sanphamtheoloai.css') : 1;
?>

<link rel="stylesheet" href="<?= $rootDir ?>themes/stylectsp.css">
<link rel="stylesheet" href="<?= $rootDir ?>themes/sanphamtheoloai.css?v=<?= $v_css ?>">
<style>
    .btn-group {
        display: flex;
        gap: 8px;
        margin-top: 10px;
    }
    .btn-add-cart {
        background-color: #22c55e;
        color: #fff;
        border: none;
        padding: 8px 12px;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s;
        flex: 1;
        text-align: center;
    }
    .btn-add-cart:hover {
        background-color: #15803d;
    }
</style>

<?php if (!$loai): ?>
  <main class="container page">
    <div class="empty-box">
      <div class="empty-icon">🔍</div>
      <h2>Loại nông sản không tồn tại</h2>
      <p>Đường dẫn không hợp lệ hoặc loại này đã bị xóa.</p>
      <a class="btn" href="<?= $rootDir ?>index1.php">← Về trang chủ</a>
    </div>
  </main>

<?php else: ?>

  <!-- Banner loại -->
  <section class="loai-banner">
    <div class="container loai-banner-inner">
      <div class="loai-banner-icon"><?= $icon ?></div>
      <div>
        <h1><?= htmlspecialchars($loai['TenLoai']) ?></h1>
        <p><?= htmlspecialchars($mo_ta_loai) ?></p>
      </div>
    </div>
  </section>

  <main class="container page">

    <div class="breadcrumb">
      <a href="<?= $rootDir ?>index1.php">Trang chủ</a> <i>›</i>
      <a href="<?= $rootDir ?>customer/danhmuc.php">Danh mục</a> <i>›</i>
      <span><?= htmlspecialchars($loai['TenLoai']) ?></span>
    </div>

    <div class="layout">

      <aside class="sidebar">
        <h3>Danh mục nông sản</h3>
        <ul>
          <?php while ($l = $ds_loai->fetch_assoc()): ?>
            <li class="<?= $l['MaLoai'] == $ma_loai ? 'active' : '' ?>">
              <a href="sanpham_theoloai.php?id=<?= $l['MaLoai'] ?>">
                <span class="ico"><?= $icon_loai[$l['MaLoai']] ?? '🌿' ?></span>
                <?= htmlspecialchars($l['TenLoai']) ?>
              </a>
            </li>
          <?php endwhile; ?>
        </ul>
      </aside>

      <section class="content">

        <div class="toolbar">
          <p class="result-count">
            Hiển thị <strong><?= $ds_sp->num_rows ?></strong> / <strong><?= $tong_sp ?></strong> sản phẩm
          </p>

          <form class="sort-box" method="get" action="sanpham_theoloai.php">
            <input type="hidden" name="id" value="<?= $ma_loai ?>">
            <label for="sapxep">Sắp xếp:</label>
            <select id="sapxep" name="sapxep" onchange="this.form.submit()">
              <option value="">Mới nhất</option>
              <option value="gia_tang" <?= $sapxep == 'gia_tang' ? 'selected' : '' ?>>Giá tăng dần</option>
              <option value="gia_giam" <?= $sapxep == 'gia_giam' ? 'selected' : '' ?>>Giá giảm dần</option>
            </select>
            <noscript><button type="submit">Lọc</button></noscript>
          </form>
        </div>

        <?php if ($ds_sp->num_rows > 0): ?>
          <div class="product-list">
            <?php while ($sp = $ds_sp->fetch_assoc()): ?>
              <?php
                $anh    = trim((string)$sp['HinhAnh']);
                $la_url = preg_match('#^https?://#', $anh) === 1;
                
                if ($la_url) {
                    $src = $anh;
                } else {
                    $filename = basename($anh);
                    $src = $rootDir . 'themes/images/' . rawurlencode($filename);
                }

                // ĐƯỜNG DẪN ĐẾN FILE CHITIETSANPHAM.PHP NẰM TRONG THƯ MỤC CHITIETSP
                $link = $rootDir . 'chitietsp/chitietsanpham.php?id=' . $sp['MaSanPham'];
              ?>
              <article class="product-item">
                <a class="thumb" href="<?= $link ?>">
                  <?php if ($anh !== ''): ?>
                    <img src="<?= htmlspecialchars($src) ?>" alt="<?= htmlspecialchars($sp['TenSanPham']) ?>" loading="lazy" onerror="this.src='<?= $rootDir ?>themes/images/placeholder.jpg'">
                  <?php else: ?>
                    <div class="thumb-placeholder"><?= $icon ?></div>
                  <?php endif; ?>
                </a>
                <div class="info">
                  <h3><a href="<?= $link ?>"><?= htmlspecialchars($sp['TenSanPham']) ?></a></h3>
                  <p class="desc"><?= htmlspecialchars((string)$sp['MoTa']) ?></p>
                  <p class="price">
                    <?= number_format($sp['DonGia'], 0, ',', '.') ?>đ<?php if ($sp['DonViTinh']): ?><small>/<?= htmlspecialchars($sp['DonViTinh']) ?></small><?php endif; ?>
                  </p>
                  <div class="btn-group">
                    <!-- NÚT CHI TIẾT ĐÃ ĐƯỢC GÁN ĐÚNG $link -->
                    <a class="btn" href="<?= $link ?>">Chi tiết</a>
                    
                    <!-- NÚT THÊM GIỎ HÀNG BẮT BUỘC ĐĂNG NHẬP -->
                    <button type="button" class="btn-add-cart" onclick="checkLoginBeforeAction(() => addToCart(<?= (int)$sp['MaSanPham'] ?>))">
                        + Giỏ hàng
                    </button>
                    <button type="button" class="btn-add-cart" style="background:#f59e0b;" onclick="checkLoginBeforeAction(() => buyNow(<?= (int)$sp['MaSanPham'] ?>))">
                        Mua ngay
                    </button>
                  </div>
                </div>
              </article>
            <?php endwhile; ?>
          </div>
        <?php else: ?>
          <div class="empty-box">
            <div class="empty-icon">🌱</div>
            <p>Chưa có nông sản nào thuộc loại này.</p>
          </div>
        <?php endif; ?>

        <?php if ($tong_trang > 1): ?>
          <div class="pagination">
            <?php if ($trang > 1): ?>
              <a href="<?= tao_link($ma_loai, $sapxep, $trang - 1) ?>">« Trước</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $tong_trang; $i++): ?>
              <a class="<?= $i == $trang ? 'active' : '' ?>"
                 href="<?= tao_link($ma_loai, $sapxep, $i) ?>"><?= $i ?></a>
            <?php endfor; ?>

            <?php if ($trang < $tong_trang): ?>
              <a href="<?= tao_link($ma_loai, $sapxep, $trang + 1) ?>">Sau »</a>
            <?php endif; ?>
          </div>
        <?php endif; ?>

      </section>
    </div>
  </main>
<?php endif; ?>



<?php require_once __DIR__ . '/footer.php'; ?>