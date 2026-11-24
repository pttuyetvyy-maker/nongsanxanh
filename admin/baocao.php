<?php
// Trang báo cáo doanh thu - Nông Sản Xanh (baocao.php)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate   = $_GET['end_date']   ?? date('Y-m-t');

$sql = "
    SELECT 
        COUNT(MaDonHang) AS tong_don,
        COALESCE(SUM(TongTien), 0) AS tong_doanh_thu,
        COALESCE(AVG(TongTien), 0) AS don_trung_binh
    FROM donhang
    WHERE DATE(NgayDat) BETWEEN ? AND ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $startDate, $endDate);
$stmt->execute();
$stat = $stmt->get_result()->fetch_assoc();

$tongDon = (int)($stat['tong_don'] ?? 0);
$tongDoanhThu = (float)($stat['tong_doanh_thu'] ?? 0);
$donTrungBinh = (float)($stat['don_trung_binh'] ?? 0);

$sqlKhach = "SELECT COUNT(DISTINCT MaND) AS tong_khach FROM donhang WHERE DATE(NgayDat) BETWEEN ? AND ?";
$stmtK = $conn->prepare($sqlKhach);
$stmtK->bind_param('ss', $startDate, $endDate);
$stmtK->execute();
$tongKhach = (int)($stmtK->get_result()->fetch_assoc()['tong_khach'] ?? 0);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nông sản Xanh — Báo cáo doanh số</title>
    <link rel="stylesheet" href="../themes/styleadmin.css?v=1">
    <link rel="stylesheet" href="../themes/category-fix.css?v=1">
    <style>
        .report-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .report-title { font-size: 24px; font-weight: 700; color: #1e3a29; }
        .filter-card { background: #fff; border-radius: 12px; padding: 18px 24px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border: 1px solid #e5e7eb; }
        .filter-group { display: flex; gap: 16px; align-items: center; }
        .filter-item { display: flex; flex-direction: column; gap: 4px; font-size: 12px; font-weight: 600; color: #6b7280; }
        .filter-item input { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; }
        .quick-btn-group { display: flex; gap: 8px; }
        .btn-quick { padding: 8px 16px; border: 1px solid #d1d5db; background: #fff; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; color: #374151; }
        .btn-quick.active { background: #16834b; color: #fff; border-color: #16834b; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; margin-bottom: 24px; }
        .stat-card { background: #fff; border-radius: 12px; padding: 20px; border: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: flex-start; }
        .stat-label { font-size: 12px; font-weight: 700; color: #6b7280; text-transform: uppercase; margin-bottom: 8px; }
        .stat-value { font-size: 22px; font-weight: 800; color: #111827; }
        .stat-icon { width: 42px; height: 42px; border-radius: 10px; background: #e5f2e9; color: #087443; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .report-bottom-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
        .chart-card, .list-card { background: #fff; border-radius: 12px; padding: 24px; border: 1px solid #e5e7eb; }
        .quick-link-item { display: flex; justify-content: space-between; align-items: center; padding: 14px 16px; background: #f9fafb; border-radius: 10px; margin-bottom: 12px; font-weight: 600; font-size: 14px; }
        .quick-link-item .check { color: #16834b; font-size: 16px; }
    </style>
</head>
<body>
<div class="shell">
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <div class="main">
        <div class="topbar">
            <div class="topbar-title">Báo cáo<span>Báo cáo doanh số bán hàng</span></div>
            <div class="topbar-right">
                <div class="admin-chip"><div class="avatar-dot">TV</div><span>Admin</span></div>
            </div>
        </div>

        <div class="content">
            <div class="report-header">
                <h1 class="report-title">Báo Cáo Bán Hàng Tháng <?= date('m/Y', strtotime($startDate)) ?></h1>
                <button class="btn btn-primary" style="background:#1e3a29;" onclick="window.print()">📥 Xuất báo cáo</button>
            </div>

            <form method="get" action="baocao.php" class="filter-card">
                <div class="filter-group">
                    <div class="filter-item">
                        <label>NGÀY BẮT ĐẦU</label>
                        <input type="date" name="start_date" value="<?= e($startDate) ?>">
                    </div>
                    <div class="filter-item">
                        <label>NGÀY KẾT THÚC</label>
                        <input type="date" name="end_date" value="<?= e($endDate) ?>">
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top: 18px; padding: 8px 16px;">Lọc dữ liệu</button>
                </div>
                <div class="quick-btn-group">
                    <a href="baocao.php?start_date=<?= date('Y-m-d') ?>&end_date=<?= date('Y-m-d') ?>" class="btn-quick">Hôm nay</a>
                    <a href="baocao.php?start_date=<?= date('Y-m-d', strtotime('-7 days')) ?>&end_date=<?= date('Y-m-d') ?>" class="btn-quick">7 ngày qua</a>
                    <a href="baocao.php?start_date=<?= date('Y-m-01') ?>&end_date=<?= date('Y-m-t') ?>" class="btn-quick active">Tháng này</a>
                </div>
            </form>

            <div class="stats-grid">
                <div class="stat-card">
                    <div>
                        <div class="stat-label">TỔNG DOANH THU</div>
                        <div class="stat-value" style="color:#087443;"><?= number_format($tongDoanhThu, 0, ',', '.') ?> đ</div>
                    </div>
                    <div class="stat-icon">💰</div>
                </div>
                <div class="stat-card">
                    <div>
                        <div class="stat-label">TỔNG ĐƠN HÀNG</div>
                        <div class="stat-value"><?= number_format($tongDon) ?></div>
                    </div>
                    <div class="stat-icon">🛍️</div>
                </div>
                <div class="stat-card">
                    <div>
                        <div class="stat-label">ĐƠN TRUNG BÌNH</div>
                        <div class="stat-value"><?= number_format($donTrungBinh, 0, ',', '.') ?> đ</div>
                    </div>
                    <div class="stat-icon">🧮</div>
                </div>
                <div class="stat-card">
                    <div>
                        <div class="stat-label">TỔNG KHÁCH HÀNG</div>
                        <div class="stat-value"><?= number_format($tongKhach) ?></div>
                    </div>
                    <div class="stat-icon">👥</div>
                </div>
            </div>

            <div class="report-bottom-grid">
                <div class="chart-card">
                    <h3 style="font-size: 16px; margin-bottom: 20px; color:#111827;">Tổng quan tăng trưởng doanh thu</h3>
                    <div style="height: 200px; display: flex; align-items: flex-end; gap: 18px; padding-top: 20px; border-bottom: 2px solid #e5e7eb;">
                        <div style="flex:1; background:#eaf4ed; height:40%; border-radius:6px 6px 0 0; text-align:center; font-size:11px;">T5</div>
                        <div style="flex:1; background:#eaf4ed; height:55%; border-radius:6px 6px 0 0; text-align:center; font-size:11px;">T6</div>
                        <div style="flex:1; background:#eaf4ed; height:70%; border-radius:6px 6px 0 0; text-align:center; font-size:11px;">T7</div>
                        <div style="flex:1; background:#16834b; height:90%; border-radius:6px 6px 0 0; text-align:center; font-size:11px; color:#fff; font-weight:bold;">T8</div>
                    </div>
                </div>
                <div class="list-card">
                    <h3 style="font-size: 16px; margin-bottom: 20px; color:#111827;">Danh Mục Quản Lý</h3>
                    <div class="quick-link-item"><span>Sản phẩm</span><span class="check">✔</span></div>
                    <div class="quick-link-item"><span>Đơn hàng</span><span class="check">✔</span></div>
                    <div class="quick-link-item"><span>Khách hàng</span><span class="check">✔</span></div>
                    <div class="quick-link-item"><span>Bình luận</span><span class="check">✔</span></div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>