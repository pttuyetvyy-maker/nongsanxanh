// Hàm mở Modal thêm người dùng
function openAddUserModal() {
    const modal = document.getElementById('addUserModal');
    if (modal) modal.classList.add('active');
}

// Hàm đóng Modal
function closeAddUserModal() {
    const modal = document.getElementById('addUserModal');
    if (modal) modal.classList.remove('active');
}

document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('users-search');
    const filterRole = document.getElementById('users-filter-role');
    const filterStatus = document.getElementById('users-filter-status');
    const tbody = document.getElementById('users-tbody');

    // Bộ lọc tìm kiếm, vai trò, trạng thái
    function filterUsers() {
        if (!tbody) return;
        const keyword = (searchInput ? searchInput.value : '').trim().toLowerCase();
        const selectedRole = (filterRole ? filterRole.value : '').trim();
        const selectedStatus = (filterStatus ? filterStatus.value : '').trim();

        const rows = tbody.querySelectorAll('tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const role = row.getAttribute('data-role') || '';
            const status = row.getAttribute('data-status') || '';

            const matchSearch = text.includes(keyword);
            const matchRole = selectedRole === '' || role === selectedRole;
            const matchStatus = selectedStatus === '' || status === selectedStatus;

            row.style.display = (matchSearch && matchRole && matchStatus) ? '' : 'none';
        });
    }

    if (searchInput) searchInput.addEventListener('input', filterUsers);
    if (filterRole) filterRole.addEventListener('change', filterUsers);
    if (filterStatus) filterStatus.addEventListener('change', filterUsers);

    // Tự đóng Modal khi click ra ngoài vùng trắng
    const modal = document.getElementById('addUserModal');
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeAddUserModal();
        });
    }

    // Tự động ẩn thông báo Toast sau 3.5s
    setTimeout(() => {
        document.querySelectorAll('.toast-msg').forEach(toast => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(8px)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        });
    }, 3500);
});