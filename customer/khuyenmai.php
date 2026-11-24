<?php
$pageTitle = 'Mã Khuyến Mãi - Nông Sản Xanh';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../config/db.php';

// Truy vấn lấy danh sách mã khuyến mãi còn hạn từ CSDL
$sql = "SELECT `MaKM`, `TenKM`, `PhanTramGiam`, `NgayBatDau`, `NgayKetThuc` 
        FROM `khuyenmai` 
        WHERE `NgayBatDau` <= NOW() AND `NgayKetThuc` >= NOW()
        ORDER BY `NgayKetThuc` ASC";
$result = $conn->query($sql);
?>

<style>
.km-container {
    max-width: 1000px;
    margin: 40px auto;
    padding: 0 20px;
}
.km-title {
    text-align: center;
    margin-bottom: 30px;
    color: #166534;
}
.km-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}
.km-card {
    background: #ffffff;
    border: 2px dashed #22c55e;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.km-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}
.km-code {
    font-size: 18px;
    font-weight: 700;
    color: #15803d;
    background: #f0fdf4;
    padding: 6px 12px;
    border-radius: 6px;
    letter-spacing: 1px;
}
.km-discount {
    font-size: 18px;
    font-weight: 800;
    color: #dc2626;
}
.km-name {
    font-weight: 600;
    font-size: 16px;
    color: #1f2937;
    margin-bottom: 8px;
}
.km-date {
    color: #6b7280;
    font-size: 13px;
    margin-bottom: 15px;
    line-height: 1.4;
}
.km-btn {
    background-color: #22c55e;
    color: white;
    border: none;
    padding: 10px 16px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    width: 100%;
    transition: background 0.2s;
}
.km-btn:hover {
    background-color: #15803d;
}
.km-empty {
    text-align: center;
    padding: 50px 0;
    color: #6b7280;
}
</style>

<main class="km-container">
    <div class="km-title">
        <h1>🎁 Mã Khuyến Mãi Ưu Đãi</h1>
        <p>Lưu ngay các mã giảm giá hấp dẫn để sử dụng khi mua hàng!</p>
    </div>

    <?php if ($result && $result->num_rows > 0): ?>
        <div class="km-grid">
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="km-card">
                    <div>
                        <div class="km-header">
                            <span class="km-code"><?= htmlspecialchars($row['MaKM']) ?></span>
                            <span class="km-discount">-<?= (int)$row['PhanTramGiam'] ?>%</span>
                        </div>
                        <div class="km-name"><?= htmlspecialchars($row['TenKM']) ?></div>
                        <div class="km-date">
                            🕒 Từ: <?= date('d/m/Y H:i', strtotime($row['NgayBatDau'])) ?><br>
                            ⏳ Đến: <?= date('d/m/Y H:i', strtotime($row['NgayKetThuc'])) ?>
                        </div>
                    </div>
                    <button class="km-btn" onclick="copyCode('<?= htmlspecialchars($row['MaKM']) ?>', this)">Sao chép mã</button>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="km-empty">
            <h3>Hiện chưa có mã khuyến mãi nào khả dụng!</h3>
            <p>Vui lòng quay lại sau nhé.</p>
        </div>
    <?php endif; ?>
</main>

<script>
function copyCode(code, btnElement) {
    navigator.clipboard.writeText(code).then(() => {
        const oldText = btnElement.innerText;
        btnElement.innerText = 'Đã chép mã! ✓';
        btnElement.style.backgroundColor = '#166534';
        setTimeout(() => {
            btnElement.innerText = oldText;
            btnElement.style.backgroundColor = '#22c55e';
        }, 2000);
    }).catch(err => {
        alert('Lỗi sao chép mã!');
    });
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>