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

// Lấy danh mục nạp vào Dropdown & bộ lọc
$categories = $conn->query("SELECT maloaisanpham, tenloaisanpham FROM loaisanpham ORDER BY maloaisanpham ASC")->fetch_all(MYSQLI_ASSOC);

// Lấy các tham số lọc từ URL (nếu có)
$filterStatus = trim($_GET['status'] ?? 'ALL');
$filterCat    = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$searchQuery  = trim($_GET['q'] ?? '');

// Đếm số lượng sản phẩm theo từng trạng thái trên toàn bộ CSDL
$countSql = "
    SELECT 
        COUNT(*) AS total_all,
        SUM(CASE WHEN Soluongton = 0 THEN 1 ELSE 0 END) AS total_out,
        SUM(CASE WHEN Soluongton >= 10 AND Soluongton <= 35 THEN 1 ELSE 0 END) AS total_low,
        SUM(CASE WHEN Soluongton > 0 AND (Soluongton < 10 OR Soluongton > 35) THEN 1 ELSE 0 END) AS total_in,
        SUM(CASE WHEN an_hien = 0 THEN 1 ELSE 0 END) AS total_hidden
    FROM sanpham
";
$countsRow = $conn->query($countSql)->fetch_assoc();
$statusCounts = [
    'ALL'       => (int)($countsRow['total_all'] ?? 0),
    'con_hang'  => (int)($countsRow['total_in'] ?? 0),
    'sap_het'   => (int)($countsRow['total_low'] ?? 0),
    'het_hang'  => (int)($countsRow['total_out'] ?? 0),
    'dang_an'   => (int)($countsRow['total_hidden'] ?? 0),
];

// Xây dựng điều kiện WHERE động để lọc chính xác trên toàn bộ CSDL
$whereClauses = [];
$params = [];
$types = '';

if ($filterStatus === 'het_hang') {
    $whereClauses[] = "s.Soluongton = 0";
} elseif ($filterStatus === 'sap_het') {
    $whereClauses[] = "s.Soluongton >= 10 AND s.Soluongton <= 35";
} elseif ($filterStatus === 'con_hang') {
    $whereClauses[] = "s.Soluongton > 0 AND (s.Soluongton < 10 OR s.Soluongton > 35)";
} elseif ($filterStatus === 'dang_an') {
    $whereClauses[] = "s.an_hien = 0";
}

if ($filterCat > 0) {
    $whereClauses[] = "s.maloaisanpham = ?";
    $params[] = $filterCat;
    $types .= 'i';
}

if ($searchQuery !== '') {
    $whereClauses[] = "(s.TenSP LIKE ? OR l.tenloaisanpham LIKE ?)";
    $likeParam = '%' . $searchQuery . '%';
    $params[] = $likeParam;
    $params[] = $likeParam;
    $types .= 'ss';
}

$whereSql = !empty($whereClauses) ? ('WHERE ' . implode(' AND ', $whereClauses)) : '';

// Phân trang 12 sản phẩm theo kết quả đã lọc
$limit = 12; 
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$totalSql = "SELECT COUNT(*) AS total FROM sanpham s LEFT JOIN loaisanpham l ON s.maloaisanpham = l.maloaisanpham $whereSql";
$stmtTotal = $conn->prepare($totalSql);
if ($types !== '') {
    $stmtTotal->bind_param($types, ...$params);
}
$stmtTotal->execute();
$totalRows = (int)$stmtTotal->get_result()->fetch_assoc()['total'];
$totalPages = max(1, (int)ceil($totalRows / $limit));
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $limit;
}

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
    $whereSql
    ORDER BY s.MaSP DESC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);
