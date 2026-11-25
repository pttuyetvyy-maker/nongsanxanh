<?php
if (!isset($rootDir)) {
    $rootDir = '/' . trim(explode('/', trim($_SERVER['SCRIPT_NAME'], '/'))[0], '/') . '/';
}
?>
<!-- FOOTER -->
<footer class="footer">

    <div class="footer-content">

        <div class="footer-column">
            <h3>Nông Sản Xanh</h3>
            <p>
                Mang nông sản sạch, an toàn và chất lượng
                đến với mọi gia đình Việt.
            </p>
        </div>

        <div class="footer-column">
            <h3>Liên kết</h3>
            <a href="<?= $rootDir ?>index1.php">Trang chủ</a>
            <a href="<?= $rootDir ?>customer/danhmuc.php">Danh mục</a>
            <a href="<?= $rootDir ?>customer/sanpham_theoloai.php">Sản phẩm</a>
            <a href="<?= $rootDir ?>index1.php#gioi-thieu">Giới thiệu</a>
        </div>

        <div class="footer-column">
            <h3>Hỗ trợ</h3>
<a href="<?= $rootDir ?? '/nongsanxanh/' ?>customer/chinhsach_muahang.php">Chính sách mua hàng</a>
<a href="<?= $rootDir ?? '/nongsanxanh/' ?>customer/chinhsach_doitra.php">Chính sách đổi trả</a>
<a href="<?= $rootDir ?? '/nongsanxanh/' ?>customer/chinhsach_giaohang.php">Chính sách giao hàng</a>
        </div>

        <div class="footer-column">
            <h3>Liên hệ</h3>
            <p>Thành phố Hồ Chí Minh, Việt Nam</p>
            <p>Hotline: 0907049243</p>
            <p>nhom11@vaa.edu.vn</p>
        </div>

    </div>

    <div class="copyright">
        2026 Nông Sản Xanh. All Rights Reserved.
    </div>

</footer>

</body> 
</html>