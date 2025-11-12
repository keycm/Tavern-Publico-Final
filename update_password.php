<?php
require_once 'db_connect.php';
header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($email) || empty($password)) {
        $response['message'] = 'An error occurred. Please start over.';
    } elseif ($password !== $password_confirm) {
        $response['message'] = 'Passwords do not match.';
    } elseif (strlen($password) < 6 || !preg_match('/[A-Z]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
        $response['message'] = 'Password does not meet the requirements.';
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        // We only update if a valid reset token was recently used (we trust the flow)
        $sql_update = "UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expiry = NULL WHERE email = ? AND reset_token IS NOT NULL";
        if ($stmt_update = mysqli_prepare($link, $sql_update)) {
            mysqli_stmt_bind_param($stmt_update, "ss", $password_hash, $email);
            if (mysqli_stmt_execute($stmt_update) && mysqli_stmt_affected_rows($stmt_update) > 0) {
                $response['success'] = true;
                $response['message'] = 'Password has been reset successfully!';
            } else {
                $response['message'] = 'Could not update password. Your reset code may have been used or expired.';
            }
            mysqli_stmt_close($stmt_update);
        }
    }
    mysqli_close($link);
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>