<?php
// Start output buffering to catch any stray PHP warnings or errors
ob_start();

// Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

// Include the necessary files
require_once 'db_connect.php';
require_once 'mail_config.php';

// Manually include the PHPMailer files
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

// NEW: Automatically delete unverified users whose OTP has expired (older than 15 minutes)
// This runs every time the registration page's backend is hit.
$cleanup_sql = "DELETE FROM users WHERE is_verified = 0 AND otp_expiry < NOW()";
if (!mysqli_query($link, $cleanup_sql)) {
    // Log error if cleanup fails, but don't stop the registration process
    error_log("Failed to cleanup expired unverified users: " . mysqli_error($link));
}
// END NEW LOGIC


$response = ['success' => false, 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // --- Validation ---
    if (empty($username) || empty($email) || empty($password)) {
        $response['message'] = 'Please fill in all fields.';
    
    // --- NEW VALIDATION: Added this elseif block ---
    } elseif (strlen($username) > 10) {
        $response['message'] = 'Username must be 10 characters or less.';
    // --- END OF NEW VALIDATION ---

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || substr($email, -10) !== '@gmail.com') {
        $response['message'] = 'Invalid email format or not a Gmail address.';
    } elseif (strlen($password) < 6 || !preg_match('/[A-Z]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
        $response['message'] = 'Password does not meet the requirements.';
    } else {
        // Check if username or email already exists
        $sql_check = "SELECT user_id FROM users WHERE (username = ? OR email = ?) AND deleted_at IS NULL";
        if ($stmt_check = mysqli_prepare($link, $sql_check)) {
            mysqli_stmt_bind_param($stmt_check, "ss", $username, $email);
            mysqli_stmt_execute($stmt_check);
            mysqli_stmt_store_result($stmt_check);
            if (mysqli_stmt_num_rows($stmt_check) > 0) {
                $response['message'] = 'Username or Email already taken.';
            }
            mysqli_stmt_close($stmt_check);
        }

        // If no validation errors so far, proceed
        if (empty($response['message'])) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $otp = rand(100000, 999999);
            $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));

            // Start a transaction to ensure both DB insert and email sending succeed or fail together
            mysqli_begin_transaction($link);
            
            $default_avatar_path = 'Tavern.png'; 

            try {
                // Step 1: Prepare and execute the user insertion
                $sql_insert = "INSERT INTO users (username, email, password_hash, is_verified, otp, otp_expiry, avatar) VALUES (?, ?, ?, 0, ?, ?, ?)";
                $stmt_insert = mysqli_prepare($link, $sql_insert);
                if ($stmt_insert === false) {
                    throw new Exception('Database error: Could not prepare the user insertion statement.');
                }

                // Bind parameters with correct types (s for string, i for integer)
                mysqli_stmt_bind_param($stmt_insert, "sssiss", $username, $email, $password_hash, $otp, $otp_expiry, $default_avatar_path);

                if (!mysqli_stmt_execute($stmt_insert)) {
                    // If insert fails (e.g., duplicate username), throw a specific error
                    throw new Exception('Database error: ' . mysqli_stmt_error($stmt_insert));
                }
                mysqli_stmt_close($stmt_insert);

                // Step 2: If insertion is successful, send the verification email
                $mail = new PHPMailer(true);
                
                $mail->isSMTP();
                $mail->Host       = SMTP_HOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = SMTP_USERNAME;
                $mail->Password   = SMTP_PASSWORD;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = SMTP_PORT;

                $mail->setFrom(SMTP_USERNAME, 'Tavern Publico');
                $mail->addAddress($email, $username);

                $mail->isHTML(true);
                $mail->Subject = 'Your Verification Code - Tavern Publico';
                $mail->Body    = "<h1>Welcome to Tavern Publico!</h1><p>Your verification code is: <strong>$otp</strong>. This code will expire in 15 minutes.</p>";
                $mail->AltBody = "Your verification code is: $otp. This code will expire in 15 minutes.";

                $mail->send(); // This will throw a PHPMailerException on failure

                // If both DB insert and email were successful, commit the transaction
                mysqli_commit($link);
                $response['success'] = true;
                $response['message'] = 'Registration successful! A verification code has been sent to your email.';

            } catch (Throwable $e) {
                // If anything in the 'try' block fails, roll back the transaction to undo the user creation
                mysqli_rollback($link);

                $response['success'] = false; 
                if ($e instanceof PHPMailerException) {
                    $response['message'] = "Could not send verification email. Please check your email address and try again.";
                    error_log("PHPMailer Error: " . ($mail->ErrorInfo ?? $e->getMessage()));
                } else {
                    // Send back the specific database or other error message
                    $response['message'] = $e->getMessage();
                    error_log("Registration Error: " . $e->getMessage());
                }
            }
        }
    }
    mysqli_close($link);
} else {
    $response['message'] = 'Invalid request method.';
}

// Clean (erase) any stray output from the buffer
ob_end_clean();

// Send the final, clean JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>