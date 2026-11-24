document.addEventListener('DOMContentLoaded', function () {
    // 1. Lọc tìm kiếm sản phẩm nhanh
    const searchInput = document.getElementById('prod-search');
    const tbody = document.getElementById('prod-tbody');

    if (searchInput && tbody) {
        searchInput.addEventListener('input', function () {
            const keyword = this.value.trim().toLowerCase();
            tbody.querySelectorAll('tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(keyword) ? '' : 'none';
            });
        });
    }

    // 2. Modal Thêm sản phẩm
    const btnOpenAdd = document.getElementById('btnOpenAddModal');
    const addOverlay = document.getElementById('addModalOverlay');
    const addClose = document.getElementById('modalCloseAdd');
    const addCancel = document.getElementById('modalCancelAdd');

    if (btnOpenAdd && addOverlay) {
        btnOpenAdd.addEventListener('click', () => addOverlay.classList.add('active'));
    }
    if (addClose && addOverlay) {
        addClose.addEventListener('click', () => addOverlay.classList.remove('active'));
    }
    if (addCancel && addOverlay) {
        addCancel.addEventListener('click', () => addOverlay.classList.remove('active'));
    }

    // 3. Modal Sửa sản phẩm
    const editOverlay = document.getElementById('editModalOverlay');
    const editClose = document.getElementById('modalCloseEdit');
    const editCancel = document.getElementById('modalCancelEdit');

    if (editClose && editOverlay) {
        editClose.addEventListener('click', () => editOverlay.classList.remove('active'));
    }
    if (editCancel && editOverlay) {
        editCancel.addEventListener('click', () => editOverlay.classList.remove('active'));
    }

    document.querySelectorAll('.btn-edit-prod').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('edit-id').value = this.getAttribute('data-id') || '';
            document.getElementById('edit-name').value = this.getAttribute('data-name') || '';
            document.getElementById('edit-price').value = this.getAttribute('data-price') || 0;
            document.getElementById('edit-unit').value = this.getAttribute('data-unit') || 'kg';
            document.getElementById('edit-qty').value = this.getAttribute('data-qty') || 0;
            document.getElementById('edit-cat').value = this.getAttribute('data-cat') || '';
            document.getElementById('edit-img').value = this.getAttribute('data-img') || '';
            document.getElementById('edit-status').value = this.getAttribute('data-status') || '1';
            document.getElementById('edit-desc').value = this.getAttribute('data-desc') || '';

            if (editOverlay) editOverlay.classList.add('active');
        });
    });

    // 4. XỬ LÝ TOAST: XÓA tham số ?msg/err khỏi thanh URL & TỰ ĐỘNG TẮT sau 3 giây
    const toasts = document.querySelectorAll('.toast-msg');
    if (toasts.length > 0) {
        // Xóa tham số ?msg= và ?err= trên thanh địa chỉ URL mà không reload lại trang
        if (window.history.replaceState) {
            const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
            window.history.replaceState({ path: cleanUrl }, '', cleanUrl);
        }

        // Tự động biến mất mượt mà sau 3 giây
        setTimeout(() => {
            toasts.forEach(toast => {
                toast.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(12px)';
                setTimeout(() => toast.remove(), 400);
            });
        }, 3000);
    }
});