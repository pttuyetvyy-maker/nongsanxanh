<?php
// Bật hiển thị lỗi để debug nếu cần
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';

$ma_nhap_vao = isset($_GET['code']) ? trim($_GET['code']) : '';

if ($ma_nhap_vao === '') {
    echo json_encode(["success" => false, "message" => "Vui lòng nhập mã giảm giá!"]);
    exit;
}

// Truy vấn lấy thông tin mã giảm giá (Sử dụng Prepared Statement & đúng tên cột PhanTramGiam)
$stmt = $conn->prepare(
    "SELECT PhanTramGiam, NgayBatDau, NgayKetThuc 
     FROM khuyenmai 
     WHERE MaKM = ? 
     LIMIT 1"
);
$stmt->bind_param("s", $ma_nhap_vao);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $today = date('Y-m-d H:i:s');
    
    // Kiểm tra thời hạn mã giảm giá
    if ($today >= $row['NgayBatDau'] && $today <= $row['NgayKetThuc']) {
        echo json_encode([
            "success" => true, 
            "percent" => (int)$row['PhanTramGiam']
        ]);
    } else {
        echo json_encode([
            "success" => false, 
            "message" => "Mã giảm giá đã hết hạn hoặc chưa đến ngày áp dụng!"
        ]);
    }
} else {
    echo json_encode([
        "success" => false, 
        "message" => "Mã giảm giá không tồn tại!"
    ]);
}

$stmt->close();
$conn->close();
?>