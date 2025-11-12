<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');
$response = [
    'success' => false,
    'pending_reservations' => 0,
    'new_messages' => 0,
    'reservations_html' => '',
    'messages_html' => ''
];

// MODIFIED: Updated authorization to allow managers
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['owner', 'manager'])) {
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}


// Get count of pending reservations
$sql_res = "SELECT COUNT(*) as count FROM reservations WHERE status = 'Pending' AND deleted_at IS NULL";
$result_res = mysqli_query($link, $sql_res);
if ($row_res = mysqli_fetch_assoc($result_res)) {
    $response['pending_reservations'] = (int)$row_res['count'];
}

// Get count of new messages (not yet replied to)
$sql_msg = "SELECT COUNT(*) as count FROM contact_messages WHERE replied_at IS NULL AND deleted_at IS NULL";
$result_msg = mysqli_query($link, $sql_msg);
if ($row_msg = mysqli_fetch_assoc($result_msg)) {
    $response['new_messages'] = (int)$row_msg['count'];
}

// Get latest 5 pending reservations for dropdown
$sql_res_details = "SELECT reservation_id, res_name, res_date FROM reservations WHERE status = 'Pending' AND deleted_at IS NULL ORDER BY created_at DESC LIMIT 5";
$res_html = '<div class="admin-dropdown-header">Pending Reservations</div>';
$result_res_details = mysqli_query($link, $sql_res_details);
if (mysqli_num_rows($result_res_details) > 0) {
    while($row = mysqli_fetch_assoc($result_res_details)) {
        $res_html .= '<div class="admin-dropdown-item-wrapper">';
        $res_html .= '  <a href="reservation.php" class="admin-dropdown-item">';
        $res_html .= '      <strong>' . htmlspecialchars($row['res_name']) . '</strong><br>';
        $res_html .= '      <small>For ' . htmlspecialchars($row['res_date']) . '</small>';
        $res_html .= '  </a>';
        $res_html .= '  <button class="admin-notification-dismiss" data-id="' . $row['reservation_id'] . '" data-type="reservation" title="Dismiss">&times;</button>';
        $res_html .= '</div>';
    }
    $res_html .= '<a href="reservation.php" class="admin-dropdown-footer">View All Reservations</a>';
} else {
    $res_html .= '<div class="admin-dropdown-empty">No new reservations.</div>';
}
$response['reservations_html'] = $res_html;


// Get latest 5 new messages for dropdown
$sql_msg_details = "SELECT id, name, subject FROM contact_messages WHERE replied_at IS NULL AND deleted_at IS NULL ORDER BY created_at DESC LIMIT 5";
$msg_html = '<div class="admin-dropdown-header">New Messages</div>';
$result_msg_details = mysqli_query($link, $sql_msg_details);
if (mysqli_num_rows($result_msg_details) > 0) {
    while($row = mysqli_fetch_assoc($result_msg_details)) {
        $msg_html .= '<div class="admin-dropdown-item-wrapper">';
        $msg_html .= '  <a href="notification_control.php" class="admin-dropdown-item">';
        $msg_html .= '      <strong>From: ' . htmlspecialchars($row['name']) . '</strong><br>';
        $msg_html .= '      <small>' . htmlspecialchars(substr($row['subject'], 0, 35)) . '...</small>';
        $msg_html .= '  </a>';
        $msg_html .= '  <button class="admin-notification-dismiss" data-id="' . $row['id'] . '" data-type="message" title="Dismiss">&times;</button>';
        $msg_html .= '</div>';
    }
     $msg_html .= '<a href="notification_control.php" class="admin-dropdown-footer">View All Messages</a>';
} else {
    $msg_html .= '<div class="admin-dropdown-empty">No new messages.</div>';
}
$response['messages_html'] = $msg_html;

$response['success'] = true;
mysqli_close($link);
echo json_encode($response);
?>