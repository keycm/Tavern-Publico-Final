<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    $response['message'] = 'You must be logged in to submit a rating.';
    echo json_encode($response);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $reservation_id = filter_input(INPUT_POST, 'reservation_id', FILTER_SANITIZE_NUMBER_INT);
    $rating = filter_input(INPUT_POST, 'rating', FILTER_SANITIZE_NUMBER_INT);
    $comment = htmlspecialchars(trim($_POST['comment'] ?? ''));

    if (empty($reservation_id) || empty($rating) || empty($comment)) {
        $response['message'] = 'Please provide a rating and a comment.';
        echo json_encode($response);
        exit;
    }

    $sql = "INSERT INTO testimonials (user_id, reservation_id, rating, comment) VALUES (?, ?, ?, ?)";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "iiis", $user_id, $reservation_id, $rating, $comment);
        if (mysqli_stmt_execute($stmt)) {
            $response['success'] = true;
            $response['message'] = 'Thank you for your feedback!';
        } else {
            $response['message'] = 'You have already submitted a rating for this reservation.';
        }
        mysqli_stmt_close($stmt);
    } else {
        $response['message'] = 'Database error.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

mysqli_close($link);
echo json_encode($response);
?>