<?php
// Xử lý cập nhật trạng thái đơn hàng
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
$id     = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$status = trim($_POST['status'] ?? '');

$validStatuses = ['Chờ xác nhận', 'Chờ xử lý', 'Đã thanh toán', 'Đang giao', 'Đã hoàn thành', 'Đã hủy'];

try {
    if ($action === 'update_status') {
        if (!$id || $id < 1) {
            goBack('err', 'Vui lòng chọn đơn hàng cần cập nhật.');
        }

        if (!in_array($status, $validStatuses, true)) {
            goBack('err', 'Trạng thái đơn hàng không hợp lệ.');
        }

        // Cập nhật CSDL
        $stmt = $conn->prepare("UPDATE donhang SET TrangThai = ? WHERE MaDonHang = ?");
        $stmt->bind_param('si', $status, $id);
        $stmt->execute();

        $formattedCode = '#DH' . str_pad($id, 4, '0', STR_PAD_LEFT);
        goBack('msg', "Cập nhật trạng thái đơn hàng {$formattedCode} thành '{$status}' thành công!");
    }

    goBack('err', 'Thao tác không được hỗ trợ.');
} catch (mysqli_sql_exception $ex) {
    error_log('process_orders.php: ' . $ex->getMessage());
    goBack('err', 'Lỗi hệ thống: Không thể cập nhật trạng thái đơn hàng.');
}