<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

$events = [];

// MODIFIED: Updated authorization to allow managers with the correct permission
$is_authorized = false;
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    // Admins are always authorized
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
        $is_authorized = true;
    }
    // Managers are authorized only if they have the specific permission for this page
    elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'manager') {
        if (isset($_SESSION['permissions']) && is_array($_SESSION['permissions']) && in_array('access_tables', $_SESSION['permissions'])) {
            $is_authorized = true;
        }
    }
}


if ($is_authorized) {
    // Fetch individual reservations with specific times
    $sql_reservations = "SELECT res_name, res_date, res_time FROM reservations WHERE deleted_at IS NULL";
    if ($result = mysqli_query($link, $sql_reservations)) {
        while ($row = mysqli_fetch_assoc($result)) {
            $events[] = [
                'title' => $row['res_name'], // The event title will now be the customer's name
                'start' => $row['res_date'] . 'T' . $row['res_time'], // Combines date and time
                'backgroundColor' => '#28a745', // Green for reservations
                'borderColor' => '#28a745'
            ];
        }
    }

    // Fetch all-day blocked dates
    $sql_blocked = "SELECT block_date FROM blocked_dates";
    if ($result = mysqli_query($link, $sql_blocked)) {
        while ($row = mysqli_fetch_assoc($result)) {
            $events[] = [
                'title' => 'Blocked',
                'start' => $row['block_date'],
                'backgroundColor' => '#dc3545', // Red for blocked dates
                'borderColor' => '#dc3545'
            ];
        }
    }
}

echo json_encode($events);
?>