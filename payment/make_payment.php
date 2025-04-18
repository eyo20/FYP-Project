<?php
session_start();
include("db_connect.php");

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['id'];
$user_email = $_SESSION['email'];

// 从 URL 获取预约编号和金额
$booking_id = $_GET['booking_id'];
$amount = $_GET['amount'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>确认付款 - Peer Tutoring</title>
    <style>
        body {
            font-family: Arial;
            text-align: center;
            margin-top: 50px;
        }
        .box {
            border: 1px solid #ccc;
            padding: 40px;
            display: inline-block;
            border-radius: 10px;
        }
        .btn {
            background-color: #0071e3;
            color: white;
            padding: 15px 40px;
            font-size: 18px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #005bb5;
        }
    </style>
</head>
<body>

<h2>预约付款确认</h2>

<div class="box">
    <p><strong>预约编号：</strong> <?php echo $booking_id; ?></p>
    <p><strong>付款金额：</strong> RM <?php echo number_format($amount, 2); ?></p>

    <form method="post" action="payment_process.php">
        <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
        <input type="hidden" name="amount" value="<?php echo $amount; ?>">
        <input type="submit" class="btn" value="确认付款">
    </form>
</div>

</body>
</html>
<!-- 3,14,15 这边有问题-->
