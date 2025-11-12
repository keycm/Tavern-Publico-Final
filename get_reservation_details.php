<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');
$response = ['success' => false, 'reservations' => [], 'message' => 'An error occurred.'];

// MODIFIED: Updated authorization to allow managers with 'access_tables' permission
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

if (!$is_authorized) {
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit;
}


if (isset($_GET['date'])) {
    $date = $_GET['date'];

    // Prepare and execute the query to get reservations for the selected date
    $sql = "SELECT res_name, res_phone, res_time, num_guests, status 
            FROM reservations 
            WHERE res_date = ? AND deleted_at IS NULL 
            ORDER BY res_time ASC";
            
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $date);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $reservations = [];
        while ($row = mysqli_fetch_assoc($result)) {
            // Format time for better readability
            $row['res_time_formatted'] = date('g:i A', strtotime($row['res_time']));
            $reservations[] = $row;
        }
        
        $response['success'] = true;
        $response['reservations'] = $reservations;
        mysqli_stmt_close($stmt);
    } else {
        $response['message'] = 'Database query failed.';
    }
} else {
    $response['message'] = 'No date provided.';
}

mysqli_close($link);
echo json_encode($response);
?>