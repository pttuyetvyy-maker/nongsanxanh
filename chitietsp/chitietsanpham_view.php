<?php
/** @var array|null $sp */
/** @var mysqli_result|null $sp_lienquan */
/** @var array $ds_binhluan */
/** @var int $tong_danhgia */
/** @var float $diem_trungbinh */
/** @var mixed $v_css */

if (!isset($rootDir)) {
    $rootDir = '/' . trim(explode('/', trim($_SERVER['SCRIPT_NAME'], '/'))[0], '/') . '/';
}

$pageTitle = $sp ? htmlspecialchars($sp['TenSP']) . ' - Nông Sản Xanh' : 'Nông Sản Xanh';
require_once __DIR__ . '/../customer/header.php';
?>

<link rel="stylesheet" href="<?= $rootDir ?>themes/chitietmoi.css?v=reset">
<style>
    .ct-rating-mini {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin: 4px 0 10px;
        font-size: 14px;
        color: #4b5563;
    }
    .ct-rating-mini .stars-inline {
        display: inline-flex;
        align-items: center;
        gap: 2px;
        color: #f59e0b;
    }
    .ct-reviews {
        margin-top: 36px;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 24px;
    }
    .ct-reviews-head {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        padding-bottom: 18px;
        border-bottom: 1px solid #edf2f0;
        margin-bottom: 20px;
    }
    .ct-reviews-head h2 {
        margin: 0;
        font-size: 20px;
        color: #1e3a29;
    }
    .ct-reviews-score {
        display: flex;
        align-items: center;
        gap: 12px;
        background: #f3f9f5;
        border: 1px solid #d1e7d8;
        padding: 10px 16px;
        border-radius: 10px;
    }
    .ct-reviews-score .score-num {
        font-size: 24px;
        font-weight: 800;
        color: #16834b;
        line-height: 1;
    }
    .ct-reviews-score .score-stars {
        display: flex;
        gap: 2px;
        color: #f59e0b;
    }
    .ct-reviews-score .score-count {
        font-size: 12.5px;
        color: #4b5563;
        margin-top: 2px;
    }
    .ct-review-list {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }
    .ct-review-item {
        padding-bottom: 18px;
        border-bottom: 1px solid #f1f5f9;
    }
    .ct-review-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    .ct-review-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
    }
    .ct-review-user {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .ct-review-avatar {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: #e5f2e9;
        color: #16834b;
        font-weight: 700;
        font-size: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .ct-review-name {
        font-weight: 700;
        font-size: 14.5px;
        color: #111827;
    }
    .ct-review-stars {
        display: flex;
        align-items: center;
        gap: 2px;
        margin-top: 3px;
    }
    .ct-review-date {
        font-size: 12.5px;
        color: #6b7280;
        white-space: nowrap;
    }
    .ct-review-content {
        margin: 10px 0 0 48px;
        font-size: 14.5px;
        line-height: 1.6;
        color: #1f2937;
    }
    .ct-review-img-wrap {
        margin: 10px 0 0 48px;
    }
    .ct-review-img-wrap img {
        width: 84px;
        height: 84px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
    }
    .ct-admin-reply {
        margin: 12px 0 0 48px;
        background: #f4faf6;
        border-left: 3px solid #16834b;
        border-radius: 0 8px 8px 0;
        padding: 12px 14px;
    }
    .ct-admin-reply-title {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        font-weight: 700;
        color: #16834b;
        margin-bottom: 4px;
    }
    .ct-admin-reply-text {
        font-size: 13.8px;
        color: #374151;
        line-height: 1.5;
        margin: 0;
    }
    .ct-reviews-empty {
        text-align: center;
        padding: 28px 16px;
        color: #6b7280;
        font-size: 14px;
        background: #f9fafb;
        border-radius: 10px;
    }
    @media (max-width: 600px) {
        .ct-review-content, .ct-review-img-wrap, .ct-admin-reply {
            margin-left: 0;
        }
    }
</style>

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

                <!-- Tóm tắt điểm sao đánh giá -->
                <div class="ct-rating-mini">
                    <?php if ($tong_danhgia > 0): ?>
                        <span class="stars-inline">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="<?= $i <= round($diem_trungbinh) ? '#f59e0b' : '#e5e7eb' ?>">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                </svg>
                            <?php endfor; ?>
                        </span>
                        <strong><?= number_format($diem_trungbinh, 1) ?></strong>
                        <span>(<?= $tong_danhgia ?> đánh giá)</span>
                    <?php else: ?>
                        <span>Chưa có đánh giá</span>
                    <?php endif; ?>
                </div>

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
                            <button type="button" class="ct-btn" onclick="xuLyDatHang('them_gio_hang', <?= (int)$sp['MaSP'] ?>)">
                                🛒 Thêm giỏ hàng
                            </button>
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

        <!-- KHU VỰC ĐÁNH GIÁ & BÌNH LUẬN CỦA KHÁCH HÀNG -->
        <section class="ct-reviews" id="danh-gia-san-pham">
            <div class="ct-reviews-head">
                <h2>Đánh giá từ khách hàng</h2>
                <?php if ($tong_danhgia > 0): ?>
                    <div class="ct-reviews-score">
                        <span class="score-num"><?= number_format($diem_trungbinh, 1) ?>/5</span>
                        <div>
                            <div class="score-stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="<?= $i <= round($diem_trungbinh) ? '#f59e0b' : '#d1d5db' ?>">
                                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                    </svg>
                                <?php endfor; ?>
                            </div>
                            <div class="score-count">Dựa trên <?= $tong_danhgia ?> đánh giá</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (empty($ds_binhluan)): ?>
                <div class="ct-reviews-empty">
                    Sản phẩm này chưa có đánh giá nào từ khách hàng.
                </div>
            <?php else: ?>
                <div class="ct-review-list">
                    <?php foreach ($ds_binhluan as $bl): 
                        $tenKhach = trim((string)$bl['TenNguoiDung']);
                        $chuCaiDau = mb_strtoupper(mb_substr($tenKhach, 0, 1, 'UTF-8'), 'UTF-8');
                        $soSao = max(1, min(5, (int)$bl['SoSao']));
                        $ngayDG = !empty($bl['NgayDanhGia']) ? date('d/m/Y H:i', strtotime($bl['NgayDanhGia'])) : '';
                        $anhBL = lay_anh_binhluan($bl['HinhAnh'] ?? '');
                        $traLoiAdmin = trim((string)($bl['TraLoiAdmin'] ?? ''));
                    ?>
                        <article class="ct-review-item">
                            <div class="ct-review-top">
                                <div class="ct-review-user">
                                    <div class="ct-review-avatar"><?= htmlspecialchars($chuCaiDau) ?></div>
                                    <div>
                                        <div class="ct-review-name"><?= htmlspecialchars($tenKhach) ?></div>
                                        <div class="ct-review-stars" title="<?= $soSao ?> sao">
                                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="<?= $s <= $soSao ? '#f59e0b' : '#e5e7eb' ?>">
                                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                                </svg>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($ngayDG !== ''): ?>
                                    <span class="ct-review-date"><?= htmlspecialchars($ngayDG) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="ct-review-content">
                                <?= nl2br(htmlspecialchars((string)$bl['NoiDung'])) ?>
                            </div>

                            <?php if ($anhBL !== ''): ?>
                                <div class="ct-review-img-wrap">
                                    <img src="<?= htmlspecialchars($anhBL) ?>" alt="Ảnh đánh giá của <?= htmlspecialchars($tenKhach) ?>" loading="lazy" onerror="this.parentElement.style.display='none'">
                                </div>
                            <?php endif; ?>

                            <?php if ($traLoiAdmin !== ''): ?>
                                <div class="ct-admin-reply">
                                    <div class="ct-admin-reply-title">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="9 17 4 12 9 7"></polyline>
                                            <path d="M20 18v-2a4 4 0 0 0-4-4H4"></path>
                                        </svg>
                                        Phản hồi từ Nông Sản Xanh
                                    </div>
                                    <p class="ct-admin-reply-text"><?= nl2br(htmlspecialchars($traLoiAdmin)) ?></p>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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