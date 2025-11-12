document.addEventListener('DOMContentLoaded', () => {
    // --- Notification Modal Elements ---
    const notificationModal = document.getElementById('notificationModal');
    const modalHeaderIcon = document.getElementById('modalHeaderIcon');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');
    const notificationCloseButton = notificationModal ? notificationModal.querySelector('.close-button') : null;
    const notificationOkButton = notificationModal ? notificationModal.querySelector('.modal-close-btn') : null;
    let notificationCallback = null;

    // --- Notification Modal Functions ---
    function showNotification(type, title, message, callback = null) {
        if (!notificationModal || !modalHeaderIcon || !modalTitle || !modalMessage) {
            alert(`${title}: ${message}`);
            if (callback) callback();
            return;
        }
        modalHeaderIcon.innerHTML = type === 'success' ? '<i class="material-icons">check_circle</i>' : '<i class="material-icons">error</i>';
        const icon = modalHeaderIcon.querySelector('i');
        if (icon) {
            icon.style.fontSize = '3.5em';
            icon.style.color = type === 'success' ? '#28a745' : '#dc3545';
        }
        modalTitle.textContent = title;
        modalMessage.textContent = message;
        notificationCallback = callback;
        notificationModal.style.display = 'flex';
    }

    function closeNotificationModal() {
        if (!notificationModal) return;
        notificationModal.style.display = 'none';
        if (notificationCallback) {
            notificationCallback();
            notificationCallback = null;
        }
    }

    if (notificationCloseButton) notificationCloseButton.addEventListener('click', closeNotificationModal);
    if (notificationOkButton) notificationOkButton.addEventListener('click', closeNotificationModal);
    
    // --- Reservation Modal Functionality ---
    const reservationModal = document.getElementById('reservationModal');
    const closeButton = document.querySelector('#reservationModal .close-button');
    const modalDetails = document.getElementById('modalDetails');
    const modalConfirmBtn = document.querySelector('.modal-confirm-btn');
    const modalDeleteBtn = document.querySelector('.modal-delete-btn');
    const modalDeclineBtn = document.querySelector('.modal-decline-btn');
    const confirmDeleteModal = document.getElementById('confirmDeleteModal');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    const closeDeleteModalBtn = document.querySelector('#confirmDeleteModal .close-button');

    const imageIdModal = document.getElementById('imageIdModal');
    const modalImageContent = document.getElementById('modalImageContent');
    const closeImageModalBtn = document.querySelector('.close-image-modal');
    const validIdDisplayModal = document.getElementById('validIdDisplayModal'); 

    let currentReservationId = null;

    function openModal(reservationData) {
        if (!reservationData || Object.keys(reservationData).length === 0) {
            return;
        }
        modalDetails.innerHTML = '';
        currentReservationId = reservationData['Reservation ID'];
        for (const key in reservationData) {
            if (Object.hasOwnProperty.call(reservationData, key) && key !== 'Valid ID Path') {
                const p = document.createElement('p');
                p.innerHTML = `<strong>${key}:</strong> <span>${reservationData[key] || 'N/A'}</span>`;
                modalDetails.appendChild(p);
            }
        }

        const idPath = reservationData['Valid ID Path'];
        if (validIdDisplayModal) {
            if (idPath) {
                validIdDisplayModal.innerHTML = `<button type="button" class="btn btn-small view-id-btn" data-src="${idPath}" style="background-color: #007bff; color: white;">View Uploaded ID</button>`;
            } else {
                validIdDisplayModal.innerHTML = `<p style="color: #888; margin: 0; padding-top: 8px;">No ID was uploaded for this reservation.</p>`;
            }
        }

        reservationModal.style.display = 'flex';
    }

    function closeModal(modal) {
        if (modal) {
            modal.style.display = 'none';
        }
        if (modal === reservationModal) {
            currentReservationId = null;
        }
    }

    if (closeButton) closeButton.addEventListener('click', () => closeModal(reservationModal));
    if (cancelDeleteBtn) cancelDeleteBtn.addEventListener('click', () => closeModal(confirmDeleteModal));
    if (closeDeleteModalBtn) closeDeleteModalBtn.addEventListener('click', () => closeModal(confirmDeleteModal));
    if (closeImageModalBtn) closeImageModalBtn.addEventListener('click', () => closeModal(imageIdModal));

    window.addEventListener('click', (event) => {
        if (event.target === reservationModal) closeModal(reservationModal);
        if (event.target === confirmDeleteModal) closeModal(confirmDeleteModal);
        if (event.target === imageIdModal) closeModal(imageIdModal);
        if (event.target === notificationModal) closeNotificationModal();
    });

    const reservationTableBody = document.querySelector('table tbody');
    if (reservationTableBody) {
        reservationTableBody.addEventListener('click', (event) => {
            const target = event.target;
            if (target.classList.contains('view-btn')) {
                const row = target.closest('tr');
                if (!row) return;
                try {
                    const fullReservationData = JSON.parse(row.dataset.fullReservation);
                    openModal(fullReservationData);
                } catch (e) {
                    console.error("Error parsing reservation data:", e);
                    showNotification('error', 'Error', 'Could not load reservation details.');
                }
            }
        });
    }

    document.body.addEventListener('click', function(event) {
        if (event.target.classList.contains('view-id-btn')) {
            const imgSrc = event.target.dataset.src;
            if (imgSrc && imageIdModal && modalImageContent) {
                modalImageContent.src = imgSrc;
                imageIdModal.style.display = 'flex';
            }
        }
    });

    async function handleAction(button, status, action, phpFile) {
        if (!currentReservationId) return;
        button.classList.add('btn-loading');
        if (modalConfirmBtn) modalConfirmBtn.disabled = true;
        if (modalDeclineBtn) modalDeclineBtn.disabled = true;
        if (modalDeleteBtn) modalDeleteBtn.disabled = true;
        if (confirmDeleteBtn) confirmDeleteBtn.disabled = true;

        try {
            await updateReservation(currentReservationId, status, action, phpFile);
            location.reload();
        } catch (error) {
            if (modalConfirmBtn) modalConfirmBtn.disabled = false;
            if (modalDeclineBtn) modalDeclineBtn.disabled = false;
            if (modalDeleteBtn) modalDeleteBtn.disabled = false;
            if (confirmDeleteBtn) confirmDeleteBtn.disabled = false;
            button.classList.remove('btn-loading');
        }
    }

    if (modalConfirmBtn) modalConfirmBtn.addEventListener('click', () => handleAction(modalConfirmBtn, 'Confirmed', 'update', 'update_reservation_status.php'));
    if (modalDeclineBtn) modalDeclineBtn.addEventListener('click', () => handleAction(modalDeclineBtn, 'Declined', 'update', 'update_reservation_status.php'));

    if (modalDeleteBtn) {
        modalDeleteBtn.addEventListener('click', () => {
            if (currentReservationId) {
                closeModal(reservationModal);
                confirmDeleteModal.style.display = 'flex';
            }
        });
    }

    if (confirmDeleteBtn) {
         confirmDeleteBtn.addEventListener('click', () => {
            if(currentReservationId) {
                handleAction(confirmDeleteBtn, null, 'delete', 'update_reservation.php');
            }
         });
    }

    async function updateReservation(reservationId, newStatus, actionType, targetPhpFile) {
        const formData = new URLSearchParams();
        formData.append('reservation_id', reservationId);
        formData.append('action', actionType);
        if (newStatus !== null) {
            formData.append('status', newStatus);
        }

        let errorShown = false;
        try {
            const response = await fetch(targetPhpFile, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            });

            if (!response.ok) {
                const errorText = await response.text();
                showNotification('error', `Server Error (${response.status})`, `The server returned an error. This is often a fatal PHP error. Details: ${errorText.substring(0, 250)}`);
                console.error('Server response text:', errorText);
                errorShown = true;
                throw new Error(`Server error: ${response.status}`);
            }

            const result = await response.json();
            if (!result.success) {
                showNotification('error', 'Action Failed', result.message);
                errorShown = true;
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Error in updateReservation:', error);
            if (!errorShown) {
                showNotification('error', 'Network Error', 'Could not connect to the server. Please check your network or the server script path.');
            }
            throw error;
        }
    }

    if ($('#calendar').length) {
        $('#calendar').fullCalendar({
            header: {
                left: 'prev,next today',
                center: 'title',
                right: 'month,agendaDay'
            },
            events: 'get_reservations.php',
            editable: false,
            droppable: false,
            eventLimit: true,
        });
    }

    const searchInputTop = document.getElementById('reservationSearchTop');
    if (searchInputTop) {
        searchInputTop.addEventListener('keyup', () => {
            const filter = searchInputTop.value.toLowerCase();
            document.querySelectorAll('.recent-reservations-section table tbody tr').forEach(row => {
                if (row.querySelector('td').colSpan < 7) {
                    const rowText = row.textContent.toLowerCase();
                    row.style.display = rowText.includes(filter) ? '' : 'none';
                }
            });
        });
    }
});