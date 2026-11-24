document.addEventListener('DOMContentLoaded', function () {
    const overlay = document.getElementById('modalOverlay');
    const form = document.getElementById('categoryForm');
    const title = document.getElementById('modalTitle');
    const actionInput = document.getElementById('f-action');
    const idInput = document.getElementById('f-id');
    const iconInput = document.getElementById('f-icon');
    const nameInput = document.getElementById('f-name');
    const descInput = document.getElementById('f-desc');
    const statusInput = document.getElementById('f-status');
    const deleteButton = document.getElementById('modalDelete');
    const searchInput = document.getElementById('categories-search');
    const tbody = document.getElementById('categories-tbody');
    const countLabel = document.getElementById('categories-count');

    function openModal(mode, data = {}) {
        const editing = mode === 'update';
        title.textContent = editing ? 'Chỉnh sửa danh mục' : 'Thêm danh mục mới';
        actionInput.value = editing ? 'update' : 'add';
        idInput.value = data.id || '';
        iconInput.value = data.icon || '📦';
        nameInput.value = data.name || '';
        descInput.value = data.desc || '';
        
        // Đặt trạng thái mặc định: nếu có dữ liệu truyền vào thì dùng, không thì lấy "Hoạt động"
        statusInput.value = data.status || 'Hoạt động';
        
        deleteButton.hidden = !editing;

        overlay.classList.add('is-open');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
        window.setTimeout(() => nameInput.focus(), 50);
    }

    function closeModal() {
        overlay.classList.remove('is-open');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
        form.reset();
        actionInput.value = 'add';
        deleteButton.hidden = true;
    }

    const btnAdd = document.getElementById('btn-add-category');
    if (btnAdd) {
        btnAdd.addEventListener('click', () => {
            openModal('add');
        });
    }

    document.querySelectorAll('.btn-edit').forEach(button => {
        button.addEventListener('click', () => {
            openModal('update', {
                id: button.dataset.id,
                name: button.dataset.name,
                icon: button.dataset.icon,
                desc: button.dataset.desc,
                status: button.dataset.status
            });
        });
    });

    const modalClose = document.getElementById('modalClose');
    if (modalClose) modalClose.addEventListener('click', closeModal);

    const modalCancel = document.getElementById('modalCancel');
    if (modalCancel) modalCancel.addEventListener('click', closeModal);

    if (overlay) {
        overlay.addEventListener('click', event => {
            if (event.target === overlay) closeModal();
        });
    }

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && overlay && overlay.classList.contains('is-open')) {
            closeModal();
        }
    });

    if (deleteButton) {
        deleteButton.addEventListener('click', () => {
            const id = idInput.value;
            const name = nameInput.value.trim();

            if (!id) return;
            if (!window.confirm('Bạn có chắc muốn xóa danh mục "' + name + '" không?')) {
                return;
            }

            const deleteForm = document.createElement('form');
            deleteForm.method = 'post';
            deleteForm.action = 'process_danhmuc.php';

            const action = document.createElement('input');
            action.type = 'hidden';
            action.name = 'action';
            action.value = 'delete';

            const categoryId = document.createElement('input');
            categoryId.type = 'hidden';
            categoryId.name = 'id';
            categoryId.value = id;

            deleteForm.append(action, categoryId);
            document.body.appendChild(deleteForm);
            deleteForm.submit();
        });
    }

    // Lọc danh sách ngay khi gõ từ khóa.
    function filterRows() {
        if (!tbody) return;

        const keyword = (searchInput ? searchInput.value : '').trim().toLocaleLowerCase('vi');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        let visible = 0;

        rows.forEach(row => {
            const matched = row.textContent.toLocaleLowerCase('vi').includes(keyword);
            row.hidden = !matched;
            if (matched) visible++;
        });

        if (countLabel) {
            countLabel.textContent = 'Hiển thị ' + visible + ' / ' + rows.length + ' danh mục';
        }
    }

    if (searchInput) searchInput.addEventListener('input', filterRows);
    filterRows();

    // Tự ẩn thông báo toast sau 3,5 giây.
    window.setTimeout(() => {
        document.querySelectorAll('.toast-msg').forEach(toast => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(8px)';
            window.setTimeout(() => toast.remove(), 250);
        });
    }, 3500);

    // Ngăn gửi form nhiều lần khi bấm lưu.
    if (form) {
        form.addEventListener('submit', function () {
            const saveButton = document.getElementById('modalSave');
            if (saveButton) {
                saveButton.disabled = true;
                saveButton.textContent = 'Đang lưu...';
            }
        });
    }
});