<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id']) && isset($_POST['type'])) {
    $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
    $type = $_POST['type'];
    $user_id = $_SESSION['user_id'];

    if ($type === 'reservation') {
        $sql = "UPDATE reservations SET is_notified = 1 WHERE reservation_id = ? AND user_id = ?";
    } elseif ($type === 'custom') {
        $sql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
    } else {
        $response['message'] = 'Invalid notification type.';
        echo json_encode($response);
        exit;
    }

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $response['success'] = true;
            $response['message'] = 'Notification cleared.';
        } else {
            $response['message'] = 'Failed to clear notification.';
        }
        mysqli_stmt_close($stmt);
    } else {
        $response['message'] = 'Database error.';
    }

    mysqli_close($link);
} else {
    $response['message'] = 'Invalid request.';
}

echo json_encode($response);
?>