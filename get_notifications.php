<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

$response = ['success' => false, 'notifications' => []];

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$notifications = [];

// Use a single, more reliable UNION ALL query to fetch all notification types at once
$sql = "
    (SELECT
        reservation_id as id,
        CONCAT('Your reservation for ', res_date, ' has been ', status, '.') as message,
        'profile.php' as link,
        'reservation' as type,
        created_at
    FROM reservations
    WHERE user_id = ? AND status != 'Pending' AND is_notified = 0)
    UNION ALL
    (SELECT
        id,
        CONCAT('Admin Reply: ', message) as message,
        '#' as link,
        'custom' as type,
        created_at
    FROM notifications
    WHERE user_id = ? AND is_read = 0)
    ORDER BY created_at DESC
";

if ($stmt = mysqli_prepare($link, $sql)) {
    // Bind the user_id parameter for both parts of the UNION query
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            // Build the final notification array for the frontend
            $notifications[] = [
                'id' => $row['id'],
                'message' => $row['message'],
                'link' => $row['link'],
                'type' => $row['type']
            ];
        }
        $response['success'] = true;
        $response['notifications'] = $notifications;
    } else {
         $response['message'] = 'Failed to execute notification query.';
    }
    mysqli_stmt_close($stmt);
} else {
    $response['message'] = 'Failed to prepare notification query.';
}

mysqli_close($link);
echo json_encode($response);