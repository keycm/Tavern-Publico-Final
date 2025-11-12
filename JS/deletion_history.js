document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('historyTableBody');
    // const searchInput = document.getElementById('historySearch'); // <-- REMOVED
    const alertModal = document.getElementById('alertModal');
    const alertModalTitle = document.getElementById('alertModalTitle');
    const alertModalMessage = document.getElementById('alertModalMessage');
    const alertModalActions = document.getElementById('alertModalActions');
    const alertModalCloseBtn = alertModal.querySelector('.close-button');

    const passwordConfirmModal = document.getElementById('passwordConfirmModal');
    const passwordConfirmForm = document.getElementById('passwordConfirmForm');
    const passwordModalCloseBtn = passwordConfirmModal.querySelector('.close-button');
    const cancelPurgeBtn = document.getElementById('cancelPurgeBtn');

    const showAlert = (title, message, callback = null) => {
        alertModalTitle.textContent = title;
        alertModalMessage.textContent = message;
        alertModalActions.innerHTML = '<button class="btn" id="alertOkBtn" style="background-color: #007bff; color: white;">OK</button>';
        alertModal.style.display = 'flex';

        document.getElementById('alertOkBtn').onclick = () => {
            alertModal.style.display = 'none';
            if (callback) callback();
        };
    };

    const showConfirm = (title, message, callback) => {
        alertModalTitle.textContent = title;
        alertModalMessage.textContent = message;
        alertModalActions.innerHTML = `
            <button class="btn" id="confirmCancelBtn" style="background-color: #6c757d; color: white;">Cancel</button>
            <button class="btn" id="confirmOkBtn" style="background-color: #dc3545; color: white;">Yes, Proceed</button>
        `;
        alertModal.style.display = 'flex';

        document.getElementById('confirmOkBtn').onclick = () => {
            alertModal.style.display = 'none';
            callback(true);
        };
        document.getElementById('confirmCancelBtn').onclick = () => {
            alertModal.style.display = 'none';
            callback(false);
        };
    };
    
    alertModalCloseBtn.onclick = () => alertModal.style.display = 'none';
    window.addEventListener('click', (event) => {
        if (event.target === alertModal) {
            alertModal.style.display = 'none';
        }
        if (event.target === passwordConfirmModal) {
            passwordConfirmModal.style.display = 'none';
        }
    });
    
    passwordModalCloseBtn.onclick = () => passwordConfirmModal.style.display = 'none';
    cancelPurgeBtn.onclick = () => passwordConfirmModal.style.display = 'none';

    tableBody.addEventListener('click', (event) => {
        const target = event.target;
        const row = target.closest('tr');
        if (!row) return;

        const logId = row.dataset.logId;

        if (target.classList.contains('restore-btn')) {
            showConfirm('Confirm Restore', 'Are you sure you want to restore this item?', (confirmed) => {
                if (confirmed) {
                    handleDeletionAction(logId, 'restore', row);
                }
            });
        } else if (target.classList.contains('purge-btn')) {
            document.getElementById('purgeLogId').value = logId;
            passwordConfirmModal.style.display = 'flex';
        }
    });

    passwordConfirmForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const logId = document.getElementById('purgeLogId').value;
        const password = document.getElementById('adminPassword').value;
        const row = document.querySelector(`tr[data-log-id="${logId}"]`);
        
        passwordConfirmModal.style.display = 'none';
        
        handleDeletionAction(logId, 'purge', row, password);
        document.getElementById('adminPassword').value = '';
    });

    async function handleDeletionAction(logId, action, rowElement, password = null) {
        const formData = new URLSearchParams();
        formData.append('log_id', logId);
        formData.append('action', action);
        if (password) {
            formData.append('admin_password', password);
        }

        try {
            const response = await fetch('manage_deletion.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            showAlert(result.success ? 'Success!' : 'Error', result.message, () => {
                // FIXED: Reload the page on success to show the updated state
                if (result.success) {
                    location.reload();
                }
            });

        } catch (error) {
            console.error('Error:', error);
            showAlert('Error', 'An unexpected network error occurred.');
        }
    }

    // --- CONFLICTING SEARCH BLOCK REMOVED ---
});