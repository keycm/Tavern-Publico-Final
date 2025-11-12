<?php
// Start output buffering to prevent "headers already sent" errors.
ob_start();

session_start(); // Start the session at the very beginning
require_once 'db_connect.php'; // Use the $link variable from this file

// Admin check
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !$_SESSION['is_admin']) {
    header('Location: login.php'); // Redirect to login page if not logged in or not admin
    exit;
}

// Helper function for logging and soft-deleting
function log_and_soft_delete($link, $item_id, $item_type) {
    $table_info = [
        'hero_slide' => ['table' => 'hero_slides', 'pk' => 'id'],
        'event' => ['table' => 'events', 'pk' => 'id'],
        'gallery_image' => ['table' => 'gallery', 'pk' => 'id'],
        'menu_item' => ['table' => 'menu', 'pk' => 'id'],
        'team_member' => ['table' => 'team', 'pk' => 'id'],
        'coupon' => ['table' => 'coupons', 'pk' => 'id'], // Coupon map
    ];

    if (!isset($table_info[$item_type])) return false;

    $table = $table_info[$item_type]['table'];
    $pk = $table_info[$item_type]['pk'];

    $sql_select = "SELECT * FROM {$table} WHERE {$pk} = ?";
    $stmt_select = mysqli_prepare($link, $sql_select);
    mysqli_stmt_bind_param($stmt_select, "i", $item_id);
    mysqli_stmt_execute($stmt_select);
    $result = mysqli_stmt_get_result($stmt_select);
    $item_data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt_select);

    if ($item_data) {
        $item_data_json = json_encode($item_data);
        
        mysqli_begin_transaction($link);
        try {
            $sql_log = "INSERT INTO deletion_history (item_type, item_id, item_data, purge_date) VALUES (?, ?, ?, DATE_ADD(CURDATE(), INTERVAL 30 DAY))";
            $stmt_log = mysqli_prepare($link, $sql_log);
            mysqli_stmt_bind_param($stmt_log, "sis", $item_type, $item_id, $item_data_json);
            mysqli_stmt_execute($stmt_log);
            mysqli_stmt_close($stmt_log);

            if ($item_type === 'coupon') {
                // Soft delete for coupons is setting is_active to 0
                $sql_soft_delete = "UPDATE {$table} SET is_active = 0 WHERE {$pk} = ?";
            } else {
                 $sql_soft_delete = "UPDATE {$table} SET deleted_at = NOW() WHERE {$pk} = ?";
            }

            $stmt_soft_delete = mysqli_prepare($link, $sql_soft_delete);
            mysqli_stmt_bind_param($stmt_soft_delete, "i", $item_id);
            mysqli_stmt_execute($stmt_soft_delete);
            mysqli_stmt_close($stmt_soft_delete);
            
            mysqli_commit($link);
            return true;
        } catch (Exception $e) {
            mysqli_rollback($link);
            error_log("Soft delete failed: " . $e->getMessage());
            return false;
        }
    }
    return false;
}

function sanitize($link, $data) {
    return mysqli_real_escape_string($link, strip_tags($data));
}

// --- Coupon Handling ---
if (isset($_POST['add_coupon'])) {
    $code = sanitize($link, $_POST['coupon_code']);
    $type = sanitize($link, $_POST['coupon_type']);
    $value = filter_var($_POST['coupon_value'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $usage_limit = filter_var($_POST['usage_limit'], FILTER_SANITIZE_NUMBER_INT);
    $expiry_date = !empty($_POST['expiry_date']) ? sanitize($link, $_POST['expiry_date']) : NULL;

    $sql = "INSERT INTO coupons (code, type, value, expiry_date, usage_limit, is_active) VALUES (?, ?, ?, ?, ?, 1)";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "ssdsi", $code, $type, $value, $expiry_date, $usage_limit);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['message'] = "New coupon added successfully.";
    } else {
        if (mysqli_errno($link) == 1062) {
             $_SESSION['message'] = "Error: A coupon with this code already exists.";
        } else {
             $_SESSION['message'] = "Error adding coupon.";
        }
    }
    mysqli_stmt_close($stmt);
    header('Location: manage_coupons.php');
    exit;
}

