<?php
session_start();
require_once 'db_connect.php'; // Include your database connection

// MODIFIED: Check if the user is logged in AND is an admin or manager
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['owner', 'manager'])) {
    header('Location: login.php'); // Redirect if not authorized
    exit;
}

// Get the current page name for active link highlighting
$currentPage = basename($_SERVER['SCRIPT_NAME']);

// Fetch all reservations from the database, including new fields
$allReservations = [];
// MODIFIED: Added r.action_by
$sql = "SELECT r.reservation_id, r.user_id, r.res_date, r.res_time, r.num_guests,
        r.res_name, r.res_phone, r.res_email, r.status, r.created_at,
        r.reservation_type, r.valid_id_path,
        r.applied_coupon_code,
        r.action_by, 
        u.avatar
        FROM reservations r
        LEFT JOIN users u ON r.user_id = u.user_id
        WHERE r.deleted_at IS NULL
        ORDER BY r.created_at DESC";

if ($result = mysqli_query($link, $sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $allReservations[] = $row;
    }
    mysqli_free_result($result);
} else {
    error_log("Reservation page database error: " . mysqli_error($link));
}

mysqli_close($link);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tavern Publico - All Reservations</title>
    <link rel="stylesheet" href="CSS/admin.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        .sort-by-status label {
            margin-right: 5px;
        }
        .sort-by-status select {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 20px;
            background-color: #fff;
            font-size: 14px;
            cursor: pointer;
        }
    </style>
