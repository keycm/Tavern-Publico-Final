<?php
session_start();
require_once 'db_connect.php';

// MODIFIED: This is the fix for the authorization
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') {
    header('Location: login.php');
    exit;
}
// END FIX

// Helper function to get an icon for each item type
function getItemTypeIcon($type) {
    $icons = [
        'user' => 'person',
        'reservation' => 'event_note',
        'menu_item' => 'restaurant_menu',
        'gallery_image' => 'collections',
        'event' => 'event',
        'team_member' => 'group',
        'hero_slide' => 'view_carousel',
        'contact_message' => 'email',
        'testimonial' => 'star_rate',
        'blocked_date' => 'block',
        'coupon' => 'sell' // Added coupon icon
    ];
    return $icons[$type] ?? 'history'; // Default icon
}


// --- Sorting Logic ---
$sort_by = $_GET['sort'] ?? 'deleted_at';
$sort_order = $_GET['order'] ?? 'DESC';
$allowed_sort_columns = ['deleted_at', 'purge_date', 'action_by', 'item_type'];
if (!in_array($sort_by, $allowed_sort_columns)) {
    $sort_by = 'deleted_at';
}
if (!in_array(strtoupper($sort_order), ['ASC', 'DESC'])) {
    $sort_order = 'DESC';
}

// --- Fetching Logic ---
$deleted_items = [];
// Get all unique item types for the filter tabs
$item_types_result = mysqli_query($link, "SELECT DISTINCT item_type FROM deletion_history ORDER BY item_type ASC");
$item_types = [];
while($row = mysqli_fetch_assoc($item_types_result)) {
    $item_types[] = $row['item_type'];
}

$sql = "SELECT log_id, item_type, item_id, item_data, action_by, deleted_at, purge_date FROM deletion_history ORDER BY $sort_by $sort_order";
if ($result = mysqli_query($link, $sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $deleted_items[] = $row;
    }
    mysqli_free_result($result);
} else {
    error_log("Deletion History page error: " . mysqli_error($link));
}
mysqli_close($link);

