<?php
session_start();
require_once 'db_connect.php'; // Include your database connection

header('Content-Type: application/json'); // Set header to return JSON response

$response = ['success' => false, 'message' => 'Invalid request.'];

// Check if the user is logged in AND is an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['is_admin'] !== true) {
    $response['message'] = 'Unauthorized access. Please log in as an administrator.';
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $check_date = filter_var(trim($_POST['check_date'] ?? ''), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $check_time = filter_var(trim($_POST['check_time'] ?? ''), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $check_num_guests = filter_var($_POST['check_num_guests'] ?? 0, FILTER_SANITIZE_NUMBER_INT);

    // Validate inputs
    if (empty($check_date) || empty($check_time) || !is_numeric($check_num_guests) || $check_num_guests <= 0) {
        $response['message'] = 'Please provide a valid date, time, and number of guests.';
        echo json_encode($response);
        exit;
    }

    $TOTAL_RESTAURANT_CAPACITY = 50; 

    $booked_guests_for_slot = 0;

    // FIXED: Added "AND deleted_at IS NULL" to ensure deleted reservations don't block capacity
    $sql = "SELECT SUM(num_guests) AS total_booked_guests FROM reservations WHERE res_date = ? AND res_time = ? AND (status = 'Confirmed' OR status = 'Pending') AND deleted_at IS NULL";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $check_date, $check_time);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_bind_result($stmt, $total_booked_guests);
            if (mysqli_stmt_fetch($stmt)) {
                $booked_guests_for_slot = $total_booked_guests ?? 0;
            }
            mysqli_stmt_close($stmt);
        } else {
            $response['message'] = 'Database error fetching booked guests: ' . mysqli_error($link);
            error_log("Check availability DB error: " . mysqli_error($link));
            echo json_encode($response);
            exit;
        }
    } else {
        $response['message'] = 'Database error preparing statement: ' . mysqli_error($link);
        error_log("Check availability prepare error: " . mysqli_error($link));
        echo json_encode($response);
        exit;
    }

    $available_capacity = $TOTAL_RESTAURANT_CAPACITY - $booked_guests_for_slot;

    if ($available_capacity >= $check_num_guests) {
        $response['success'] = true;
        $response['available'] = true;
        $response['message'] = 'Tables are available for ' . $check_num_guests . ' guests.';
        $response['details'] = [
            'total_capacity' => $TOTAL_RESTAURANT_CAPACITY,
            'booked_guests' => $booked_guests_for_slot,
            'remaining_capacity' => $available_capacity,
            'requested_guests' => $check_num_guests
        ];
    } else {
        $response['success'] = true; // Still a successful check, just no availability
        $response['available'] = false;
        $response['message'] = 'Not enough capacity for ' . $check_num_guests . ' guests. Only ' . $available_capacity . ' spots remain.';
        $response['details'] = [
            'total_capacity' => $TOTAL_RESTAURANT_CAPACITY,
            'booked_guests' => $booked_guests_for_slot,
            'remaining_capacity' => $available_capacity,
            'requested_guests' => $check_num_guests
        ];
    }
    mysqli_close($link);
}

echo json_encode($response);
?>