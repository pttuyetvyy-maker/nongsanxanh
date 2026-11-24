-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th10 24, 2026 lúc 09:20 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `nongsanxanh`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `binhluan`
--

CREATE TABLE `binhluan` (
  `MaBinhLuan` int(11) NOT NULL,
  `MaND` int(11) NOT NULL,
  `MaSP` int(11) NOT NULL,
  `NoiDung` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `TraLoiAdmin` text DEFAULT NULL,
  `NgayDanhGia` datetime NOT NULL,
  `TrangThai` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `binhluan`
--

INSERT INTO `binhluan` (`MaBinhLuan`, `MaND`, `MaSP`, `NoiDung`, `TraLoiAdmin`, `NgayDanhGia`, `TrangThai`) VALUES
(9, 1, 1, 'Rau xanh rất tươi, đóng gói cẩn thận. Sẽ tiếp tục ủng hộ shop!', 'Cảm ơn bạn đã tin tưởng và ủng hộ Nông sản Xanh ạ!', '2026-11-10 16:28:56', 1),
(10, 1, 2, 'Trái cây ngọt mát, giao hàng nhanh trong ngày.', NULL, '2026-11-10 16:28:56', 1),
(11, 2, 1, 'Sản phẩm tươi ngon nhưng shipper giao hơi chậm một chút.', 'Shop xin lỗi vì sự chậm trễ này, shop sẽ rút kinh nghiệm ạ!', '2026-11-10 16:28:56', 1),
(12, 2, 3, 'Giá hơi cao hơn chợ một tí nhưng chất lượng rau sạch rất đáng tiền.', NULL, '2026-11-10 16:28:56', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chitietdonhang`
--

CREATE TABLE `chitietdonhang` (
  `MaDonHang` int(11) NOT NULL,
  `MaSP` int(11) NOT NULL,
  `TenSP` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `SoLuong` int(11) NOT NULL,
  `DonGia` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `chitietdonhang`
--

INSERT INTO `chitietdonhang` (`MaDonHang`, `MaSP`, `TenSP`, `SoLuong`, `DonGia`) VALUES
(7, 1, '', 3, 50000.00),
(7, 2, '', 2, 80000.00),
(8, 1, '', 5, 50000.00),
(8, 3, '', 4, 25000.00),
(17, 79, '', 1, 55000.00);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chitietgiohang`
--

CREATE TABLE `chitietgiohang` (
  `MaGioHang` int(11) NOT NULL,
  `MaSP` int(11) NOT NULL,
  `SoLuong` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `donhang`
--

CREATE TABLE `donhang` (
  `MaDonHang` int(11) NOT NULL,
  `NgayDat` datetime NOT NULL,
  `NgayGiao` datetime NOT NULL,
  `TongTien` decimal(15,2) NOT NULL,
  `TrangThai` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `DiaChiGiao` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `MaND` int(11) NOT NULL,
  `MaPTTT` int(11) NOT NULL,
  `MaKM` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `SoDienThoai` varchar(20) NOT NULL,
  `NguoiNhan` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `donhang`
--

INSERT INTO `donhang` (`MaDonHang`, `NgayDat`, `NgayGiao`, `TongTien`, `TrangThai`, `DiaChiGiao`, `MaND`, `MaPTTT`, `MaKM`, `SoDienThoai`, `NguoiNhan`) VALUES
(7, '2026-11-10 16:19:44', '2026-11-10 16:19:44', 150000.00, 'Đang xử lý', '123 Nguyễn Huệ, Quận 1, TP.HCM', 1, 1, 'KM0', '0901234567', 'Nguyễn Văn A'),
(8, '2026-11-10 16:19:44', '2026-11-10 16:19:44', 350000.00, 'Đã giao', '456 Lê Lợi, Quận 1, TP.HCM', 1, 1, 'KM0', '0987654321', 'Trần Thị B'),
(17, '2026-11-10 18:00:28', '2026-11-10 18:00:28', 70000.00, 'Đang xử lý', 'Thành phố Hồ Chí Minh, Việt Nam', 9, 1, 'KM0', '0946378461', 'Tí');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `giohang`
--

CREATE TABLE `giohang` (
  `MaGioHang` int(11) NOT NULL,
  `MaND` int(11) NOT NULL,
  `NgayTao` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `khuyenmai`
--

CREATE TABLE `khuyenmai` (
  `MaKM` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `TenKM` varchar(255) NOT NULL,
  `PhanTramGiam` int(11) NOT NULL,
  `NgayBatDau` datetime NOT NULL,
  `NgayKetThuc` datetime NOT NULL,
  `TrangThai` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `khuyenmai`
--

INSERT INTO `khuyenmai` (`MaKM`, `TenKM`, `PhanTramGiam`, `NgayBatDau`, `NgayKetThuc`, `TrangThai`) VALUES
('KM0', 'Không áp dụng', 0, '2026-11-10 16:19:44', '2027-11-10 16:19:44', 1),
('KM01', 'Giảm giá khách hàng mới', 20, '2026-09-01 00:00:00', '2026-09-30 23:59:59', 1),
('KM02', 'Khuyến mãi mùa thu', 10, '2026-10-01 00:00:00', '2026-10-31 23:59:59', 1),
('KM03', 'Tri ân khách hàng', 15, '2026-11-01 00:00:00', '2026-11-15 23:59:59', 1),
('KM04', 'Mua 2 giảm 20%', 20, '2026-11-10 16:15:00', '2026-12-19 16:15:00', 1),
('KM05', 'TYUIOP1', 10, '2026-11-14 11:14:00', '2026-11-29 11:14:00', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `loaisanpham`
--

CREATE TABLE `loaisanpham` (
  `maloaisanpham` int(11) NOT NULL,
  `tenloaisanpham` varchar(100) NOT NULL,
  `hien_thi` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `loaisanpham`
--

INSERT INTO `loaisanpham` (`maloaisanpham`, `tenloaisanpham`, `hien_thi`) VALUES
(1, 'Rau củ quả', 1),
(2, 'Trái cây', 1),
(3, 'Gạo và ngũ cốc', 1),
(4, 'Thịt, trứng, hải sản', 1),
(5, 'Đặc sản và đồ khô', 1),
(6, 'Sản phẩm chế biến', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `loaisanpham_admin`
--

CREATE TABLE `loaisanpham_admin` (
  `maloaisanpham` int(11) NOT NULL,
  `icon` varchar(30) NOT NULL DEFAULT '?',
  `mota` varchar(255) NOT NULL DEFAULT '',
  `trangthai` varchar(20) NOT NULL DEFAULT 'Hoạt động'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `loaisanpham_admin`
--

INSERT INTO `loaisanpham_admin` (`maloaisanpham`, `icon`, `mota`, `trangthai`) VALUES
(1, '🥬', 'Các loại rau, củ tươi theo mùa', 'Hoạt động'),
(6, '🥗', 'Thực phẩm đóng gói, chế biến sẵn', 'Tạm ẩn');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phuongthucthanhtoan`
--

CREATE TABLE `phuongthucthanhtoan` (
  `MaPTTT` int(11) NOT NULL,
  `TenPTTT` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `phuongthucthanhtoan`
--

INSERT INTO `phuongthucthanhtoan` (`MaPTTT`, `TenPTTT`) VALUES
(1, 'Thanh toán khi nhận hàng (COD)'),
(2, 'Ví MoMo');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `sanpham`
--

CREATE TABLE `sanpham` (
  `MaSP` int(11) NOT NULL,
  `TenSP` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Gia` decimal(10,0) NOT NULL,
  `Donvitinh` varchar(20) NOT NULL,
  `Soluongton` int(11) NOT NULL,
  `Hinhanh` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Mota` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `maloaisanpham` int(11) NOT NULL,
  `an_hien` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1: Hiện, 0: Ẩn'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `sanpham`
--

INSERT INTO `sanpham` (`MaSP`, `TenSP`, `Gia`, `Donvitinh`, `Soluongton`, `Hinhanh`, `Mota`, `maloaisanpham`, `an_hien`) VALUES
(1, 'Cải xanh', 15000, 'bó', 23, 'caixanh.jpg', 'Rau sạch VietGAP', 1, 1),
(2, 'Cà chua', 25000, 'kg', 22, 'cachua.jpg', 'Cà chua Đà Lạt', 1, 1),
(3, 'Xoài cát Hòa Lộc', 45000, 'kg', 42, 'xoai.jpg', 'Chín cây, ngọt thơm', 2, 1),
(4, 'Cam sành', 30000, 'kg', 64, 'cam.jpg', 'Cam sành miền Tây', 2, 1),
(5, 'Thanh long ruột đỏ', 35000, 'kg', 32, 'thanhlong.jpg', 'Thanh long Bình Thuận', 2, 1),
(6, 'Bưởi da xanh', 60000, 'trái', 51, 'buoi.jpg', 'Bưởi Bến Tre', 2, 1),
(7, 'Chuối sứ', 20000, 'kg', 76, 'chuoi.jpg', 'Chuối chín tự nhiên', 2, 1),
(8, 'Dưa hấu', 18000, 'kg', 65, 'duahau.jpg', 'Dưa hấu không hạt', 2, 1),
(9, 'Gạo ST25', 32000, 'kg', 19, 'gaost25.jpg', 'Gạo thơm Sóc Trăng', 3, 1),
(10, 'Trứng gà ta', 45000, 'hộp', 59, 'trung.jpg', 'Hộp 10 trứng', 4, 1),
(11, 'Mật ong rừng', 150000, 'chai', 80, 'matong.jpg', 'Chai 500ml', 5, 1),
(12, 'Cà rốt Đà Lạt', 22000, 'kg', 61, 'carot.jpg', 'Giòn ngọt, giàu vitamin A', 1, 1),
(13, 'Khoai tây Đà Lạt', 28000, 'kg', 65, 'khoaitay.jpg', 'Củ to, bột, chiên hầm đều ngon', 1, 1),
(14, 'Bắp cải xanh', 18000, 'kg', 63, 'bapcai.jpg', 'Bắp chắc, lá xanh tươi', 1, 1),
(15, 'Rau muống', 12000, 'bó', 37, 'raumuong.jpg', 'Rau sạch hái trong ngày', 1, 1),
(16, 'Bí đỏ', 20000, 'kg', 80, 'bido.jpg', 'Thịt dẻo, vị ngọt tự nhiên', 1, 1),
(17, 'Dưa leo', 16000, 'kg', 44, 'dualeo.jpg', 'Giòn mát, ăn sống hoặc làm salad', 1, 1),
(18, 'Sầu riêng Ri6', 120000, 'kg', 65, 'saurieng.jpg', 'Cơm vàng, hạt lép, béo ngậy', 2, 1),
(19, 'Chôm chôm', 35000, 'kg', 28, 'chomchom.jpg', 'Cùi dày, ngọt thanh', 2, 1),
(20, 'Nhãn lồng', 45000, 'kg', 28, 'nhan.jpg', 'Cùi dày, hạt nhỏ, ngọt đậm', 2, 1),
(21, 'Dâu tây Đà Lạt', 150000, 'hộp', 58, 'dautay.jpg', 'Hộp 500g, chín đỏ, thơm', 2, 1),
(22, 'Gạo lứt đỏ', 38000, 'kg', 42, 'gaolut.jpg', 'Tốt cho người ăn kiêng', 3, 1),
(23, 'Nếp cái hoa vàng', 40000, 'kg', 38, 'nep.jpg', 'Dẻo thơm, nấu xôi, làm bánh', 3, 1),
(24, 'Đậu xanh', 45000, 'kg', 66, 'dauxanh.jpg', 'Hạt mẩy, nấu chè, làm bánh', 3, 1),
(25, 'Đậu đen', 42000, 'kg', 52, 'dauden.jpg', 'Nấu chè, nấu nước mát', 3, 1),
(66, 'Hạt sen khô', 160000, 'kg', 63, 'hatsen.jpg', 'Sen Huế, bùi ngọt', 3, 1),
(67, 'Thịt heo ba rọi', 130000, 'kg', 78, 'thitheo.jpg', 'Heo sạch, lớp nạc mỡ đều', 4, 1),
(68, 'Trứng vịt muối', 36000, 'hộp', 38, 'trungvit.jpg', 'Hộp 6 trứng, lòng đỏ bở', 4, 0),
(69, 'Cá lóc đồng', 120000, 'kg', 40, 'caloc.jpg', 'Thịt chắc, ngọt', 4, 1),
(70, 'Tôm sú', 280000, 'kg', 3, 'tomsu.jpg', 'Tươi sống, size vừa', 4, 1),
(71, 'Ức gà phi lê', 75000, 'kg', 57, 'ucga.jpg', 'Thịt sạch, ít mỡ', 4, 1),
(72, 'Hạt điều rang muối', 190000, 'kg', 35, 'hatdieu.jpg', 'Bình Phước, vỏ lụa giòn', 5, 1),
(73, 'Cà phê Buôn Ma Thuột', 120000, 'gói', 3, 'caphe.jpg', 'Gói 500g, rang xay nguyên chất', 5, 1),
(74, 'Trà Thái Nguyên', 150000, 'hộp', 72, 'tra.jpg', 'Hộp 200g, hương cốm thơm', 5, 1),
(75, 'Nước mắm Phú Quốc', 85000, 'chai', 28, 'nuocmam.jpg', 'Chai 500ml, 40 độ đạm', 5, 1),
(76, 'Tiêu đen Phú Quốc', 110000, 'hũ', 5, 'tieu.jpg', 'Hũ 200g, cay nồng thơm', 5, 1),
(77, 'Mứt gừng', 65000, 'hộp', 21, 'mutgung.jpg', 'Hộp 300g, vị cay ấm', 6, 1),
(78, 'Xoài sấy dẻo', 90000, 'gói', 12, 'xoaisay.jpg', 'Gói 250g, không đường hóa học', 6, 1),
(79, 'Chuối sấy giòn', 55000, 'gói', 79, 'chuoisay.jpg', 'Gói 250g, giòn tan', 6, 1),
(80, 'Khô cá lóc', 220000, 'kg', 36, 'khocaloc.jpg', 'Phơi nắng tự nhiên', 6, 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `MaND` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `account_type` varchar(20) DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Hoạt động'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`MaND`, `fullname`, `email`, `phone`, `password`, `account_type`, `created_at`, `reset_token`, `reset_expires`, `status`) VALUES
(1, 'Tuyết Vy', '2531540047@gmail.com', '0907158402', '8bb0cf6eb9b17d0f7d22b456f121257dc1254e1f01665370476383ea776df414', 'admin', '2026-11-08 14:12:33', NULL, NULL, 'Hoạt động'),
(2, 'Tran Thi B', 'tranthib@gmail.com', '0987654321', 'ef797c8118f02dfb649607dd5d3f8c7623048c9c063d532cc95c5ed7a898a64f', 'customer', '2026-11-10 09:28:56', NULL, NULL, 'Hoạt động'),
(6, 'Tèo', 'tuyetvy02022000@gmail.com', '0987452759', '15e2b0d3c33891ebb0f1ef609ec419420c20e320ce94c65fbc8c3312448eb225', 'customer', '2026-11-09 04:57:23', NULL, NULL, 'Hoạt động'),
(9, 'Tí', 'admin@gmail.com', '0946378461', '8bb0cf6eb9b17d0f7d22b456f121257dc1254e1f01665370476383ea776df414', 'customer', '2026-11-10 10:00:17', NULL, NULL, 'Hoạt động');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `binhluan`
--
ALTER TABLE `binhluan`
  ADD PRIMARY KEY (`MaBinhLuan`),
  ADD KEY `MaND` (`MaND`,`MaSP`),
  ADD KEY `MaND_2` (`MaND`),
  ADD KEY `MaSP` (`MaSP`),
  ADD KEY `MaND_3` (`MaND`),
  ADD KEY `MaSP_2` (`MaSP`),
  ADD KEY `MaND_4` (`MaND`),
  ADD KEY `MaSP_3` (`MaSP`);

--
-- Chỉ mục cho bảng `chitietdonhang`
--
ALTER TABLE `chitietdonhang`
  ADD PRIMARY KEY (`MaDonHang`,`MaSP`),
  ADD KEY `TenSP` (`TenSP`),
  ADD KEY `MaDonHang` (`MaDonHang`,`MaSP`),
  ADD KEY `MaDonHang_2` (`MaDonHang`),
  ADD KEY `MaSP` (`MaSP`),
  ADD KEY `MaDonHang_3` (`MaDonHang`),
  ADD KEY `MaSP_2` (`MaSP`),
  ADD KEY `MaDonHang_4` (`MaDonHang`),
  ADD KEY `MaSP_3` (`MaSP`);

--
-- Chỉ mục cho bảng `chitietgiohang`
--
ALTER TABLE `chitietgiohang`
  ADD PRIMARY KEY (`MaGioHang`,`MaSP`),
  ADD KEY `MaGioHang` (`MaGioHang`,`MaSP`),
  ADD KEY `MaGioHang_2` (`MaGioHang`),
  ADD KEY `MaSP` (`MaSP`),
  ADD KEY `MaGioHang_3` (`MaGioHang`),
  ADD KEY `MaSP_2` (`MaSP`),
  ADD KEY `MaGioHang_4` (`MaGioHang`),
  ADD KEY `MaSP_3` (`MaSP`);

--
-- Chỉ mục cho bảng `donhang`
--
ALTER TABLE `donhang`
  ADD PRIMARY KEY (`MaDonHang`),
  ADD KEY `MaND` (`MaND`,`MaPTTT`,`MaKM`),
  ADD KEY `MaND_2` (`MaND`),
  ADD KEY `MaPTTT` (`MaPTTT`),
  ADD KEY `MaKM` (`MaKM`),
  ADD KEY `MaND_3` (`MaND`),
  ADD KEY `MaPTTT_2` (`MaPTTT`),
  ADD KEY `MaKM_2` (`MaKM`),
  ADD KEY `MaND_4` (`MaND`),
  ADD KEY `MaPTTT_3` (`MaPTTT`),
  ADD KEY `MaKM_3` (`MaKM`);

--
-- Chỉ mục cho bảng `giohang`
--
ALTER TABLE `giohang`
  ADD PRIMARY KEY (`MaGioHang`),
  ADD KEY `MaND` (`MaND`),
  ADD KEY `MaND_2` (`MaND`),
  ADD KEY `MaND_3` (`MaND`),
  ADD KEY `MaND_4` (`MaND`);

--
-- Chỉ mục cho bảng `khuyenmai`
--
ALTER TABLE `khuyenmai`
  ADD PRIMARY KEY (`MaKM`);

--
-- Chỉ mục cho bảng `loaisanpham`
--
ALTER TABLE `loaisanpham`
  ADD PRIMARY KEY (`maloaisanpham`);

--
-- Chỉ mục cho bảng `loaisanpham_admin`
--
ALTER TABLE `loaisanpham_admin`
  ADD PRIMARY KEY (`maloaisanpham`);

--
-- Chỉ mục cho bảng `phuongthucthanhtoan`
--
ALTER TABLE `phuongthucthanhtoan`
  ADD PRIMARY KEY (`MaPTTT`);

--
-- Chỉ mục cho bảng `sanpham`
--
ALTER TABLE `sanpham`
  ADD PRIMARY KEY (`MaSP`),
  ADD KEY `maloaisanpham` (`maloaisanpham`),
  ADD KEY `maloaisanpham_2` (`maloaisanpham`),
  ADD KEY `maloaisanpham_3` (`maloaisanpham`),
  ADD KEY `maloaisanpham_4` (`maloaisanpham`),
  ADD KEY `maloaisanpham_5` (`maloaisanpham`),
  ADD KEY `maloaisanpham_6` (`maloaisanpham`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`MaND`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `binhluan`
--
ALTER TABLE `binhluan`
  MODIFY `MaBinhLuan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT cho bảng `donhang`
--
ALTER TABLE `donhang`
  MODIFY `MaDonHang` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT cho bảng `giohang`
--
ALTER TABLE `giohang`
  MODIFY `MaGioHang` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `loaisanpham`
--
ALTER TABLE `loaisanpham`
  MODIFY `maloaisanpham` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT cho bảng `phuongthucthanhtoan`
--
ALTER TABLE `phuongthucthanhtoan`
  MODIFY `MaPTTT` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `sanpham`
--
ALTER TABLE `sanpham`
  MODIFY `MaSP` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `MaND` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `binhluan`
--
ALTER TABLE `binhluan`
  ADD CONSTRAINT `binhluan_ibfk_1` FOREIGN KEY (`MaSP`) REFERENCES `sanpham` (`MaSP`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bl_sp_v2` FOREIGN KEY (`MaSP`) REFERENCES `sanpham` (`MaSP`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bl_user_v2` FOREIGN KEY (`MaND`) REFERENCES `users` (`MaND`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `chitietdonhang`
--
ALTER TABLE `chitietdonhang`
  ADD CONSTRAINT `chitietdonhang_ibfk_1` FOREIGN KEY (`MaSP`) REFERENCES `sanpham` (`MaSP`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ctdh_dh_v2` FOREIGN KEY (`MaDonHang`) REFERENCES `donhang` (`MaDonHang`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ctdh_sp_v2` FOREIGN KEY (`MaSP`) REFERENCES `sanpham` (`MaSP`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `chitietgiohang`
--
ALTER TABLE `chitietgiohang`
  ADD CONSTRAINT `fk_ctgh_gh_v2` FOREIGN KEY (`MaGioHang`) REFERENCES `giohang` (`MaGioHang`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ctgh_sp_v2` FOREIGN KEY (`MaSP`) REFERENCES `sanpham` (`MaSP`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `donhang`
--
ALTER TABLE `donhang`
  ADD CONSTRAINT `donhang_ibfk_1` FOREIGN KEY (`MaKM`) REFERENCES `khuyenmai` (`MaKM`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dh_km_v2` FOREIGN KEY (`MaKM`) REFERENCES `khuyenmai` (`MaKM`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dh_pttt_v2` FOREIGN KEY (`MaPTTT`) REFERENCES `phuongthucthanhtoan` (`MaPTTT`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dh_user_v2` FOREIGN KEY (`MaND`) REFERENCES `users` (`MaND`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `giohang`
--
ALTER TABLE `giohang`
  ADD CONSTRAINT `fk_gh_user_v2` FOREIGN KEY (`MaND`) REFERENCES `users` (`MaND`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `sanpham`
--
ALTER TABLE `sanpham`
  ADD CONSTRAINT `fk_sp_loai` FOREIGN KEY (`maloaisanpham`) REFERENCES `loaisanpham` (`maloaisanpham`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sp_loai_v2` FOREIGN KEY (`maloaisanpham`) REFERENCES `loaisanpham` (`maloaisanpham`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `sanpham_ibfk_1` FOREIGN KEY (`maloaisanpham`) REFERENCES `loaisanpham` (`maloaisanpham`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
