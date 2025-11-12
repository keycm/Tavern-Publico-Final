<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'An unknown error occurred.'];

// MODIFIED: Allow both 'owner' and 'manager' roles to access this script
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !in_array($_SESSION['role'], ['owner', 'manager'])) {
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = filter_var($_POST['user_id'] ?? 0, FILTER_SANITIZE_NUMBER_INT);

    if ($userId > 0) {
        $sql = "UPDATE users SET is_verified = 1 WHERE user_id = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $userId);
            if (mysqli_stmt_execute($stmt)) {
                $response['success'] = true;
                $response['message'] = 'User verified successfully.';
            } else {
                $response['message'] = 'Error verifying user.';
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $response['message'] = 'Invalid user ID.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

mysqli_close($link);
echo json_encode($response);
?>