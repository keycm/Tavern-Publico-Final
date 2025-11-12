<?php
// process_contact_form.php

// ADDED: PHPMailer dependencies
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'db_connect.php'; // Your database connection
require_once 'mail_config.php'; // Your mail config

// ADDED: PHPMailer files
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['contactName'] ?? '');
    $email = trim($_POST['contactEmail'] ?? '');
    $subject = trim($_POST['contactSubject'] ?? '');
    $message = trim($_POST['contactMessage'] ?? '');

    if (empty($name) || empty($email) || empty($message)) {
        // Handle error - redirect back to contact page with an error message
        header("Location: contact.php?status=error");
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Handle invalid email format
        header("Location: contact.php?status=invalid_email");
        exit;
    }

    // Block emails with only numbers before the @
    $email_parts = explode('@', $email);
    $local_part = $email_parts[0];
    if (is_numeric($local_part)) {
        header("Location: contact.php?status=invalid_email_numeric");
        exit;
    }

    $sql = "INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $subject, $message);

        if (mysqli_stmt_execute($stmt)) {
            // Success, now send acknowledgement email
            
            // ADDED: Email sending logic
            $mail = new PHPMailer(true);
            try {
                //Server settings
                $mail->isSMTP();
                $mail->Host       = SMTP_HOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = SMTP_USERNAME;
                $mail->Password   = SMTP_PASSWORD;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = SMTP_PORT;

                //Recipients
                $mail->setFrom(SMTP_USERNAME, 'Tavern Publico');
                $mail->addAddress($email, $name);     // Add a recipient

                // Content
                $mail->isHTML(true);                                  // Set email format to HTML
                $mail->Subject = 'Inquiry Received - Tavern Publico';
                $mail->Body    = 'Dear ' . $name . ',<br><br>Thank you for contacting us. We have received your inquiry and will get back to you within 24 hours.<br><br>Sincerely,<br>Tavern Publico Team';
                $mail->AltBody = 'Dear ' . $name . ',\n\nThank you for contacting us. We have received your inquiry and will get back to you within 24 hours.\n\nSincerely,\nTavern Publico Team';

                $mail->send();
                
                header("Location: contact.php?status=success");
                exit;

            } catch (Exception $e) {
                // Log error but still show success to user
                error_log("Acknowledgement email could not be sent. Mailer Error: {$mail->ErrorInfo}");
                header("Location: contact.php?status=success_no_email"); // A different status if email fails
                exit;
            }

        } else {
            // Database execution error
            header("Location: contact.php?status=db_error");
            exit;
        }
        mysqli_stmt_close($stmt);
    } else {
        // Database prepare error
        header("Location: contact.php?status=db_error");
        exit;
    }

    mysqli_close($link);
} else {
    // Not a POST request
    header("Location: contact.php");
    exit;
}
?>