<?php

include("db_connection.php");


// 从 URL 获取预约编号和金额

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
    <p><strong>预约编号：</strong> </p>
    <p><strong>付款金额：</strong> RM </p>

    <form method="post" action="payment_process.php">
        <input type="hidden" name="booking_id" value="23">
        <input type="hidden" name="amount" value="45">
        <input type="submit" class="btn" value="确认付款">
    </form>
</div>

</body>
</html>
<!-- 3,14,15 这边有问题-->

<!-- 
1.接收到 booking_id 和 amount（从 URL 上，比如 confirm_payment.php?booking_id=5&amount=20）

2.显示确认页面

3.提交表单到 payment_process.php 去完成付款 -->