if (isset($_POST['delete_coupon'])) {
    $id = (int)$_POST['coupon_id'];
    if (log_and_soft_delete($link, $id, 'coupon')) { 
        $_SESSION['message'] = "Coupon moved to deletion history."; 
    } else { 
        $_SESSION['message'] = "Error deleting coupon."; 
    }
    header('Location: manage_coupons.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tavern Publico - Manage Coupons</title>
    <link rel="stylesheet" href="CSS/admin.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        /* Copied styles from update.php */
        .content-card { background-color: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05); margin-bottom: 20px; }
        .content-card h2, .content-card h3, .content-card h4 { color: #2c3e50; margin-bottom: 15px; padding-bottom: 5px; border-bottom: 1px solid #eee; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #555; }
        .form-group input[type="text"], .form-group input[type="date"], .form-group input[type="file"], .form-group textarea, .form-group select, .form-group input[type="number"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; transition: border-color 0.3s ease; }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { border-color: #3498db; outline: none; }
        .data-item { background-color: #f9f9f9; border: 1px solid #eee; padding: 15px; border-radius: 6px; display: flex; flex-direction: column; gap: 10px; position: relative; transition: box-shadow 0.3s ease; }
        .data-item:hover { box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08); }
        .data-item h4 { margin: 0; color: #3498db; }
        .data-item p { margin: 0; color: #666; font-size: 0.9em; }
        button, .btn { display: inline-block; padding: 10px 20px; font-size: 16px; font-weight: 600; border: none; border-radius: 4px; cursor: pointer; transition: background-color 0.3s ease, transform 0.2s ease; }
        button[type="submit"], .btn-primary { background-color: #27ae60; color: #fff; }
        button[type="submit"]:hover, .btn-primary:hover { background-color: #2ecc71; transform: translateY(-2px); }
        .delete-btn { background-color: #e74c3c; color: #fff; font-size: 0.9em; padding: 8px 15px; }
        .delete-btn:hover { background-color: #c0392b; transform: translateY(-2px); }
        .image-grid-admin { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
        .message-box { position: fixed; top: 20px; left: 50%; transform: translateX(-50%); padding: 15px 30px; background-color: #4CAF50; color: white; border-radius: 8px; z-index: 1000; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2); font-weight: bold; display: none; opacity: 0; transition: opacity 0.5s ease-in-out; }
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0, 0, 0, 0.6); justify-content: center; align-items: center; }
        .modal-content { background-color: #fff; padding: 30px; border-radius: 8px; width: 90%; max-width: 500px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2); position: relative; text-align: center; }
        .modal-content h2 { margin-top: 0; font-size: 1.5em; color: #333; }
        .modal-content p { margin-bottom: 25px; color: #555; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
    </style>
</head>
<body>

    <div class="page-wrapper">
        <aside class="admin-sidebar">
             <div class="sidebar-header"><img src="Tavern.png" alt="Home Icon" class="home-icon"></div>
            <nav>
                <ul class="sidebar-menu">
                    <li class="menu-item"><a href="admin.php"><i class="material-icons">dashboard</i> Dashboard</a></li>
                    <li class="menu-item"><a href="reservation.php"><i class="material-icons">event_note</i> Reservation</a></li>
                    <li class="menu-item"><a href="update.php"><i class="material-icons">file_upload</i> Upload Management</a></li>
                    <li class="menu-item active"><a href="manage_coupons.php"><i class="material-icons">sell</i> Manage Coupons</a></li>
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
                    <h1 class="header-page-title">Coupon Management</h1>
                    
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
                <div id="message-box" class="message-box"></div>

                <section class="content-card">
                    <h2>Manage Discount Coupons</h2>
                    <h3>Add New Coupon</h3>
                    <form action="" method="post" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group" style="grid-column: 1 / -1;"><label for="coupon_code">Coupon Code:</label><input type="text" id="coupon_code" name="coupon_code" placeholder="e.g., TAVERN10" required></div>
                        <div class="form-group">
                            <label for="coupon_type">Discount Type:</label>
                            <select id="coupon_type" name="coupon_type" required>
                                <option value="percent">Percentage (%)</option>
                                <option value="fixed">Fixed Amount (₱)</option>
                            </select>
                        </div>
                        <div class="form-group"><label for="coupon_value">Value:</label><input type="number" id="coupon_value" name="coupon_value" step="0.01" min="0" required></div>
                        <div class="form-group"><label for="usage_limit">Usage Limit:</label><input type="number" id="usage_limit" name="usage_limit" step="1" min="1" value="100" required></div>
                        <div class="form-group"><label for="expiry_date">Expiry Date (Optional):</label><input type="date" id="expiry_date" name="expiry_date" min="<?php echo date('Y-m-d'); ?>"></div>
                        <button type="submit" name="add_coupon" style="grid-column: 1 / -1; width: 100%;">Add Coupon</button>
                    </form>
                </section>
                
                <section class="content-card">
                    <h3>Existing Active Coupons</h3>
                    <div class="image-grid-admin">
                        <?php
                        // Get all active coupons
                        $sql = "SELECT * FROM coupons WHERE is_active = 1 ORDER BY id DESC";
                        $result = mysqli_query($link, $sql);
                        if (mysqli_num_rows($result) > 0) {
                            while($row = mysqli_fetch_assoc($result)) {
                                echo "<div class='data-item'>";
                                $value_display = ($row['type'] == 'percent') ? $row['value'] . '%' : '₱' . number_format($row['value'], 2);
                                $expiry_display = $row['expiry_date'] ? date('M j, Y', strtotime($row['expiry_date'])) : 'No Expiry';
                                
                                echo "<h4>" . htmlspecialchars($row['code']) . "</h4>";
                                echo "<p><strong>Type:</strong> " . ucfirst($row['type']) . "</p>";
                                echo "<p><strong>Value:</strong> " . $value_display . "</p>";
                                echo "<p><strong>Usage:</strong> " . $row['current_usage'] . " / " . $row['usage_limit'] . "</p>";
                                echo "<p><strong>Expires:</strong> " . $expiry_display . "</p>";
                                
                                echo "<form action='' method='post' class='delete-form' style='display:inline; margin-top: auto;'>
                                        <input type='hidden' name='coupon_id' value='" . $row['id'] . "'>
                                        <button type='button' class='delete-btn delete-trigger-btn'>Delete</button>
                                        <input type='hidden' name='delete_coupon' value='1'>
                                      </form>";
                                echo "</div>";
                            }
                        } else { echo "<p>No active coupons found.</p>"; }
                        ?>
                    </div>
                </section>

            </main>
        </div>
    </div>
    
    <div id="confirmDeleteModal" class="modal">
        <div class="modal-content">
            <h2>Confirm Deletion</h2>
            <p>Are you sure you want to move this item to the deletion history? It can be restored later.</p>
            <div class="modal-actions">
                <button type="button" class="btn" id="cancelDeleteBtn" style="background-color: #6c757d; color: white;">Cancel</button>
                <button type="button" class="btn delete-btn" id="confirmDeleteBtn">Yes, Delete</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // This script only needs the delete modal and session message logic

            const confirmDeleteModal = document.getElementById('confirmDeleteModal');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
            const deleteTriggerButtons = document.querySelectorAll('.delete-trigger-btn');
            let formToSubmit = null;

            deleteTriggerButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    formToSubmit = e.target.closest('form');
                    if (confirmDeleteModal) confirmDeleteModal.style.display = 'flex';
                });
            });

            if (confirmDeleteBtn) {
                confirmDeleteBtn.addEventListener('click', () => {
                    if (formToSubmit) formToSubmit.submit();
                });
            }

            if (cancelDeleteBtn) {
                cancelDeleteBtn.addEventListener('click', () => {
                    confirmDeleteModal.style.display = 'none';
                    formToSubmit = null;
                });
            }

            window.addEventListener('click', (event) => {
                if (event.target == confirmDeleteModal) {
                    confirmDeleteModal.style.display = 'none';
                    formToSubmit = null;
                }
            });

            const messageBox = document.getElementById('message-box');
            <?php
            if (isset($_SESSION['message'])) {
                echo "if(messageBox) { messageBox.textContent = '{$_SESSION['message']}'; messageBox.style.display = 'block'; setTimeout(() => { messageBox.style.opacity = '1'; }, 10); setTimeout(() => { messageBox.style.opacity = '0'; }, 3000); setTimeout(() => { messageBox.style.display = 'none'; }, 3500); }";
                unset($_SESSION['message']);
            }
            ?>
        });
    </script>
    <script>
    // NEW MODIFIED Notification script (with profile dropdown logic)
    document.addEventListener('DOMContentLoaded', () => {
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
                    alert(result.message); // Show alert for actions that can't be dismissed
                }
            } catch (error) {
                console.error('Error dismissing notification:', error);
                alert('An error occurred. Please try again.');
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
<?php
mysqli_close($link);
ob_end_flush();
?>