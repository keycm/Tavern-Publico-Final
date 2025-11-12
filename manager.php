<?php
session_start();
require_once 'db_connect.php';

// Check if the user is logged in AND is an admin/manager
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !in_array($_SESSION['role'], ['owner', 'manager'])) {
    header('Location: login.php');
    exit;
}

// --- Data Fetching (Same as admin.php for dashboard stats) ---
$reservations = [];
$sql_reservations = "
    SELECT r.reservation_id, r.user_id, r.res_date, r.res_time, r.num_guests,
        r.res_name, r.res_phone, r.res_email, r.status, r.created_at,
        r.reservation_type, r.valid_id_path, u.avatar
    FROM reservations r
    LEFT JOIN users u ON r.user_id = u.user_id
    WHERE r.deleted_at IS NULL ORDER BY r.created_at DESC";

if ($result = mysqli_query($link, $sql_reservations)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $reservations[] = $row;
    }
    mysqli_free_result($result);
}

$totalReservations = count($reservations);
$pendingReservations = count(array_filter($reservations, function($r) { return $r['status'] === 'Pending'; }));
$confirmedReservations = count(array_filter($reservations, function($r) { return $r['status'] === 'Confirmed'; }));
$cancelledReservations = count(array_filter($reservations, function($r) { return $r['status'] === 'Cancelled'; }));

mysqli_close($link);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tavern Publico - Manager Dashboard</title>
    <link rel="stylesheet" href="CSS/admin.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>

<div class="page-wrapper">
    
    <?php include 'partials/manager_sidebar.php'; ?>

    <div class="admin-content-area">
        <header class="main-header">
            <div class="header-content">
                <h1 class="header-page-title">Manager Dashboard</h1>
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

    <div class="admin-profile-area">
        <?php $admin_avatar_path = isset($_SESSION['avatar']) && file_exists($_SESSION['avatar']) ? htmlspecialchars($_SESSION['avatar']) : 'images/default_avatar.png'; ?>
        <img src="<?php echo $admin_avatar_path; ?>" alt="Admin Avatar" class="admin-avatar">
        <div class="admin-user-info">
            <span class="admin-username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <span class="admin-role"><?php echo ucfirst(htmlspecialchars($_SESSION['role'])); ?></span>
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
            
            <section class="recent-reservations-section">
                <h2>Recent Reservations <input type="text" id="reservationSearchTop" class="search-input-top" placeholder="Search"></h2>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>CUSTOMER</th>
                                <th>DATE</th>
                                <th>TIME</th>
                                <th>TYPE</th>
                                <th>ID?</th>
                                <th>STATUS</th>
                                <th>Info</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reservations)): ?>
                                <tr><td colspan="7">No reservations found.</td></tr>
                            <?php else: ?>
                                <?php foreach (array_slice($reservations, 0, 10) as $reservation): ?>
                                    <?php
                                        $statusClass = strtolower($reservation['status']);
                                        $displayData = [
                                            'Reservation ID' => $reservation['reservation_id'], 'User ID' => $reservation['user_id'] ?? 'N/A',
                                            'Date' => $reservation['res_date'], 'Time' => date("g:i A", strtotime($reservation['res_time'])),
                                            'Guests' => $reservation['num_guests'], 'Name' => $reservation['res_name'],
                                            'Phone' => $reservation['res_phone'], 'Email' => $reservation['res_email'],
                                            'Type' => $reservation['reservation_type'] ?? 'Dine-in', 'Status' => $reservation['status'],
                                            'Booked At' => $reservation['created_at'], 'Valid ID Path' => $reservation['valid_id_path']
                                        ];
                                        $fullReservationJson = htmlspecialchars(json_encode($displayData), ENT_QUOTES, 'UTF-8');
                                    ?>
                                    <tr data-reservation-id="<?php echo $reservation['reservation_id']; ?>" data-full-reservation='<?php echo $fullReservationJson; ?>'>
                                        <td>
                                            <?php
                                            $avatar_path = !empty($reservation['avatar']) && file_exists($reservation['avatar']) ? $reservation['avatar'] : 'images/default_avatar.png';
                                            $customer_info_html = '<div class="customer-info"><img src="' . htmlspecialchars($avatar_path) . '" alt="Customer Avatar" class="customer-avatar"><div><strong>' . htmlspecialchars($reservation['res_name']) . '</strong><br><small>' . htmlspecialchars($reservation['res_email']) . '</small></div></div>';
                                            
                                            if (!empty($reservation['user_id'])) {
                                                echo '<a href="view_customer.php?id=' . $reservation['user_id'] . '&return_to=manager" style="text-decoration: none; color: inherit;">' . $customer_info_html . '</a>';
                                            } else {
                                                echo $customer_info_html;
                                            }
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
        
    </div>
</div>

<div id="reservationModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h2 id="modal-title-h2">Reservation Details</h2><span class="close-button">&times;</span></div>
        <div class="modal-body">
             <div id="modalDetails"></div>
             <div class="form-group" style="margin-top: 15px;">
                <label style="font-weight: 600; color: #333;">Uploaded ID:</label>
                <div id="validIdDisplayModal"></div>
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


<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="JS/admin.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const messageBtn = document.getElementById('adminMessageBtn');
    const reservationBtn = document.getElementById('adminReservationBtn');
    const messageDropdown = document.getElementById('adminMessageDropdown');
    const reservationDropdown = document.getElementById('adminReservationDropdown');
    const messageCountBadge = document.getElementById('adminMessageCount');
    const reservationCountBadge = document.getElementById('adminReservationCount');

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

    messageBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        reservationDropdown.classList.remove('show');
        messageDropdown.classList.toggle('show');
    });

    reservationBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        messageDropdown.classList.remove('show');
        reservationDropdown.classList.toggle('show');
    });

    window.addEventListener('click', () => {
        messageDropdown.classList.remove('show');
        reservationDropdown.classList.remove('show');
    });

    [messageDropdown, reservationDropdown].forEach(dropdown => {
        dropdown.addEventListener('click', (e) => {
            if (!e.target.classList.contains('admin-notification-dismiss')) {
                e.stopPropagation();
            }
        });
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
                alert(result.message);
            }
        } catch (error) {
            console.error('Error dismissing notification:', error);
            alert('An error occurred. Please try again.');
        }
    }

    messageDropdown.addEventListener('click', handleDismiss);
    reservationDropdown.addEventListener('click', handleDismiss);

    fetchAdminNotifications();
    setInterval(fetchAdminNotifications, 30000);
});
</script>

</body>
</html>