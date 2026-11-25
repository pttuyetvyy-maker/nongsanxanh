<?php
// Trang báo cáo doanh thu - Nông Sản Xanh (admin/baocao.php)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Điều kiện đồng bộ với Dashboard: chỉ tính đơn hàng đã giao thành công
$deliveredFilter = "TrangThai IN ('Đã giao', 'Đã hoàn thành')";

$today      = date('Y-m-d');
$sevenDays  = date('Y-m-d', strtotime('-7 days'));
$monthStart = date('Y-m-01');
$monthEnd   = date('Y-m-t');

$startDate = $_GET['start_date'] ?? $monthStart;
$endDate   = $_GET['end_date']   ?? $monthEnd;

// 1. Thống kê doanh thu, số đơn đã giao, đơn trung bình theo khoảng ngày lọc
$sql = "
    SELECT 
        COUNT(MaDonHang) AS tong_don,
        COALESCE(SUM(TongTien), 0) AS tong_doanh_thu,
        COALESCE(AVG(TongTien), 0) AS don_trung_binh
    FROM donhang
    WHERE DATE(NgayDat) BETWEEN ? AND ?
      AND {$deliveredFilter}
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $startDate, $endDate);
$stmt->execute();
$stat = $stmt->get_result()->fetch_assoc();

$tongDon      = (int)($stat['tong_don'] ?? 0);
$tongDoanhThu = (float)($stat['tong_doanh_thu'] ?? 0);
$donTrungBinh = (float)($stat['don_trung_binh'] ?? 0);

// Số khách hàng có đơn đã giao trong khoảng ngày lọc
$sqlKhach = "
    SELECT COUNT(DISTINCT MaND) AS tong_khach 
    FROM donhang 
    WHERE DATE(NgayDat) BETWEEN ? AND ?
      AND {$deliveredFilter}
";
$stmtK = $conn->prepare($sqlKhach);
$stmtK->bind_param('ss', $startDate, $endDate);
$stmtK->execute();
$tongKhach = (int)($stmtK->get_result()->fetch_assoc()['tong_khach'] ?? 0);

