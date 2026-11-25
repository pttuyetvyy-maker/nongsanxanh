<?php
$pageTitle = 'Chính sách giao hàng — Nông Sản Xanh';
require_once __DIR__ . '/header.php';
?>

<style>
    .policy-wrap {
        width: 88%;
        max-width: 960px;
        margin: 105px auto 60px;
    }
    .policy-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 36px 40px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.03);
    }
    .policy-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #eaf5e7;
        color: #16834b;
        font-size: 12.5px;
        font-weight: 600;
        padding: 5px 12px;
        border-radius: 999px;
        margin-bottom: 12px;
    }
    .policy-card h1 {
        margin: 0 0 8px 0;
        font-size: 26px;
        color: #1e3a29;
    }
    .policy-lead {
        margin: 0 0 26px 0;
        color: #6b7280;
        font-size: 14.5px;
        line-height: 1.6;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 20px;
    }
    .policy-section {
        margin-bottom: 24px;
    }
    .policy-section h2 {
        display: flex;
        align-items: center;
        gap: 9px;
        font-size: 17px;
        color: #1e3a29;
        margin: 0 0 10px 0;
    }
    .policy-section p,
    .policy-section li {
        font-size: 14.5px;
        color: #374151;
        line-height: 1.7;
    }
    .policy-section ul {
        margin: 8px 0 0 20px;
        padding: 0;
    }
    .policy-section li {
        margin-bottom: 6px;
    }
    .policy-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 12px;
        font-size: 14px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        overflow: hidden;
    }
    .policy-table th {
        background: #eaf5e7;
        color: #1e3a29;
        text-align: left;
        padding: 11px 14px;
        font-weight: 700;
    }
    .policy-table td {
        padding: 11px 14px;
        border-top: 1px solid #f1f5f9;
        color: #374151;
    }
    .policy-nav {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 28px;
        padding-top: 20px;
        border-top: 1px dashed #e5e7eb;
    }
    .policy-nav a {
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 13.5px;
        font-weight: 600;
        text-decoration: none;
        border: 1px solid #d1d5db;
        color: #374151;
        transition: 0.15s;
    }
    .policy-nav a:hover,
    .policy-nav a.current {
        background: #16834b;
        border-color: #16834b;
        color: #fff;
    }
    @media (max-width: 650px) {
        .policy-card { padding: 24px 20px; }
    }
</style>

<main class="policy-wrap">
    <div class="policy-card">
        <span class="policy-badge">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
            <span>Hỗ trợ khách hàng</span>
        </span>

        <h1>Chính sách giao hàng</h1>
        <p class="policy-lead">Thông tin phạm vi vận chuyển, thời gian giao nhận và biểu phí giao hàng tận nơi của Nông Sản Xanh tại TP. Hồ Chí Minh và các khu vực lân cận.</p>

        <div class="policy-section">
            <h2>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#16834b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <span>1. Thời gian & Phạm vi giao hàng</span>
            </h2>
            <table class="policy-table">
                <thead>
                    <tr>
                        <th>Khu vực giao nhận</th>
                        <th>Thời gian dự kiến</th>
                        <th>Phí giao hàng tiêu chuẩn</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Nội thành TP. Hồ Chí Minh</td>
                        <td>Giao nhanh trong ngày (2 – 4 giờ)</td>
                        <td>15.000đ / đơn</td>
                    </tr>
                    <tr>
                        <td>Ngoại thành TP. Hồ Chí Minh</td>
                        <td>Trong vòng 24 giờ kể từ lúc xác nhận</td>
                        <td>15.000đ – 25.000đ / đơn</td>
                    </tr>
                    <tr>
                        <td>Đơn hàng đạt giá trị ưu đãi</td>
                        <td>Theo khung giờ khách hàng lựa chọn</td>
                        <td>Miễn phí vận chuyển (0đ)</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="policy-section">
            <h2>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#16834b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                <span>2. Quy cách đóng gói & Kiểm hàng</span>
            </h2>
            <ul>
                <li>Nông sản được phân loại và đóng gói riêng biệt (rau ăn lá, củ quả, trái cây) nhằm tránh dập nát trong quá trình vận chuyển.</li>
                <li>Quý khách được quyền đồng kiểm sản phẩm cùng nhân viên giao hàng trước khi ký nhận và thanh toán.</li>
            </ul>
        </div>

        <div class="policy-section">
            <h2>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#16834b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                <span>3. Thay đổi thời gian hoặc địa chỉ nhận hàng</span>
            </h2>
            <p>Nếu cần thay đổi giờ nhận hàng hoặc địa chỉ giao khi đơn đang ở trạng thái <em>Đang xử lý</em>, vui lòng gọi ngay Hotline <strong>0907049243</strong> để bộ phận điều phối hỗ trợ kịp thời.</p>
        </div>

        <div class="policy-nav">
            <a href="<?= $rootDir ?>customer/chinhsach_muahang.php">Chính sách mua hàng</a>
            <a href="<?= $rootDir ?>customer/chinhsach_doitra.php">Chính sách đổi trả</a>
            <a href="<?= $rootDir ?>customer/chinhsach_giaohang.php" class="current">Chính sách giao hàng</a>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>