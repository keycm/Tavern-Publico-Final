<?php
// Get the current page filename to set the 'active' class
$currentPage = basename($_SERVER['SCRIPT_NAME']);
// Get permissions from the session, default to an empty array if not set
$permissions = $_SESSION['permissions'] ?? [];
?>
<aside class="admin-sidebar">
    <div class="sidebar-header">
        <img src="Tavern.png" alt="Home Icon" class="home-icon">
    </div>
    <nav>
        <ul class="sidebar-menu">
            <li class="menu-item <?php echo ($currentPage == 'manager.php') ? 'active' : ''; ?>"><a href="manager.php"><i class="material-icons">dashboard</i> Dashboard</a></li>
            
            <?php // Only show Reservation link if manager has permission ?>
            <?php if (in_array('manage_reservations', $permissions)): ?>
                <li class="menu-item <?php echo ($currentPage == 'reservation.php') ? 'active' : ''; ?>"><a href="reservation.php"><i class="material-icons">event_note</i> Reservation</a></li>
            <?php endif; ?>

            <?php // Only show Calendar Management link if manager has permission ?>
            <?php if (in_array('access_tables', $permissions)): ?>
                <li class="menu-item <?php echo ($currentPage == 'table_management.php') ? 'active' : ''; ?>"><a href="table_management.php"><i class="material-icons">table_chart</i>Calendar Management</a></li>
            <?php endif; ?>

            <?php // Only show Notification Control link if manager has permission ?>
            <?php if (in_array('access_notifications', $permissions)): ?>
                <li class="menu-item <?php echo ($currentPage == 'notification_control.php') ? 'active' : ''; ?>"><a href="notification_control.php"><i class="material-icons">notifications</i> Notification Control</a></li>
            <?php endif; ?>

            <li class="menu-item"><a href="logout.php"><i class="material-icons">logout</i> Log out</a></li>
        </ul>
    </nav>
</aside>