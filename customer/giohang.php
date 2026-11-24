<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/header.php';
?>

<main class="container page">
    <div class="cart-title-section">
        <h1>Giỏ hàng của bạn</h1>
        <p class="subtitle">Kiểm tra sản phẩm trước khi thanh toán</p>
    </div>

    <div class="cart-layout" style="display: flex; gap: 30px; margin-top: 20px;">
        <!-- CỘT TRÁI -->
        <div class="cart-left" style="flex: 2;">
            <div class="card" style="background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <h2>Sản phẩm đã chọn</h2>
                <div id="cart-items-list" style="margin-top: 15px;">
                    <p>Đang tải giỏ hàng...</p>
                </div>
                <div style="margin-top: 20px;">
                    <a href="<?= $rootDir ?>index1.php" style="color: #22c55e; text-decoration: none; font-weight: bold;">← Tiếp tục mua hàng</a>
                </div>
            </div>
        </div>

        <!-- CỘT PHẢI -->
        <div class="cart-right" style="flex: 1;">
            <div class="card" style="background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <h2>Tóm tắt đơn hàng</h2>
                
                <div style="display: flex; gap: 10px; margin: 15px 0;">
                    <input type="text" id="km-code" placeholder="Nhập mã giảm giá (VD: KM03)" style="flex: 1; padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px;">
                    <button onclick="apDungMaKM()" style="background: #15803d; color: #fff; border: none; padding: 8px 15px; border-radius: 6px; cursor: pointer;">Áp dụng</button>
                <button onclick="xoaMaKM()" style="background: #ef4444; color: #fff; border: none; padding: 8px 15px; border-radius: 6px; cursor: pointer;">Xóa mã</button>

                </div>

                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span>Tạm tính</span>
                    <strong id="cart-subtotal">0đ</strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span>Phí vận chuyển</span>
                    <strong>0đ</strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; color: #22c55e;">
                    <span>Giảm giá</span>
                    <strong id="cart-discount">-0đ</strong>
                </div>
                <hr style="border: none; border-top: 1px solid #eee; margin: 15px 0;">
                <div style="display: flex; justify-content: space-between; font-size: 18px; font-weight: bold; margin-bottom: 20px;">
                    <span>Tổng tiền</span>
                    <strong id="cart-total" style="color: #15803d;">0đ</strong>
                </div>

                <button id="btn-checkout" onclick="tienHanhThanhToan()" style="width: 100%; background: #22c55e; color: #fff; border: none; padding: 12px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 16px;">
                    Tiến hành thanh toán →
                </button>
            </div>
        </div>
    </div>
</main>

<script>
    let tongTienGoc = 0; 
let phanTramGiam = 0;
document.addEventListener('DOMContentLoaded', loadGioHang);

