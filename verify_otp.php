<?php
require_once 'db_connect.php';
session_start();

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $otp = trim($_POST['otp'] ?? '');

    if (empty($email) || empty($otp)) {
        $response['message'] = 'Email and OTP are required.';
    } else {
        $sql = "SELECT user_id, otp, otp_expiry FROM users WHERE email = ? AND is_verified = 0";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);

            if ($user && $user['otp'] == $otp && strtotime($user['otp_expiry']) > time()) {
                $sql_update = "UPDATE users SET is_verified = 1, otp = NULL, otp_expiry = NULL WHERE user_id = ?";
                if ($stmt_update = mysqli_prepare($link, $sql_update)) {
                    mysqli_stmt_bind_param($stmt_update, "i", $user['user_id']);
                    if (mysqli_stmt_execute($stmt_update)) {
                        $response['success'] = true;
                        $response['message'] = 'Account verified successfully! You can now log in.';
                    }
                }
            } else {
                $response['message'] = 'Invalid or expired OTP.';
            }
        }
    }
    mysqli_close($link);
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>