// 2. Dữ liệu thật 6 tháng gần nhất (đồng bộ biểu đồ với Dashboard)
$monthlyLabels = [];
$monthlySalesData = [];
for ($i = 5; $i >= 0; $i--) {
    $monthYear    = date('Y-m', strtotime("-$i months"));
    $monthDisplay = 'T' . date('m', strtotime("-$i months"));

    $stmtM = $conn->prepare("
        SELECT COALESCE(SUM(TongTien), 0) AS total 
        FROM donhang 
        WHERE DATE_FORMAT(NgayDat, '%Y-%m') = ?
          AND {$deliveredFilter}
    ");
    $stmtM->bind_param('s', $monthYear);
    $stmtM->execute();
    $rev = (float)($stmtM->get_result()->fetch_assoc()['total'] ?? 0);

    $monthlyLabels[]    = $monthDisplay;
    $monthlySalesData[] = $rev;
}

// Xác định nút lọc nhanh đang active
$isToday = ($startDate === $today && $endDate === $today);
$is7Days = ($startDate === $sevenDays && $endDate === $today);
$isMonth = ($startDate === $monthStart && $endDate === $monthEnd);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nông sản Xanh — Báo cáo doanh số</title>
    <link rel="stylesheet" href="../themes/styleadmin.css?v=1">
    <link rel="stylesheet" href="../themes/category-fix.css?v=1">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .report-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .report-title { font-size: 24px; font-weight: 700; color: #1e3a29; margin: 0; }
        .btn-export {
            display: inline-flex; align-items: center; gap: 8px;
            background: #1e3a29; color: #fff; border: none; padding: 10px 18px;
            border-radius: 8px; font-size: 13.5px; font-weight: 600; cursor: pointer;
        }
        .btn-export:hover { background: #16834b; }

        .filter-card { background: #fff; border-radius: 12px; padding: 18px 24px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border: 1px solid #e5e7eb; flex-wrap: wrap; gap: 14px; }
        .filter-group { display: flex; gap: 16px; align-items: center; flex-wrap: wrap; }
        .filter-item { display: flex; flex-direction: column; gap: 4px; font-size: 12px; font-weight: 600; color: #6b7280; }
        .filter-item input { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; font-family: inherit; }
        .filter-item input:focus { border-color: #16834b; }
        .quick-btn-group { display: flex; gap: 8px; }
        .btn-quick { padding: 8px 16px; border: 1px solid #d1d5db; background: #fff; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; color: #374151; transition: 0.15s; }
        .btn-quick:hover { border-color: #16834b; color: #16834b; }
        .btn-quick.active { background: #16834b; color: #fff; border-color: #16834b; }

        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; margin-bottom: 24px; }
        .stat-card { background: #fff; border-radius: 12px; padding: 20px; border: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: flex-start; }
        .stat-label { font-size: 12px; font-weight: 700; color: #6b7280; text-transform: uppercase; margin-bottom: 8px; }
        .stat-value { font-size: 22px; font-weight: 800; color: #111827; }
        .stat-sub { font-size: 11.5px; color: #6b7280; margin-top: 6px; }
        .stat-icon { width: 42px; height: 42px; border-radius: 10px; background: #e5f2e9; color: #16834b; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }

        .report-bottom-grid { display: grid; grid-template-columns: 2.2fr 1fr; gap: 20px; }
        .chart-card, .list-card { background: #fff; border-radius: 12px; padding: 24px; border: 1px solid #e5e7eb; }
        .quick-link-item {
            display: flex; justify-content: space-between; align-items: center;
            padding: 13px 16px; background: #f9fafb; border: 1px solid #f1f5f9;
            border-radius: 10px; margin-bottom: 10px; font-weight: 600; font-size: 14px;
            color: #1f2937; text-decoration: none; transition: 0.15s;
        }
        .quick-link-item:hover { background: #eaf5e7; border-color: #bbf7d0; color: #16834b; }
        .quick-link-item .check { color: #16834b; display: inline-flex; }

        @media (max-width: 1000px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .report-bottom-grid { grid-template-columns: 1fr; }
        }
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
                <button type="button" class="btn-export" onclick="window.print()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    <span>Xuất báo cáo</span>
                </button>
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
                    <a href="baocao.php?start_date=<?= $today ?>&end_date=<?= $today ?>" class="btn-quick <?= $isToday ? 'active' : '' ?>">Hôm nay</a>
                    <a href="baocao.php?start_date=<?= $sevenDays ?>&end_date=<?= $today ?>" class="btn-quick <?= $is7Days ? 'active' : '' ?>">7 ngày qua</a>
                    <a href="baocao.php?start_date=<?= $monthStart ?>&end_date=<?= $monthEnd ?>" class="btn-quick <?= $isMonth ? 'active' : '' ?>">Tháng này</a>
                </div>
            </form>

            <div class="stats-grid">
                <div class="stat-card">
                    <div>
                        <div class="stat-label">DOANH THU ĐÃ GIAO</div>
                        <div class="stat-value" style="color:#16834b;"><?= number_format($tongDoanhThu, 0, ',', '.') ?> đ</div>
                        <div class="stat-sub">Chỉ tính đơn giao thành công</div>
                    </div>
                    <div class="stat-icon">
                        <span style="font-size:12px; font-weight:800;">VNĐ</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div>
                        <div class="stat-label">ĐƠN HÀNG ĐÃ GIAO</div>
                        <div class="stat-value"><?= number_format($tongDon) ?></div>
                        <div class="stat-sub">Đơn hoàn tất trong kỳ lọc</div>
                    </div>
                    <div class="stat-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                    </div>
                </div>

                <div class="stat-card">
                    <div>
                        <div class="stat-label">ĐƠN TRUNG BÌNH</div>
                        <div class="stat-value"><?= number_format($donTrungBinh, 0, ',', '.') ?> đ</div>
                        <div class="stat-sub">Giá trị bình quân / đơn</div>
                    </div>
                    <div class="stat-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                    </div>
                </div>

                <div class="stat-card">
                    <div>
                        <div class="stat-label">KHÁCH MUA HÀNG</div>
                        <div class="stat-value"><?= number_format($tongKhach) ?></div>
                        <div class="stat-sub">Khách có đơn đã giao</div>
                    </div>
                    <div class="stat-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                </div>
            </div>

            <div class="report-bottom-grid">
                <div class="chart-card">
                    <h3 style="font-size: 16px; margin: 0 0 18px 0; color:#1e3a29;">Tổng quan tăng trưởng doanh thu đã giao (6 tháng gần nhất)</h3>
                    <div style="height: 280px; position: relative;">
                        <canvas id="reportSalesChart"></canvas>
                    </div>
                </div>

                <div class="list-card">
                    <h3 style="font-size: 16px; margin: 0 0 18px 0; color:#1e3a29;">Danh Mục Quản Lý</h3>
                    <a href="sanpham.php" class="quick-link-item">
                        <span>Sản phẩm</span>
                        <span class="check"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></span>
                    </a>
                    <a href="orders.php" class="quick-link-item">
                        <span>Đơn hàng</span>
                        <span class="check"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></span>
                    </a>
                    <a href="nguoidung.php" class="quick-link-item">
                        <span>Khách hàng</span>
                        <span class="check"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></span>
                    </a>
                    <a href="binhluan.php" class="quick-link-item">
                        <span>Bình luận</span>
                        <span class="check"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const ctxReport = document.getElementById('reportSalesChart');
if (ctxReport) {
    new Chart(ctxReport, {
        type: 'bar',
        data: {
            labels: <?= json_encode($monthlyLabels) ?>,
            datasets: [{
                label: 'Doanh thu đã giao (đ)',
                data: <?= json_encode($monthlySalesData) ?>,
                backgroundColor: '#16834b',
                borderRadius: 6,
                maxBarThickness: 42
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            return new Intl.NumberFormat('vi-VN').format(ctx.parsed.y) + ' đ';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('vi-VN').format(value) + ' đ';
                        }
                    }
                }
            }
        }
    });
}
</script>
</body>
</html>