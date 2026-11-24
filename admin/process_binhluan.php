<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';

$action = $_POST['action'] ?? '';
$mabl   = (int)($_POST['mabinhluan'] ?? 0);

if ($action === 'reply' && $mabl > 0) {
    $reply = trim($_POST['traloi'] ?? '');
    $stmt  = $conn->prepare("UPDATE binhluan SET TraLoiAdmin=? WHERE MaBinhLuan=?");
    $stmt->bind_param("si", $reply, $mabl);
    if ($stmt->execute()) {
        header("Location: binhluan.php?msg=" . urlencode("Đã gửi phản hồi thành công!"));
    } else {
        header("Location: binhluan.php?err=" . urlencode("Lỗi khi phản hồi!"));
    }
    exit();
}

if ($action === 'toggle_status' && $mabl > 0) {
    $status = (int)$_POST['status'];
    $stmt   = $conn->prepare("UPDATE binhluan SET TrangThai=? WHERE MaBinhLuan=?");
    $stmt->bind_param("ii", $status, $mabl);
    if ($stmt->execute()) {
        $msg = $status == 1 ? "Đã hiển thị bình luận!" : "Đã ẩn bình luận thành công!";
        header("Location: binhluan.php?msg=" . urlencode($msg));
    } else {
        header("Location: binhluan.php?err=" . urlencode("Lỗi cập nhật trạng thái!"));
    }
    exit();
}

if ($action === 'delete' && $mabl > 0) {
    $stmt = $conn->prepare("DELETE FROM binhluan WHERE MaBinhLuan=?");
    $stmt->bind_param("i", $mabl);
    if ($stmt->execute()) {
        header("Location: binhluan.php?msg=" . urlencode("Đã xóa bình luận!"));
    } else {
        header("Location: binhluan.php?err=" . urlencode("Không thể xóa bình luận này!"));
    }
    exit();
}

header("Location: binhluan.php");
exit();