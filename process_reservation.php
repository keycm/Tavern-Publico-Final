<?php
session_start();
require_once 'db_connect.php';
require_once 'mail_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // MODIFIED: Collect coupon code
    $applied_coupon_code = !empty($_POST['coupon_code']) ? trim($_POST['coupon_code']) : null;
    $is_coupon_valid = false;
    
    // --- Handle File Upload First ---
    $valid_id_path = null;
    if (isset($_FILES['valid_id']) && $_FILES['valid_id']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['valid_id'];
        $target_dir = "uploads/ids/"; 
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        // --- Validation ---
        $file_size = $file['size'];
        $file_tmp_name = $file['tmp_name'];
        
        // UPDATED: Changed file size limit to 10MB
        if ($file_size > 10000000) { // 10MB limit
            header('Location: reserve.php?status=error&message=' . urlencode('Error: File is too large. Max size is 10MB.'));
            exit;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_tmp_name);
        finfo_close($finfo);
        $allowed_mime_types = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($mime_type, $allowed_mime_types)) {
            header('Location: reserve.php?status=error&message=' . urlencode('Error: Invalid file type. Only JPG and PNG are allowed.'));
            exit;
        }

        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_filename = 'id_' . uniqid('', true) . '.' . strtolower($file_extension);
        $target_file = $target_dir . $new_filename;

        if (move_uploaded_file($file_tmp_name, $target_file)) {
            $valid_id_path = $target_file;
        } else {
            header('Location: reserve.php?status=error&message=' . urlencode('Error: Failed to upload your ID. Please try again.'));
            exit;
        }
    } else {
        $error_message = 'Please upload a valid ID.';
        if (isset($_FILES['valid_id']['error'])) {
            switch ($_FILES['valid_id']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $error_message = 'File is too large.';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $error_message = 'No ID file was uploaded. It is required.';
                    break;
            }
        }
         header('Location: reserve.php?status=error&message=' . urlencode($error_message));
         exit;
    }


    // Collect and sanitize other form data
    $resDate = htmlspecialchars(trim($_POST['resDate'] ?? ''));
    $resTime = htmlspecialchars(trim($_POST['resTime'] ?? ''));
    $numGuests = filter_var(trim($_POST['numGuests'] ?? ''), FILTER_SANITIZE_NUMBER_INT);
    $reservationType = htmlspecialchars(trim($_POST['reservation_type'] ?? 'Dine-in'));
    $resName = htmlspecialchars(trim($_POST['resName'] ?? ''));
    $resPhone = htmlspecialchars(trim($_POST['resPhone'] ?? ''));
    $resEmail = filter_var(trim($_POST['resEmail'] ?? ''), FILTER_SANITIZE_EMAIL);
    $source = 'Online'; 
    $status = "Pending";
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    // Server-Side Blocked Date Check
    $sql_check_blocked = "SELECT COUNT(*) as count FROM blocked_dates WHERE block_date = ?";
    if ($stmt_check = mysqli_prepare($link, $sql_check_blocked)) {
        mysqli_stmt_bind_param($stmt_check, "s", $resDate);
        if (mysqli_stmt_execute($stmt_check)) {
            $result_check = mysqli_stmt_get_result($stmt_check);
            $row_check = mysqli_fetch_assoc($result_check);
            if ($row_check['count'] > 0) {
                header('Location: reserve.php?status=error&message=' . urlencode('The selected date is not available.'));
                exit;
            }
        }
        mysqli_stmt_close($stmt_check);
    }
    
    // Server-Side Validation
    if (empty($resDate) || empty($resTime) || empty($numGuests) || empty($resName) || empty($resPhone) || empty($resEmail)) {
        header('Location: reserve.php?status=error&message=' . urlencode('Please fill in all required fields.'));
        exit;
    }
    
    if (!preg_match('/^09\d{9}$/', $resPhone)) {
        header('Location: reserve.php?status=error&message=' . urlencode('Invalid phone number format.'));
        exit;
    }
    
    // MODIFIED: Start transaction
    mysqli_begin_transaction($link);
    try {

        // MODIFIED: Re-validate coupon on server-side
        if ($applied_coupon_code) {
            $sql_check_coupon = "SELECT * FROM coupons WHERE code = ? AND is_active = 1 AND (expiry_date IS NULL OR expiry_date >= CURDATE()) AND current_usage < usage_limit FOR UPDATE"; // FOR UPDATE locks the row
            if ($stmt_check = mysqli_prepare($link, $sql_check_coupon)) {
                mysqli_stmt_bind_param($stmt_check, "s", $applied_coupon_code);
                mysqli_stmt_execute($stmt_check);
                $result_check = mysqli_stmt_get_result($stmt_check);
                if (mysqli_num_rows($result_check) > 0) {
                    $is_coupon_valid = true;
                } else {
                    // Coupon is invalid, but we'll still let the reservation go through without it.
                    $applied_coupon_code = null; 
                }
                mysqli_stmt_close($stmt_check);
            }
        }
        
        // MODIFIED: Added 'applied_coupon_code' to INSERT statement
        $sql = "INSERT INTO reservations (user_id, res_date, res_time, num_guests, res_name, res_phone, res_email, status, source, reservation_type, valid_id_path, applied_coupon_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        if ($stmt = mysqli_prepare($link, $sql)) {
            // MODIFIED: Added "s" and $applied_coupon_code to bind_param
            mysqli_stmt_bind_param($stmt, "ississssssss", $user_id, $resDate, $resTime, $numGuests, $resName, $resPhone, $resEmail, $status, $source, $reservationType, $valid_id_path, $applied_coupon_code);

            if (mysqli_stmt_execute($stmt)) {
                
                // MODIFIED: If reservation was successful AND coupon was valid, increment usage
                if ($is_coupon_valid) {
                    $sql_update_coupon = "UPDATE coupons SET current_usage = current_usage + 1 WHERE code = ?";
                    if ($stmt_update = mysqli_prepare($link, $sql_update_coupon)) {
                        mysqli_stmt_bind_param($stmt_update, "s", $applied_coupon_code);
                        if (!mysqli_stmt_execute($stmt_update)) {
                            throw new Exception('Failed to update coupon usage.');
                        }
                        mysqli_stmt_close($stmt_update);
                    }
                }
                
                // MODIFIED: Commit transaction
                mysqli_commit($link);

                // ... (Email notification code remains the same) ...
                header('Location: reserve.php?status=success');
                exit;
            } else {
                throw new Exception('Database insert failed.');
            }
            mysqli_stmt_close($stmt);
        } else {
            throw new Exception('Database preparation failed.');
        }

    // MODIFIED: Catch block for transaction
    } catch (Exception $e) {
        mysqli_rollback($link);
        error_log("Reservation transaction error: " . $e->getMessage());
        header('Location: reserve.php?status=error&message=' . urlencode('An error occurred. Please try again.'));
        exit;
    }

    mysqli_close($link);

} else {
    header('Location: reserve.php');
    exit;
}
?>