</head>
<body>

    <div class="page-wrapper">
        
        <?php
        // Conditionally include the sidebar based on the user's role
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'manager') {
            // For managers, include the specific manager sidebar
            include 'partials/manager_sidebar.php';
        } else {
            // For owners/admins, display the full admin sidebar
        ?>
            <aside class="admin-sidebar">
                <div class="sidebar-header">
                    <img src="Tavern.png" alt="Home Icon" class="home-icon">
                </div>
                <nav>
                    <ul class="sidebar-menu">
                        <li class="menu-item"><a href="admin.php"><i class="material-icons">dashboard</i> Dashboard</a></li>
                        <li class="menu-item active"><a href="reservation.php"><i class="material-icons">event_note</i> Reservation</a></li>
                        <li class="menu-item"><a href="update.php"><i class="material-icons">file_upload</i> Upload Management</a></li>
                        <li class="menu-item"><a href="manage_coupons.php"><i class="material-icons">sell</i> Manage Coupons</a></li>
                    </ul>
                    <div class="user-management-title">User Management</div>
                    <ul class="sidebar-menu user-management-menu">
                       <li class="menu-item"><a href="customer_database.php"><i class="material-icons">people</i> Customer Database</a></li>
                       <li class="menu-item"><a href="notification_control.php"><i class="material-icons">notifications</i> Notification Control</a></li>
                        <li class="menu-item"><a href="table_management.php"><i class="material-icons">table_chart</i>Calendar Management</a></li>
                        <li class="menu-item"><a href="reports.php"><i class="material-icons">analytics</i>Reservation Reports</a></li>
                        <li class="menu-item"><a href="deletion_history.php"><i class="material-icons">history</i>Archive</a></li>
                    </ul>
                </nav>
            </aside>
        <?php
        }
        ?>

        <div class="admin-content-area">
            <header class="main-header">
                <div class="header-content">
                    <h1 class="header-page-title">All Reservations</h1>
                    
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
                    <input type="text" id="reservationSearch" class="search-input" placeholder="Search reservations...">
                    <div class="sort-by-status" style="display: flex; align-items: center; gap: 10px;">
                        <label for="statusSort" style="font-weight: 500; color: #555;">Sort by Status:</label>
                        <select id="statusSort" class="form-control" style="padding: 8px 15px; border: 1px solid #ddd; border-radius: 20px; background-color: #fff; font-size: 14px; cursor: pointer;">
                            <option value="all">All Statuses</option>
                            <option value="Pending">Pending</option>
                            <option value="Confirmed">Confirmed</option>
                            <option value="Cancelled">Cancelled</option>
                            <option value="Declined">Declined</option>
                        </select>
                    </div>
                    <button id="addReservationBtn" class="btn btn-primary" style="background-color: #28a745;">Add New Reservation</button>
                </div>

                <section class="all-reservations-section">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>CUSTOMER</th>
                                    <th>DATE & TIME</th>
                                    <th>GUESTS</th>
                                    <th>TYPE</th>
                                    <th>STATUS</th>
                                    <th>BOOKED AT</th>
                                    <th>ACTION BY</th> 
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($allReservations)): ?>
                                    <tr><td colspan="8" style="text-align: center;">No reservations found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($allReservations as $reservation): ?>
                                        <?php
                                            $statusClass = strtolower($reservation['status']);
                                            $fullReservationData = [
                                                'reservation_id' => $reservation['reservation_id'], 'user_id' => $reservation['user_id'] ?? 'N/A',
                                                'res_date' => $reservation['res_date'], 'res_time' => $reservation['res_time'],
                                                'num_guests' => $reservation['num_guests'], 'res_name' => $reservation['res_name'],
                                                'res_phone' => $reservation['res_phone'], 'res_email' => $reservation['res_email'],
                                                'status' => $reservation['status'], 'created_at' => $reservation['created_at'],
                                                'reservation_type' => $reservation['reservation_type'],
                                                'valid_id_path' => $reservation['valid_id_path'],
                                                'applied_coupon_code' => $reservation['applied_coupon_code'] ?? null,
                                                'action_by' => $reservation['action_by'] ?? null 
                                            ];
                                            $fullReservationJson = htmlspecialchars(json_encode($fullReservationData), ENT_QUOTES, 'UTF-8');
                                        ?>
                                        <tr data-reservation-id="<?php echo $reservation['reservation_id']; ?>"
                                            data-full-reservation='<?php echo $fullReservationJson; ?>'
                                            data-status="<?php echo htmlspecialchars($reservation['status']); ?>">
                                            <td>
                                                <?php
                                                $avatar_path = !empty($reservation['avatar']) && file_exists($reservation['avatar']) ? $reservation['avatar'] : 'images/default_avatar.png';

                                                $customer_info_html = '
                                                    <div class="customer-info">
                                                        <img src="' . htmlspecialchars($avatar_path) . '" alt="Customer Avatar" class="customer-avatar">
                                                        <div>
                                                            <strong>' . htmlspecialchars($reservation['res_name']) . '</strong><br>
                                                            <small>' . htmlspecialchars($reservation['res_email']) . '</small>
                                                        </div>
                                                    </div>';

                                                if (!empty($reservation['user_id'])) {
                                                    echo '<a href="view_customer.php?id=' . $reservation['user_id'] . '&return_to=reservation" style="text-decoration: none; color: inherit;">' . $customer_info_html . '</a>';
                                                } else {
                                                    echo $customer_info_html;
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($reservation['res_date']); ?><br><small><?php echo htmlspecialchars(date('g:i A', strtotime($reservation['res_time']))); ?></small></td>
                                            <td><?php echo htmlspecialchars($reservation['num_guests']); ?></td>
                                            <td><?php echo htmlspecialchars($reservation['reservation_type']); ?></td>
                                            <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($reservation['status']); ?></span></td>
                                            <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($reservation['created_at']))); ?></td>
                                            <td><?php echo htmlspecialchars($reservation['action_by'] ?? 'N/A'); ?></td>
                                            <td class="actions">
                                                <button class="btn btn-small view-edit-btn">View/Edit</button>
                                                <button class="btn btn-small delete-btn">Delete</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
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

            <div id="reservationModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 id="modal-title-h2">Reservation Details & Edit</h2>
                        <span class="close-button">&times;</span>
                    </div>
                    <div class="modal-body">
                        <form id="editReservationForm">
                            <input type="hidden" id="modalReservationId" name="reservation_id">
                            <div class="form-group"><label for="modalResName">Customer Name:</label><input type="text" id="modalResName" name="res_name" required></div>
                            <div class="form-group"><label for="modalResEmail">Email:</label><input type="email" id="modalResEmail" name="res_email" required></div>
                            <div class="form-group"><label for="modalResPhone">Phone:</label><input type="tel" id="modalResPhone" name="res_phone"></div>
                            <div class="form-group"><label for="modalResDate">Date:</label><input type="date" id="modalResDate" name="res_date" required></div>
                            <div class="form-group"><label for="modalResTime">Time:</label><input type="time" id="modalResTime" name="res_time" required></div>
                            <div class="form-group"><label for="modalNumGuests">Number of Guests:</label><input type="number" id="modalNumGuests" name="num_guests" min="1" required></div>
                             <div class="form-group">
                                <label for="modalReservationType">Reservation Type:</label>
                                <select id="modalReservationType" name="reservation_type">
                                    <option value="Dine-in">Dine-in</option>
                                    <option value="Private Event">Private Event</option>
                                    <option value="Special Occasion">Special Occasion</option>
                                </select>
                            </div>
                            <div class="form-group"><label for="modalStatus">Status:</label><select id="modalStatus" name="status"><option value="Pending">Pending</option><option value="Confirmed">Confirmed</option><option value="Cancelled">Cancelled</option><option value="Declined">Declined</option></select></div>

                            <div class="form-group"><label for="modalCreatedAt">Booked At:</label><input type="text" id="modalCreatedAt" name="created_at" readonly></div>
                             
                            <div class="form-group">
                                <label for="modalActionBy">Last Action By:</label>
                                <input type="text" id="modalActionBy" name="action_by" readonly style="background-color: #e9ecef; cursor: not-allowed;">
                            </div>
                            
                             <div class="form-group" style="grid-column: 1 / -1;"> 
                                <label>Uploaded ID:</label>
                                <div id="validIdDisplay">
                                   
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-actions">
                        <button type="submit" class="btn modal-save-btn" form="editReservationForm">Save Changes</button>
                        
                        <button type="button" class="btn modal-delete-btn" style="background-color: #dc3545; color: white;">Delete</button>
                    </div>
                </div>
            </div>

            <div id="addReservationModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Add New Walk-in Reservation</h2>
                        <span class="close-button">&times;</span>
                    </div>
                    <div class="modal-body">
                        <form id="addReservationForm">
                            <div class="form-group"><label for="addResName">Customer Name:</label><input type="text" id="addResName" name="res_name" required></div>
                            <div class="form-group"><label for="addResEmail">Email:</label><input type="email" id="addResEmail" name="res_email" required></div>
                            <div class="form-group"><label for="addResPhone">Phone:</label><input type="tel" id="addResPhone" name="res_phone" required></div>
                            <div class="form-group"><label for="addResDate">Date:</label><input type="date" id="addResDate" name="res_date" required></div>
                            <div class="form-group"><label for="addResTime">Time:</label><input type="time" id="addResTime" name="res_time" required></div>
                            <div class="form-group"><label for="addNumGuests">Number of Guests:</label><input type="number" id="addNumGuests" name="num_guests" min="1" required></div>
                            <div class="form-group">
                                <label for="addReservationType">Reservation Type:</label>
                                <select id="addReservationType" name="reservation_type">
                                    <option value="Dine-in">Dine-in</option>
                                    <option value="Private Event">Private Event</option>
                                    <option value="Special Occasion">Special Occasion</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-actions">
                        <button type="submit" class="btn modal-save-btn" form="addReservationForm">Add Reservation</button>
                    </div>
                </div>
            </div>

            <div id="confirmDeleteModal" class="modal">
                <div class="modal-content" style="max-width: 500px;">
                    <div class="modal-header">
                        <h2 id="modal-title-h2">Confirm Deletion</h2>
                        <span class="close-button">&times;</span>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to move this reservation to the deletion history? It can be restored within 30 days.</p>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn" id="cancelDeleteBtn" style="background-color: #6c757d; color: white;">Cancel</button>
                        <button type="button" class="btn delete-btn" id="confirmDeleteBtn">Yes, Delete</button>
                    </div>
                </div>
            </div>

            <div id="notificationModal" class="modal">
                <div class="modal-content" style="max-width: 450px; text-align: center;">
                    <span class="close-button">&times;</span>
                    <div id="modalHeaderIcon" class="modal-header-icon"></div>
                    <h2 id="modalTitle"></h2>
                    <p id="modalMessage"></p>
                    <div class="modal-actions" style="justify-content: center;">
                        <button class="btn modal-close-btn" style="background-color: #007bff; color: white;">OK</button>
                    </div>
                </div>
            </div>

            <div id="imageIdModal" class="modal" style="background-color: rgba(0, 0, 0, 0.85); z-index: 2001;">
                <span class="close-button close-image-modal" style="color: #f1f1f1; font-size: 40px; top: 20px; right: 35px; z-index: 2002;">&times;</span>
                <img class="modal-content image-modal-content" id="modalImageContent" style="max-width: 85%; max-height: 85vh; padding: 0; border-radius: 5px;">
            </div>

        </div>
    </div>

    <script src="JS/reservation.js"></script>
    <script>
    // NEW MODIFIED Notification script (with profile dropdown logic)
    document.addEventListener('DOMContentLoaded', () => {
        
        // --- MODIFICATION START: Copied from JS/reservation.js ---
        // This makes the showNotification function available in this script's scope
        const notificationModal = document.getElementById('notificationModal');
        const modalHeaderIcon = document.getElementById('modalHeaderIcon');
        const modalTitle = document.getElementById('modalTitle');
        const modalMessage = document.getElementById('modalMessage');
        const notificationCloseButton = notificationModal ? notificationModal.querySelector('.close-button') : null;
        const notificationOkButton = notificationModal ? notificationModal.querySelector('.modal-close-btn') : null;
        let notificationCallback = null;

        function showNotification(type, title, message, callback = null) {
            if (!notificationModal || !modalHeaderIcon || !modalTitle || !modalMessage) {
                 alert(`${title}: ${message}`); // Fallback
                if (callback) callback();
                return;
            }

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
        if(messageBtn) {
            messageBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                if(reservationDropdown) reservationDropdown.classList.remove('show');
                if(adminProfileDropdown) adminProfileDropdown.classList.remove('show'); // Close profile
                if(messageDropdown) messageDropdown.classList.toggle('show');
            });
        }

        if(reservationBtn) {
            reservationBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                if(messageDropdown) messageDropdown.classList.remove('show');
                if(adminProfileDropdown) adminProfileDropdown.classList.remove('show'); // Close profile
                if(reservationDropdown) reservationDropdown.classList.toggle('show');
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
            if(messageDropdown) messageDropdown.classList.remove('show');
            if(reservationDropdown) reservationDropdown.classList.remove('show');
            if(adminProfileDropdown) adminProfileDropdown.classList.remove('show'); // Close profile
        });

        // Prevent dropdown from closing when clicking inside link area
        [messageDropdown, reservationDropdown, adminProfileDropdown].forEach(dropdown => {
           if(dropdown) {
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
                    // Now it uses the showNotification function defined above
                    showNotification('error', 'Action Failed', result.message);
                }
            } catch (error) {
                console.error('Error dismissing notification:', error);
                // --- THIS IS THE FIX ---
                showNotification('error', 'Error', 'An error occurred. Please try again.');
            }
        }

       if(messageDropdown) messageDropdown.addEventListener('click', handleDismiss);
       if(reservationDropdown) reservationDropdown.addEventListener('click', handleDismiss);

        // Initial fetch and polling
        fetchAdminNotifications();
        setInterval(fetchAdminNotifications, 30000); // Check for new notifications every 30 seconds
    });
    </script>
</body>
</html>