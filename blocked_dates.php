<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !$_SESSION['is_admin']) {
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    if ($action === 'block') {
        // ... block logic is unchanged
        $start_date_str = $_POST['block_date_start'] ?? '';
        $end_date_str = $_POST['block_date_end'] ?? '';

        if (empty($start_date_str)) {
             $response['message'] = 'Invalid date. The start date cannot be empty.';
             echo json_encode($response);
             exit;
        }

        if (!empty($end_date_str)) {
            if ($start_date_str > $end_date_str) {
                $response['message'] = 'End date cannot be earlier than the start date.';
            } else {
                $begin = new DateTime($start_date_str);
                $end = new DateTime($end_date_str);
                $end = $end->modify('+1 day');
                $interval = new DateInterval('P1D');
                $dateRange = new DatePeriod($begin, $interval, $end);
                
                $sql = "INSERT IGNORE INTO blocked_dates (block_date) VALUES (?)";
                $stmt = mysqli_prepare($link, $sql);
                $success_count = 0;
                
                foreach ($dateRange as $date) {
                    $date_to_insert = $date->format("Y-m-d");
                    mysqli_stmt_bind_param($stmt, "s", $date_to_insert);
                    if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
                        $success_count++;
                    }
                }
                mysqli_stmt_close($stmt);
                
                $response['success'] = true;
                $response['message'] = $success_count > 0 ? "$success_count day(s) have been blocked successfully." : "The selected date(s) were already blocked.";
            }
        } else {
            $sql = "INSERT INTO blocked_dates (block_date) VALUES (?)";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "s", $start_date_str);
                if (mysqli_stmt_execute($stmt)) {
                    $response['success'] = true;
                    $response['message'] = 'Date blocked successfully.';
                } else {
                    $response['message'] = 'This date is already blocked.';
                }
                mysqli_stmt_close($stmt);
            }
        }

    } elseif ($action === 'unblock') {
        
        // MODIFIED: Get the username of the admin performing the action
        $action_by_username = $_SESSION['username'] ?? 'System';

        // Unblocking now becomes a soft delete
        $date_to_manage = $_POST['block_date'] ?? '';
        if (!empty($date_to_manage)) {
            $sql_select = "SELECT * FROM blocked_dates WHERE block_date = ?";
            $stmt_select = mysqli_prepare($link, $sql_select);
            mysqli_stmt_bind_param($stmt_select, "s", $date_to_manage);
            mysqli_stmt_execute($stmt_select);
            $result = mysqli_stmt_get_result($stmt_select);
            $item_data = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt_select);
            
            if ($item_data) {
                $item_data_json = json_encode($item_data);
                $item_id = $item_data['id'];

                mysqli_begin_transaction($link);
                try {
                    // MODIFIED: Added action_by column and ? placeholder
                    $sql_log = "INSERT INTO deletion_history (item_type, item_id, item_data, action_by, purge_date) VALUES ('blocked_date', ?, ?, ?, DATE_ADD(CURDATE(), INTERVAL 30 DAY))";
                    $stmt_log = mysqli_prepare($link, $sql_log);
                    // MODIFIED: Added "s" for string and $action_by_username
                    mysqli_stmt_bind_param($stmt_log, "iss", $item_id, $item_data_json, $action_by_username);
                    mysqli_stmt_execute($stmt_log);
                    mysqli_stmt_close($stmt_log);

                    $sql_delete = "DELETE FROM blocked_dates WHERE id = ?";
                    $stmt_delete = mysqli_prepare($link, $sql_delete);
                    mysqli_stmt_bind_param($stmt_delete, "i", $item_id);
                    mysqli_stmt_execute($stmt_delete);
                    mysqli_stmt_close($stmt_delete);
                    
                    mysqli_commit($link);
                    $response['success'] = true;
                    $response['message'] = 'Blocked date moved to deletion history.';

                } catch (Exception $e) {
                    mysqli_rollback($link);
                    $response['message'] = 'Error removing blocked date.';
                }
            } else {
                $response['message'] = 'Blocked date not found.';
            }
        } else {
            $response['message'] = 'Invalid date provided for unblocking.';
        }
    } else {
        $response['message'] = 'Invalid action specified.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

mysqli_close($link);
echo json_encode($response);
?>