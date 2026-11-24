<?php
// Trang quản lý sản phẩm - Nông Sản Xanh (admin/sanpham.php)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Phân trang 12 sản phẩm
$limit = 12; 
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$totalResult = $conn->query("SELECT COUNT(*) AS total FROM sanpham");
$totalRows = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

// Lấy danh mục nạp vào Dropdown
$categories = $conn->query("SELECT maloaisanpham, tenloaisanpham FROM loaisanpham ORDER BY maloaisanpham ASC")->fetch_all(MYSQLI_ASSOC);

$sql = "
    SELECT 
        s.MaSP,
        s.TenSP,
        s.Gia,
        s.Donvitinh,
        s.Soluongton,
        s.Hinhanh,
        s.Mota,
        s.maloaisanpham,
        s.an_hien,
        COALESCE(l.tenloaisanpham, 'Chưa phân loại') AS TenLoai
    FROM sanpham s
    LEFT JOIN loaisanpham l ON s.maloaisanpham = l.maloaisanpham
    ORDER BY s.MaSP DESC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $limit, $offset);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$msg = trim($_GET['msg'] ?? '');
$err = trim($_GET['err'] ?? '');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nông sản Xanh — Quản lý sản phẩm</title>
    <link rel="stylesheet" href="../themes/styleadmin.css?v=v10">
    <link rel="stylesheet" href="../themes/category-fix.css?v=v10">
    <style>
        .category-table { width:100%; border-collapse:collapse; font-size:14px; }
        .category-table th { padding:14px 16px; text-align:left; background:#eaf4ed; color:#1e3a29; font-weight:700; white-space:nowrap; }
        .category-table td { padding:12px 16px; border-bottom:1px solid #edf2f0; vertical-align:middle; }
        
        .category-table th:nth-child(5), .category-table th:nth-child(6), .category-table th:nth-child(7),
        .category-table td:nth-child(5), .category-table td:nth-child(6), .category-table td:nth-child(7) { text-align: center; }

        .prod-img { width: 45px; height: 45px; border-radius: 8px; object-fit: cover; border: 1px solid #e5e7eb; background: #f9fafb; }
        .row-actions { display:flex; align-items:center; justify-content:center; gap:6px; }
        .row-actions .icon-btn { 
            width:34px; height:34px; border:1px solid #d1d5db; background:#fff; 
            border-radius:8px; display:inline-flex; align-items:center; justify-content:center; 
            cursor:pointer; color:#4b5563; transition: all 0.2s ease;
        }
        .row-actions .icon-btn:hover { background:#f3f7f4; color:#16834b; border-color:#16834b; }
        .row-actions .icon-btn.btn-hidden { color:#9ca3af; background:#f3f4f6; }
        .row-actions .icon-btn.btn-delete:hover { color:#bd3b32; border-color:#bd3b32; background:#fdf2f2; }
        .row-actions .icon-btn svg { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

        .category-badge { display:inline-block; padding:4px 10px; border-radius:20px; font-size:12px; font-weight:600; }
        .badge-green   { background:#e5f2e9; color:#087443; }
        .badge-warning { background:#fef3c7; color:#92400e; }
        .badge-danger  { background:#fee2e2; color:#991b1b; }

        .pagination-container { display: flex; justify-content: space-between; align-items: center; margin-top: 18px; padding: 12px 0; }
        .pagination-info { font-size: 13px; color: #6b7280; }
        .pagination { display: flex; gap: 6px; }
        .page-link { padding: 6px 12px; border: 1px solid #d1d5db; background: #fff; border-radius: 6px; color: #374151; text-decoration: none; font-size: 13px; font-weight: 600; }
        .page-link.active { background: #16834b; color: #fff; border-color: #16834b; }
        .page-link.disabled { opacity: 0.5; pointer-events: none; }

        .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .toast-container { position:fixed; right:24px; bottom:24px; z-index:10001; display:flex; flex-direction:column; gap:10px; }
        .toast-msg { padding:13px 18px; border-radius:10px; color:#fff; font-size:14px; font-weight:600; box-shadow:0 8px 25px rgba(0,0,0,.15); }
        .toast-msg.success { background:#16834b; }
        .toast-msg.error { background:#bd3b32; }

        /* ÉP HIỂN THỊ MODAL KHI CÓ CLASS ACTIVE */
        .category-modal-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;
        }
        .category-modal-overlay.active { display: flex !important; }
    </style>
</head>
<body>
<div class="shell">
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <div class="main">
        <div class="topbar">
            <div class="topbar-title">Sản phẩm<span>Quản lý danh sách sản phẩm</span></div>
            <div class="topbar-right">
                <div class="admin-chip"><div class="avatar-dot">TV</div><span>Admin</span></div>
            </div>
        </div>

        <div class="content">
            <section class="panel active">
                <div class="toolbar" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                    <div class="toolbar-left">
                        <div class="search-box">
                            <span>🔍</span>
                            <input id="prod-search" type="text" placeholder="Tìm kiếm sản phẩm...">
                        </div>
                    </div>
                    <!-- Nút Thêm sản phẩm -->
                    <button class="btn btn-primary" id="btnOpenAddModal" type="button" style="border-radius:20px; font-weight:700; padding:9px 20px;">
                        + Thêm sản phẩm
                    </button>
                </div>

                <div class="table-card">
                    <table class="category-table">
                        <thead>
                            <tr>
                                <th style="width:6%">Ảnh</th>
                                <th style="width:24%">Tên sản phẩm</th>
                                <th style="width:16%">Danh mục</th>
                                <th style="width:14%">Đơn giá</th>
                                <th style="width:12%">Số lượng tồn</th>
                                <th style="width:14%">Trạng thái hàng</th>
                                <th style="width:14%">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="prod-tbody">
                        <?php foreach ($products as $p): 
                            $imgFileName = !empty($p['Hinhanh']) ? $p['Hinhanh'] : 'default.jpg';
                            $imgSrc = '../themes/image/' . $imgFileName; 

                            $formattedPrice = number_format((float)($p['Gia'] ?? 0), 0, ',', '.') . 'đ / ' . e($p['Donvitinh'] ?? 'kg');
                            $qty = (int)($p['Soluongton'] ?? 0);
                            $anHien = (int)($p['an_hien'] ?? 1);

                            if ($qty == 0) {
                                $sttText = 'Hết hàng';
                                $sttClass = 'badge-danger';
                            } elseif ($qty >= 10 && $qty <= 35) {
                                $sttText = 'Sắp hết';
                                $sttClass = 'badge-warning';
                            } else {
                                $sttText = 'Còn hàng';
                                $sttClass = 'badge-green';
                            }
                        ?>
                            <tr style="<?= $anHien === 0 ? 'opacity: 0.55; background: #fafafa;' : '' ?>">
                                <td>
                                    <img src="<?= e($imgSrc) ?>" alt="<?= e($p['TenSP']) ?>" class="prod-img" onerror="this.src='../themes/images/<?= e($imgFileName) ?>'; this.onerror=null;">
                                </td>
                                <td>
                                    <strong><?= e($p['TenSP']) ?></strong>
                                    <?php if ($anHien === 0): ?>
                                        <small style="display:block; color:#bd3b32;">(Đang ẩn trên Shop)</small>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($p['TenLoai']) ?></td>
                                <td><strong style="color:#087443;"><?= $formattedPrice ?></strong></td>
                                <td><strong><?= $qty ?></strong></td>
                                <td>
                                    <span class="category-badge <?= $sttClass ?>"><?= $sttText ?></span>
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <!-- Nút Ẩn/Hiện -->
                                        <form method="post" action="process_sanpham.php" style="display:inline;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="id" value="<?= $p['MaSP'] ?>">
                                            <input type="hidden" name="current_status" value="<?= $anHien ?>">
                                            <button class="icon-btn <?= $anHien === 0 ? 'btn-hidden' : '' ?>" type="submit" title="<?= $anHien === 1 ? 'Ẩn sản phẩm' : 'Hiện sản phẩm' ?>">
                                                <?php if ($anHien === 1): ?>
                                                    <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                                <?php else: ?>
                                                    <svg viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                                                <?php endif; ?>
                                            </button>
                                        </form>

                                        <!-- Nút Sửa Bút Chì -->
                                        <button class="icon-btn btn-edit-prod" type="button" title="Sửa sản phẩm"
                                            data-id="<?= $p['MaSP'] ?>"
                                            data-name="<?= e($p['TenSP']) ?>"
                                            data-price="<?= (float)$p['Gia'] ?>"
                                            data-unit="<?= e($p['Donvitinh']) ?>"
                                            data-qty="<?= (int)$p['Soluongton'] ?>"
                                            data-cat="<?= (int)$p['maloaisanpham'] ?>"
                                            data-img="<?= e($p['Hinhanh']) ?>"
                                            data-desc="<?= e($p['Mota'] ?? '') ?>"
                                            data-status="<?= $anHien ?>"
                                        >
                                            <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                        </button>

                                        <!-- Nút Xóa -->
                                        <form method="post" action="process_sanpham.php" onsubmit="return confirm('Bạn có chắc muốn xóa sản phẩm này?');" style="display:inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $p['MaSP'] ?>">
                                            <button class="icon-btn btn-delete" type="submit" title="Xóa">
                                                <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                <div class="pagination-container">
                    <div class="pagination-info">
                        Hiển thị <strong><?= count($products) ?></strong> / <strong><?= $totalRows ?></strong> sản phẩm
                    </div>
                    <div class="pagination">
                        <a href="sanpham.php?page=<?= $page - 1 ?>" class="page-link <?= $page <= 1 ? 'disabled' : '' ?>">« Trước</a>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="sanpham.php?page=<?= $i ?>" class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                        <a href="sanpham.php?page=<?= $page + 1 ?>" class="page-link <?= $page >= $totalPages ? 'disabled' : '' ?>">Sau »</a>
                    </div>
                </div>
                <?php endif; ?>

            </section>
        </div>
    </div>
</div>

<!-- 1. MODAL THÊM SẢN PHẨM MỚI -->
<div class="category-modal-overlay" id="addModalOverlay">
    <div class="category-modal" style="max-width:540px; background:#fff; padding:24px; border-radius:12px;">
        <form method="post" action="process_sanpham.php" enctype="multipart/form-data">
            <div class="category-modal-head" style="display:flex; justify-content:space-between; margin-bottom:16px;">
                <div>
                    <h2 style="font-size:18px; margin:0;">Thêm sản phẩm mới</h2>
                    <p style="font-size:13px; color:#6b7280; margin:4px 0 0 0;">Nhập thông tin sản phẩm mới vào hệ thống</p>
                </div>
                <button class="modal-x" type="button" id="modalCloseAdd" style="background:none; border:none; font-size:20px; cursor:pointer;">×</button>
            </div>

            <div class="category-modal-body" style="display:flex; flex-direction:column; gap:12px;">
                <input type="hidden" name="action" value="add">

                <div class="form-field">
                    <label style="display:block; font-size:13px; font-weight:600; margin-bottom:4px;">Tên sản phẩm <span style="color:red;">*</span></label>
                    <input type="text" name="name" id="add-name" placeholder="Ví dụ: Dưa hấu không hạt" style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:6px;" required>
                </div>

                <div class="form-grid-2">
                    <div class="form-field">
                        <label style="display:block; font-size:13px; font-weight:600; margin-bottom:4px;">Đơn giá (VNĐ) <span style="color:red;">*</span></label>
                        <input type="number" name="price" id="add-price" min="0" step="500" placeholder="25000" style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:6px;" required>
                    </div>
                    <div class="form-field">
                        <label style="display:block; font-size:13px; font-weight:600; margin-bottom:4px;">Đơn vị tính <span style="color:red;">*</span></label>
                        <input type="text" name="unit" id="add-unit" placeholder="kg, bó, trái..." value="kg" style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:6px;" required>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-field">
                        <label style="display:block; font-size:13px; font-weight:600; margin-bottom:4px;">Số lượng nhập kho <span style="color:red;">*</span></label>
                        <input type="number" name="qty" id="add-qty" min="0" value="50" style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:6px;" required>
                    </div>
                    <div class="form-field">
                        <label style="display:block; font-size:13px; font-weight:600; margin-bottom:4px;">Danh mục <span style="color:red;">*</span></label>
                        <select name="maloaisanpham" id="add-cat" style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:6px;" required>
                            <option value="">-- Chọn danh mục --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['maloaisanpham'] ?>"><?= e($cat['tenloaisanpham']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-field">
                    <label style="display:block; font-size:13px; font-weight:600; margin-bottom:4px;">Hình ảnh (Tải file lên hoặc Nhập tên file)</label>
                    <input type="file" name="image_file" accept="image/*" style="margin-bottom:6px; font-size:13px;">
                    <input type="text" name="image_name" placeholder="Hoặc nhập tên file: duahau.jpg" style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:6px;">
                </div>

                <div class="form-field">
                    <label style="display:block; font-size:13px; font-weight:600; margin-bottom:4px;">Mô tả sản phẩm</label>
                    <textarea name="desc" rows="3" placeholder="Mô tả chi tiết..." style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:6px;"></textarea>
                </div>
            </div>

            <div class="category-modal-foot" style="display:flex; justify-content:flex-end; gap:8px; margin-top:16px;">
                <button class="btn btn-ghost" type="button" id="modalCancelAdd" style="padding:8px 16px; border:1px solid #d1d5db; border-radius:6px; background:#fff; cursor:pointer;">Hủy</button>
                <button class="btn btn-primary" type="submit" style="padding:8px 16px; background:#16834b; color:#fff; border:none; border-radius:6px; font-weight:600; cursor:pointer;">Lưu sản phẩm</button>
            </div>
        </form>
    </div>
</div>

<!-- 2. MODAL CHỈNH SỬA SẢN PHẨM -->
<div class="category-modal-overlay" id="editModalOverlay">
    <div class="category-modal" style="max-width:540px; background:#fff; padding:24px; border-radius:12px;">
        <form method="post" action="process_sanpham.php" enctype="multipart/form-data">
            <div class="category-modal-head" style="display:flex; justify-content:space-between; margin-bottom:16px;">
                <div>
                    <h2 style="font-size:18px; margin:0;">Chỉnh sửa sản phẩm</h2>
                    <p style="font-size:13px; color:#6b7280; margin:4px 0 0 0;">Cập nhật mô tả, số lượng, giá và ảnh sản phẩm</p>
                </div>
                <button class="modal-x" type="button" id="modalCloseEdit" style="background:none; border:none; font-size:20px; cursor:pointer;">×</button>
            </div>

            <div class="category-modal-body" style="display:flex; flex-direction:column; gap:12px;">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit-id" value="">

                <div class="form-field">
                    <label style="display:block; font-size:13px; font-weight:600; margin-bottom:4px;">Tên sản phẩm <span style="color:red;">*</span></label>
                    <input type="text" name="name" id="edit-name" style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:6px;" required>
                </div>

                <div class="form-grid-2">
                    <div class="form-field">
                        <label style="display:block; font-size:13px; font-weight:600; margin-bottom:4px;">Đơn giá (VNĐ) <span style="color:red;">*</span></label>
                        <input type="number" name="price" id="edit-price" min="0" step="500" style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:6px;" required>
                    </div>
                    <div class="form-field">
                        <label style="display:block; font-size:13px; font-weight:600; margin-bottom:4px;">Đơn vị tính</label>
                        <input type="text" name="unit" id="edit-unit" style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:6px;">
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-field">
                        <label style="display:block; font-size:13px; font-weight:600; margin-bottom:4px;">Số lượng tồn kho <span style="color:red;">*</span></label>
                        <input type="number" name="qty" id="edit-qty" min="0" style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:6px;" required>
                    </div>
                    <div class="form-field">
                        <label style="display:block; font-size:13px; font-weight:600; margin-bottom:4px;">Danh mục sản phẩm</label>
                        <select name="maloaisanpham" id="edit-cat" style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:6px;">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['maloaisanpham'] ?>"><?= e($cat['tenloaisanpham']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-field">
                        <label style="display:block; font-size:13px; font-weight:600; margin-bottom:4px;">Tên file ảnh</label>
                        <input type="text" name="image" id="edit-img" style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:6px;">
                    </div>
                    <div class="form-field">
                        <label style="display:block; font-size:13px; font-weight:600; margin-bottom:4px;">Trạng thái ẩn/hiện</label>
                        <select name="an_hien" id="edit-status" style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:6px;">
                            <option value="1">Hiển thị</option>
                            <option value="0">Tạm ẩn</option>
                        </select>
                    </div>
                </div>

                <div class="form-field">
                    <label style="display:block; font-size:13px; font-weight:600; margin-bottom:4px;">Mô tả chi tiết sản phẩm</label>
                    <textarea name="desc" id="edit-desc" rows="3" style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:6px;"></textarea>
                </div>
            </div>

            <div class="category-modal-foot" style="display:flex; justify-content:flex-end; gap:8px; margin-top:16px;">
                <button class="btn btn-ghost" type="button" id="modalCancelEdit" style="padding:8px 16px; border:1px solid #d1d5db; border-radius:6px; background:#fff; cursor:pointer;">Hủy</button>
                <button class="btn btn-primary" type="submit" style="padding:8px 16px; background:#16834b; color:#fff; border:none; border-radius:6px; font-weight:600; cursor:pointer;">Cập nhật sản phẩm</button>
            </div>
        </form>
    </div>
</div>

<div class="toast-container">
    <?php if ($msg !== ''): ?><div class="toast-msg success"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($err !== ''): ?><div class="toast-msg error"><?= e($err) ?></div><?php endif; ?>
</div>

<script src="../js/sanpham.js?v=v10"></script>
</body>
</html>