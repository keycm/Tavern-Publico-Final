<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'db_connect.php';
require_once 'mail_config.php';
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Please enter a valid email address.';
    } else {
        $sql = "SELECT user_id FROM users WHERE email = ? AND deleted_at IS NULL";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);

            if ($user) {
                $otp = rand(100000, 999999); // Generate 6-digit OTP
                $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));

                // Store OTP in the reset_token column
                $sql_update = "UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE user_id = ?";
                if ($stmt_update = mysqli_prepare($link, $sql_update)) {
                    mysqli_stmt_bind_param($stmt_update, "ssi", $otp, $expiry, $user['user_id']);
                    mysqli_stmt_execute($stmt_update);

                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host       = SMTP_HOST;
                        $mail->SMTPAuth   = true;
                        $mail->Username   = SMTP_USERNAME;
                        $mail->Password   = SMTP_PASSWORD;
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = SMTP_PORT;

                        $mail->setFrom(SMTP_USERNAME, 'Tavern Publico');
                        $mail->addAddress($email);
                        
                        // Email the OTP code
                        $mail->isHTML(true);
                        $mail->Subject = 'Your Password Reset Code - Tavern Publico';
                        $mail->Body    = "<h1>Password Reset</h1><p>Your password reset code is: <strong>$otp</strong></p><p>This code will expire in 15 minutes.</p>";
                        $mail->AltBody = "Your password reset code is: $otp. This code will expire in 15 minutes.";

                        $mail->send();
                        $response['success'] = true;
                        $response['message'] = 'A password reset code has been sent to your email.';
                    } catch (Exception $e) {
                        $response['message'] = "Could not send email. Mailer Error: {$mail->ErrorInfo}";
                    }
                }
            } else {
                // MODIFIED: Return a specific error for non-registered emails
                $response['success'] = false;
                $response['message'] = 'This email is not registered. Please check the address or register for a new account.';
            }
        }
    }
    mysqli_close($link);
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>