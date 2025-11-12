<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit;
}

// --- Date Filtering Logic ---
$currentYear = date('Y');
$startDate = $_GET['startDate'] ?? "$currentYear-01-01";
$endDate = $_GET['endDate'] ?? "$currentYear-12-31";

// --- Data Fetching for Reports with Date Filtering ---

// 1. Pacing Report Data (This Year vs Last Year)
$pacing_labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$pacing_this_year_data = array_fill_keys($pacing_labels, 0);
$pacing_last_year_data = array_fill_keys($pacing_labels, 0);

$sql_ty = "SELECT MONTHNAME(res_date) as month, COUNT(reservation_id) as count FROM reservations WHERE res_date BETWEEN ? AND ? GROUP BY MONTH(res_date), MONTHNAME(res_date) ORDER BY MONTH(res_date)";
if ($stmt_ty = mysqli_prepare($link, $sql_ty)) {
    mysqli_stmt_bind_param($stmt_ty, "ss", $startDate, $endDate);
    mysqli_stmt_execute($stmt_ty);
    $result_ty = mysqli_stmt_get_result($stmt_ty);
    while ($row = mysqli_fetch_assoc($result_ty)) {
        $month_short = substr($row['month'], 0, 3);
        if (isset($pacing_this_year_data[$month_short])) {
            $pacing_this_year_data[$month_short] = $row['count'];
        }
    }
    mysqli_stmt_close($stmt_ty);
}

$startDateLY = date('Y-m-d', strtotime('-1 year', strtotime($startDate)));
$endDateLY = date('Y-m-d', strtotime('-1 year', strtotime($endDate)));
$sql_ly = "SELECT MONTHNAME(res_date) as month, COUNT(reservation_id) as count FROM reservations WHERE res_date BETWEEN ? AND ? GROUP BY MONTH(res_date), MONTHNAME(res_date) ORDER BY MONTH(res_date)";
if ($stmt_ly = mysqli_prepare($link, $sql_ly)) {
    mysqli_stmt_bind_param($stmt_ly, "ss", $startDateLY, $endDateLY);
    mysqli_stmt_execute($stmt_ly);
    $result_ly = mysqli_stmt_get_result($stmt_ly);
    while ($row = mysqli_fetch_assoc($result_ly)) {
        $month_short = substr($row['month'], 0, 3);
        if (isset($pacing_last_year_data[$month_short])) {
            $pacing_last_year_data[$month_short] = $row['count'];
        }
    }
    mysqli_stmt_close($stmt_ly);
}


// 2. Source of Business Data
$sql_source = "SELECT 
                CASE 
                    WHEN source IS NOT NULL AND source != '' THEN source
                    ELSE 'Unknown'
                END AS business_source, 
                COUNT(reservation_id) as count
               FROM reservations
               WHERE res_date BETWEEN ? AND ? AND deleted_at IS NULL
               GROUP BY business_source";
$source_counts = [];
if ($stmt_source = mysqli_prepare($link, $sql_source)) {
    mysqli_stmt_bind_param($stmt_source, "ss", $startDate, $endDate);
    mysqli_stmt_execute($stmt_source);
    $result_source = mysqli_stmt_get_result($stmt_source);
    while ($row = mysqli_fetch_assoc($result_source)) {
        $source_counts[$row['business_source']] = $row['count'];
    }
    mysqli_stmt_close($stmt_source);
}


