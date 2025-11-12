<?php
require_once 'db_connect.php';
header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $otp = trim($_POST['otp'] ?? '');

    if (empty($email) || empty($otp)) {
        $response['message'] = 'Email and OTP are required.';
    } else {
        $sql = "SELECT user_id, reset_token, reset_token_expiry FROM users WHERE email = ? AND is_verified = 1";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);

            if ($user && $user['reset_token'] == $otp && strtotime($user['reset_token_expiry']) > time()) {
                $response['success'] = true;
                $response['message'] = 'Code verified. You can now set a new password.';
            } else {
                $response['message'] = 'Invalid or expired code. Please try again.';
            }
            mysqli_stmt_close($stmt);
        }
    }
    mysqli_close($link);
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>