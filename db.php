<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ProductManagement";

// 连接数据库
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("连接失败: " . $conn->connect_error);
}
?>
