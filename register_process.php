<?php
require_once 'db_connect.php'; // Your database connection

// Check if it's a POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password']; // Remember to hash this!

    // --- Step 1: Sanitize and Validate the Email Format ---
    $clean_email = filter_var($email, FILTER_SANITIZE_EMAIL);

    if (!filter_var($clean_email, FILTER_VALIDATE_EMAIL)) {
        // Not a valid email format
        die("Error: Invalid email format.");
    }

    // --- Step 2: Check if the Domain is specifically 'gmail.com' ---
    $domain = substr(strrchr($clean_email, "@"), 1);
    if (strtolower($domain) !== 'gmail.com') {
        // Not a Gmail address
        die("Error: Registration is only allowed with a Gmail address.");
    }

    // --- Step 3: Check if Email Already Exists (Optional but recommended) ---
    // (Add your code here to query the database and see if the email is already registered)

    // --- Step 4: Create a Verification Token and Hash Password ---
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $verification_token = bin2hex(random_bytes(50)); // Generate a secure random token

    // --- Step 5: Insert User into Database (as unverified) ---
    $sql = "INSERT INTO users (email, password, verification_token, is_verified) VALUES (?, ?, ?, 0)";
    
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "sss", $clean_email, $password_hash, $verification_token);
        
        if (mysqli_stmt_execute($stmt)) {
            // --- Step 6: Send the Verification Email ---
            $verification_link = "http://yourwebsite.com/verify.php?token=" . $verification_token;
            
            $subject = "Verify Your Email Address for Tavern Publico";
            $message = "Welcome! Please click the link below to verify your account:\n\n" . $verification_link;
            $headers = "From: no-reply@yourwebsite.com";

            // Use PHP's mail() function or a library like PHPMailer
            if (mail($clean_email, $subject, $message, $headers)) {
                echo "Registration successful! A verification link has been sent to your Gmail address.";
            } else {
                echo "Registration successful, but could not send verification email. Please contact support.";
            }
        } else {
            echo "Error: Could not execute the query.";
        }
        mysqli_stmt_close($stmt);
    }
    mysqli_close($link);
}
?>