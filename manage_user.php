<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'An unknown error occurred.'];

// MODIFIED: Allow both 'owner' and 'manager' roles to access this script
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !in_array($_SESSION['role'], ['owner', 'manager'])) {
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    if ($action === 'deleteUser') {
        $userId = filter_var($_POST['user_id'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
        if ($userId > 0) {
            
            // MODIFIED: Get the username of the admin performing the action
            $action_by_username = $_SESSION['username'] ?? 'System';

            // Soft delete logic for users
            // FIXED: Check if the user is not already soft-deleted to prevent duplicates
            $sql_select = "SELECT * FROM users WHERE user_id = ? AND deleted_at IS NULL";
            $stmt_select = mysqli_prepare($link, $sql_select);
            mysqli_stmt_bind_param($stmt_select, "i", $userId);
            mysqli_stmt_execute($stmt_select);
            $result = mysqli_stmt_get_result($stmt_select);
            $item_data = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt_select);
            
            if ($item_data) {
                unset($item_data['password_hash']); // Do not log password hash
                $item_data_json = json_encode($item_data);
                
                mysqli_begin_transaction($link);
                try {
                    // MODIFIED: Added action_by column and ? placeholder
                    $sql_log = "INSERT INTO deletion_history (item_type, item_id, item_data, action_by, purge_date) VALUES ('user', ?, ?, ?, DATE_ADD(CURDATE(), INTERVAL 30 DAY))";
                    $stmt_log = mysqli_prepare($link, $sql_log);
                    // MODIFIED: Added "s" for string and $action_by_username
                    mysqli_stmt_bind_param($stmt_log, "iss", $userId, $item_data_json, $action_by_username);
                    mysqli_stmt_execute($stmt_log);
                    mysqli_stmt_close($stmt_log);

                    $sql_soft_delete = "UPDATE users SET deleted_at = NOW() WHERE user_id = ?";
                    $stmt_soft_delete = mysqli_prepare($link, $sql_soft_delete);
                    mysqli_stmt_bind_param($stmt_soft_delete, "i", $userId);
                    mysqli_stmt_execute($stmt_soft_delete);
                    mysqli_stmt_close($stmt_soft_delete);

                    mysqli_commit($link);
                    $response['success'] = true;
                    $response['message'] = 'User moved to deletion history.';

                } catch (Exception $e) {
                    mysqli_rollback($link);
                    $response['message'] = 'Error deleting user.';
                }
            } else {
                $response['message'] = 'User not found or has already been deleted.';
            }
        } else {
            $response['message'] = 'Invalid user ID.';
        }
    }
    // ... (rest of the saveUser logic is unchanged)
    elseif ($action === 'saveUser') {
        $userId = filter_var($_POST['user_id'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($email)) {
            $response['message'] = 'Username and Email are required.';
            echo json_encode($response);
            exit;
        }

        if ($userId > 0) {
            if (!empty($password)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET username = ?, email = ?, password_hash = ? WHERE user_id = ?";
                $stmt = mysqli_prepare($link, $sql);
                mysqli_stmt_bind_param($stmt, "sssi", $username, $email, $password_hash, $userId);
            } else {
                $sql = "UPDATE users SET username = ?, email = ? WHERE user_id = ?";
                $stmt = mysqli_prepare($link, $sql);
                mysqli_stmt_bind_param($stmt, "ssi", $username, $email, $userId);
            }

            if ($stmt && mysqli_stmt_execute($stmt)) {
                $response['success'] = true;
                $response['message'] = 'User updated successfully.';
            } else {
                $response['message'] = 'Failed to update user. Username or email might already be taken.';
            }
            if ($stmt) mysqli_stmt_close($stmt);
        }
        else {
             if (empty($password)) {
                $response['message'] = 'Password is required for new users.';
                echo json_encode($response);
                exit;
            }
            
            $sql_check = "SELECT user_id FROM users WHERE username = ? OR email = ?";
            if ($stmt_check = mysqli_prepare($link, $sql_check)) {
                mysqli_stmt_bind_param($stmt_check, "ss", $username, $email);
                if (mysqli_stmt_execute($stmt_check)) {
                    mysqli_stmt_store_result($stmt_check);
                    if (mysqli_stmt_num_rows($stmt_check) > 0) {
                        $response['message'] = 'Username or Email already taken.';
                        echo json_encode($response);
                        mysqli_stmt_close($stmt_check);
                        mysqli_close($link);
                        exit;
                    }
                }
                mysqli_stmt_close($stmt_check);
            }

            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, email, password_hash, is_admin) VALUES (?, ?, ?, 0)";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, "sss", $username, $email, $password_hash);
            
            if ($stmt && mysqli_stmt_execute($stmt)) {
                $response['success'] = true;
                $response['message'] = 'User added successfully.';
            } else {
                $response['message'] = 'Failed to add user. Please try again.';
            }
            if ($stmt) mysqli_stmt_close($stmt);
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