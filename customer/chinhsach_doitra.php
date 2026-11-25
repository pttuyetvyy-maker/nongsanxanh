<?php
$pageTitle = 'Chính sách đổi trả — Nông Sản Xanh';
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
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
            <span>Hỗ trợ khách hàng</span>
        </span>

        <h1>Chính sách đổi trả</h1>
        <p class="policy-lead">Nông Sản Xanh cam kết bảo vệ quyền lợi người tiêu dùng với chính sách đổi trả minh bạch đối với các mặt hàng nông sản tươi sống.</p>

        <div class="policy-section">
            <h2>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#16834b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                <span>1. Điều kiện áp dụng đổi trả</span>
            </h2>
            <ul>
                <li>Nông sản bị dập nát, hư hỏng, héo úa hoặc có dấu hiệu mất an toàn thực phẩm ngay tại thời điểm nhận hàng.</li>
                <li>Giao sai chủng loại sản phẩm, thiếu số lượng hoặc sai trọng lượng so với đơn hàng đã đặt.</li>
                <li>Sản phẩm đóng gói bị rách bao bì, hết hạn sử dụng hoặc lỗi từ khâu bảo quản của cửa hàng.</li>
            </ul>
        </div>

        <div class="policy-section">
            <h2>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#16834b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <span>2. Thời gian thông báo & Xử lý</span>
            </h2>
            <ul>
                <li><strong>Đối với rau củ, trái cây tươi sống:</strong> Quý khách vui lòng kiểm tra hàng khi nhận và phản hồi trong vòng <strong>24 giờ</strong> kể từ lúc giao hàng thành công.</li>
                <li><strong>Đối với nông sản khô, đóng gói:</strong> Hỗ trợ đổi trả trong vòng <strong>03 ngày</strong> kể từ ngày nhận hàng (sản phẩm còn nguyên tem, nhãn mác).</li>
            </ul>
        </div>

        <div class="policy-section">
            <h2>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#16834b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <span>3. Phương thức đổi hàng hoặc hoàn tiền</span>
            </h2>
            <p>Quý khách chỉ cần chụp ảnh tình trạng sản phẩm kèm mã đơn hàng gửi qua Hotline <strong>0907049243</strong> hoặc Email <strong>nhom11@vaa.edu.vn</strong>. Cửa hàng sẽ đổi bù sản phẩm mới <strong>miễn phí 100% phí vận chuyển</strong> hoặc hoàn lại tiền tương ứng trong vòng 24 giờ.</p>
        </div>

        <div class="policy-nav">
            <a href="<?= $rootDir ?>customer/chinhsach_muahang.php">Chính sách mua hàng</a>
            <a href="<?= $rootDir ?>customer/chinhsach_doitra.php" class="current">Chính sách đổi trả</a>
            <a href="<?= $rootDir ?>customer/chinhsach_giaohang.php">Chính sách giao hàng</a>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>