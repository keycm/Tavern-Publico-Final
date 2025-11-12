<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Invalid request.'];

if (!isset($_SESSION['loggedin']) || !$_SESSION['is_admin']) {
    $response['message'] = 'Unauthorized.';
    echo json_encode($response);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
    $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);

    if (!$id || !$type) {
        $response['message'] = 'Missing parameters.';
        echo json_encode($response);
        exit;
    }

    if ($type === 'message') {
        // Mark the message as read (or replied, which serves as a 'dismissed' status)
        $sql = "UPDATE contact_messages SET is_read = 1, replied_at = NOW() WHERE id = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $id);
            if (mysqli_stmt_execute($stmt)) {
                $response['success'] = true;
                $response['message'] = 'Message dismissed.';
            } else {
                $response['message'] = 'Failed to update message status.';
            }
            mysqli_stmt_close($stmt);
        }
    } elseif ($type === 'reservation') {
        // Do not allow dismissing reservations, as they are tasks that need action.
        $response['success'] = false;
        $response['message'] = 'Pending reservations cannot be dismissed. Please Confirm or Decline them on the Reservations page.';
    } else {
        $response['message'] = 'Invalid notification type.';
    }

    mysqli_close($link);
}

echo json_encode($response);
?>