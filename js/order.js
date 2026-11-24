document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('orders-search');
    const filterStatus = document.getElementById('orders-filter-status');
    const tbody = document.getElementById('orders-tbody');

    // Các element của Panel Chi Tiết
    const detailTitle = document.getElementById('detail-title');
    const dCustomer = document.getElementById('d-customer');
    const dEmail = document.getElementById('d-email');
    const dPhone = document.getElementById('d-phone');
    const dAddress = document.getElementById('d-address');
    const dTotal = document.getElementById('d-total');
    const dMethod = document.getElementById('d-method');
    const dDate = document.getElementById('d-date');

    const fId = document.getElementById('f-id');
    const fStatus = document.getElementById('f-status');
    const btnSave = document.getElementById('btn-save-order');
    const btnCancel = document.getElementById('btn-cancel-order');
    const orderForm = document.getElementById('orderProcessForm');

    // 1. Lọc đơn hàng theo từ khóa và trạng thái
    function filterOrders() {
        if (!tbody) return;
        const keyword = (searchInput.value || '').trim().toLowerCase();
        const selectedStatus = (filterStatus.value || '').trim();
        const rows = tbody.querySelectorAll('tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const status = row.getAttribute('data-status') || '';

            const matchSearch = text.includes(keyword);
            const matchStatus = selectedStatus === '' || status === selectedStatus;

            row.style.display = (matchSearch && matchStatus) ? '' : 'none';
        });
    }

    if (searchInput) searchInput.addEventListener('input', filterOrders);
    if (filterStatus) filterStatus.addEventListener('change', filterOrders);

    // 2. Bấm mắt 👁️ để đẩy thông tin xuống panel chi tiết
    document.querySelectorAll('.btn-view-order').forEach(btn => {
        btn.addEventListener('click', function () {
            const d = this.dataset;

            detailTitle.textContent = 'Chi tiết đơn hàng ' + d.code;
            dCustomer.textContent = d.customer;
            dEmail.textContent = d.email;
            dPhone.textContent = d.phone;
            dAddress.textContent = d.address;
            dTotal.textContent = d.total;
            dMethod.textContent = d.method;
            dDate.textContent = d.date;

            fId.value = d.id;
            fStatus.value = d.status;

            // Enable 2 nút thao tác
            btnSave.disabled = false;
            btnCancel.disabled = false;

            // Cuộn mượt xuống khung chi tiết
            document.getElementById('order-detail-panel').scrollIntoView({ behavior: 'smooth' });
        });
    });

    // 3. Nút Hủy Đơn nhanh
    if (btnCancel) {
        btnCancel.addEventListener('click', function () {
            if (!fId.value) return;
            if (confirm('Bạn có chắc chắn muốn hủy đơn hàng này không?')) {
                fStatus.value = 'Đã hủy';
                orderForm.submit();
            }
        });
    }

    // 4. Tự ẩn thông báo Toast sau 3,5s
    setTimeout(() => {
        document.querySelectorAll('.toast-msg').forEach(toast => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(8px)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        });
    }, 3500);
});