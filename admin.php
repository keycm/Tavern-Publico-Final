<?php
session_start(); // Start the session at the very beginning
require_once 'db_connect.php'; // Include your database connection

// Check if the user is logged in AND is an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !$_SESSION['is_admin']) {
    header('Location: login.php'); // Redirect to login page if not logged in or not admin
    exit;
}

// Fetch reservations from the database for the main table
$reservations = [];

// MODIFIED SQL: Added r.reservation_type and r.valid_id_path
$sql_reservations = "
    SELECT
        r.reservation_id, r.user_id, r.res_date, r.res_time, r.num_guests,
        r.res_name, r.res_phone, r.res_email, r.status, r.created_at,
        r.reservation_type, r.valid_id_path, -- Added reservation_type and valid_id_path
        r.applied_coupon_code, -- Added coupon code
        r.action_by, -- Added action_by
        u.avatar
    FROM reservations r
    LEFT JOIN users u ON r.user_id = u.user_id
    WHERE r.deleted_at IS NULL
    ORDER BY r.created_at DESC
";

if ($result = mysqli_query($link, $sql_reservations)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $reservations[] = $row;
    }
    mysqli_free_result($result);
} else {
    error_log("Admin page database error: " . mysqli_error($link));
}

// --- FIXED: ADDED THIS BLOCK BACK IN ---
$totalReservations = count($reservations);
$pendingReservations = count(array_filter($reservations, function($r) { return $r['status'] === 'Pending'; }));
$confirmedReservations = count(array_filter($reservations, function($r) { return $r['status'] === 'Confirmed'; }));
$cancelledReservations = count(array_filter($reservations, function($r) { return $r['status'] === 'Cancelled'; }));

// --- Data Fetching for Charts ---
$sql_source = "SELECT
                    CASE
                        WHEN source IS NULL OR source = '' THEN 'Online'
                        ELSE source
                    END AS business_source,
                    COUNT(reservation_id) as count
               FROM reservations
               WHERE deleted_at IS NULL
               GROUP BY business_source";
$source_counts = [];
$result_source = mysqli_query($link, $sql_source);
while ($row = mysqli_fetch_assoc($result_source)) {
    $source_counts[$row['business_source']] = $row['count'];
}

// Guest Demographics (New vs. Returning)
$new_guests = 0;
$returning_guests = 0;
$sql_guests = "SELECT res_email, MIN(res_date) as first_visit FROM reservations WHERE deleted_at IS NULL GROUP BY res_email";
$result_guests = mysqli_query($link, $sql_guests);
$guest_first_visits = [];
while ($guest = mysqli_fetch_assoc($result_guests)) {
    $guest_first_visits[$guest['res_email']] = $guest['first_visit'];
}
$sql_all_reservations_for_guests = "SELECT res_email, res_date FROM reservations WHERE deleted_at IS NULL";
$result_all_reservations = mysqli_query($link, $sql_all_reservations_for_guests);
while ($res = mysqli_fetch_assoc($result_all_reservations)) {
    if (isset($guest_first_visits[$res['res_email']])) {
        if ($res['res_date'] == $guest_first_visits[$res['res_email']]) {
            $new_guests++;
        } else {
            $returning_guests++;
        }
    }
}

