<?php
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>付款成功</title>
    <style>
        body {
            text-align: center;
            margin-top: 100px;
            font-family: Arial;
        }
        .success-box {
            display: inline-block;
            padding: 50px;
            border: 2px solid #28a745;
            border-radius: 10px;
            background-color: #e9f8ec;
        }
        .btn {
            margin-top: 20px;
            padding: 10px 30px;
            background-color: #28a745;
            color: white;
            font-size: 16px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
        }
    </style>
</head>
<body>

<div class="success-box">
    <h2>🎉 付款成功！</h2>
    <p>感谢您的付款。我们已记录您的预约。</p>
    <a class="btn" href="my_bookings.php">查看我的预约</a>
</div>

</body>
</html>
<!-- 这里没有问题但是要link去查看booking session -->
