<?php
// Xử lý thêm / cập nhật / xóa danh mục (đã gộp 1 bảng loaisanpham).
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

function goBack($type, $message) {
    header('Location: danhmuc.php?' . $type . '=' . urlencode($message));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    goBack('err', 'Yêu cầu không hợp lệ.');
}

$action = $_POST['action'] ?? '';

try {
    // 1. THÊM DANH MỤC
    if ($action === 'add') {
        $name   = trim($_POST['name'] ?? '');
        $icon   = trim($_POST['icon'] ?? '📦');
        $desc   = trim($_POST['desc'] ?? '');
        $status = $_POST['status'] ?? 'Hoạt động';

        if ($name === '') {
            goBack('err', 'Vui lòng nhập tên danh mục.');
        }

        // Chuyển đổi trạng thái chữ sang 1 (Hoạt động) hoặc 0 (Tạm ẩn) cho cột hien_thi
        $hienThi = ($status === 'Hoạt động') ? 1 : 0;
        if (!in_array($status, ['Hoạt động', 'Tạm ẩn'], true)) {
            $status = 'Hoạt động';
            $hienThi = 1;
        }

        if ($icon === '') $icon = '📦';

        // Kiểm tra trùng tên danh mục
        $check = $conn->prepare("SELECT maloaisanpham FROM loaisanpham WHERE tenloaisanpham = ?");
        $check->bind_param('s', $name);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            goBack('err', 'Tên danh mục đã tồn tại.');
        }

        // Thêm trực tiếp tất cả thông tin vào bảng loaisanpham
        $stmt = $conn->prepare("
            INSERT INTO loaisanpham (tenloaisanpham, hien_thi, icon, mota, trangthai) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('sisss', $name, $hienThi, $icon, $desc, $status);
        $stmt->execute();

        goBack('msg', 'Thêm danh mục thành công!');
    }

    // 2. CẬP NHẬT DANH MỤC
    if ($action === 'update') {
        $id     = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $name   = trim($_POST['name'] ?? '');
        $icon   = trim($_POST['icon'] ?? '📦');
        $desc   = trim($_POST['desc'] ?? '');
        $status = $_POST['status'] ?? 'Hoạt động';

        if (!$id || $id < 1 || $name === '') {
            goBack('err', 'Thông tin danh mục không hợp lệ.');
        }

        if (!in_array($status, ['Hoạt động', 'Tạm ẩn'], true)) {
            goBack('err', 'Trạng thái không hợp lệ.');
        }

        $hienThi = ($status === 'Hoạt động') ? 1 : 0;
        if ($icon === '') $icon = '📦';

        // Kiểm tra trùng tên danh mục ở các ID khác
        $check = $conn->prepare("
            SELECT maloaisanpham FROM loaisanpham
            WHERE tenloaisanpham = ? AND maloaisanpham <> ?
        ");
        $check->bind_param('si', $name, $id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            goBack('err', 'Tên danh mục này đã được sử dụng.');
        }

        // Cập nhật toàn bộ thông tin trong bảng loaisanpham
        $stmt = $conn->prepare("
            UPDATE loaisanpham 
            SET tenloaisanpham = ?, hien_thi = ?, icon = ?, mota = ?, trangthai = ? 
            WHERE maloaisanpham = ?
        ");
        $stmt->bind_param('sisssi', $name, $hienThi, $icon, $desc, $status, $id);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            $exists = $conn->prepare("SELECT maloaisanpham FROM loaisanpham WHERE maloaisanpham = ?");
            $exists->bind_param('i', $id);
            $exists->execute();
            if ($exists->get_result()->num_rows === 0) {
                goBack('err', 'Không tìm thấy danh mục cần cập nhật.');
            }
        }

        goBack('msg', 'Cập nhật danh mục thành công!');
    } // <--- Dấu ngoặc đóng bị thiếu nằm ở đây

    // 3. XÓA DANH MỤC
    if ($action === 'delete') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$id || $id < 1) {
            goBack('err', 'Mã danh mục không hợp lệ.');
        }

        // Không cho xóa danh mục đang có sản phẩm để tránh lỗi khóa ngoại
        $count = $conn->prepare("SELECT COUNT(*) AS total FROM sanpham WHERE maloaisanpham = ?");
        $count->bind_param('i', $id);
        $count->execute();
        $total = (int)$count->get_result()->fetch_assoc()['total'];

        if ($total > 0) {
            goBack('err', 'Danh mục đang có sản phẩm. Hãy chuyển sản phẩm sang danh mục khác hoặc tạm ẩn danh mục.');
        }

        $stmt = $conn->prepare("DELETE FROM loaisanpham WHERE maloaisanpham = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();

        if ($stmt->affected_rows < 1) {
            goBack('err', 'Không tìm thấy danh mục cần xóa.');
        }

        goBack('msg', 'Xóa danh mục thành công!');
    }

    goBack('err', 'Thao tác không được hỗ trợ.');
} catch (mysqli_sql_exception $ex) {
    error_log('process_danhmuc.php: ' . $ex->getMessage());
    goBack('err', 'Không thể lưu thay đổi. Kiểm tra cấu trúc bảng hoặc dữ liệu liên quan.');
}