mysqli_close($link);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tavern Publico - Admin Dashboard</title>
    <link rel="stylesheet" href="CSS/admin.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-main-grid { display: grid; grid-template-columns: 1fr 1fr 1.2fr; gap: 20px; margin-bottom: 30px; align-items: stretch; }
        .dashboard-main-grid .chart-container, .dashboard-main-grid .calendar-box { display: flex; flex-direction: column; }
        .chart-container { background-color: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1); }
        .chart-container h3 { margin-top: 0; margin-bottom: 20px; text-align: center; font-size: 18px; color: #333; }
        .chart-canvas-container { position: relative; flex-grow: 1; min-height: 250px; }
        .calendar-box { background-color: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1); }
        /* Added style for ID Uploaded icon */
        .id-uploaded-icon { color: #28a745; font-size: 1.2em; vertical-align: middle; }
        .no-id-icon { color: #ccc; font-size: 1.2em; vertical-align: middle; }
        @media (max-width: 1200px) { .dashboard-main-grid { grid-template-columns: 1fr 1fr; } .dashboard-main-grid .calendar-box { grid-column: 1 / -1; } }
        @media (max-width: 768px) { .dashboard-main-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <div class="page-wrapper">
        <aside class="admin-sidebar">
             <div class="sidebar-header"><img src="Tavern.png" alt="Home Icon" class="home-icon"></div>
            <nav>
                <ul class="sidebar-menu">
                    <li class="menu-item active"><a href="admin.php"><i class="material-icons">dashboard</i> Dashboard</a></li>
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
                    <li class="menu-item"><a href="deletion_history.php"><i class="material-icons">history</i>Archive</a></li>
                    </ul>
            </nav>
        </aside>

        <div class="admin-content-area">
             <header class="main-header">
                <div class="header-content">
                    <h1 class="header-page-title">Reservation Dashboard</h1>
                    
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
                 <section class="dashboard-summary">
                    <div class="summary-box total"><h3>Total reservations</h3><p><?php echo $totalReservations; ?></p><div class="box-icon">📊</div></div>
                    <div class="summary-box pending"><h3>Pending</h3><p><?php echo $pendingReservations; ?></p><div class="box-icon">🕒</div></div>
                    <div class="summary-box confirmed"><h3>Confirmed</h3><p><?php echo $confirmedReservations; ?></p><div class="box-icon">✅</div></div>
                    <div class="summary-box cancelled"><h3>Cancelled</h3><p><?php echo $cancelledReservations; ?></p><div class="box-icon">❌</div></div>
                </section>
                <section class="dashboard-main-grid">
                    <div class="chart-container"><h3>Source of Business</h3><div class="chart-canvas-container"><canvas id="sourceChart"></canvas></div></div>
                    <div class="chart-container"><h3>Guest Demographics</h3><div class="chart-canvas-container"><canvas id="demographicsChart"></canvas></div></div>
                    <div class="calendar-box"><h3>Calendar</h3><div id="calendar"></div></div>
                </section>
                <section class="recent-reservations-section">
                     <h2>Recent reservations <input type="text" id="reservationSearchTop" class="search-input-top" placeholder="Search"></h2>
                    <div class="table-responsive">
                         <table>
                            <thead>
                                <tr>
                                    <th>CUSTOMER</th>
                                    <th>DATE</th>
                                    <th>TIME</th>
                                    <th>TYPE</th>
                                    <th>ID?</th> <th>STATUS</th>
                                    <th>Info</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reservations)): ?>
                                    <tr><td colspan="7">No reservations found.</td></tr> <?php else: ?>
                                    <?php foreach (array_slice($reservations, 0, 5) as $reservation): ?>
                                        <?php
                                            $statusClass = strtolower($reservation['status']);
                                            $displayData = [
                                                'Reservation ID' => $reservation['reservation_id'],
                                                'User ID' => $reservation['user_id'] ?? 'N/A',
                                                'Date' => $reservation['res_date'],
                                                'Time' => date("g:i A", strtotime($reservation['res_time'])),
                                                'Guests' => $reservation['num_guests'],
                                                'Name' => $reservation['res_name'],
                                                'Phone' => $reservation['res_phone'],
                                                'Email' => $reservation['res_email'],
                                                'Type' => $reservation['reservation_type'] ?? 'Dine-in',
                                                'Status' => $reservation['status'],
                                                'Booked At' => $reservation['created_at'],
                                                'Valid ID Path' => $reservation['valid_id_path'],
                                                'Coupon Used' => $reservation['applied_coupon_code'] ?? 'N/A',
                                                'Action By' => $reservation['action_by'] ?? 'N/A' // Added this line
                                            ];
                                            $fullReservationJson = htmlspecialchars(json_encode($displayData), ENT_QUOTES, 'UTF-8');
                                        ?>
                                        <tr data-reservation-id="<?php echo $reservation['reservation_id']; ?>" data-full-reservation='<?php echo $fullReservationJson; ?>'>
                                             <td>
                                                <?php
                                                $avatar_path = !empty($reservation['avatar']) && file_exists($reservation['avatar']) ? $reservation['avatar'] : 'images/default_avatar.png';
                                                $customer_info_html = '<div class="customer-info"><img src="' . htmlspecialchars($avatar_path) . '" alt="Customer Avatar" class="customer-avatar"><div><strong>' . htmlspecialchars($reservation['res_name']) . '</strong><br><small>' . htmlspecialchars($reservation['res_email']) . '</small></div></div>';
                                                if (!empty($reservation['user_id'])) { echo '<a href="view_customer.php?id=' . $reservation['user_id'] . '&return_to=admin" style="text-decoration: none; color: inherit;">' . $customer_info_html . '</a>'; } else { echo $customer_info_html; }
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($reservation['res_date']); ?></td>
                                            <td><?php echo date("g:i A", strtotime($reservation['res_time'])); ?></td>
                                            <td><?php echo htmlspecialchars($reservation['reservation_type'] ?? 'Dine-in'); ?></td>
                                            <td style="text-align: center;">
                                                <?php if (!empty($reservation['valid_id_path'])): ?>
                                                    <i class="material-icons id-uploaded-icon" title="Valid ID Uploaded">check_circle</i>
                                                <?php else: ?>
                                                    <i class="material-icons no-id-icon" title="No ID Uploaded">cancel</i>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($reservation['status']); ?></span></td>
                                            <td class="actions"><button class="btn btn-small view-btn">View</button></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>

            <div id="reservationModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header"><h2 id="modal-title-h2">Reservation Details</h2><span class="close-button">&times;</span></div>
                    <div class="modal-body">
                         <div id="modalDetails">
                             </div>
                         <div class="form-group" style="margin-top: 15px;">
                            <label style="font-weight: 600; color: #333;">Uploaded ID:</label>
                            <div id="validIdDisplayModal">
                                </div>
                         </div>
                    </div>
                    <div class="modal-actions">
                        <button class="btn btn-small modal-confirm-btn" data-status="Confirmed">Confirm</button>
                        <button class="btn btn-small modal-decline-btn" data-status="Declined">Decline</button>
                        <button class="btn btn-small modal-delete-btn">Delete</button>
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
                        <p>Are you sure you want to move this reservation to the deletion history? It can be restored within 30 days before it is permanently deleted.</p>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn" id="cancelDeleteBtn" style="background-color: #6c757d; color: white;">Cancel</button>
                        <button type="button" class="btn delete-btn" id="confirmDeleteBtn">Yes, Delete</button>
                    </div>
                </div>
            </div>

            <div id="imageIdModal" class="modal" style="background-color: rgba(0, 0, 0, 0.85); z-index: 2001;">
                <span class="close-button close-image-modal" style="color: #f1f1f1; font-size: 40px; top: 20px; right: 35px; z-index: 2002;">&times;</span>
                <img class="modal-content image-modal-content" id="modalImageContent" style="max-width: 85%; max-height: 85vh; padding: 0; border-radius: 5px;">
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
            
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.js"></script>

    <script src="JS/admin.js"></script> 
    
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        // --- CHART LOGIC (remains unchanged) ---
        const sourceCtx = document.getElementById('sourceChart')?.getContext('2d');
        if (sourceCtx) {
            new Chart(sourceCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_keys($source_counts)); ?>,
                    datasets: [{
                        label: 'Reservations',
                        data: <?php echo json_encode(array_values($source_counts)); ?>,
                        backgroundColor: ['rgba(255, 159, 64, 0.7)', 'rgba(75, 192, 192, 0.7)', 'rgba(153, 102, 255, 0.7)'],
                        borderColor: ['rgba(255, 159, 64, 1)', 'rgba(75, 192, 192, 1)', 'rgba(153, 102, 255, 1)'],
                        borderWidth: 1
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
            });
        }

        const demographicsCtx = document.getElementById('demographicsChart')?.getContext('2d');
        if (demographicsCtx) {
            new Chart(demographicsCtx, {
                type: 'bar',
                data: {
                    labels: ['New Guests', 'Returning Guests'],
                    datasets: [{
                        label: 'Guests',
                        data: [<?php echo $new_guests; ?>, <?php echo $returning_guests; ?>],
                        backgroundColor: ['rgba(255, 206, 86, 0.7)', 'rgba(54, 162, 235, 0.7)'],
                        borderColor: ['rgba(255, 206, 86, 1)', 'rgba(54, 162, 235, 1)'],
                        borderWidth: 1
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
            });
        }
    });
    </script>

    <script>
    // NEW MODIFIED Notification Script (with profile dropdown logic)
    document.addEventListener('DOMContentLoaded', () => {
        
        // --- MODIFICATION START: Copied from JS/admin.js ---
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
                alert(`${title}: ${message}`); // Fallback if modal is missing
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
        // --- MODIFICATION END ---


        const messageBtn = document.getElementById('adminMessageBtn');
        const reservationBtn = document.getElementById('adminReservationBtn');
        const messageDropdown = document.getElementById('adminMessageDropdown');
        const reservationDropdown = document.getElementById('adminReservationDropdown');

        const messageCountBadge = document.getElementById('adminMessageCount');
        const reservationCountBadge = document.getElementById('adminReservationCount');

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
                    if (!e.target.classList.contains('admin-notification-dismiss')) {
                        e.stopPropagation();
                    }
                });
            }
        });

        // --- Handle Dismiss Click ---
        async function handleDismiss(e) {
            if (!e.target.classList.contains('admin-notification-dismiss')) return;

            e.preventDefault(); 
            e.stopPropagation(); 

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

        if (messageDropdown) messageDropdown.addEventListener('click', handleDismiss);
        if (reservationDropdown) reservationDropdown.addEventListener('click', handleDismiss);

        // Initial fetch and polling
        fetchAdminNotifications();
        setInterval(fetchAdminNotifications, 30000); // Check for new notifications every 30 seconds
    });
    </script>
</body>
</html>