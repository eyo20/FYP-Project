<?php
session_start();
require_once "db_connection.php";

if (!isset($_SESSION['user_id']) || !isset($_GET['receiver_id'])) {
    header("HTTP/1.1 400 Bad Request");
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];
$other_user_id = (int)$_GET['receiver_id'];

// Check for new messages
$query = "SELECT COUNT(*) as count FROM message 
          WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
          AND sent_datetime > (SELECT MAX(sent_datetime) FROM message 
          WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))";
$stmt = $conn->prepare($query);
$stmt->bind_param("iiiiiiii", $other_user_id, $current_user_id, $current_user_id, $other_user_id,
                 $other_user_id, $current_user_id, $current_user_id, $other_user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

header('Content-Type: application/json');
echo json_encode(['new_messages' => $row['count'] > 0]);

$stmt->close();
$conn->close();
?>