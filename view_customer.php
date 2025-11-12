<?php
session_start();
require_once 'db_connect.php';

// MODIFIED: This is the fix for the authorization
// This page is owner-only, so we check for the 'owner' role
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') {
    header('Location: login.php');
    exit;
}
// END FIX

$return_to_page = 'admin.php';
$return_title = 'Back to Dashboard';
if (isset($_GET['return_to'])) {
    if ($_GET['return_to'] === 'reservation') {
        $return_to_page = 'reservation.php';
        $return_title = 'Back to Reservations';
    }
    // Added this case for returning to the customer database
    if ($_GET['return_to'] === 'customer_database') {
        $return_to_page = 'customer_database.php';
        $return_title = 'Back to Customer Database';
    }
}

$customer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($customer_id <= 0) {
    // We don't have a session message system here, so just redirect
    header('Location: customer_database.php');
    exit;
}

$customer = null;
$sql_user = "SELECT username, email, created_at, avatar, mobile, birthday FROM users WHERE user_id = ? AND deleted_at IS NULL";
if ($stmt_user = mysqli_prepare($link, $sql_user)) {
    mysqli_stmt_bind_param($stmt_user, "i", $customer_id);
    if (mysqli_stmt_execute($stmt_user)) {
        $result_user = mysqli_stmt_get_result($stmt_user);
        $customer = mysqli_fetch_assoc($result_user);
    } else {
        error_log("Error executing user query: " . mysqli_stmt_error($stmt_user));
    }
    mysqli_stmt_close($stmt_user);
} else {
     error_log("Error preparing user query: " . mysqli_error($link));
}

if (!$customer) {
    // Customer not found, redirect back
    header('Location: customer_database.php');
    exit;
}

$reservations = [];
$sql_reservations = "SELECT reservation_id, res_date, res_time, num_guests, status, created_at FROM reservations WHERE user_id = ? AND deleted_at IS NULL ORDER BY created_at DESC";
if ($stmt_reservations = mysqli_prepare($link, $sql_reservations)) {
    mysqli_stmt_bind_param($stmt_reservations, "i", $customer_id);
    if (mysqli_stmt_execute($stmt_reservations)) {
        $result_reservations = mysqli_stmt_get_result($stmt_reservations);
        while ($row = mysqli_fetch_assoc($result_reservations)) {
            $reservations[] = $row;
        }
    } else {
        error_log("Error executing reservations query: " . mysqli_stmt_error($stmt_reservations));
    }
    mysqli_stmt_close($stmt_reservations);
}

