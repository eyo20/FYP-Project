<?php
session_start();
require_once "db_connection.php";

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];

// Validate POST data
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Invalid request method");
    header("Location: messages_list.php");
    exit();
}

if (!isset($_POST['message']) || !isset($_POST['receiver_id'])) {
    error_log("Missing required fields");
    header("Location: messages_list.php");
    exit();
}

$message = trim($_POST['message']);
$receiver_id = (int)$_POST['receiver_id'];

if (empty($message)) {
    error_log("Empty message content");
    $redirect = ($receiver_id === 0) ? 'community_chat.php' : 'messages.php?user_id=' . $receiver_id;
    header("Location: " . $redirect);
    exit();
}

// For community messages (receiver_id === 0)
if ($receiver_id === 0) {
    // Check for duplicate messages within 5 seconds
    $check_query = "SELECT message_id FROM message 
                   WHERE sender_id = ? 
                   AND content = ? 
                   AND is_community = 1
                   AND sent_datetime > DATE_SUB(NOW(), INTERVAL 5 SECOND)
                   LIMIT 1";
    
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("is", $current_user_id, $message);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        error_log("Duplicate community message detected");
        header("Location: community_chat.php");
        exit();
    }
    
    // Insert community message
    $insert_query = "INSERT INTO message (sender_id, receiver_id, content, sent_datetime, is_read, is_community) 
                    VALUES (?, NULL, ?, NOW(), 0, 1)";
    $stmt = $conn->prepare($insert_query);
    
    if ($stmt->bind_param("is", $current_user_id, $message) && $stmt->execute()) {
        header("Location: community_chat.php");
        exit();
    } else {
        error_log("Failed to send community message: " . $stmt->error);
        die("Failed to send message. Please try again.");
    }
    $stmt->close();
} 
// For private messages
else {
    // Verify receiver exists
    $user_check = "SELECT user_id FROM user WHERE user_id = ?";
    $stmt = $conn->prepare($user_check);
    $stmt->bind_param("i", $receiver_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        error_log("Invalid receiver ID: " . $receiver_id);
        header("Location: messages_list.php");
        exit();
    }
    
    // Insert private message
    $insert_query = "INSERT INTO message (sender_id, receiver_id, content, sent_datetime, is_read, is_community) 
                    VALUES (?, ?, ?, NOW(), 0, 0)";
    $stmt = $conn->prepare($insert_query);
    
    if ($stmt->bind_param("iis", $current_user_id, $receiver_id, $message) && $stmt->execute()) {
        header("Location: messages.php?user_id=" . $receiver_id);
        exit();
    } else {
        error_log("Failed to send private message: " . $stmt->error);
        die("Failed to send message. Please try again.");
    }
    $stmt->close();
}

$conn->close();
?>
