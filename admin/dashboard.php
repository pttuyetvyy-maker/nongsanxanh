<?php
// Trang Dashboard Quản trị - Nông Sản Xanh (admin/dashboard.php)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Điều kiện lọc đơn hàng đã giao thành công
$deliveredFilter = "TrangThai IN ('Đã giao', 'Đã hoàn thành')";

// 1. Thống kê KPI tổng quan từ Database (chỉ tính đơn Đã giao)
$totalRevenue = (float)($conn->query("
    SELECT COALESCE(SUM(TongTien), 0) AS rev 
    FROM donhang 
    WHERE {$deliveredFilter}
")->fetch_assoc()['rev'] ?? 0);

$totalOrders = (int)($conn->query("
    SELECT COUNT(*) AS total 
    FROM donhang 
    WHERE {$deliveredFilter}
")->fetch_assoc()['total'] ?? 0);

$tableUsers = $conn->query("SHOW TABLES LIKE 'users'")->num_rows > 0 ? 'users' : 'nguoidung';
$totalUsers = (int)($conn->query("SELECT COUNT(*) AS total FROM {$tableUsers}")->fetch_assoc()['total'] ?? 0);

// Tính tổng sản phẩm đã bán (chỉ tính đơn Đã giao)
$ctCols = array_column($conn->query("SHOW COLUMNS FROM chitietdonhang")->fetch_all(MYSQLI_ASSOC), 'Field');
$fkCol  = in_array('MaDonHang', $ctCols) ? 'MaDonHang' : 'MaDH';

$totalItemsSold = (int)($conn->query("
    SELECT COALESCE(SUM(c.SoLuong), 0) AS total 
    FROM chitietdonhang c
    INNER JOIN donhang d ON c.{$fkCol} = d.MaDonHang
    WHERE d.{$deliveredFilter}
")->fetch_assoc()['total'] ?? 0);

// 2. Lấy dữ liệu doanh thu 6 tháng gần nhất để vẽ Biểu đồ cột (chỉ tính đơn Đã giao)
$monthlySalesData = [];
$monthlyLabels = [];
for ($i = 5; $i >= 0; $i--) {
    $monthYear = date('Y-m', strtotime("-$i months"));
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

    $monthlyLabels[] = $monthDisplay;
    $monthlySalesData[] = $rev;
}

// 3. Lấy Top 5 sản phẩm bán chạy nhất (Sales Ranking - chỉ tính đơn Đã giao)
$sqlRanking = "
    SELECT 
        s.TenSP,
        COALESCE(SUM(c.SoLuong), 0) AS TongBan,
        COALESCE(SUM(c.SoLuong * c.DonGia), 0) AS TongTienBan
    FROM sanpham s
    LEFT JOIN chitietdonhang c ON s.MaSP = c.MaSP
    LEFT JOIN donhang d ON c.{$fkCol} = d.MaDonHang AND d.{$deliveredFilter}
    GROUP BY s.MaSP, s.TenSP
    HAVING TongBan > 0
    ORDER BY TongBan DESC
    LIMIT 5
";
$topProducts = $conn->query($sqlRanking)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nông sản Xanh — Dashboard Quản trị</title>
    <link rel="stylesheet" href="../themes/styleadmin.css?v=fresh">
    <link rel="stylesheet" href="../themes/category-fix.css?v=fresh">
    <!-- Nhúng Chart.js để vẽ biểu đồ cột & miền -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* GRID KHU VỰC THỐNG KÊ TRÊN */
        .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; margin-bottom: 24px; }
        .kpi-card { background: #fff; border-radius: 12px; padding: 20px; border: 1px solid #edf2f0; box-shadow: 0 2px 6px rgba(0,0,0,0.02); display: flex; flex-direction: column; justify-content: space-between; }
        .kpi-title { font-size: 13px; font-weight: 600; color: #64748b; margin-bottom: 8px; display: flex; justify-content: space-between; }
        .kpi-num { font-size: 24px; font-weight: 800; color: #1e3a29; margin-bottom: 12px; }
        .kpi-sparkline { height: 45px; width: 100%; }
        .kpi-foot { font-size: 12px; color: #64748b; border-top: 1px solid #f1f5f9; padding-top: 8px; margin-top: 6px; }

        /* GRID KHU VỰC BIỂU ĐỒ CHÍNH & RANKING */
        .main-dash-grid { display: grid; grid-template-columns: 2.8fr 1.2fr; gap: 20px; }
        .dash-panel { background: #fff; border-radius: 12px; padding: 22px; border: 1px solid #edf2f0; }
        .dash-panel-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .dash-panel-title { font-size: 16px; font-weight: 700; color: #1e3a29; }
        
        /* BẢNG XẾP HẠNG TOP SẢN PHẨM */
        .ranking-list { list-style: none; padding: 0; margin: 0; }
        .ranking-item { display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f8fafc; }
        .ranking-item:last-child { border-bottom: none; }
        .rank-left { display: flex; align-items: center; gap: 12px; }
        .rank-num { width: 24px; height: 24px; border-radius: 50%; background: #eef2f0; color: #475569; font-size: 12px; font-weight: 700; display: flex; align-items: center; justify-content: center; }
        .rank-num.top-1 { background: #16834b; color: #fff; }
        .rank-num.top-2 { background: #22c55e; color: #fff; }
        .rank-num.top-3 { background: #86efac; color: #166534; }
        .rank-name { font-size: 14px; font-weight: 600; color: #334155; }
        .rank-val { font-size: 13px; font-weight: 700; color: #16834b; }
    </style>
</head>
<body>
<div class="shell">
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <div class="main">
        <div class="topbar">
            <div class="topbar-title">Dashboard<span>Tổng quan kinh doanh Nông Sản Xanh</span></div>
            <div class="topbar-right">
                <div class="admin-chip"><div class="avatar-dot">TV</div><span>Admin</span></div>
            </div>
        </div>

        <div class="content">
            <!-- HÀNG 1: 4 THẺ CHỈ SỐ KPI -->
            <div class="kpi-row">
                <!-- Thẻ 1: Tổng doanh thu -->
                <div class="kpi-card">
                    <div>
                        <div class="kpi-title">Tổng Doanh Thu <span>ⓘ</span></div>
                        <div class="kpi-num"><?= number_format($totalRevenue, 0, ',', '.') ?> đ</div>
                    </div>
                    <div class="kpi-foot">Tính trên các đơn hàng đã giao</div>
                </div>

                <!-- Thẻ 2: Đơn hàng -->
                <div class="kpi-card">
                    <div>
                        <div class="kpi-title">Đơn Hàng Đã Giao <span>ⓘ</span></div>
                        <div class="kpi-num"><?= number_format($totalOrders) ?></div>
                    </div>
                    <div class="kpi-foot">Số đơn giao thành công</div>
                </div>

                <!-- Thẻ 3: Sản phẩm bán ra -->
                <div class="kpi-card">
                    <div>
                        <div class="kpi-title">Sản Phẩm Đã Bán <span>ⓘ</span></div>
                        <div class="kpi-num"><?= number_format($totalItemsSold) ?></div>
                    </div>
                    <div class="kpi-foot">Tổng sản lượng đã giao khách</div>
                </div>

                <!-- Thẻ 4: Khách hàng -->
                <div class="kpi-card">
                    <div>
                        <div class="kpi-title">Khách Hàng <span>ⓘ</span></div>
                        <div class="kpi-num"><?= number_format($totalUsers) ?></div>
                    </div>
                    <div class="kpi-foot">Tài khoản thành viên hệ thống</div>
                </div>
            </div>

            <!-- HÀNG 2: BIỂU ĐỒ CỘT DOANH THU & TOP XẾP HẠNG SẢN PHẨM -->
            <div class="main-dash-grid">
                <!-- Cột trái: Biểu đồ cột doanh thu 6 tháng -->
                <div class="dash-panel">
                    <div class="dash-panel-head">
                        <div class="dash-panel-title">Xu hướng doanh thu đã giao (6 tháng gần nhất)</div>
                    </div>
                    <div style="height: 320px; position: relative;">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>

                <!-- Cột phải: Top sản phẩm bán chạy nhất -->
                <div class="dash-panel">
                    <div class="dash-panel-head">
                        <div class="dash-panel-title">Top sản phẩm bán chạy</div>
                    </div>
                    <ul class="ranking-list">
                    <?php 
                    $rank = 1;
                    foreach ($topProducts as $tp): 
                        $rankClass = ($rank <= 3) ? "top-$rank" : "";
                    ?>
                        <li class="ranking-item">
                            <div class="rank-left">
                                <span class="rank-num <?= $rankClass ?>"><?= $rank ?></span>
                                <span class="rank-name"><?= e($tp['TenSP']) ?></span>
                            </div>
                            <span class="rank-val"><?= number_format((int)$tp['TongBan']) ?> sp</span>
                        </li>
                    <?php 
                        $rank++;
                    endforeach; 
                    ?>
                    <?php if (count($topProducts) === 0): ?>
                        <li style="padding: 20px 0; text-align: center; color: #94a3b8;">Chưa có đơn hàng nào đã giao</li>
                    <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Truyền dữ liệu PHP sang JS để vẽ Chart -->
<script>
    window.dashboardData = {
        labels: <?= json_encode($monthlyLabels) ?>,
        sales: <?= json_encode($monthlySalesData) ?>
    };
</script>
<script src="../js/dashboard.js?v=fresh"></script>
</body>
</html>