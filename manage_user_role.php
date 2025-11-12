<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if (!isset($_SESSION['loggedin']) || !$_SESSION['is_admin']) {
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = filter_var($_POST['user_id'] ?? 0, FILTER_SANITIZE_NUMBER_INT);

    if ($userId > 0) {
        // First, get the current role of the user
        $current_role = '';
        $sql_select = "SELECT role FROM users WHERE user_id = ?";
        if ($stmt_select = mysqli_prepare($link, $sql_select)) {
            mysqli_stmt_bind_param($stmt_select, "i", $userId);
            mysqli_stmt_execute($stmt_select);
            mysqli_stmt_bind_result($stmt_select, $current_role);
            mysqli_stmt_fetch($stmt_select);
            mysqli_stmt_close($stmt_select);
        }

        if ($current_role) {
            // Determine the new role
            $new_role = ($current_role === 'manager') ? 'user' : 'manager';

            $stmt_update = null;
            if ($new_role === 'manager') {
                // Logic for PROMOTING to manager
                $permissions = $_POST['permissions'] ?? [];
                $permissions_json = json_encode($permissions);
                $sql_update = "UPDATE users SET role = ?, permissions = ? WHERE user_id = ?";
                $stmt_update = mysqli_prepare($link, $sql_update);
                mysqli_stmt_bind_param($stmt_update, "ssi", $new_role, $permissions_json, $userId);
            } else {
                // Logic for DEMOTING to user
                // This query will also clear any permissions the manager had by setting the column to NULL.
                $sql_update = "UPDATE users SET role = ?, permissions = NULL WHERE user_id = ?";
                $stmt_update = mysqli_prepare($link, $sql_update);
                mysqli_stmt_bind_param($stmt_update, "si", $new_role, $userId);
            }

            if ($stmt_update) {
                if (mysqli_stmt_execute($stmt_update)) {
                    $response['success'] = true;
                    $response['message'] = 'User role updated successfully.';
                    $response['newRole'] = $new_role;
                } else {
                    // Provide a helpful error if the query fails, likely due to the missing column
                    $response['message'] = 'Error updating user role. Please ensure your database `users` table has a `permissions` column (e.g., of type TEXT or JSON).';
                }
                mysqli_stmt_close($stmt_update);
            } else {
                 $response['message'] = 'Database error preparing the update.';
            }

        } else {
            $response['message'] = 'User not found.';
        }
    } else {
        $response['message'] = 'Invalid user ID.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

mysqli_close($link);
echo json_encode($response);
?>