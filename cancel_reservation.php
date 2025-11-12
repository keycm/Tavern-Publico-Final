<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'An unknown error occurred.'];

// 1. Check if user is logged in
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['user_id'])) {
    $response['message'] = 'You must be logged in to cancel a reservation.';
    echo json_encode($response);
    exit;
}

// 2. Check if it's a POST request with the required ID
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reservation_id'])) {
    $reservation_id = (int)$_POST['reservation_id'];
    $user_id = (int)$_SESSION['user_id'];

    // 3. Fetch the reservation to verify ownership and time
    $sql = "SELECT user_id, status, created_at FROM reservations WHERE reservation_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $reservation_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $reservation = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($reservation) {
            // 4. Security Checks
            // Check if the reservation belongs to the logged-in user
            if ($reservation['user_id'] !== $user_id) {
                $response['message'] = 'You do not have permission to cancel this reservation.';
                echo json_encode($response);
                exit;
            }

            // Check if the reservation is already cancelled or declined
            if (!in_array($reservation['status'], ['Pending', 'Confirmed'])) {
                 $response['message'] = 'This reservation cannot be cancelled.';
                 echo json_encode($response);
                 exit;
            }

            // Check if the reservation was made within the last 30 minutes (1800 seconds)
            $created_timestamp = strtotime($reservation['created_at']);
            $current_timestamp = time();
            if (($current_timestamp - $created_timestamp) > 1800) {
                $response['message'] = 'The 30-minute cancellation window has passed.';
                echo json_encode($response);
                exit;
            }
            
            // 5. All checks passed, update the status to "Cancelled"
            $update_sql = "UPDATE reservations SET status = 'Cancelled' WHERE reservation_id = ?";
            if ($update_stmt = mysqli_prepare($link, $update_sql)) {
                mysqli_stmt_bind_param($update_stmt, "i", $reservation_id);
                if (mysqli_stmt_execute($update_stmt)) {
                    $response['success'] = true;
                    $response['message'] = 'Reservation cancelled successfully.';
                } else {
                    $response['message'] = 'Failed to update reservation status.';
                }
                mysqli_stmt_close($update_stmt);
            }
        } else {
            $response['message'] = 'Reservation not found.';
        }
    }
} else {
    $response['message'] = 'Invalid request.';
}

mysqli_close($link);
echo json_encode($response);
?>