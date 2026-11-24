<?php
// File xử lý Sản phẩm - Nông Sản Xanh (admin/process_sanpham.php)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

// Bật báo lỗi Mysqli dạng Exception để dễ bắt catch
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

$action = $_POST['action'] ?? '';
$id     = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

// ----------------------------------------------------
// 1. THÊM SẢN PHẨM MỚI
// ----------------------------------------------------
if ($action === 'add') {
    $name   = trim($_POST['name'] ?? '');
    $price  = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT) ?? 0;
    $unit   = trim($_POST['unit'] ?? 'kg');
    $qty    = filter_input(INPUT_POST, 'qty', FILTER_VALIDATE_INT) ?? 0;
    $cat    = filter_input(INPUT_POST, 'maloaisanpham', FILTER_VALIDATE_INT);
    $desc   = trim($_POST['desc'] ?? '');

    // Xử lý file ảnh upload hoặc tên file nhập tay
    $imageName = 'default.jpg';

    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image_file']['tmp_name'];
        $fileName = time() . '_' . basename($_FILES['image_file']['name']);
        $uploadFileDir = __DIR__ . '/../themes/image/';

        if (!is_dir($uploadFileDir)) {
            mkdir($uploadFileDir, 0755, true);
        }

        if (move_uploaded_file($fileTmpPath, $uploadFileDir . $fileName)) {
            $imageName = $fileName;
        }
    } elseif (!empty($_POST['image_name'])) {
        $imageName = trim($_POST['image_name']);
    }

    if ($name !== '' && $cat) {
        try {
            $stmt = $conn->prepare("
                INSERT INTO sanpham (TenSP, Gia, Donvitinh, Soluongton, maloaisanpham, Hinhanh, Mota, an_hien) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->bind_param('sdsiiss', $name, $price, $unit, $qty, $cat, $imageName, $desc);
            $stmt->execute();

            header('Location: sanpham.php?msg=' . urlencode('Đã thêm sản phẩm mới thành công!'));
            exit;
        } catch (Exception $e) {
            header('Location: sanpham.php?err=' . urlencode('Lỗi thêm sản phẩm: ' . $e->getMessage()));
            exit;
        }
    } else {
        header('Location: sanpham.php?err=' . urlencode('Vui lòng nhập tên và chọn danh mục sản phẩm!'));
        exit;
    }
}

// ----------------------------------------------------
// 2. CẬP NHẬT THÔNG TIN SẢN PHẨM (CHỈNH SỬA)
// ----------------------------------------------------
if ($action === 'update' && $id) {
    $name   = trim($_POST['name'] ?? '');
    $price  = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT) ?? 0;
    $unit   = trim($_POST['unit'] ?? 'kg');
    $qty    = filter_input(INPUT_POST, 'qty', FILTER_VALIDATE_INT) ?? 0;
    $cat    = filter_input(INPUT_POST, 'maloaisanpham', FILTER_VALIDATE_INT);
    $img    = trim($_POST['image'] ?? '');
    $status = filter_input(INPUT_POST, 'an_hien', FILTER_VALIDATE_INT) ?? 1;
    $desc   = trim($_POST['desc'] ?? '');

    // Nếu có chọn file ảnh mới lúc sửa thì ưu tiên lưu file mới
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image_file']['tmp_name'];
        $fileName = time() . '_' . basename($_FILES['image_file']['name']);
        $uploadFileDir = __DIR__ . '/../themes/image/';

        if (!is_dir($uploadFileDir)) {
            mkdir($uploadFileDir, 0755, true);
        }

        if (move_uploaded_file($fileTmpPath, $uploadFileDir . $fileName)) {
            $img = $fileName;
        }
    }

    if ($name !== '' && $cat) {
        try {
            $stmt = $conn->prepare("
                UPDATE sanpham 
                SET TenSP = ?, Gia = ?, Donvitinh = ?, Soluongton = ?, maloaisanpham = ?, Hinhanh = ?, an_hien = ?, Mota = ? 
                WHERE MaSP = ?
            ");
            $stmt->bind_param('sdsiissii', $name, $price, $unit, $qty, $cat, $img, $status, $desc, $id);
            $stmt->execute();

            header('Location: sanpham.php?msg=' . urlencode('Đã cập nhật thông tin sản phẩm thành công!'));
            exit;
        } catch (Exception $e) {
            header('Location: sanpham.php?err=' . urlencode('Lỗi cập nhật sản phẩm: ' . $e->getMessage()));
            exit;
        }
    } else {
        header('Location: sanpham.php?err=' . urlencode('Tên sản phẩm và danh mục không được để trống!'));
        exit;
    }
}

// ----------------------------------------------------
// 3. THAO TÁC NỔI / ẨN SẢN PHẨM NHANH (Nút Con mắt)
// ----------------------------------------------------
if ($action === 'toggle_status' && $id) {
    $currentStatus = filter_input(INPUT_POST, 'current_status', FILTER_VALIDATE_INT);
    $newStatus = ($currentStatus === 1) ? 0 : 1;

    try {
        $stmt = $conn->prepare("UPDATE sanpham SET an_hien = ? WHERE MaSP = ?");
        $stmt->bind_param('ii', $newStatus, $id);
        $stmt->execute();
        
        $msgText = ($newStatus === 1) ? 'Đã hiện sản phẩm trên cửa hàng!' : 'Đã ẩn sản phẩm khỏi cửa hàng!';
        header('Location: sanpham.php?msg=' . urlencode($msgText));
        exit;
    } catch (Exception $e) {
        header('Location: sanpham.php?err=' . urlencode('Cập nhật trạng thái sản phẩm thất bại.'));
        exit;
    }
}

// ----------------------------------------------------
// 4. THAO TÁC XÓA SẢN PHẨM
// ----------------------------------------------------
if ($action === 'delete' && $id) {
    try {
        $stmt = $conn->prepare("DELETE FROM sanpham WHERE MaSP = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        
        header('Location: sanpham.php?msg=' . urlencode('Đã xóa sản phẩm thành công!'));
        exit;
    } catch (Exception $e) {
        header('Location: sanpham.php?err=' . urlencode('Không thể xóa sản phẩm này do đã có trong hóa đơn/đơn hàng.'));
        exit;
    }
}

// Nếu không khớp action nào, chuyển hướng về trang danh sách
header('Location: sanpham.php');
exit;