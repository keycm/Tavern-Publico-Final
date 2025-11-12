document.addEventListener('DOMContentLoaded', () => {
    const reservationModal = document.getElementById('reservationModal');
    const editReservationForm = document.getElementById('editReservationForm');

    // Edit Modal Fields
    const modalReservationId = document.getElementById('modalReservationId');
    const modalResName = document.getElementById('modalResName');
    const modalResEmail = document.getElementById('modalResEmail');
    const modalResPhone = document.getElementById('modalResPhone');
    const modalResDate = document.getElementById('modalResDate');
    const modalResTime = document.getElementById('modalResTime');
    const modalNumGuests = document.getElementById('modalNumGuests');
    const modalReservationType = document.getElementById('modalReservationType');
    const modalStatus = document.getElementById('modalStatus');
    const modalCreatedAt = document.getElementById('modalCreatedAt');
    const modalActionBy = document.getElementById('modalActionBy'); // MODIFIED: Added this
    const validIdDisplay = document.getElementById('validIdDisplay'); // In Edit Modal
    const modalDeleteBtn = document.querySelector('#reservationModal .modal-delete-btn');

    // Add Modal Fields
    const addReservationModal = document.getElementById('addReservationModal');
    const addReservationBtn = document.getElementById('addReservationBtn');
    const addReservationForm = document.getElementById('addReservationForm');

    // Delete Modal
    const confirmDeleteModal = document.getElementById('confirmDeleteModal');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    let itemToDelete = { id: null, element: null }; // Keep track of item to delete

    // Image Modal Fields
    const imageIdModal = document.getElementById('imageIdModal');
    const modalImageContent = document.getElementById('modalImageContent');
    const closeImageModalBtn = document.querySelector('.close-image-modal');

    // General Modal Closing Logic
    const closeButtons = document.querySelectorAll('.modal .close-button');
    closeButtons.forEach(button => {
        button.addEventListener('click', () => {
            button.closest('.modal').style.display = 'none';
        });
    });

    window.addEventListener('click', (event) => {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    });

    // Image Modal Event Listeners
    if (imageIdModal && closeImageModalBtn && modalImageContent) {
        closeImageModalBtn.addEventListener('click', () => {
            imageIdModal.style.display = 'none';
        });
        imageIdModal.addEventListener('click', (e) => {
            if (e.target === imageIdModal) {
                imageIdModal.style.display = 'none';
            }
        });
    }

    // Function to open the main edit modal
    function openReservationModal(reservationData) {
        // MODIFIED: Added modalActionBy to the check
        if (!reservationModal || !modalReservationId || !modalResName || !modalResEmail || !modalResPhone || !modalResDate || !modalResTime || !modalNumGuests || !modalReservationType || !modalStatus || !modalCreatedAt || !validIdDisplay || !modalActionBy) {
            console.error("One or more modal elements not found.");
            return;
        }

        modalReservationId.value = reservationData.reservation_id;
        modalResName.value = reservationData.res_name;
        modalResEmail.value = reservationData.res_email;
        modalResPhone.value = reservationData.res_phone;
        modalResDate.value = reservationData.res_date;
        modalResTime.value = reservationData.res_time; // Keep time in 24hr format for input
        modalNumGuests.value = reservationData.num_guests;
        modalReservationType.value = reservationData.reservation_type;
        modalStatus.value = reservationData.status;
        modalCreatedAt.value = reservationData.created_at; 
        modalActionBy.value = reservationData.action_by || 'N/A'; // MODIFIED: Added this line

        // Populate the Uploaded ID section
        const idPath = reservationData.valid_id_path;
        if (idPath) {
            validIdDisplay.innerHTML = `<button type="button" class="btn btn-small view-id-btn" data-src="${idPath}" style="background-color: #007bff; color: white;">View Uploaded ID</button>`;
        } else {
            validIdDisplay.innerHTML = `<p style="color: #888; margin: 0; padding-top: 8px;">No ID was uploaded for this reservation.</p>`;
        }

        reservationModal.style.display = 'flex';
    }

    // Delegated event listener for the dynamically created "View Uploaded ID" button
    document.body.addEventListener('click', function(event) {
        if (event.target.classList.contains('view-id-btn')) {
            const imgSrc = event.target.dataset.src;
            if (imgSrc && imageIdModal && modalImageContent) {
                modalImageContent.src = imgSrc;
                imageIdModal.style.display = 'flex';
            }
        }
    });


    // --- Form Submissions, Delete Logic, etc. ---

    // Edit Form Submission
    if (editReservationForm) {
        editReservationForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const formData = new FormData(editReservationForm);
            formData.append('action', 'update'); // Make sure action is set
            try {
                const response = await fetch('update_reservation.php', { method: 'POST', body: formData });
                const result = await response.json();
                // Use a notification modal or alert for feedback
                showNotification(result.success ? 'success' : 'error', result.success ? 'Success!' : 'Error', result.message, result.success ? () => location.reload() : null);
                if (result.success) {
                   if(reservationModal) reservationModal.style.display = 'none'; // Close modal on success
                }
            } catch (error) {
                console.error('Error updating reservation:', error);
                showNotification('error', 'Error', 'An unexpected network error occurred.');
            }
        });
    }

    // Add Button Click
    if (addReservationBtn) {
        addReservationBtn.addEventListener('click', () => {
            if(addReservationForm) addReservationForm.reset();
            if(addReservationModal) addReservationModal.style.display = 'flex';
        });
    }

    // Add Form Submission
    if (addReservationForm) {
        addReservationForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const formData = new FormData(addReservationForm);
            formData.append('action', 'create'); // Make sure action is set
            try {
                const response = await fetch('update_reservation.php', { method: 'POST', body: formData });
                const result = await response.json();
                showNotification(result.success ? 'success' : 'error', result.success ? 'Success!' : 'Error', result.message, result.success ? () => location.reload() : null);
                 if (result.success) {
                   if(addReservationModal) addReservationModal.style.display = 'none'; // Close modal on success
                }
            } catch (error) {
                console.error('Error adding reservation:', error);
                showNotification('error', 'Error', 'An unexpected error occurred.');
            }
        });
    }

    // Function to open delete confirmation
    function openConfirmDeleteModal(reservationId, rowElement) {
        itemToDelete.id = reservationId;
        itemToDelete.element = rowElement; // Store the row element if needed for UI update later
        if(confirmDeleteModal) confirmDeleteModal.style.display = 'flex';
    }

    // Confirm Delete Button Click
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', () => {
            if (itemToDelete.id) {
                deleteReservation(itemToDelete.id, itemToDelete.element);
                if(confirmDeleteModal) confirmDeleteModal.style.display = 'none';
            }
        });
    }

    // Cancel Delete Button Click
    if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener('click', () => {
            if(confirmDeleteModal) confirmDeleteModal.style.display = 'none';
            itemToDelete = { id: null, element: null }; // Reset item to delete
        });
    }

    // Delete Button within Edit Modal Click
    if (modalDeleteBtn) {
        modalDeleteBtn.addEventListener('click', () => {
            const reservationId = modalReservationId ? modalReservationId.value : null;
            if (reservationId) {
                const row = document.querySelector(`tr[data-reservation-id="${reservationId}"]`);
                if(reservationModal) reservationModal.style.display = 'none'; // Close edit modal
                openConfirmDeleteModal(reservationId, row); // Open confirm modal
            }
        });
    }

    // Async function to handle deletion
    async function deleteReservation(reservationId, rowElement) {
        const formData = new URLSearchParams(); // Use URLSearchParams for simple key-value pairs
        formData.append('reservation_id', reservationId);
        formData.append('action', 'delete');
        try {
            const response = await fetch('update_reservation.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            });
            const result = await response.json();
             showNotification(result.success ? 'success' : 'error', result.success ? 'Success!' : 'Error', result.message, result.success ? () => location.reload() : null);
             // Reloading the page handles UI update
        } catch (error) {
            console.error('Error deleting reservation:', error);
             showNotification('error', 'Error', 'An unexpected network error occurred.');
        } finally {
             itemToDelete = { id: null, element: null }; // Reset item to delete
        }
    }


    // --- Table Row Click Listeners (View/Edit and Delete) ---
    const reservationTableBody = document.querySelector('table tbody');
    if (reservationTableBody) {
        reservationTableBody.addEventListener('click', (event) => {
            const target = event.target;
            const row = target.closest('tr');
            // Ignore clicks if not on a row or if it's the 'no reservations' row
            if (!row || row.querySelector('td[colspan="8"]')) return; // MODIFIED: Changed to 8

            if (target.classList.contains('view-edit-btn')) {
                const fullReservationJson = row.dataset.fullReservation;
                try {
                    const reservationData = JSON.parse(fullReservationJson);
                    openReservationModal(reservationData); // Open the edit modal
                } catch (e) { console.error("Error parsing reservation data:", e); showNotification('error','Error','Could not load reservation details.');}
            } else if (target.classList.contains('delete-btn')) {
                const reservationId = row.dataset.reservationId;
                openConfirmDeleteModal(reservationId, row); // Open confirm delete modal
            }
        });
    }

    // --- Pagination and Filtering Elements & Logic ---
    const tableBody = document.querySelector('table tbody');
    const allRows = tableBody ? Array.from(tableBody.querySelectorAll('tr')) : [];
    const rowsPerPage = 6;
    let currentPage = 1;
    let filteredRows = allRows;

    const prevPageBtn = document.getElementById('prevPageBtn');
    const nextPageBtn = document.getElementById('nextPageBtn');
    const pageNumbersContainer = document.getElementById('pageNumbers');
    const searchInput = document.getElementById('reservationSearch');
    const statusSortSelect = document.getElementById('statusSort'); // Get the status dropdown
    const paginationContainer = document.querySelector('.pagination-container');
    const noItemsRow = tableBody ? tableBody.querySelector('td[colspan="8"]') : null; // MODIFIED: Changed to 8

    function displayPage(page) {
        currentPage = page;
        // Detach all rows before clearing to keep event listeners
        allRows.forEach(row => {
            if (row.parentElement) { row.remove(); }
        });

        if (noItemsRow && noItemsRow.parentElement && noItemsRow.parentElement.parentElement === tableBody) {
             noItemsRow.parentElement.remove();
        }

        const start = (page - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        const paginatedItems = filteredRows.slice(start, end);

        if (paginatedItems.length > 0) {
            paginatedItems.forEach(row => tableBody.appendChild(row));
        } else if (noItemsRow) {
            tableBody.appendChild(noItemsRow.parentElement);
        }

        updatePaginationUI();
    }

    function updatePaginationUI() {
        if (!paginationContainer) return;

        const pageCount = Math.ceil(filteredRows.length / rowsPerPage);

        if (pageCount <= 1) {
            paginationContainer.style.display = 'none';
            return;
        }
        paginationContainer.style.display = 'flex';

        if(prevPageBtn) prevPageBtn.disabled = currentPage === 1;
        if(nextPageBtn) nextPageBtn.disabled = currentPage === pageCount;

        if(pageNumbersContainer) {
            pageNumbersContainer.innerHTML = '';
            for (let i = 1; i <= pageCount; i++) {
                const pageBtn = document.createElement('button');
                pageBtn.textContent = i;
                pageBtn.className = 'page-number' + (i === currentPage ? ' active' : '');
                pageBtn.addEventListener('click', () => displayPage(i));
                pageNumbersContainer.appendChild(pageBtn);
            }
        }
    }

    function applyFilters() {
        const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
        const selectedStatus = statusSortSelect ? statusSortSelect.value : 'all';

        filteredRows = allRows.filter(row => {
             if (row.querySelector('td[colspan="8"]')) return false; // MODIFIED: Changed to 8

            const rowText = row.textContent.toLowerCase();
            const rowStatus = row.dataset.status; // Get status from data attribute

            const matchesSearch = rowText.includes(searchTerm);
            const matchesStatus = (selectedStatus === 'all') || (rowStatus === selectedStatus);

            return matchesSearch && matchesStatus;
        });
        displayPage(1);
    }

    // Attach Event Listeners for Filters and Pagination
    if (searchInput) {
        searchInput.addEventListener('keyup', applyFilters);
    }
    if (statusSortSelect) {
        statusSortSelect.addEventListener('change', applyFilters); // Trigger filter on dropdown change
    }
    if (prevPageBtn) {
        prevPageBtn.addEventListener('click', () => {
            if (currentPage > 1) displayPage(currentPage - 1);
        });
    }
    if (nextPageBtn) {
        nextPageBtn.addEventListener('click', () => {
            const pageCount = Math.ceil(filteredRows.length / rowsPerPage);
            if (currentPage < pageCount) displayPage(currentPage + 1);
        });
    }

    // Initial setup
    if (allRows.length > 0 || noItemsRow) {
        applyFilters(); // Apply initial filter and display page 1
    } else if (paginationContainer) {
         paginationContainer.style.display = 'none';
    }

    // --- Notification Modal Logic ---
    const notificationModal = document.getElementById('notificationModal');
    const modalHeaderIcon = document.getElementById('modalHeaderIcon');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');
    const notificationCloseButton = notificationModal ? notificationModal.querySelector('.close-button') : null;
    const notificationOkButton = notificationModal ? notificationModal.querySelector('.modal-close-btn') : null;
    let notificationCallback = null;

    function showNotification(type, title, message, callback = null) {
        if (!notificationModal || !modalHeaderIcon || !modalTitle || !modalMessage) return; // Guard clause

        modalHeaderIcon.innerHTML = type === 'success' ? '<i class="material-icons">check_circle</i>' : '<i class="material-icons">error</i>';
        modalHeaderIcon.className = 'modal-header-icon ' + type; // Set class for color styling
        modalTitle.textContent = title;
        modalMessage.textContent = message;
        notificationCallback = callback; // Store the callback
        notificationModal.style.display = 'flex';
    }

    function closeNotificationModal() {
        if (!notificationModal) return;
        notificationModal.style.display = 'none';
        if (notificationCallback) {
            notificationCallback(); // Execute the callback after closing
            notificationCallback = null; // Clear the callback
        }
    }

    if (notificationCloseButton) notificationCloseButton.addEventListener('click', closeNotificationModal);
    if (notificationOkButton) notificationOkButton.addEventListener('click', closeNotificationModal);
    // Optional: Close on background click
    window.addEventListener('click', (event) => {
        if (event.target == notificationModal) closeNotificationModal();
    });

}); // End DOMContentLoaded