function get_sort_href($column, $current_sort, $current_order) {
    $order = ($current_sort === $column && $current_order === 'ASC') ? 'DESC' : 'ASC';
    return "?sort=$column&order=$order";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tavern Publico - Deletion History</title>
    <link rel="stylesheet" href="CSS/admin.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        /* --- STYLES FOR NEW FILTER DROPDOWN --- */
        .reservation-page-header {
            display: flex;
            justify-content: flex-start; /* MODIFIED: Was space-between */
            align-items: center;
            flex-wrap: wrap; 
            gap: 20px;
        }
        .search-input {
            width: 350px;       /* MODIFIED: Set a fixed width */
            flex-grow: 0;         /* MODIFIED: Stopped it from growing */
            min-width: 250px;
        }
        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .filter-group label {
            font-weight: 500; 
            color: #555; 
            margin-bottom: 0;
            white-space: nowrap;
        }
        .filter-group select {
            padding: 8px 15px; 
            border: 1px solid #ddd; 
            border-radius: 20px; 
            background-color: #fff; 
            font-size: 14px; 
            cursor: pointer;
            font-family: 'Mada', sans-serif;
        }
        /* --- END NEW STYLES --- */


        .sort-link { color: #555; text-decoration: none; display: inline-flex; align-items: center; }
        .sort-link:hover { color: #007bff; }
        .sort-link .material-icons { font-size: 16px; margin-left: 4px; }
        
        /* Pagination Styles */
        .pagination-container { display: flex; justify-content: center; align-items: center; margin-top: 25px; padding: 10px 0; gap: 10px; }
        #pageNumbers { display: flex; gap: 5px; }
        .page-number { padding: 8px 14px; border: 1px solid #ddd; border-radius: 4px; cursor: pointer; transition: background-color 0.2s, color 0.2s; background-color: #fff; color: #333; font-weight: 500; }
        .page-number:hover { background-color: #f0f0f0; }
        .page-number.active { background-color: #007bff; color: white; border-color: #007bff; font-weight: bold; }
        .pagination-container .btn:disabled { background-color: #e9ecef; color: #6c757d; cursor: not-allowed; opacity: 0.7; }
        
        /* Style for the 'no items' row */
        .no-items-row {
            text-align: center;
            display: none; /* Hidden by default */
        }
    </style>
</head>
<body>

    <div class="page-wrapper">
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <img src="Tavern.png" alt="Home Icon" class="home-icon">
            </div>
            <nav>
                <ul class="sidebar-menu">
                    <li class="menu-item"><a href="admin.php"><i class="material-icons">dashboard</i> Dashboard</a></li>
                    <li class="menu-item"><a href="reservation.php"><i class="material-icons">event_note</i> Reservation</a></li>
                    <li class="menu-item"><a href="update.php"><i class="material-icons">file_upload</i> Upload Management</a></li>
                    <li class="menu-item"><a href="manage_coupons.php"><i class="material-icons">sell</i> Manage Coupons</a></li>
                </ul>
                <div class="user-management-title">User Management</div>
                <ul class="sidebar-menu user-management-menu">
                    <li class="menu-item"><a href="customer_database.php"><i class="material-icons">people</i> Customer Database</a></li>
                    <li class="menu-item"><a href="notification_control.php"><i class="material-icons">notifications</i> Notification Control</a></li>
                    <li class="menu-item"><a href="table_management.php"><i class="material-icons">table_chart</i>Calendar Management</a></li>
                    <li class="menu-item"><a href="reports.php"><i class="material-icons">analytics</i>Reservation Reports</a></li>
                    <li class="menu-item active"><a href="deletion_history.php"><i class="material-icons">history</i>Archive</a></li>
                </ul>
            </nav>
        </aside>

        <div class="admin-content-area">
            <header class="main-header">
                <div class="header-content">
                    <h1 class="header-page-title">Deletion History</h1>
                    
                    <div class="admin-header-right">
    
                        <div class="admin-notification-area">
                            <div class="admin-notification-item">
                                <button class="admin-notification-button" id="adminMessageBtn" title="Messages">
                                    <i class="material-icons">email</i>
                                    <span class="admin-notification-badge" id="adminMessageCount" style="display: none;">0</span>
                                </button>
                                <div class="admin-notification-dropdown" id="adminMessageDropdown"></div>
                            </div>
                            <div class="admin-notification-item">
                                <button class="admin-notification-button" id="adminReservationBtn" title="Reservations">
                                    <i class="material-icons">notifications</i> <span class="admin-notification-badge" id="adminReservationCount" style="display: none;">0</span>
                                </button>
                                <div class="admin-notification-dropdown" id="adminReservationDropdown"></div>
                            </div>
                        </div>

                        <div class="header-separator"></div>

                        <div class="admin-profile-dropdown">
                            <div class="admin-profile-area" id="adminProfileBtn">
                                <?php $admin_avatar_path = isset($_SESSION['avatar']) && file_exists($_SESSION['avatar']) ? htmlspecialchars($_SESSION['avatar']) : 'images/default_avatar.png'; ?>
                                <img src="<?php echo $admin_avatar_path; ?>" alt="Admin Avatar" class="admin-avatar">
                                <div class="admin-user-info">
                                    <span class="admin-username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                                    <span class="admin-role"><?php echo ucfirst(htmlspecialchars($_SESSION['role'])); ?></span>
                                </div>
                                <i class="material-icons" style="color: #666; margin-left: 5px;">arrow_drop_down</i>
                            </div>
                            <div class="admin-dropdown" id="adminProfileDropdown">
                                <a href="logout.php" class="admin-dropdown-item">
                                    <i class="material-icons">logout</i>
                                    <span>Log Out</span>
                                </a>
                            </div>
                        </div>

                    </div>
                    </div>
            </header>

            <main class="dashboard-main-content">
                
                <div class="reservation-page-header">
                    <input type="text" id="historySearch" class="search-input" placeholder="Search deleted items...">
                    
                    <div class="filter-group">
                        <label for="itemTypeFilter">Filter by Type:</label>
                        <select id="itemTypeFilter">
                            <option value="all">All Items</option>
                            <?php foreach($item_types as $type): ?>
                                <option value="<?= htmlspecialchars($type) ?>">
                                    <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $type))) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <section class="all-reservations-section">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>
                                        <a href="<?= get_sort_href('item_type', $sort_by, $sort_order) ?>" class="sort-link">
                                            Item Type <i class="material-icons">sort</i>
                                        </a>
                                    </th>
                                    <th>ITEM DETAILS</th>
                                    <th>
                                        <a href="<?= get_sort_href('action_by', $sort_by, $sort_order) ?>" class="sort-link">
                                            Deleted By <i class="material-icons">sort</i>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="<?= get_sort_href('deleted_at', $sort_by, $sort_order) ?>" class="sort-link">
                                            Deleted At <i class="material-icons">sort</i>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="<?= get_sort_href('purge_date', $sort_by, $sort_order) ?>" class="sort-link">
                                            Purge Date <i class="material-icons">sort</i>
                                        </a>
                                    </th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="historyTableBody">
                                <?php if (empty($deleted_items)): ?>
                                    <tr class="no-items-row" style="display: table-row;">
                                        <td colspan="6" style="text-align: center;">No deleted items found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($deleted_items as $item): 
                                        $item_data = json_decode($item['item_data'], true);
                                        $details = 'ID: ' . htmlspecialchars($item['item_id']);
                                        if (is_array($item_data)) {
                                            if (isset($item_data['username'])) $details = "User: " . htmlspecialchars($item_data['username']);
                                            elseif (isset($item_data['res_name'])) $details = "Reservation: " . htmlspecialchars($item_data['res_name']);
                                            elseif (isset($item_data['name'])) $details = "Name: " . htmlspecialchars($item_data['name']);
                                            elseif (isset($item_data['title'])) $details = "Title: " . htmlspecialchars($item_data['title']);
                                            elseif (isset($item_data['subject'])) $details = "Subject: " . htmlspecialchars($item_data['subject']);
                                            elseif (isset($item_data['block_date'])) $details = "Date: " . htmlspecialchars($item_data['block_date']);
                                            elseif (isset($item_data['code'])) $details = "Coupon: " . htmlspecialchars($item_data['code']);
                                        }
                                    ?>
                                        <tr data-log-id="<?= $item['log_id']; ?>" data-item-type="<?= htmlspecialchars($item['item_type']); ?>">
                                            <td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $item['item_type']))); ?></td>
                                            <td><?= $details; ?></td>
                                            <td><?= htmlspecialchars($item['action_by'] ?? 'Unknown'); ?></td>
                                            <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($item['deleted_at']))); ?></td>
                                            <td><?= htmlspecialchars($item['purge_date']); ?></td>
                                            <td class="actions">
                                                <button class="btn btn-small restore-btn" style="background-color: #28a745;">Restore</button>
                                                <button class="btn btn-small purge-btn" style="background-color: #dc3545;">Delete Permanently</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr class="no-items-row" style="display: none;">
                                        <td colspan="6" style="text-align: center;">No items found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination-container">
                        <button class="btn" id="prevPageBtn" disabled>&laquo; Previous</button>
                        <div id="pageNumbers"></div>
                        <button class="btn" id="nextPageBtn">Next &raquo;</button>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <div id="alertModal" class="modal">
        <div class="modal-content" style="max-width: 450px; text-align: center;">
            <div class="modal-header" style="padding: 20px 20px 0; border: none;">
                <span class="close-button">&times;</span>
            </div>
            <div class="modal-body" style="padding: 0 20px 20px;">
                <div id="modalHeaderIcon" class="modal-header-icon" style="font-size: 3.5em; margin-bottom: 15px;"></div>
                <h2 id="alertModalTitle" style="margin-top: 0; margin-bottom: 10px; padding-bottom: 0; border: none; font-size: 1.8em;"></h2>
                <p id="alertModalMessage" style="margin-bottom: 0;"></p>
            </div>
            <div id="alertModalActions" class="modal-actions" style="justify-content: center; padding: 20px;">
                </div>
        </div>
    </div>

    <div id="passwordConfirmModal" class="modal">
        <div class="modal-content" style="max-width: 450px;">
            <div class="modal-header">
                <h2 style="margin: 0; margin-bottom: 15px; text-align: left;">Confirm Deletion</h2>
                <span class="close-button">&times;</span>
            </div>
            <form id="passwordConfirmForm">
                <div class="modal-body" style="text-align: left;">
                    <p>To permanently delete this item, please enter your administrator password.</p>
                    <input type="hidden" id="purgeLogId" name="log_id">
                    <div class="form-group">
                        <label for="adminPassword" style="text-align: left;">Password</label>
                        <input type="password" id="adminPassword" name="admin_password" required>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn" id="cancelPurgeBtn" style="background-color: #6c757d; color: white;">Cancel</button>
                    <button type="submit" class="btn purge-btn" style="background-color: #dc3545; color: white;">Confirm & Delete</button>
                </div>
            </form>
        </div>
    </div>

    <script src="JS/deletion_history.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // --- Pagination and Filtering Logic ---
            const tableBody = document.getElementById('historyTableBody');
            const allRows = Array.from(tableBody.querySelectorAll('tr:not(.no-items-row)')); // Get only data rows
            const rowsPerPage = 8;
            let currentPage = 1;

            const historySearch = document.getElementById('historySearch');
            const itemTypeFilter = document.getElementById('itemTypeFilter');

            const prevPageBtn = document.getElementById('prevPageBtn');
            const nextPageBtn = document.getElementById('nextPageBtn');
            const pageNumbersContainer = document.getElementById('pageNumbers');
            const paginationContainer = document.querySelector('.pagination-container');
            const noItemsRow = tableBody.querySelector('.no-items-row'); // Get the 'no items' row

            let currentFilteredRows = allRows;

            function displayPage(page) {
                currentPage = page;
                
                // Hide all rows first
                allRows.forEach(row => row.style.display = 'none');
                if (noItemsRow) noItemsRow.style.display = 'none';

                const start = (page - 1) * rowsPerPage;
                const end = start + rowsPerPage;
                const paginatedItems = currentFilteredRows.slice(start, end);

                if (paginatedItems.length > 0) {
                    paginatedItems.forEach(row => {
                        row.style.display = ''; // Show the row (it's a <tr>)
                    });
                } else if (noItemsRow) {
                    noItemsRow.style.display = 'table-row'; // Show the 'no items' row
                }
                
                updatePaginationUI();
            }

            function updatePaginationUI() {
                const pageCount = Math.ceil(currentFilteredRows.length / rowsPerPage);
                
                if (pageCount <= 1) {
                    paginationContainer.style.display = 'none';
                    return;
                }
                paginationContainer.style.display = 'flex';

                prevPageBtn.disabled = currentPage === 1;
                nextPageBtn.disabled = currentPage === pageCount;

                pageNumbersContainer.innerHTML = '';
                for (let i = 1; i <= pageCount; i++) {
                    const pageBtn = document.createElement('button');
                    pageBtn.textContent = i;
                    pageBtn.className = 'page-number' + (i === currentPage ? ' active' : '');
                    pageBtn.addEventListener('click', () => displayPage(i));
                    pageNumbersContainer.appendChild(pageBtn);
                }
            }

            function applyFilters() {
                const searchTerm = historySearch.value.toLowerCase();
                const filterType = itemTypeFilter.value;

                currentFilteredRows = allRows.filter(row => {
                    const rowText = row.textContent.toLowerCase();
                    const rowType = row.dataset.itemType;
                    
                    const matchesSearch = rowText.includes(searchTerm);
                    const matchesType = (filterType === 'all') || (rowType === filterType);
                    
                    return matchesSearch && matchesType;
                });

                displayPage(1);
            }
            
            // Event Listeners
            if (historySearch) {
                historySearch.addEventListener('keyup', applyFilters);
            }

            if (itemTypeFilter) {
                itemTypeFilter.addEventListener('change', applyFilters);
            }

            if (prevPageBtn) {
                prevPageBtn.addEventListener('click', () => {
                    if (currentPage > 1) displayPage(currentPage - 1);
                });
            }

            if (nextPageBtn) {
                nextPageBtn.addEventListener('click', () => {
                    const pageCount = Math.ceil(currentFilteredRows.length / rowsPerPage);
                    if (currentPage < pageCount) displayPage(currentPage + 1);
                });
            }

            // Initial load
            if (allRows.length > 0) {
                 applyFilters();
            } else if(noItemsRow) {
                // If table is empty on load, show the 'no items' row
                noItemsRow.style.display = 'table-row';
                paginationContainer.style.display = 'none';
            }
        });
    </script>
    <script>
    // NEW MODIFIED Notification script (with profile dropdown logic)
    document.addEventListener('DOMContentLoaded', () => {
        
        // --- MODIFICATION START: Added Notification Modal JS ---
        const notificationModal = document.getElementById('alertModal');
        const modalHeaderIcon = document.getElementById('modalHeaderIcon');
        const modalTitle = document.getElementById('alertModalTitle');
        const modalMessage = document.getElementById('alertModalMessage');
        const modalActions = document.getElementById('alertModalActions');
        const notificationCloseButton = notificationModal ? notificationModal.querySelector('.close-button') : null;
        let notificationCallback = null;

        function showNotification(type, title, message, callback = null) {
            if (!notificationModal || !modalTitle || !modalMessage || !modalActions) {
                alert(message); // Fallback
                return;
            }
            
            // Re-style alertModal to look like the notification modal
            modalHeaderIcon.innerHTML = (type === 'success' ? '<i class="material-icons" style="color: #28a745; font-size: 3.5em;">check_circle</i>' : '<i class="material-icons" style="color: #dc3545; font-size: 3.5em;">error</i>');
            modalTitle.textContent = title;
            modalMessage.textContent = message;
            
            modalActions.innerHTML = '<button class="btn modal-close-btn" style="background-color: #007bff; color: white;">OK</button>';
            
            const okButton = modalActions.querySelector('.modal-close-btn');
            
            notificationCallback = callback;
            
            const closeModal = () => {
                notificationModal.style.display = 'none';
                if(notificationCallback) notificationCallback();
                notificationCallback = null;
            };

            if(okButton) okButton.onclick = closeModal;
            if(notificationCloseButton) notificationCloseButton.onclick = closeModal;

            notificationModal.style.display = 'flex';
        }
        // --- MODIFICATION END ---
        
        
        const messageBtn = document.getElementById('adminMessageBtn');
        const reservationBtn = document.getElementById('adminReservationBtn');
        const messageDropdown = document.getElementById('adminMessageDropdown');
        const reservationDropdown = document.getElementById('adminReservationDropdown');
        
        const messageCountBadge = document.getElementById('adminMessageCount');
        const reservationCountBadge = document.getElementById('adminReservationCount');

        // NEW: Profile Dropdown elements
        const adminProfileBtn = document.getElementById('adminProfileBtn');
        const adminProfileDropdown = document.getElementById('adminProfileDropdown');

        async function fetchAdminNotifications() {
            try {
                const response = await fetch('get_admin_notifications.php');
                const data = await response.json();

                if (data.success) {
                    // Update Message Count and Dropdown
                    if (data.new_messages > 0) {
                        messageCountBadge.textContent = data.new_messages;
                        messageCountBadge.style.display = 'block';
                    } else {
                        messageCountBadge.style.display = 'none';
                    }
                    messageDropdown.innerHTML = data.messages_html;

                    // Update Reservation Count and Dropdown
                    if (data.pending_reservations > 0) {
                        reservationCountBadge.textContent = data.pending_reservations;
                        reservationCountBadge.style.display = 'block';
                    } else {
                        reservationCountBadge.style.display = 'none';
                    }
                    reservationDropdown.innerHTML = data.reservations_html;
                }
            } catch (error) {
                console.error('Error fetching admin notifications:', error);
            }
        }

        // Toggle dropdowns
        if (messageBtn) {
            messageBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (reservationDropdown) reservationDropdown.classList.remove('show');
                if (adminProfileDropdown) adminProfileDropdown.classList.remove('show'); // Close profile
                if (messageDropdown) messageDropdown.classList.toggle('show');
            });
        }

        if (reservationBtn) {
            reservationBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (messageDropdown) messageDropdown.classList.remove('show');
                if (adminProfileDropdown) adminProfileDropdown.classList.remove('show'); // Close profile
                if (reservationDropdown) reservationDropdown.classList.toggle('show');
            });
        }

        // NEW: Toggle Profile Dropdown
        if (adminProfileBtn) {
            adminProfileBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (messageDropdown) messageDropdown.classList.remove('show');
                if (reservationDropdown) reservationDropdown.classList.remove('show');
                if (adminProfileDropdown) adminProfileDropdown.classList.toggle('show');
            });
        }


        // Close dropdowns when clicking outside
        window.addEventListener('click', () => {
            if (messageDropdown) messageDropdown.classList.remove('show');
            if (reservationDropdown) reservationDropdown.classList.remove('show');
            if (adminProfileDropdown) adminProfileDropdown.classList.remove('show'); // Close profile
        });
        
        // Prevent dropdown from closing when clicking inside link area
        [messageDropdown, reservationDropdown, adminProfileDropdown].forEach(dropdown => {
            if (dropdown) {
                dropdown.addEventListener('click', (e) => {
                    // Only stop propagation if it's NOT the dismiss button
                    if (!e.target.classList.contains('admin-notification-dismiss')) {
                        e.stopPropagation();
                    }
                });
            }
        });

        // --- Handle Dismiss Click ---
        async function handleDismiss(e) {
            if (!e.target.classList.contains('admin-notification-dismiss')) return;

            e.preventDefault(); // Prevent default button action
            e.stopPropagation(); // Stop event from bubbling up and closing dropdown

            const button = e.target;
            const id = button.dataset.id;
            const type = button.dataset.type;
            const itemWrapper = button.parentElement;
            
            const formData = new FormData();
            formData.append('id', id);
            formData.append('type', type);

            try {
                const response = await fetch('clear_admin_notification.php', { method: 'POST', body: formData });
                const result = await response.json();

                if (result.success) {
                    // Visually remove the item
                    itemWrapper.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    itemWrapper.style.opacity = '0';
                    itemWrapper.style.transform = 'translateX(-20px)';
                    setTimeout(() => {
                        itemWrapper.remove();
                        // Refetch to update counts and check if dropdown should be empty
                        fetchAdminNotifications(); 
                    }, 300);
                } else {
                    // --- THIS IS THE FIX ---
                    showNotification('error', 'Action Failed', result.message);
                }
            } catch (error) {
                console.error('Error dismissing notification:', error);
                // --- THIS IS THE FIX ---
                showNotification('error', 'Error', 'An error occurred. Please try again.');
            }
        }

        if (messageDropdown) messageDropdown.addEventListener('click', handleDismiss);
        if (reservationDropdown) reservationDropdown.addEventListener('click', handleDismiss);

        // Initial fetch and polling
        fetchAdminNotifications();
        setInterval(fetchAdminNotifications, 30000); // Check for new notifications every 30 seconds
    });
    </script>
</body>
</html>