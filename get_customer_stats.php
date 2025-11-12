<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

// Ensure only a logged-in admin can access this data
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !$_SESSION['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// FIXED: Added "AND deleted_at IS NULL" to all queries to exclude soft-deleted records from stats

// Calculate total guests for the current week (Monday to Sunday)
$sql_weekly = "SELECT SUM(num_guests) as weekly_total FROM reservations WHERE YEARWEEK(res_date, 1) = YEARWEEK(CURDATE(), 1) AND deleted_at IS NULL";
$weekly_result = mysqli_query($link, $sql_weekly);
$weekly_customers = 0;
if ($weekly_row = mysqli_fetch_assoc($weekly_result)) {
    $weekly_customers = (int)$weekly_row['weekly_total'];
}

// Calculate total guests for the current month
$sql_monthly = "SELECT SUM(num_guests) as monthly_total FROM reservations WHERE MONTH(res_date) = MONTH(CURDATE()) AND YEAR(res_date) = YEAR(CURDATE()) AND deleted_at IS NULL";
$monthly_result = mysqli_query($link, $sql_monthly);
$monthly_customers = 0;
if ($monthly_row = mysqli_fetch_assoc($monthly_result)) {
    $monthly_customers = (int)$monthly_row['monthly_total'];
}

// Calculate reservations for today
$sql_today = "SELECT COUNT(reservation_id) as reservations_today FROM reservations WHERE res_date = CURDATE() AND deleted_at IS NULL";
$today_result = mysqli_query($link, $sql_today);
$reservations_today = 0;
if ($today_row = mysqli_fetch_assoc($today_result)) {
    $reservations_today = (int)$today_row['reservations_today'];
}

// Calculate total registered users (non-admins)
$sql_users = "SELECT COUNT(user_id) as total_users FROM users WHERE is_admin = 0 AND deleted_at IS NULL";
$users_result = mysqli_query($link, $sql_users);
$total_users = 0;
if ($users_row = mysqli_fetch_assoc($users_result)) {
    $total_users = (int)$users_row['total_users'];
}


mysqli_close($link);

// Return the data as a JSON object
echo json_encode([
    'success' => true,
    'weekly_customers' => $weekly_customers,
    'monthly_customers' => $monthly_customers,
    'reservations_today' => $reservations_today,
    'total_users' => $total_users
]);
?>