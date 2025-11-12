<?php
session_start();
require_once 'db_connect.php'; // $link is created here

// Check if the request is POST (from your login form)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // --- This is all your original AJAX login logic ---
    
    header('Content-Type: application/json'); // Set JSON header for the AJAX response
    $response = ['success' => false, 'message' => '', 'redirect' => ''];

    $username_or_email = trim($_POST['username_email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username_or_email) || empty($password)) {
        $response['message'] = 'Please fill in both username/email and password.';
        mysqli_close($link); // Close connection
        echo json_encode($response);
        exit;
    }

    $sql = "SELECT user_id, username, password_hash, is_admin, is_verified, avatar, role, permissions FROM users WHERE (username = ? OR email = ?) AND deleted_at IS NULL";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $username_or_email, $username_or_email);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) == 1) {
                mysqli_stmt_bind_result($stmt, $user_id, $db_username, $hashed_password, $is_admin, $is_verified, $avatar, $role, $permissions);
                mysqli_stmt_fetch($stmt);

                if (password_verify($password, $hashed_password)) {
                    if ($is_verified == 1) {
                        session_regenerate_id(true);
                        $_SESSION['loggedin'] = true;
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['username'] = $db_username;
                        $_SESSION['is_admin'] = boolval($is_admin);
                        $_SESSION['avatar'] = $avatar;
                        $_SESSION['role'] = $role;
                        
                        if ($role === 'manager' && !empty($permissions)) {
                            $_SESSION['permissions'] = json_decode($permissions, true);
                        } else {
                            unset($_SESSION['permissions']);
                        }
                        
                        // --- THIS IS THE FIX ---
                        // Check for any confirmed, unrated reservations for this user
                        $sql_check_unrated = "SELECT r.reservation_id FROM reservations r 
                                              LEFT JOIN testimonials t ON r.reservation_id = t.reservation_id 
                                              WHERE r.user_id = ? 
                                              AND r.status = 'Confirmed' 
                                              AND r.deleted_at IS NULL 
                                              AND t.id IS NULL 
                                              LIMIT 1";
                                              
                        if ($stmt_check_unrated = mysqli_prepare($link, $sql_check_unrated)) {
                            mysqli_stmt_bind_param($stmt_check_unrated, "i", $user_id);
                            mysqli_stmt_execute($stmt_check_unrated);
                            mysqli_stmt_store_result($stmt_check_unrated);
                            
                            if (mysqli_stmt_num_rows($stmt_check_unrated) > 0) {
                                // User has a completed, unrated reservation. Set the flag!
                                $_SESSION['show_rating_modal'] = true;
                            } else {
                                $_SESSION['show_rating_modal'] = false;
                            }
                            mysqli_stmt_close($stmt_check_unrated);
                        } else {
                            // If query fails, just default to false
                            $_SESSION['show_rating_modal'] = false;
                        }
                        // --- END OF FIX ---


                        $response['success'] = true;
                        $response['message'] = 'Login successful!';

                        if ($role === 'owner') {
                            $response['redirect'] = 'admin.php';
                        } elseif ($role === 'manager') {
                            $response['redirect'] = 'manager.php';
                        } else {
                            $response['redirect'] = 'index.php';
                        }
                    } else {
                        $response['message'] = 'Please verify your email address before logging in.';
                    }
                } else {
                    $response['message'] = 'Invalid username/email or password.';
                }
            } else {
                $response['message'] = 'Invalid username/email or password.';
            }
        } else {
            $response['message'] = 'Oops! Something went wrong.';
        }
        mysqli_stmt_close($stmt);
    } else {
        $response['message'] = 'Database error.';
    }
    
    mysqli_close($link); // Close connection
    echo json_encode($response); // Echo the JSON response
    exit; // Stop the script

} else {
    
    // --- NEW: This block runs if the page is accessed directly (GET request) ---
    
    if (isset($link)) {
        mysqli_close($link); // Close connection if it was opened
    }
    
    // Set a 404 header
    header("HTTP/1.0 404 Not Found");
    
    // Output the HTML "Page Not Found" page
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found</title>
    <link href="https://fonts.googleapis.com/css2?family=Mada:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Mada', sans-serif;
            background-color: #121212;
            color: #e0e0e0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            text-align: center;
        }
        .container {
            padding: 20px;
        }
        h1 {
            font-size: 3em;
            color: #FFD700;
            margin-bottom: 10px;
        }
        p {
            font-size: 1.2em;
            margin-bottom: 30px;
        }
        a {
            display: inline-block;
            padding: 12px 25px;
            background-color: #FFD700;
            color: #1a1a1a;
            text-decoration: none;
            font-weight: 600;
            border-radius: 8px;
            transition: background-color 0.3s ease;
        }
        a:hover {
            background-color: #e6c200;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>404</h1>
        <p>Page Not Found. This page is not meant to be accessed directly.</p>
        <a href="index.php">Go Back to Home Page</a>
    </div>
</body>
</html>
HTML;
    exit; // Stop the script
}
?>