<?php
session_start();
require_once "db_connection.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];

// Validate POST data
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: messages_list.php");
    exit();
}

if (!isset($_POST['message']) || !isset($_POST['receiver_id'])) {
    header("Location: messages_list.php");
    exit();
}

$message = trim($_POST['message']);
$receiver_id = (int)$_POST['receiver_id'];

if (empty($message) || $receiver_id <= 0) {
    header("Location: student_messages.php?user_id=" . $receiver_id);
    exit();
}

// Insert message
$insert_query = "INSERT INTO message (sender_id, receiver_id, content, sent_datetime, is_read) 
                VALUES (?, ?, ?, NOW(), 0)";
$stmt = $conn->prepare($insert_query);
$stmt->bind_param("iis", $current_user_id, $receiver_id, $message);
$stmt->execute();
$stmt->close();

// Redirect back to chat
header("Location: student_messages.php?user_id=" . $receiver_id);
exit();
?>