$queryParams = array_merge($params, [$limit, $offset]);
$queryTypes = $types . 'ii';
$stmt->bind_param($queryTypes, ...$queryParams);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Hàm tạo URL giữ nguyên bộ lọc khi chuyển tab hoặc chuyển trang
function buildFilterUrl($overrides = []) {
    $base = [
        'status' => $_GET['status'] ?? 'ALL',
        'cat'    => $_GET['cat'] ?? 0,
        'q'      => $_GET['q'] ?? '',
        'page'   => 1,
    ];
    $merged = array_merge($base, $overrides);
    if ($merged['status'] === 'ALL') unset($merged['status']);
    if ((int)$merged['cat'] === 0) unset($merged['cat']);
    if ($merged['q'] === '') unset($merged['q']);
    if ((int)$merged['page'] <= 1) unset($merged['page']);
    $qs = http_build_query($merged);
    return 'sanpham.php' . ($qs !== '' ? '?' . $qs : '');
}

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
        /* THANH CÔNG CỤ LỌC & TÌM KIẾM ĐỒNG BỘ */
        .prod-toolbar {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            background: #fff;
            padding: 14px 18px;
            border-radius: 10px;
            border: 1px solid #edf2f0;
            margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }

        .status-filter-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }

        .filter-tab {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 13px;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #475569;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .filter-tab:hover {
            background: #eef6f0;
            border-color: #b7dec3;
            color: #1e3a29;
        }

        .filter-tab.active {
            background: #16834b;
            border-color: #16834b;
            color: #ffffff;
        }

        .filter-tab .tab-count {
            background: rgba(0, 0, 0, 0.08);
            padding: 1px 7px;
            border-radius: 12px;
            font-size: 11.5px;
            font-weight: 700;
        }

        .filter-tab.active .tab-count {
            background: rgba(255, 255, 255, 0.22);
            color: #ffffff;
        }

        .toolbar-right-controls {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
        }

        .cat-filter-select {
            padding: 9px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 13.5px;
            color: #111827;
            background: #f9fafb;
            outline: none;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .cat-filter-select:focus {
            background: #ffffff;
            border-color: #16834b;
            box-shadow: 0 0 0 3px rgba(22, 131, 75, 0.12);
        }

        .search-prod-box {
            position: relative;
            min-width: 240px;
        }

        .search-prod-box .svg-search {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            pointer-events: none;
        }

        .search-prod-input {
            width: 100%;
            padding: 9px 34px 9px 36px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 13.5px;
            color: #111827;
            background: #f9fafb;
            outline: none;
            transition: all 0.15s ease;
            box-sizing: border-box;
        }

        .search-prod-input:focus {
            background: #ffffff;
            border-color: #16834b;
            box-shadow: 0 0 0 3px rgba(22, 131, 75, 0.12);
        }

        .btn-clear-search {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 4px;
            border-radius: 50%;
            text-decoration: none;
        }

        .btn-clear-search:hover {
            color: #374151;
            background: #e5e7eb;
        }

        .btn-add-prod {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #16834b;
            color: #fff;
            border: none;
            border-radius: 20px;
            font-size: 13.5px;
            font-weight: 700;
            padding: 9px 18px;
            cursor: pointer;
            transition: background 0.15s ease;
        }

        .btn-add-prod:hover {
            background: #126b3d;
        }

        /* BẢNG SẢN PHẨM */
        .category-table { width:100%; border-collapse:collapse; font-size:14px; }
        .category-table th { padding:14px 16px; text-align:left; background:#eaf4ed; color:#1e3a29; font-weight:700; white-space:nowrap; }
        .category-table td { padding:12px 16px; border-bottom:1px solid #edf2f0; vertical-align:middle; }
        .category-table tbody tr.prod-row:hover { background: #f8fbf9; }
        
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
                <!-- THANH LỌC TRẠNG THÁI, DANH MỤC & TÌM KIẾM -->
                <div class="prod-toolbar">
                    <div class="status-filter-tabs">
                        <a href="<?= e(buildFilterUrl(['status' => 'ALL'])) ?>" class="filter-tab <?= $filterStatus === 'ALL' ? 'active' : '' ?>">
                            Tất cả <span class="tab-count"><?= $statusCounts['ALL'] ?></span>
                        </a>
                        <a href="<?= e(buildFilterUrl(['status' => 'con_hang'])) ?>" class="filter-tab <?= $filterStatus === 'con_hang' ? 'active' : '' ?>">
                            Còn hàng <span class="tab-count"><?= $statusCounts['con_hang'] ?></span>
                        </a>
                        <a href="<?= e(buildFilterUrl(['status' => 'sap_het'])) ?>" class="filter-tab <?= $filterStatus === 'sap_het' ? 'active' : '' ?>">
                            Sắp hết <span class="tab-count"><?= $statusCounts['sap_het'] ?></span>
                        </a>
                        <a href="<?= e(buildFilterUrl(['status' => 'het_hang'])) ?>" class="filter-tab <?= $filterStatus === 'het_hang' ? 'active' : '' ?>">
                            Hết hàng <span class="tab-count"><?= $statusCounts['het_hang'] ?></span>
                        </a>
                        <a href="<?= e(buildFilterUrl(['status' => 'dang_an'])) ?>" class="filter-tab <?= $filterStatus === 'dang_an' ? 'active' : '' ?>">
                            Đang ẩn <span class="tab-count"><?= $statusCounts['dang_an'] ?></span>
                        </a>
                    </div>

                    <div class="toolbar-right-controls">
                        <form method="get" action="sanpham.php" id="prodFilterForm" style="display:flex; flex-wrap:wrap; align-items:center; gap:10px; margin:0;">
                            <?php if ($filterStatus !== 'ALL'): ?>
                                <input type="hidden" name="status" value="<?= e($filterStatus) ?>">
                            <?php endif; ?>

                            <select name="cat" id="catFilterSelect" class="cat-filter-select" onchange="this.form.submit()">
                                <option value="0">Tất cả danh mục</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= (int)$cat['maloaisanpham'] ?>" <?= $filterCat === (int)$cat['maloaisanpham'] ? 'selected' : '' ?>>
                                        <?= e($cat['tenloaisanpham']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <div class="search-prod-box">
                                <svg class="svg-search" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                                <input id="prod-search" name="q" type="text" class="search-prod-input" value="<?= e($searchQuery) ?>" placeholder="Tìm tên sản phẩm...">
                                <a href="<?= e(buildFilterUrl(['q' => ''])) ?>" id="clearProdSearchBtn" class="btn-clear-search" style="<?= $searchQuery !== '' ? 'display:inline-flex;' : '' ?>" title="Xóa tìm kiếm">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg>
                                </a>
                            </div>
                        </form>

                        <!-- Nút Thêm sản phẩm -->
                        <button class="btn-add-prod" id="btnOpenAddModal" type="button">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            Thêm sản phẩm
                        </button>
                    </div>
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
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="7" style="text-align:center; padding: 32px 16px; color:#6b7280;">
                                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="display:block; margin: 0 auto 8px;">
                                        <circle cx="11" cy="11" r="8"></circle>
                                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                    </svg>
                                    Không tìm thấy sản phẩm nào phù hợp với bộ lọc hiện tại.
                                </td>
                            </tr>
                        <?php else: ?>
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
                            <tr class="prod-row" style="<?= $anHien === 0 ? 'opacity: 0.55; background: #fafafa;' : '' ?>">
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
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                <div class="pagination-container">
                    <div class="pagination-info">
                        Hiển thị <strong><?= count($products) ?></strong> / <strong><?= $totalRows ?></strong> sản phẩm
                    </div>
                    <div class="pagination">
                        <a href="<?= e(buildFilterUrl(['page' => $page - 1])) ?>" class="page-link <?= $page <= 1 ? 'disabled' : '' ?>">« Trước</a>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="<?= e(buildFilterUrl(['page' => $i])) ?>" class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                        <a href="<?= e(buildFilterUrl(['page' => $page + 1])) ?>" class="page-link <?= $page >= $totalPages ? 'disabled' : '' ?>">Sau »</a>
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
<script>
// Tự động submit tìm kiếm sau khi ngừng gõ 450ms để lọc trên toàn bộ CSDL
(function() {
    const searchInput = document.getElementById('prod-search');
    const filterForm = document.getElementById('prodFilterForm');
    if (!searchInput || !filterForm) return;

    if (searchInput.value.length > 0) {
        searchInput.focus();
        const len = searchInput.value.length;
        searchInput.setSelectionRange(len, len);
    }

    let debounceTimer;
    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            filterForm.submit();
        }, 450);
    });
})();
</script>
</body>
</html>