<?php
session_start();
include("db_connect.php");

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['id'];
$booking_id = $_POST['booking_id'];
$amount = $_POST['amount'];

// 插入付款记录
$sql = "INSERT INTO payments (user_id, booking_id, amount, payment_status, created_at)
        VALUES ('$user_id', '$booking_id', '$amount', 'Paid', NOW())";
mysqli_query($conn, $sql);

// 更新预约记录为已付款
$update = "UPDATE bookings SET is_paid = 1 WHERE id = '$booking_id'";
mysqli_query($conn, $update);

// 跳转到成功页面
header("Location: payment_success.php?booking_id=$booking_id");
exit();
?>
