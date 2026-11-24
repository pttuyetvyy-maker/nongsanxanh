// Mở modal thêm khuyến mãi mới
function openKMModal() {
    document.getElementById('kmAction').value = 'add';
    document.getElementById('modalTitle').innerText = '🎁 Thêm khuyến mãi mới';
    
    const codeInput = document.getElementById('kmCode');
    codeInput.readOnly = false;
    codeInput.value = '';
    
    document.getElementById('kmName').value = '';
    document.getElementById('kmPercent').value = '';
    document.getElementById('kmStart').value = '';
    document.getElementById('kmEnd').value = '';
    document.getElementById('kmStatus').value = '1';
    
    document.getElementById('kmModal').classList.add('active');
}

// Mở modal chỉnh sửa khuyến mãi
function editKM(km) {
    document.getElementById('kmAction').value = 'edit';
    document.getElementById('modalTitle').innerText = '✏️ Chỉnh sửa khuyến mãi';
    
    const codeInput = document.getElementById('kmCode');
    codeInput.value = km.MaKM;
    codeInput.readOnly = true;
    
    document.getElementById('kmName').value = km.TenKM;
    document.getElementById('kmPercent').value = km.PhanTramGiam;
    document.getElementById('kmStart').value = km.NgayBatDau.replace(' ', 'T').substring(0, 16);
    document.getElementById('kmEnd').value = km.NgayKetThuc.replace(' ', 'T').substring(0, 16);
    document.getElementById('kmStatus').value = km.TrangThai ?? 1;
    
    document.getElementById('kmModal').classList.add('active');
}

// Đóng modal khuyến mãi
function closeKMModal() { 
    const modal = document.getElementById('kmModal');
    if (modal) modal.classList.remove('active'); 
}

document.addEventListener('DOMContentLoaded', function () {
    // Tự đóng Modal khi click ra ngoài
    const modal = document.getElementById('kmModal');
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeKMModal();
        });
    }

    // Tự động ẩn thông báo Toast
    setTimeout(() => {
        document.querySelectorAll('.toast-msg').forEach(toast => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(8px)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        });
    }, 3500);
});