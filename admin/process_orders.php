<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

function goBack($type, $message) {
    header('Location: orders.php?' . $type . '=' . urlencode($message));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    goBack('err', 'Yêu cầu không hợp lệ.');
}

$action = $_POST['action'] ?? '';
$id     = (int)($_POST['madonhang'] ?? $_POST['id'] ?? 0);
$status = trim($_POST['trangthai'] ?? $_POST['status'] ?? '');

$validStatuses = [
    'Chờ xác nhận',
    'Đang xử lý',
    'Chờ xử lý',
    'Đã thanh toán',
    'Đang giao',
    'Đã giao',
    'Đã hoàn thành',
    'Đã hủy'
];

try {
    if ($id < 1) {
        goBack('err', 'Vui lòng chọn đơn hàng cần xử lý.');
    }

    $formattedCode = '#DH' . str_pad($id, 4, '0', STR_PAD_LEFT);

    if ($action === 'update_status') {
        if (!in_array($status, $validStatuses, true)) {
            goBack('err', 'Trạng thái đơn hàng không hợp lệ.');
        }

        $stmt = $conn->prepare("UPDATE donhang SET TrangThai = ? WHERE MaDonHang = ?");
        $stmt->bind_param('si', $status, $id);
        $stmt->execute();

        goBack('msg', "Cập nhật trạng thái đơn hàng {$formattedCode} thành '{$status}' thành công!");
    }

    if ($action === 'cancel_order') {
        $cancelStatus = 'Đã hủy';
        $stmt = $conn->prepare("UPDATE donhang SET TrangThai = ? WHERE MaDonHang = ?");
        $stmt->bind_param('si', $cancelStatus, $id);
        $stmt->execute();

        goBack('msg', "Đã hủy đơn hàng {$formattedCode} thành công!");
    }

    goBack('err', 'Thao tác không được hỗ trợ.');
} catch (mysqli_sql_exception $ex) {
    error_log('process_orders.php: ' . $ex->getMessage());
    goBack('err', 'Lỗi hệ thống: Không thể cập nhật trạng thái đơn hàng.');
}