// 3. Guest Demographics (New vs. Returning)
$new_guests = 0;
$returning_guests = 0;
$sql_guests_in_range = "SELECT DISTINCT res_email FROM reservations WHERE res_date BETWEEN ? AND ? AND deleted_at IS NULL";
if ($stmt_guests = mysqli_prepare($link, $sql_guests_in_range)) {
    mysqli_stmt_bind_param($stmt_guests, "ss", $startDate, $endDate);
    mysqli_stmt_execute($stmt_guests);
    $guests_in_range = [];
    $result_guests = mysqli_stmt_get_result($stmt_guests);
    while ($row = mysqli_fetch_assoc($result_guests)) {
        $guests_in_range[] = $row['res_email'];
    }
    mysqli_stmt_close($stmt_guests);

    if (!empty($guests_in_range)) {
        $sql_check_prior = "SELECT 1 FROM reservations WHERE res_email = ? AND res_date < ? AND deleted_at IS NULL LIMIT 1";
        if ($stmt_check = mysqli_prepare($link, $sql_check_prior)) {
            foreach ($guests_in_range as $guest_email) {
                mysqli_stmt_bind_param($stmt_check, "ss", $guest_email, $startDate);
                mysqli_stmt_execute($stmt_check);
                mysqli_stmt_store_result($stmt_check);
                if (mysqli_stmt_num_rows($stmt_check) > 0) {
                    $returning_guests++;
                } else {
                    $new_guests++;
                }
            }
            mysqli_stmt_close($stmt_check);
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
    <title>Tavern Publico - Reservation Reports</title>
    <link rel="stylesheet" href="CSS/admin.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .report-filters { background-color: #fff; padding: 15px 20px; border-radius: 12px; box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1); margin-bottom: 30px; display: flex; gap: 20px; align-items: center; flex-wrap: wrap; }
        .filter-group { display: flex; flex-direction: column; }
        .filter-group label { font-size: 14px; color: #555; margin-bottom: 5px; }
        .filter-group input { padding: 8px 12px; border-radius: 5px; border: 1px solid #ccc; }
        .report-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .report-section { background-color: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1); }
        .report-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px;}
        .report-header h3 { margin: 0; font-size: 18px; }
        .export-options button { margin-left: 10px; }
        .chart-container { padding-top: 10px; }
        @media (max-width: 992px) { .report-grid { grid-template-columns: 1fr; } }
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
                    <li class="menu-item"><a href="manage_coupons.php"><i class="material-icons">sell</i> Manage Coupons</a></li>
                </ul>
                <div class="user-management-title">User Management</div>
                <ul class="sidebar-menu user-management-menu">
                    <li class="menu-item"><a href="customer_database.php"><i class="material-icons">people</i> Customer Database</a></li>
                    <li class="menu-item"><a href="notification_control.php"><i class="material-icons">notifications</i> Notification Control</a></li>
                    <li class="menu-item"><a href="table_management.php"><i class="material-icons">table_chart</i> Calendar Management</a></li>
                    <li class="menu-item active"><a href="reports.php"><i class="material-icons">analytics</i>Reservation Reports</a></li>
                    <li class="menu-item"><a href="deletion_history.php"><i class="material-icons">history</i> Archive</a></li>
                </ul>
        </nav>
    </aside>

    <div class="admin-content-area">
            <header class="main-header">
                <div class="header-content">
                    <h1 class="header-page-title">Reservation Reports</h1>
                    
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

            <div class="report-filters">
                <form method="GET" action="reports.php" style="display: flex; gap: 20px; align-items: center; width: 100%; flex-wrap: wrap;">
                    <h4>Filter Reports by Date</h4>
                    <div class="filter-group">
                        <label for="startDate">Start Date</label>
                        <input type="date" id="startDate" name="startDate" value="<?= htmlspecialchars($startDate) ?>">
                    </div>
                    <div class="filter-group">
                        <label for="endDate">End Date</label>
                        <input type="date" id="endDate" name="endDate" value="<?= htmlspecialchars($endDate) ?>">
                    </div>
                    <button type="submit" class="btn view-btn" style="align-self: flex-end;">Apply Filter</button>
                </form>
            </div>

            <section class="report-section">
                <div class="report-header">
                    <h3>Pacing Report (This Year vs. Last Year)</h3>
                    <div class="export-options">
                        <button class="btn btn-small export-csv" data-target="pacingChart">Export CSV</button>
                        <button class="btn btn-small print-chart" data-target="pacingChart">Print</button>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="pacingChart"></canvas>
                </div>
            </section>

            <div class="report-grid" style="margin-top: 20px;">
                <section class="report-section">
                    <div class="report-header">
                        <h3>Source of Business</h3>
                        <div class="export-options">
                            <button class="btn btn-small export-csv" data-target="sourceChart">Export CSV</button>
                            <button class="btn btn-small print-chart" data-target="sourceChart">Print</button>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="sourceChart"></canvas>
                    </div>
                </section>
                
                <section class="report-section">
                    <div class="report-header">
                        <h3>Guest Demographics (New vs. Returning)</h3>
                        <div class="export-options">
                            <button class="btn btn-small export-csv" data-target="demographicsChart">Export CSV</button>
                            <button class="btn btn-small print-chart" data-target="demographicsChart">Print</button>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="demographicsChart"></canvas>
                    </div>
                </section>
            </div>

        </main>
    </div>
</div>

<script>
    const reportData = {
        pacing: {
            labels: <?= json_encode(array_values($pacing_labels)); ?>,
            thisYear: <?= json_encode(array_values($pacing_this_year_data)); ?>,
            lastYear: <?= json_encode(array_values($pacing_last_year_data)); ?>
        },
        source: {
            labels: <?= json_encode(array_keys($source_counts)); ?>,
            counts: <?= json_encode(array_values($source_counts)); ?>
        },
        demographics: {
            newGuests: <?= $new_guests; ?>,
            returningGuests: <?= $returning_guests; ?>
        }
    };
</script>
<script src="JS/reports.js"></script>
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