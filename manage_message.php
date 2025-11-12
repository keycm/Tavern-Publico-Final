<?php
// manage_message.php
session_start();
require_once 'db_connect.php';

// ADDED: PHPMailer dependencies
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'mail_config.php'; // Your mail config
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if (!isset($_SESSION['loggedin']) || !$_SESSION['is_admin']) {
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    $message_id = filter_input(INPUT_POST, 'message_id', FILTER_SANITIZE_NUMBER_INT);

    if (empty($message_id)) {
        $response['message'] = 'Invalid message ID.';
        echo json_encode($response);
        exit;
    }

    if ($action === 'reply') {
        $reply_text = trim($_POST['reply_text'] ?? '');
        $customer_email = filter_var(trim($_POST['customer_email'] ?? ''), FILTER_SANITIZE_EMAIL);
        
        // ADDED: Get customer name for the email
        $customer_name = '';
        $sql_get_name = "SELECT name FROM contact_messages WHERE id = ?";
        if($stmt_get_name = mysqli_prepare($link, $sql_get_name)){
            mysqli_stmt_bind_param($stmt_get_name, "i", $message_id);
            mysqli_stmt_execute($stmt_get_name);
            mysqli_stmt_bind_result($stmt_get_name, $found_name);
            if(mysqli_stmt_fetch($stmt_get_name)){
                $customer_name = $found_name;
            }
            mysqli_stmt_close($stmt_get_name);
        }


        if (empty($reply_text) || empty($customer_email)) {
            $response['message'] = 'Reply text and customer email are required.';
            echo json_encode($response);
            exit;
        }
        
        // --- ADDED: Send email reply to the user ---
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
            $mail->setFrom(SMTP_USERNAME, 'Tavern Publico Support');
            $mail->addAddress($customer_email, $customer_name);     // Add a recipient

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Regarding Your Inquiry - Tavern Publico';
            $mail->Body    = 'Dear ' . $customer_name . ',<br><br>Thank you for your inquiry. Here is the response from our team:<br><br>---<br>' . nl2br(htmlspecialchars($reply_text)) . '<br>---<br><br>If you have any further questions, please feel free to contact us again.<br><br>Sincerely,<br>Tavern Publico Team';
            $mail->AltBody = 'Dear ' . $customer_name . ',\n\nThank you for your inquiry. Here is the response from our team:\n\n---\n' . $reply_text . '\n---\n\nIf you have any further questions, please feel free to contact us again.\n\nSincerely,\nTavern Publico Team';

            $mail->send();
            $email_sent = true;

        } catch (Exception $e) {
            $email_sent = false;
            // Log error but continue with the in-app notification logic
            error_log("Admin reply email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        }
        // --- END OF EMAIL LOGIC ---


        // Existing in-app notification logic
        $user_id = null;
        $sql_find_user = "SELECT user_id FROM users WHERE email = ?";
        if($stmt_find_user = mysqli_prepare($link, $sql_find_user)){
            mysqli_stmt_bind_param($stmt_find_user, "s", $customer_email);
            mysqli_stmt_execute($stmt_find_user);
            mysqli_stmt_bind_result($stmt_find_user, $found_user_id);
            if(mysqli_stmt_fetch($stmt_find_user)){
                $user_id = $found_user_id;
            }
            mysqli_stmt_close($stmt_find_user);
        }

        if ($user_id) {
            $sql_insert_notification = "INSERT INTO notifications (user_id, message) VALUES (?, ?)";
            if($stmt_insert = mysqli_prepare($link, $sql_insert_notification)){
                mysqli_stmt_bind_param($stmt_insert, "is", $user_id, $reply_text);
                mysqli_stmt_execute($stmt_insert);
                mysqli_stmt_close($stmt_insert);

                $sql_update_message = "UPDATE contact_messages SET admin_reply = ?, replied_at = NOW(), is_read = 1 WHERE id = ?";
                if ($stmt_update = mysqli_prepare($link, $sql_update_message)) {
                    mysqli_stmt_bind_param($stmt_update, "si", $reply_text, $message_id);
                    mysqli_stmt_execute($stmt_update);
                    mysqli_stmt_close($stmt_update);
                }
                $response['success'] = true;
                $response['message'] = 'Reply sent as an in-app notification.';
                if ($email_sent) {
                     $response['message'] .= ' An email has also been sent to the user.';
                } else {
                     $response['message'] .= ' However, the email notification failed to send.';
                }
            }
        } else {
            // This is a guest, so only email is possible
            if ($email_sent) {
                // Update the replied_at status even for guests
                $sql_update_message = "UPDATE contact_messages SET admin_reply = ?, replied_at = NOW(), is_read = 1 WHERE id = ?";
                if ($stmt_update = mysqli_prepare($link, $sql_update_message)) {
                    mysqli_stmt_bind_param($stmt_update, "si", $reply_text, $message_id);
                    mysqli_stmt_execute($stmt_update);
                    mysqli_stmt_close($stmt_update);
                }
                $response['success'] = true;
                $response['message'] = 'Reply sent successfully via email to the guest.';
            } else {
                 $response['message'] = 'Could not send reply. The message is from a guest (not a registered user) and the email failed to send.';
            }
        }

    } elseif ($action === 'delete') {
        
        // MODIFIED: Get the username of the admin performing the action
        $action_by_username = $_SESSION['username'] ?? 'System';
        
        // Soft delete logic for contact messages
        $sql_select = "SELECT * FROM contact_messages WHERE id = ?";
        $stmt_select = mysqli_prepare($link, $sql_select);
        mysqli_stmt_bind_param($stmt_select, "i", $message_id);
        mysqli_stmt_execute($stmt_select);
        $result = mysqli_stmt_get_result($stmt_select);
        $item_data = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt_select);

        if ($item_data) {
            $item_data_json = json_encode($item_data);
            mysqli_begin_transaction($link);
            try {
                // MODIFIED: Added action_by column and ? placeholder
                $sql_log = "INSERT INTO deletion_history (item_type, item_id, item_data, action_by, purge_date) VALUES ('contact_message', ?, ?, ?, DATE_ADD(CURDATE(), INTERVAL 30 DAY))";
                $stmt_log = mysqli_prepare($link, $sql_log);
                // MODIFIED: Added "s" for string and $action_by_username
                mysqli_stmt_bind_param($stmt_log, "iss", $message_id, $item_data_json, $action_by_username);
                mysqli_stmt_execute($stmt_log);
                mysqli_stmt_close($stmt_log);

                $sql_soft_delete = "UPDATE contact_messages SET deleted_at = NOW() WHERE id = ?";
                $stmt_soft_delete = mysqli_prepare($link, $sql_soft_delete);
                mysqli_stmt_bind_param($stmt_soft_delete, "i", $message_id);
                mysqli_stmt_execute($stmt_soft_delete);
                mysqli_stmt_close($stmt_soft_delete);

                mysqli_commit($link);
                $response['success'] = true;
                $response['message'] = 'Message moved to deletion history.';
            } catch (Exception $e) {
                mysqli_rollback($link);
                $response['message'] = 'Error moving message to history.';
            }
        } else {
            $response['message'] = 'Message not found.';
        }
    } else {
        $response['message'] = 'Invalid action.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

mysqli_close($link);
echo json_encode($response);
?>