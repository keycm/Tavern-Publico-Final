<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

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
        $response['message'] = 'A valid email is required.';
        echo json_encode($response);
        exit;
    }

    // Find the unverified user
    $sql = "SELECT user_id FROM users WHERE email = ? AND is_verified = 0 AND deleted_at IS NULL";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($user) {
            // Generate new OTP and expiry
            $otp = rand(100000, 999999);
            $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));

            // Update the user's record with the new OTP
            $sql_update = "UPDATE users SET otp = ?, otp_expiry = ? WHERE user_id = ?";
            if ($stmt_update = mysqli_prepare($link, $sql_update)) {
                mysqli_stmt_bind_param($stmt_update, "ssi", $otp, $otp_expiry, $user['user_id']);
                if (mysqli_stmt_execute($stmt_update)) {
                    // Resend the email
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

                        $mail->isHTML(true);
                        $mail->Subject = 'Your New Verification Code - Tavern Publico';
                        $mail->Body    = "<h1>Verification Code</h1><p>Your new verification code is: <strong>$otp</strong>. This code will expire in 15 minutes.</p>";
                        $mail->AltBody = "Your new verification code is: $otp. This code will expire in 15 minutes.";

                        $mail->send();
                        $response['success'] = true;
                        $response['message'] = 'A new verification code has been sent to your email.';

                    } catch (PHPMailerException $e) {
                        $response['message'] = "Could not send new code. Please try again later.";
                        error_log("PHPMailer Error (Resend): " . $mail->ErrorInfo);
                    }
                } else {
                    $response['message'] = 'Database error: Could not update OTP.';
                }
                mysqli_stmt_close($stmt_update);
            }
        } else {
            $response['message'] = 'No unverified account found for this email address.';
        }
    }
} else {
    $response['message'] = 'Invalid request method.';
}

mysqli_close($link);
echo json_encode($response);
?>