mysqli_close($link);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Profile - <?= htmlspecialchars($customer['username']); ?></title>
    <link rel="stylesheet" href="CSS/admin.css">
    <link rel="stylesheet" href="CSS/profile.css"> <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        /* Page-specific styles */
        .profile-header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .close-profile-btn {
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            color: #555;
            transition: color 0.2s;
            line-height: 1;
            padding: 8px 12px;
            border-radius: 6px;
            background-color: #f0f0f0;
        }
        .close-profile-btn:hover {
            color: #000;
            background-color: #e0e0e0;
        }
        .toggle-history-btn {
            display: block;
            margin: 15px auto 0;
            background: #f0f0f0;
            border: 1px solid #ddd;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
        }
        /* Make profile.css styles more specific */
        .profile-details-card .info-row .info-label {
            flex-basis: 30%;
        }
        .profile-details-card .info-row .info-value {
            flex-basis: 70%;
            text-align: right;
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
                    <li class="menu-item active"><a href="customer_database.php"><i class="material-icons">people</i> Customer Database</a></li>
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
                    <h1 class="header-page-title">Customer Profile</h1>
                    
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
                <div class="profile-header-container">
                    <h2 style="margin: 0; font-size: 24px; color: #333;">Profile: <?= htmlspecialchars($customer['username']); ?></h2>
                    <a href="<?php echo htmlspecialchars($return_to_page); ?>" class="close-profile-btn" title="<?php echo htmlspecialchars($return_title); ?>">&times; Back</a>
                </div>

                <div class="profile-content-grid">
                    <div class="profile-details-card">
                        <div class="card-header" style="display: flex; align-items: center; gap: 15px;">
                            <?php $avatar_path = !empty($customer['avatar']) && file_exists($customer['avatar']) ? $customer['avatar'] : 'images/default_avatar.png'; ?>
                            <img src="<?= htmlspecialchars($avatar_path) ?>" alt="Avatar" style="width: 60px; height: 60px; border-radius: 50%;">
                            <h3><?= htmlspecialchars($customer['username']); ?></h3>
                        </div>
                        <div class="card-body">
                            <div class="info-row">
                                <span class="info-label">Email</span>
                                <span class="info-value"><?= htmlspecialchars($customer['email']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Mobile</span>
                                <span class="info-value"><?= htmlspecialchars($customer['mobile'] ?? 'Not Provided'); ?></span>
                            </div>
                             <div class="info-row">
                                <span class="info-label">Birthday</span>
                                <span class="info-value"><?= !empty($customer['birthday']) ? date('F j, Y', strtotime($customer['birthday'])) : 'Not Provided'; ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Member Since</span>
                                <span class="info-value"><?= date('F j, Y', strtotime($customer['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="reservation-history-card">
                        <div class="card-header">
                            <h3><i class="material-icons">calendar_today</i> Reservation History</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Guests</th>
                                            <th>Status</th>
                                            <th>Booked On</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($reservations)): ?>
                                            <?php foreach ($reservations as $index => $res): ?>
                                                <tr class="reservation-row" style="<?= $index >= 3 ? 'display: none;' : '' ?>">
                                                    <td><?= htmlspecialchars($res['res_date']); ?></td>
                                                    <td><?= htmlspecialchars(date('g:i A', strtotime($res['res_time']))); ?></td>
                                                    <td><?= htmlspecialchars($res['num_guests']); ?></td>
                                                    <td>
                                                        <span class="status-badge <?php echo strtolower(htmlspecialchars($res['status'])); ?>">
                                                            <?= htmlspecialchars($res['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?= htmlspecialchars(date('Y-m-d', strtotime($res['created_at']))); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="no-reservations">This customer has no reservation history.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                                <?php if (count($reservations) > 3): ?>
                                    <button id="toggleHistoryBtn" class="toggle-history-btn">Show More</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <div id="notificationModal" class="modal">
        <div class="modal-content" style="max-width: 450px; text-align: center;">
            <div class="modal-header" style="padding: 20px 20px 0; border: none;">
                <span class="close-button">&times;</span>
            </div>
            <div class="modal-body" style="padding: 0 20px 20px;">
                <div id="modalHeaderIcon" class="modal-header-icon" style="font-size: 3.5em; margin-bottom: 15px;"></div>
                <h2 id="modalTitle" style="margin-top: 0; margin-bottom: 10px; padding-bottom: 0; border: none; font-size: 1.8em;"></h2>
                <p id="modalMessage" style="margin-bottom: 0;"></p>
            </div>
            <div class="modal-actions" style="justify-content: center; padding: 20px;">
                <button class="btn modal-close-btn" style="background-color: #007bff; color: white;">OK</button>
            </div>
        </div>
    </div>

    <script>
        // This is the original script from your file
        document.addEventListener('DOMContentLoaded', () => {
            const toggleBtn = document.getElementById('toggleHistoryBtn');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    const rows = document.querySelectorAll('.reservation-row');
                    const isShowingAll = this.textContent === 'Show Less';

                    rows.forEach((row, index) => {
                        if (index >= 3) {
                            row.style.display = isShowingAll ? 'none' : 'table-row';
                        }
                    });

                    this.textContent = isShowingAll ? 'Show More' : 'Show Less';
                });
            }
        });
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        
        // --- Notification Modal Functions ---
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
            modalHeaderIcon.innerHTML = type === 'success' ? '<i class="material-icons" style="color: #28a745; font-size: 3.5em;">check_circle</i>' : '<i class="material-icons" style="color: #dc3545; font-size: 3.5em;">error</i>';
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
                    if (data.new_messages > 0) {
                        messageCountBadge.textContent = data.new_messages;
                        messageCountBadge.style.display = 'block';
                    } else {
                        messageCountBadge.style.display = 'none';
                    }
                    messageDropdown.innerHTML = data.messages_html;

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

        if(messageBtn) {
             messageBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                if(reservationDropdown) reservationDropdown.classList.remove('show');
                if(adminProfileDropdown) adminProfileDropdown.classList.remove('show');
                if(messageDropdown) messageDropdown.classList.toggle('show');
            });
        }

       if(reservationBtn) {
            reservationBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                if(messageDropdown) messageDropdown.classList.remove('show');
                if(adminProfileDropdown) adminProfileDropdown.classList.remove('show');
                if(reservationDropdown) reservationDropdown.classList.toggle('show');
            });
       }
       
       if(adminProfileBtn) {
           adminProfileBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (messageDropdown) messageDropdown.classList.remove('show');
                if (reservationDropdown) reservationDropdown.classList.remove('show');
                if (adminProfileDropdown) adminProfileDropdown.classList.toggle('show');
           });
       }

        window.addEventListener('click', () => {
            if(messageDropdown) messageDropdown.classList.remove('show');
            if(reservationDropdown) reservationDropdown.classList.remove('show');
            if(adminProfileDropdown) adminProfileDropdown.classList.remove('show');
        });

        [messageDropdown, reservationDropdown, adminProfileDropdown].forEach(dropdown => {
           if(dropdown) {
                dropdown.addEventListener('click', (e) => {
                    if (!e.target.classList.contains('admin-notification-dismiss')) {
                        e.stopPropagation();
                    }
                });
           }
        });

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
                    itemWrapper.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    itemWrapper.style.opacity = '0';
                    itemWrapper.style.transform = 'translateX(-20px)';
                    setTimeout(() => {
                        itemWrapper.remove();
                        fetchAdminNotifications();
                    }, 300);
                } else {
                    showNotification('error', 'Action Failed', result.message);
                }
            } catch (error) {
                console.error('Error dismissing notification:', error);
                showNotification('error', 'Error', 'An error occurred. Please try again.');
            }
        }

       if(messageDropdown) messageDropdown.addEventListener('click', handleDismiss);
       if(reservationDropdown) reservationDropdown.addEventListener('click', handleDismiss);

        fetchAdminNotifications();
        setInterval(fetchAdminNotifications, 30000);
    });
    </script>

</body>
</html>