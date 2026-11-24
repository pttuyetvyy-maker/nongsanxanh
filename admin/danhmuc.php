<?php
// Trang quản lý danh mục - Nông Sản Xanh
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Bảng phụ lưu icon, mô tả và trạng thái quản trị.
$conn->query("
    CREATE TABLE IF NOT EXISTS loaisanpham_admin (
        maloaisanpham INT NOT NULL PRIMARY KEY,
        icon VARCHAR(30) NOT NULL DEFAULT '📦',
        mota VARCHAR(255) NOT NULL DEFAULT '',
        trangthai VARCHAR(20) NOT NULL DEFAULT 'Hoạt động'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$macDinh = [
    'Rau củ quả'           => ['🥬', 'Các loại rau, củ tươi theo mùa'],
    'Trái cây'             => ['🍉', 'Trái cây trong nước và nhập khẩu'],
    'Gạo và ngũ cốc'       => ['🌾', 'Gạo ST25, ngũ cốc, hạt các loại'],
    'Thịt, trứng, hải sản' => ['🥩', 'Thịt heo, bò, gà, hải sản tươi sống'],
    'Đặc sản và đồ khô'    => ['🍯', 'Nông sản, đặc sản theo từng địa phương'],
    'Sản phẩm chế biến'    => ['🥗', 'Thực phẩm đóng gói, chế biến sẵn']
];

// Đã sửa COUNT(s.masanpham) để đếm chính xác số sản phẩm
$sql = "
    SELECT
        l.maloaisanpham,
        l.tenloaisanpham,
        l.hien_thi,
        COUNT(s.maloaisanpham) AS count_sp,
        COALESCE(a.icon, '') AS icon_admin,
        COALESCE(a.mota, '') AS mota_admin
    FROM loaisanpham l
    LEFT JOIN sanpham s
        ON s.maloaisanpham = l.maloaisanpham
    LEFT JOIN loaisanpham_admin a
        ON a.maloaisanpham = l.maloaisanpham
    GROUP BY
        l.maloaisanpham,
        l.tenloaisanpham,
        l.hien_thi,
        a.icon,
        a.mota
    ORDER BY l.maloaisanpham ASC
";

$result = $conn->query($sql);
$cats = [];

while ($row = $result->fetch_assoc()) {
    $name = $row['tenloaisanpham'];

    $default = $macDinh[$name]
        ?? ['📦', 'Các sản phẩm nông sản sạch'];

    $row['icon'] = $row['icon_admin'] !== ''
        ? $row['icon_admin']
        : $default[0];

    $row['mota'] = $row['mota_admin'] !== ''
        ? $row['mota_admin']
        : $default[1];

    // 1 = đang hiện, 0 = đang ẩn
    $row['hien_thi'] = (int)$row['hien_thi'];

    $row['trangthai_text'] = $row['hien_thi'] === 1
        ? 'Hoạt động'
        : 'Tạm ẩn';

    $cats[] = $row;
}

$msg = trim($_GET['msg'] ?? '');
$err = trim($_GET['err'] ?? '');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nông sản Xanh — Quản lý danh mục</title>
    <link rel="stylesheet" href="../themes/styleadmin.css?v=category-fix-2">
    <link rel="stylesheet" href="../themes/category-fix.css?v=2">
    <style>
        .category-table { width:100%; border-collapse:collapse; font-size:14px; }
        .category-table th { padding:16px 18px; text-align:left; background:#eaf4ed; color:#1e3a29; font-weight:700; white-space:nowrap; }
        .category-table td { padding:16px 18px; border-bottom:1px solid #edf2f0; vertical-align:middle; }
        .cell-primary { display:flex; align-items:center; gap:12px; font-size:16px; font-weight:600; color:#1f2937; }
        /* --- BỔ SUNG CĂN GIỮA NÚT SỬA & TRẠNG THÁI --- */
        .category-table th:nth-child(4),
        .category-table th:nth-child(5),
        .category-table td:nth-child(4),
        .category-table td:nth-child(5) {
        text-align: center;
    }
    .row-actions {
        display: flex;
        align-items: center;
        justify-content: center; /* Căn giữa cây bút */
        gap: 8px;
    }
        .cell-primary .thumb { width:36px; height:36px; display:inline-flex; align-items:center; justify-content:center; border-radius:9px; background:#e8f2eb; font-size:21px; }
        .row-actions { display:flex; align-items:center; gap:8px; }
        .row-actions .icon-btn { width:38px; height:38px; border:1px solid #d1d5db; background:#fff; border-radius:9px; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; font-size:17px; }
        .row-actions .icon-btn:hover { background:#f3f7f4; }
        .badge-green { background:#e5f2e9; color:#087443; }
        .badge-gray { background:#eef0f2; color:#64748b; }
        .category-badge { display:inline-block; padding:5px 12px; border-radius:20px; font-size:12px; font-weight:600; }
        .table-foot { padding:14px 18px; color:#668071; font-size:13px; }
        .empty-state { padding:40px 15px; text-align:center; color:#718078; }
        .toast-container { position:fixed; right:24px; bottom:24px; z-index:10001; display:flex; flex-direction:column; gap:10px; }
        .toast-msg { padding:13px 18px; border-radius:10px; color:#fff; font-size:14px; font-weight:600; box-shadow:0 8px 25px rgba(0,0,0,.15); }
        .toast-msg.success { background:#16834b; }
        .toast-msg.error { background:#bd3b32; }
    </style>
</head>
<body>
<div class="shell">
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <div class="main">
        <div class="topbar">
            <div class="topbar-title">Danh mục<span>Quản lý danh mục</span></div>
            <div class="topbar-right">
                <div class="admin-chip"><div class="avatar-dot">TV</div><span>Admin</span></div>
            </div>
        </div>

        <div class="content">
            <section class="panel active">
                <div class="toolbar">
                    <div class="toolbar-left">
                        <div class="search-box">
                            <span>🔍</span>
                            <input id="categories-search" type="text" placeholder="Tìm kiếm danh mục...">
                        </div>
                    </div>
                    <button class="btn btn-primary" type="button" id="btn-add-category">+ Thêm danh mục</button>
                </div>

                <div class="table-card">
                    <table class="category-table">
                        <thead>
                            <tr>
                                <th style="width:25%">Danh mục</th>
                                <th style="width:35%">Mô tả</th>
                                <th style="width:15%">Số sản phẩm</th>
                                <th style="width:12%">Trạng thái</th>
                                <th style="width:13%">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="categories-tbody">
                        <?php foreach ($cats as $c): ?>
                            <tr>
                                <td>
                                    <div class="cell-primary">
                                        <span class="thumb"><?= e($c['icon']) ?></span>
                                        <span class="name"><?= e($c['tenloaisanpham']) ?></span>
                                    </div>
                                </td>
                                <td><?= e($c['mota']) ?></td>
                                <td><strong><?= (int)$c['count_sp'] ?></strong></td>
                                <td>
                                    <span class="category-badge <?= $c['hien_thi'] === 1 ? 'badge-green' : 'badge-gray' ?>">
                                        <?= e($c['trangthai_text']) ?>
                                    </span>                         
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <button
                                            class="icon-btn btn-edit"
                                            type="button"
                                            title="Chỉnh sửa danh mục"
                                            data-id="<?= (int)$c['maloaisanpham'] ?>"
                                            data-name="<?= e($c['tenloaisanpham']) ?>"
                                            data-icon="<?= e($c['icon']) ?>"
                                            data-desc="<?= e($c['mota']) ?>"
                                            data-status="<?= e($c['trangthai_text']) ?>"
                                        >✏️</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php if (count($cats) === 0): ?>
                        <div class="empty-state">Chưa có danh mục nào. Hãy bấm “Thêm danh mục” để tạo mới.</div>
                    <?php endif; ?>

                    <div class="table-foot">
                        <span id="categories-count">Hiển thị <?= count($cats) ?> / <?= count($cats) ?> danh mục</span>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<!-- Hộp thoại thêm / sửa danh mục -->
<div class="category-modal-overlay" id="modalOverlay" aria-hidden="true">
    <div class="category-modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <form method="post" action="process_danhmuc.php" id="categoryForm">
            <div class="category-modal-head">
                <div>
                    <h2 id="modalTitle">Thêm danh mục</h2>
                    <p>Điền thông tin danh mục nông sản</p>
                </div>
                <button class="modal-x" type="button" id="modalClose" aria-label="Đóng">×</button>
            </div>

            <div class="category-modal-body">
                <input type="hidden" name="action" id="f-action" value="add">
                <input type="hidden" name="id" id="f-id" value="">

                <div class="form-field">
                    <label for="f-icon">Biểu tượng</label>
                    <input type="text" name="icon" id="f-icon" maxlength="30" value="🥬" placeholder="Ví dụ: 🥬">
                    <small>Chọn một emoji phù hợp với danh mục.</small>
                </div>

                <div class="form-field">
                    <label for="f-name">Tên danh mục <span>*</span></label>
                    <input type="text" name="name" id="f-name" maxlength="100" required placeholder="Ví dụ: Rau củ quả">
                </div>

                <div class="form-field">
                    <label for="f-desc">Mô tả</label>
                    <textarea name="desc" id="f-desc" maxlength="255" rows="3" placeholder="Mô tả ngắn về danh mục..."></textarea>
                </div>

                <div class="form-field">
                    <label for="f-status">Trạng thái hiển thị</label>
                    <select name="status" id="f-status">
                        <option value="Hoạt động">Hoạt động</option>
                        <option value="Tạm ẩn">Tạm ẩn</option>
                    </select>
                    <small>Danh mục tạm ẩn vẫn được lưu trong hệ thống quản trị.</small>
                </div>
            </div>

            <div class="category-modal-foot">
                <button class="btn btn-ghost" type="button" id="modalCancel">Hủy</button>
                <button class="btn btn-danger-soft" type="button" id="modalDelete" hidden>Xóa danh mục</button>
                <button class="btn btn-primary" type="submit" id="modalSave">Lưu danh mục</button>
            </div>
        </form>
    </div>
</div>

<div class="toast-container">
    <?php if ($msg !== ''): ?><div class="toast-msg success" id="toast-msg"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($err !== ''): ?><div class="toast-msg error" id="toast-err"><?= e($err) ?></div><?php endif; ?>
</div>

<script src="danhmuc.js?v=category-fix-2"></script>
<script src="../js/danhmuc.js?v=1"></script>

</body>
</html>