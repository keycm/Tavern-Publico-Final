<?php
session_start();
require_once 'db_connect.php';

// MODIFICATION: Only the 'owner' can access this page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') {
    header('Location: login.php');
    exit;
}

// Automatically delete unverified users whose OTP has expired.
$cleanup_sql = "DELETE FROM users WHERE is_verified = 0 AND otp_expiry < NOW()";
if (!mysqli_query($link, $cleanup_sql)) {
    error_log("Failed to cleanup expired users on customer_database.php: " . mysqli_error($link));
}

// Fetch all non-admin users from the database, including their role
$users = [];
$sql = "SELECT user_id, username, email, created_at, is_verified, role, permissions FROM users WHERE is_admin = 0 AND deleted_at IS NULL ORDER BY created_at DESC";

if ($result = mysqli_query($link, $sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
    mysqli_free_result($result);
} else {
    error_log("Customer Database page error: " . mysqli_error($link));
}

mysqli_close($link);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tavern Publico - Customer Database</title>
    <link rel="stylesheet" href="CSS/admin.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        /* --- MODIFICATION: Added color and hover for save button --- */
        .modal-save-btn {
            background-color: #28a745; /* Green */
            color: white;
        }
        .modal-save-btn:hover {
            background-color: #218838; /* Darker Green */
        }
        /* --- END MODIFICATION --- */
        
        /* Fix for edit/add modal width */
        #userModal .modal-content {
            max-width: 550px; 
        }

        /* --- Styles for New Manager Permissions Modal --- */
        #managerPermissionsModal .modal-content {
            max-width: 600px;
        }
        #managerPermissionsTitle {
            text-align: center;
        }
        .permission-group {
            margin-top: 20px;
            border-top: 1px solid #eee;
            text-align: left;
        }
        .permission-item {
            display: flex;
            align-items: center;
            padding: 20px 10px;
            border-bottom: 1px solid #eee;
            gap: 20px;
        }

        .permission-icon {
            display: flex; 
            align-items: center; 
            justify-content: center; 
            flex-shrink: 0; 
            font-size: 2.5em;
            color: #3498db;
            width: 50px;
            text-align: center;
        }

        .permission-text {
            flex-grow: 1;
        }
        .permission-text label {
            font-size: 1.1em;
            font-weight: 600;
            color: #333;
            display: block;
            margin-bottom: 4px;
            cursor: pointer;
        }
        .permission-text p {
            font-size: 0.9em;
            color: #777;
            margin: 0;
            line-height: 1.5;
        }
        .permission-toggle {
            flex-shrink: 0;
        }
        /* Toggle Switch CSS */
        .toggle-switch {
            height: 0;
            width: 0;
            visibility: hidden;
        }
        .toggle-switch + label {
            cursor: pointer;
            text-indent: -9999px;
            width: 50px;
            height: 28px;
            background: #ccc;
            display: block;
            border-radius: 100px;
            position: relative;
        }
        .toggle-switch + label:after {
            content: '';
            position: absolute;
            top: 3px;
            left: 3px;
            width: 22px;
            height: 22px;
            background: #fff;
            border-radius: 90px;
            transition: 0.3s;
        }
        .toggle-switch:checked + label {
            background: #27ae60;
        }
        .toggle-switch:checked + label:after {
            left: calc(100% - 3px);
            transform: translateX(-100%);
        }
        .toggle-switch + label:active:after {
            width: 30px;
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
                    <li class="menu-item"><a href="table_management.php"><i class="material-icons">table_chart</i> Calendar Management</a></li>
                    <li class="menu-item"><a href="reports.php"><i class="material-icons">analytics</i>Reservation Reports</a></li>
                    <li class="menu-item"><a href="deletion_history.php"><i class="material-icons">history</i> Archive</a></li>
                </ul>
            </nav>
        </aside>

        <div class="admin-content-area">
            <header class="main-header">
                <div class="header-content">
                    <h1 class="header-page-title">Customer Database</h1>
                    
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
                    <input type="text" id="userSearch" class="search-input" placeholder="Search customers...">
                    <button id="addNewUserBtn" class="btn btn-primary" style="background-color: #28a745;">Add New Customer</button>
                </div>

                <section class="all-reservations-section">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>USER ID</th>
                                    <th>USERNAME</th>
                                    <th>EMAIL</th>
                                    <th>DATE JOINED</th>
                                    <th>STATUS</th>
                                    <th>ROLE</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="usersTableBody">
                                <?php if (empty($users)): ?>
                                    <tr><td colspan="7" style="text-align: center;">No customers found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr data-user-id="<?= $user['user_id']; ?>"
                                            data-username="<?= htmlspecialchars($user['username'], ENT_QUOTES); ?>"
                                            data-email="<?= htmlspecialchars($user['email'], ENT_QUOTES); ?>"
                                            data-permissions='<?= htmlspecialchars($user['permissions'] ?? '[]', ENT_QUOTES); ?>'>
                                            
                                            <td><?= sprintf('%04d', $user['user_id']); ?></td>
                                            <td><?= htmlspecialchars($user['username']); ?></td>
                                            <td><?= htmlspecialchars($user['email']); ?></td>
                                            <td><?= htmlspecialchars(date('Y-m-d', strtotime($user['created_at']))); ?></td>
                                            <td>
                                                <?php if ($user['is_verified']): ?>
                                                    <span class="status-badge confirmed">Verified</span>
                                                <?php else: ?>
                                                    <span class="status-badge pending">Not Verified</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="role-cell"><?= htmlspecialchars(ucfirst($user['role'])); ?></td>
                                            
                                            <td class="actions">
                                                <?php if (!$user['is_verified']): ?>
                                                    <button class="btn btn-small verify-btn" style="background-color: #17a2b8;">Verify</button>
                                                <?php endif; ?>
                                                <button class="btn btn-small view-edit-btn">Edit</button>
                                                <button class="btn btn-small delete-btn">Delete</button>
                                                
                                                <?php if ($user['role'] === 'manager'): ?>
                                                    <button class="btn btn-small edit-permissions-btn" style="background-color: #17a2b8;">Edit Perms</button>
                                                    <button class="btn btn-small demote-user-btn" style="background-color: #6c757d;">Demote</button>
                                                <?php else: ?>
                                                    <button class="btn btn-small promote-user-btn" style="background-color: #28a745;">Make Manager</button>
                                                <?php endif; ?>
                                            </td>

                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>

            <div id="userModal" class="modal">
                <div class="modal-content modal-content-small">
                    <div class="modal-header">
                        <h2 id="modalTitle">Add New Customer</h2>
                        <span class="close-button">&times;</span>
                    </div>
                    <div class="modal-body">
                        <form id="userForm"> 
                            <input type="hidden" id="userId" name="user_id">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" id="username" name="username" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" id="password" name="password">
                                <small id="passwordHelp">Leave blank to keep the current password.</small>
                            </div>
                            <div class="form-group">
                                <label for="retype_password">Retype Password</label>
                                <input type="password" id="retype_password" name="retype_password">
                            </div>
                        </form>
                    </div>
                    <div class="modal-actions">
                        <button type="submit" class="btn modal-save-btn" form="userForm">Save Changes</button>
                    </div>
                </div>
            </div>
            
            <div id="managerPermissionsModal" class="modal">
                <div class="modal-content" style="max-width: 600px;">
                    <div class="modal-header">
                        <h2 id="managerPermissionsTitle">Set Manager Permissions</h2>
                        <span class="close-button">&times;</span>
                    </div>
                    <div class="modal-body">
                        <form id="managerPermissionsForm">
                            <input type="hidden" id="managerUserId" name="user_id">
                            <input type="hidden" id="permissionAction" name="action" value="promote">
                            
                            <div class="permission-group">
                                <div class="permission-item">
                                    <div class="permission-icon"><i class="material-icons">event_note</i></div>
                                    <div class="permission-text">
                                        <label for="perm_manage_reservations">Manage Reservations</label>
                                        <p>Allow access to the Reservations page to view, confirm, decline, and cancel bookings.</p>
                                    </div>
                                    <div class="permission-toggle">
                                        <input type="checkbox" id="perm_manage_reservations" name="permissions[]" value="manage_reservations" class="toggle-switch">
                                        <label for="perm_manage_reservations"></label>
                                    </div>
                                </div>

                                <div class="permission-item">
                                    <div class="permission-icon"><i class="material-icons">file_upload</i></div>
                                    <div class="permission-text">
                                        <label for="perm_manage_uploads">Manage Uploads</label>
                                        <p>Allow access to the Upload Management page to add/delete hero slides, events, gallery, etc.</p>
                                    </div>
                                    <div class="permission-toggle">
                                        <input type="checkbox" id="perm_manage_uploads" name="permissions[]" value="manage_uploads" class="toggle-switch">
                                        <label for="perm_manage_uploads"></label>
                                    </div>
                                </div>

                                <div class="permission-item">
                                    <div class="permission-icon"><i class="material-icons">notifications</i></div>
                                    <div class="permission-text">
                                        <label for="perm_access_notifications">Access Notification Control</label>
                                        <p>Allow access to the Notification Control page to view and reply to customer messages.</p>
                                    </div>
                                    <div class="permission-toggle">
                                        <input type="checkbox" id="perm_access_notifications" name="permissions[]" value="access_notifications" class="toggle-switch">
                                        <label for="perm_access_notifications"></label>
                                    </div>
                                </div>

                                <div class="permission-item">
                                    <div class="permission-icon"><i class="material-icons">table_chart</i></div>
                                    <div class="permission-text">
                                        <label for="perm_access_tables">Manage Calendar</label>
                                        <p>Allow access to the Calendar Management page to block and unblock dates.</p>
                                    </div>
                                    <div class="permission-toggle">
                                        <input type="checkbox" id="perm_access_tables" name="permissions[]" value="access_tables" class="toggle-switch">
                                        <label for="perm_access_tables"></label>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn" id="cancelPermissionsBtn" style="background-color: #6c757d; color: white;">Cancel</button>
                        <button type="submit" class="btn modal-save-btn" id="permissionSubmitBtn" form="managerPermissionsForm">Save & Promote</button>
                    </div>
                </div>
            </div>

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


    <script src="JS/customer_database.js"></script>
    <script>
    // NEW MODIFIED Notification script (with profile dropdown logic)
    document.addEventListener('DOMContentLoaded', () => {
        
        // --- MODIFICATION START: Added Notification Modal JS ---
        // This is needed for the handleDismiss function
        const notificationModal = document.getElementById('alertModal'); // Re-using alertModal as the notification modal
        const modalHeaderIcon = document.getElementById('modalHeaderIcon'); // Re-using elements
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
                    // --- THIS IS THE FIX ---
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

        fetchAdminNotifications();
        setInterval(fetchAdminNotifications, 30000);
    });
    </script>
</body>
</html>
