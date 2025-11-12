<?php
require_once 'db_connect.php';
header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['coupon_code'])) {
    $code = trim($_POST['coupon_code']);

    if (empty($code)) {
        $response['message'] = 'Please enter a code.';
        echo json_encode($response);
        exit;
    }

    $sql = "SELECT * FROM coupons WHERE code = ? AND is_active = 1";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $code);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $coupon = mysqli_fetch_assoc($result);

        if ($coupon) {
            // Check 1: Is it expired?
            if ($coupon['expiry_date'] && strtotime($coupon['expiry_date']) < time()) {
                $response['message'] = 'This coupon has expired.';
            } 
            // Check 2: Is it fully used?
            elseif ($coupon['current_usage'] >= $coupon['usage_limit']) {
                $response['message'] = 'This coupon has reached its usage limit.';
            } 
            // Coupon is valid
            else {
                $response['success'] = true;
                $discount_text = $coupon['type'] == 'percent' ? $coupon['value'] . '%' : '₱' . $coupon['value'];
                $response['message'] = 'Success! ' . $discount_text . ' off will be applied by the admin.';
            }
        } else {
            $response['message'] = 'Invalid coupon code.';
        }
        mysqli_stmt_close($stmt);
    }
    mysqli_close($link);
}
echo json_encode($response);
?>