function loadGioHang() {
    fetch('<?= $rootDir ?>customer/api_giohang.php?action=lay_gio_hang')
        .then(res => res.json())
        .then(data => {
            if (data.require_login) {
                alert('Vui lòng đăng nhập!');
                window.location.href = '<?= $rootDir ?>customer/login.php';
                return;
            }

            const container = document.getElementById('cart-items-list');
            if (!data.success || !data.items || data.items.length === 0) {
                container.innerHTML = '<p style="padding: 20px 0; color: #666;">Giỏ hàng của bạn đang trống!</p>';
                document.getElementById('cart-subtotal').innerText = '0đ';
                document.getElementById('cart-total').innerText = '0đ';
                if(document.getElementById('header-cart-count')) {
                    document.getElementById('header-cart-count').innerText = '0';
                }
                return;
            }

            // Cập nhật số đếm Header
            if(document.getElementById('header-cart-count')) {
                document.getElementById('header-cart-count').innerText = data.cart_count;
            }

            // Render danh sách sản phẩm
            let html = '';
            data.items.forEach(item => {
                html += `
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #eee;">
                        <div style="display: flex; align-items: center; gap: 15px; flex: 2;">
                            <img src="${item.image}" alt="${item.name}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;" onerror="this.src='<?= $rootDir ?>themes/images/placeholder.jpg'">
                            <div>
                                <h4 style="margin: 0; font-size: 16px;">${item.name}</h4>
                                <small style="color: #777;">${Number(item.price).toLocaleString('vi-VN')}đ / ${item.unit || 'kg'}</small>
                            </div>
                        </div>
                        <div style="flex: 1; text-align: center;">
                            <input type="number" value="${item.quantity}" min="1" onchange="capNhatSoLuong(${item.id}, this.value)" style="width: 50px; padding: 5px; text-align: center; border: 1px solid #ccc; border-radius: 4px;">
                        </div>
                        <div style="flex: 1; text-align: right; font-weight: bold; color: #15803d;">
                            ${Number(item.subtotal).toLocaleString('vi-VN')}đ
                        </div>
                        <div style="margin-left: 15px;">
                            <button onclick="xoaSanPham(${item.id})" style="background: none; border: none; color: #ef4444; cursor: pointer; font-size: 18px;">🗑️</button>
                        </div>
                    </div>
                `;
            });

   container.innerHTML = html;         
tongTienGoc = Number(data.total);
capNhatGiaoDienTien();
        })
        .catch(err => {
            console.error(err);
            document.getElementById('cart-items-list').innerHTML = '<p style="color: red;">Lỗi kết nối API giỏ hàng!</p>';
        });
}

function capNhatSoLuong(id, soluong) {
    fetch('<?= $rootDir ?>customer/api_giohang.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'cap_nhat_so_luong', id: id, soluong: parseInt(soluong) })
    }).then(() => loadGioHang());
}

function xoaSanPham(id) {
    if (confirm('Bạn muốn xóa sản phẩm này khỏi giỏ hàng?')) {
        fetch('<?= $rootDir ?>customer/api_giohang.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'xoa_san_pham', id: id })
        }).then(() => loadGioHang());
    }
}

function tienHanhThanhToan() {
    window.location.href = '<?= $rootDir ?>customer/orderdetail.php';
}
function capNhatGiaoDienTien() {
    const tienGiam = (tongTienGoc * phanTramGiam) / 100;
    const tongTienSauGiam = tongTienGoc - tienGiam;

    document.getElementById('cart-subtotal').innerText = tongTienGoc.toLocaleString('vi-VN') + 'đ';
    document.getElementById('cart-discount').innerText = '-' + tienGiam.toLocaleString('vi-VN') + 'đ';
    document.getElementById('cart-total').innerText = tongTienSauGiam.toLocaleString('vi-VN') + 'đ';
}

function apDungMaKM() {
    const maKM = document.getElementById('km-code').value.trim();
    
    if (maKM === '') {
        alert('Vui lòng nhập mã khuyến mãi!');
        return;
    }

    
    fetch('<?= $rootDir ?>customer/khuyenmai1.php?code=' + encodeURIComponent(maKM))
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                phanTramGiam = data.percent;
                alert('Áp dụng mã thành công! Bạn được giảm ' + data.percent + '%');
                capNhatGiaoDienTien();
            } else {
                alert(data.message);
                phanTramGiam = 0; // Reset lại nếu mã sai
                capNhatGiaoDienTien();
            }
        })
        .catch(err => {
            console.error(err);
            alert('Có lỗi xảy ra khi áp dụng mã giảm giá!');
        });
}

        function xoaMaKM() {
    // 1. Làm trống ô nhập mã
    document.getElementById('km-code').value = '';
    
    // 2. Đưa phần trăm giảm về 0
    phanTramGiam = 0;
    
    // 3. Tính toán lại tổng tiền hiển thị trên giao diện
    capNhatGiaoDienTien();
    
    alert('Đã gỡ bỏ mã giảm giá!');

}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>