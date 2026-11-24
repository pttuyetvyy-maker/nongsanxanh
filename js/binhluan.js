// Mở modal trả lời bình luận
function openReplyModal(id, oldReply) {
    document.getElementById('replyMaBL').value = id;
    document.getElementById('replyText').value = oldReply || '';
    document.getElementById('replyModal').classList.add('active');
}

// Đóng modal trả lời
function closeReplyModal() {
    const modal = document.getElementById('replyModal');
    if (modal) modal.classList.remove('active');
}

document.addEventListener('DOMContentLoaded', function () {
    // Tự đóng Modal khi click ra ngoài
    const modal = document.getElementById('replyModal');
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeReplyModal();
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