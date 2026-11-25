<?php
$pageTitle = 'Chính sách mua hàng — Nông Sản Xanh';
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
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            <span>Hỗ trợ khách hàng</span>
        </span>

        <h1>Chính sách mua hàng</h1>
        <p class="policy-lead">Hướng dẫn quy trình đặt mua nông sản sạch trực tuyến, hình thức thanh toán và cam kết chất lượng tại Nông Sản Xanh.</p>

        <div class="policy-section">
            <h2>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#16834b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <span>1. Quy trình đặt hàng trực tuyến</span>
            </h2>
            <ul>
                <li><strong>Bước 1:</strong> Đăng nhập hoặc đăng ký tài khoản thành viên tại Nông Sản Xanh để dễ dàng theo dõi tiến độ đơn hàng.</li>
                <li><strong>Bước 2:</strong> Chọn loại nông sản cần mua tại mục <em>Danh mục</em> hoặc <em>Sản phẩm</em>, điều chỉnh số lượng và bấm <em>Thêm vào giỏ</em> hoặc <em>Mua ngay</em>.</li>
                <li><strong>Bước 3:</strong> Kiểm tra lại giỏ hàng, nhập thông tin người nhận, số điện thoại, địa chỉ giao hàng và áp dụng mã khuyến mãi (nếu có).</li>
                <li><strong>Bước 4:</strong> Xác nhận đặt hàng. Hệ thống sẽ ghi nhận mã đơn hàng <code>#DH...</code> để bạn theo dõi trực tiếp tại mục <em>Đơn hàng của tôi</em>.</li>
            </ul>
        </div>

        <div class="policy-section">
            <h2>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#16834b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                <span>2. Hình thức thanh toán</span>
            </h2>
            <ul>
                <li><strong>Thanh toán khi nhận hàng (COD):</strong> Khách hàng kiểm tra độ tươi ngon và số lượng nông sản trước khi thanh toán tiền mặt cho nhân viên giao hàng.</li>
                <li><strong>Chuyển khoản ngân hàng / Ví điện tử:</strong> Hỗ trợ thanh toán nhanh qua chuyển khoản theo thông tin hiển thị tại bước thanh toán đơn hàng.</li>
            </ul>
        </div>

        <div class="policy-section">
            <h2>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#16834b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <span>3. Cam kết chất lượng & Hỗ trợ đặt đơn</span>
            </h2>
            <p>Toàn bộ rau củ, trái cây và nông sản tại Nông Sản Xanh đều được thu hoạch trong ngày, không chất bảo quản độc hại và có nguồn gốc rõ ràng. Nếu cần hỗ trợ đặt hàng nhanh, vui lòng liên hệ Hotline <strong>0907049243</strong> hoặc Email <strong>nhom11@vaa.edu.vn</strong>.</p>
        </div>

        <div class="policy-nav">
            <a href="<?= $rootDir ?>customer/chinhsach_muahang.php" class="current">Chính sách mua hàng</a>
            <a href="<?= $rootDir ?>customer/chinhsach_doitra.php">Chính sách đổi trả</a>
            <a href="<?= $rootDir ?>customer/chinhsach_giaohang.php">Chính sách giao hàng</a>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>