<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'An unknown error occurred.', 'birthday_updated' => false];

if (!isset($_SESSION['loggedin']) || !$_SESSION['user_id']) {
    $response['message'] = 'You must be logged in to update your profile.';
    echo json_encode($response);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    
    // --- MODIFIED: Get data for validation ---
    $birthday = trim($_POST['birthday'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $retype_password = $_POST['retype_password'] ?? '';

    // --- MODIFIED: Fetch current user data for comparison ---
    $user = null;
    $sql_get_user = "SELECT birthday, birthday_last_updated FROM users WHERE user_id = ?";
    if ($stmt_get_user = mysqli_prepare($link, $sql_get_user)) {
        mysqli_stmt_bind_param($stmt_get_user, "i", $user_id);
        if (mysqli_stmt_execute($stmt_get_user)) {
            $result_user = mysqli_stmt_get_result($stmt_get_user);
            $user = mysqli_fetch_assoc($result_user);
        }
        mysqli_stmt_close($stmt_get_user);
    }
    
    if (!$user) {
        $response['message'] = 'User not found.';
        echo json_encode($response);
        exit;
    }

    $sql_parts = [];
    $params = [];
    $types = "";

    // --- MODIFIED: Mobile Validation (11 digits or empty) ---
    if (!empty($mobile)) {
        if (!preg_match('/^[0-9]{11}$/', $mobile)) {
            $response['message'] = 'Mobile number must be exactly 11 digits (e.g., 09123456789).';
            echo json_encode($response);
            exit;
        }
        $sql_parts[] = "mobile = ?";
        $params[] = $mobile;
        $types .= "s";
    } else {
        $sql_parts[] = "mobile = NULL"; // Allow clearing the number
    }

    // --- NEW: Birthday Validation (60-day lock) ---
    $birthday_changed = ($birthday !== $user['birthday']);
    if ($birthday_changed) {
        $allow_update = false;
        if (empty($user['birthday_last_updated'])) {
            $allow_update = true; // Never been set, allow update
        } else {
            // Check if 60 days have passed
            $last_updated = new DateTime($user['birthday_last_updated']);
            $today = new DateTime();
            $interval = $last_updated->diff($today);
            if ($interval->days >= 60) {
                $allow_update = true;
            }
        }

        if ($allow_update) {
            $sql_parts[] = "birthday = ?";
            $params[] = $birthday;
            $types .= "s";
            $sql_parts[] = "birthday_last_updated = CURDATE()"; // Update the timestamp
            $response['birthday_updated'] = true; // Flag for frontend to reload
        } else {
            $response['message'] = 'You can only change your birthday once every 60 days.';
            echo json_encode($response);
            exit;
        }
    }
    // --- END NEW ---

    // --- MODIFIED: Username is NO LONGER updated ---
    // The 'username' field is removed from this logic.
    
    // Password logic (remains the same)
    if (!empty($new_password)) {
        if ($new_password !== $retype_password) {
            $response['message'] = 'Passwords do not match.';
            echo json_encode($response);
            exit;
        }
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $sql_parts[] = "password_hash = ?";
        $params[] = $password_hash;
        $types .= "s";
    }

    // Check if there is anything to update
    if (empty($sql_parts)) {
        $response['success'] = true;
        $response['message'] = 'No changes detected.';
        echo json_encode($response);
        exit;
    }

    $sql = "UPDATE users SET " . implode(", ", $sql_parts) . " WHERE user_id = ?";
    $params[] = $user_id;
    $types .= "i";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        
        if (mysqli_stmt_execute($stmt)) {
            $response['success'] = true;
            $response['message'] = 'Your account settings have been saved.';
        } else {
            $response['message'] = 'Error updating profile. Please try again.';
        }
        mysqli_stmt_close($stmt);
    } else {
        $response['message'] = 'Database error. Please try again.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

mysqli_close($link);
echo json_encode($response);
?>