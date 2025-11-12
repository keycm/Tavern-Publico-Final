<?php
require_once 'db_connect.php'; // Your database connection

if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];

    // Find the user with the given token
    $sql = "SELECT user_id FROM users WHERE verification_token = ? AND is_verified = 0";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $token);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) == 1) {
            // Token is valid, update the user's status
            mysqli_stmt_close($stmt); // Close previous statement

            $update_sql = "UPDATE users SET is_verified = 1, verification_token = NULL WHERE verification_token = ?";
            if ($update_stmt = mysqli_prepare($link, $update_sql)) {
                mysqli_stmt_bind_param($update_stmt, "s", $token);
                if (mysqli_stmt_execute($update_stmt)) {
                    echo "<h1>Email Verified! ✅</h1>";
                    echo "<p>Your account has been successfully activated. You can now <a href='index.php'>log in</a>.</p>";
                }
                mysqli_stmt_close($update_stmt);
            }
        } else {
            // Token is invalid or account is already verified
            echo "<h1>Verification Failed!</h1>";
            echo "<p>This verification link is invalid or has already been used.</p>";
        }
    }
    mysqli_close($link);
} else {
    header('Location: index.php'); // Redirect if no token is provided
    exit();
}
?>