<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1" name="viewport"/>
<title>Nông sản Xanh — Đơn hàng</title>
<link href="style_static.css" rel="stylesheet"/>
</head>
<body>
<div class="shell">
<aside class="sidebar">
<div class="brand">
<div class="brand-mark">🌾</div>
<div class="brand-text">
<strong>Nông sản Xanh</strong>
<span>Quản trị hệ thống</span>
</div>
</div>
<div class="nav-group-label">TỔNG QUAN</div>
<ul class="nav">
<li><a class="nav-btn" href="dashboard.php"><span class="icon">▦</span> Dashboard</a></li>
</ul>
<div class="nav-group-label">QUẢN LÝ</div>
<ul class="nav">
<li><a class="nav-btn" href="san_pham.php"><span class="icon">🥬</span> Sản phẩm</a></li>
<li><a class="nav-btn" href="danh_muc.php"><span class="icon">🗂</span> Danh mục</a></li>
<li><a class="nav-btn active" href="orders.php"><span class="icon">🧾</span> Đơn hàng</a></li>
<li><a class="nav-btn" href="dia_chi.php"><span class="icon">📍</span> Địa chỉ giao hàng</a></li>
</ul>
</aside>

<div class="main">
<div class="topbar">
<div class="topbar-title" id="topbar-title">Đơn hàng<span id="topbar-sub">Quản lý đơn hàng</span></div>
<div class="topbar-right">
<div class="admin-chip">
<div class="avatar-dot">TV</div>
<span>Admin</span>
</div>
</div>
</div>
<div class="content">
<section class="panel active" id="panel-orders">
<div class="toolbar">
<div class="toolbar-left">
<div class="search-box">🔍 <input id="orders-search" placeholder="Mã đơn / tên khách hàng..." type="text"/></div>
<select class="filter-select" id="orders-filter-status">
<option value="">Trạng thái: Tất cả</option>
<option value="Chờ xử lý">Chờ xử lý</option>
<option value="Đã thanh toán">Đã thanh toán</option>
<option value="Đã hủy">Đã hủy</option>
</select>
</div>
</div>
<div class="table-card">
<table>
<thead>
<tr><th>Mã đơn</th><th>Khách hàng</th><th>Ngày đặt</th><th>Tổng tiền</th><th>Trạng thái</th><th>Thao tác</th></tr>
</thead>
<tbody id="orders-tbody">
<tr class="row-selected" data-order="DH001" data-status="Đã thanh toán" id="row-DH001">
<td>#DH001</td><td>Nguyễn Văn A</td><td>10/09/2026</td><td>500.000đ</td>
<td><span class="badge badge-green" id="badge-DH001">Đã thanh toán</span></td>
<td><div class="row-actions"><button class="icon-btn btn-view">👁</button></div></td>
</tr>
<tr class="" data-order="DH002" data-status="Chờ xử lý" id="row-DH002">
<td>#DH002</td><td>Trần Thị B</td><td>11/09/2026</td><td>250.000đ</td>
<td><span class="badge badge-amber" id="badge-DH002">Chờ xử lý</span></td>
<td><div class="row-actions"><button class="icon-btn btn-view">👁</button></div></td>
</tr>
<tr class="" data-order="DH003" data-status="Đã thanh toán" id="row-DH003">
<td>#DH003</td><td>Lê Văn C</td><td>11/09/2026</td><td>1.200.000đ</td>
<td><span class="badge badge-green" id="badge-DH003">Đã thanh toán</span></td>
<td><div class="row-actions"><button class="icon-btn btn-view">👁</button></div></td>
</tr>
<tr class="" data-order="DH004" data-status="Đã hủy" id="row-DH004">
<td>#DH004</td><td>Phạm Thị D</td><td>12/09/2026</td><td>340.000đ</td>
<td><span class="badge badge-red" id="badge-DH004">Đã hủy</span></td>
<td><div class="row-actions"><button class="icon-btn btn-view">👁</button></div></td>
</tr>
<tr class="" data-order="DH005" data-status="Chờ xử lý" id="row-DH005">
<td>#DH005</td><td>Hoàng Văn E</td><td>12/09/2026</td><td>620.000đ</td>
<td><span class="badge badge-amber" id="badge-DH005">Chờ xử lý</span></td>
<td><div class="row-actions"><button class="icon-btn btn-view">👁</button></div></td>
</tr>
<tr class="" data-order="DH006" data-status="Đã thanh toán" id="row-DH006">
<td>#DH006</td><td>Đỗ Thị F</td><td>13/09/2026</td><td>180.000đ</td>
<td><span class="badge badge-green" id="badge-DH006">Đã thanh toán</span></td>
<td><div class="row-actions"><button class="icon-btn btn-view">👁</button></div></td>
</tr>
<tr class="" data-order="DH007" data-status="Chờ xử lý" id="row-DH007">
<td>#DH007</td><td>Vũ Văn G</td><td>13/09/2026</td><td>990.000đ</td>
<td><span class="badge badge-amber" id="badge-DH007">Chờ xử lý</span></td>
<td><div class="row-actions"><button class="icon-btn btn-view">👁</button></div></td>
</tr>
<tr class="" data-order="DH008" data-status="Đã thanh toán" id="row-DH008">
<td>#DH008</td><td>Bùi Thị H</td><td>14/09/2026</td><td>410.000đ</td>
<td><span class="badge badge-green" id="badge-DH008">Đã thanh toán</span></td>
<td><div class="row-actions"><button class="icon-btn btn-view">👁</button></div></td>
</tr>
</tbody>
</table>
<div class="table-foot">
<span class="count" id="orders-count"></span>
<div class="pagination" id="orders-pagination"></div>
</div>
</div>

<div class="order-layout">
<div class="detail-card">
<h3>Chi tiết đơn hàng <span id="detail-order-id">#DH001</span></h3>
<div class="kv-row"><span class="k">Khách hàng</span><span class="v" id="detail-cust">—</span></div>
<div class="kv-row"><span class="k">Email</span><span class="v" id="detail-email">—</span></div>
<div class="kv-row"><span class="k">Điện thoại</span><span class="v" id="detail-phone">—</span></div>
<div class="kv-row"><span class="k">Địa chỉ giao</span><span class="v" id="detail-addr">—</span></div>
<div class="line-items" id="detail-items"></div>
<div class="total-row"><span>Tổng tiền</span><span id="detail-total">—</span></div>
</div>
<div class="detail-card">
<h3>Xử lý đơn hàng</h3>
<div class="kv-row"><span class="k">Phương thức thanh toán</span><span class="v" id="detail-pay">—</span></div>
<div class="kv-row"><span class="k">Ngày đặt</span><span class="v" id="detail-date">—</span></div>
<label style="font-size:12px; color:var(--ink-soft); display:block; margin-top:14px;">Trạng thái đơn hàng</label>
<select class="status-select" disabled="disabled" id="detail-status-select">
<option>Chờ xác nhận</option>
<option>Chờ xử lý</option>
<option>Đã thanh toán</option>
<option>Đang giao</option>
<option>Hoàn tất</option>
<option>Đã hủy</option>
</select>
<div class="detail-actions">
<button class="btn btn-primary" disabled="disabled" id="btn-update-order">Cập nhật</button>
<button class="btn btn-ghost" disabled="disabled" id="btn-cancel-order">Hủy đơn</button>
</div>
</div>
</div>
</section>
</div>
</div>
</div>

<script src="orders.js"></script>
</body>
</html>