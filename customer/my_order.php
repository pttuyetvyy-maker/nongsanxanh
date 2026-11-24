<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/header.php';

if (empty($_SESSION['user_id'])) {
    header("Location: " . $rootDir . "customer/login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// Lấy danh sách đơn hàng của Khách hàng hiện tại
$sql = "SELECT * FROM donhang WHERE MaND = ? ORDER BY NgayDat DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$ds_donhang = $stmt->get_result();

// Trạng thái lưu dạng chữ tiếng Việt (khớp danh sách trong admin/process_orders.php)
$trang_thai_label = [
    'Chờ xác nhận'  => ['text' => '⏳ Chờ xác nhận',         'color' => '#f59e0b'],
    'Chờ xử lý'     => ['text' => '⏳ Chờ xử lý',            'color' => '#f59e0b'],
    'Đang xử lý'    => ['text' => '⚙️ Đang xử lý',           'color' => '#6b7280'],
    'Đã thanh toán' => ['text' => '✅ Đã thanh toán',        'color' => '#3b82f6'],
    'Đang giao'     => ['text' => '🚚 Đang giao hàng',       'color' => '#8b5cf6'],
    'Đã hoàn thành' => ['text' => '🎉 Giao hàng thành công', 'color' => '#10b981'],
    'Đã hủy'        => ['text' => '❌ Đơn hàng đã hủy',      'color' => '#ef4444'],
];
?>

<main class="container page" style="margin-top: 30px; margin-bottom: 50px;">
    <h2>📦 Danh sách đơn hàng của bạn</h2>
    <p style="color: #666; margin-bottom: 20px;">Theo dõi tiến độ xử lý và giao nhận nông sản</p>

    <?php if ($ds_donhang->num_rows == 0): ?>
        <div style="background: #fff; padding: 40px; text-align: center; border-radius: 12px;">
            <p>Bạn chưa có đơn hàng nào.</p>
            <a href="<?= $rootDir ?>index1.php" style="color: #22c55e; font-weight: bold;">Đi mua sắm ngay →</a>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 15px;">
            <?php while ($dh = $ds_donhang->fetch_assoc()): ?>
                <?php 
                    $tt = $trang_thai_label[$dh['TrangThai']] ?? ['text' => $dh['TrangThai'], 'color' => '#666'];
                ?>
                <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="margin: 0 0 5px 0;">Mã đơn: #<?= 'DH' . str_pad((string)($dh['MaDonHang'] ?? $dh['id'] ?? 0), 4, '0', STR_PAD_LEFT) ?></h3>
                        <p style="margin: 0; color: #666; font-size: 14px;">Ngày đặt: <?= date('d/m/Y H:i', strtotime($dh['NgayDat'])) ?></p>
                        <p style="margin: 5px 0 0 0; font-weight: bold; color: #15803d;">Tổng tiền: <?= number_format($dh['TongTien'], 0, ',', '.') ?>đ</p>
                    </div>

                    <div style="text-align: right;">
                        <span style="display: inline-block; padding: 6px 14px; border-radius: 20px; color: #fff; font-size: 13px; font-weight: bold; background-color: <?= $tt['color'] ?>;">
                            <?= $tt['text'] ?>
                        </span>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/footer.php'; ?>