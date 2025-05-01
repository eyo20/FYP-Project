<?php
require_once 'db_connection.php';

header('Content-Type: application/json');

if (!isset($_GET['subject_id']) || empty($_GET['subject_id'])) {
    echo json_encode([]);
    exit;
}

$subject_id = intval($_GET['subject_id']);

$query = "SELECT course_id, course_name, course_code FROM course WHERE subject_id = ? ORDER BY course_name";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$result = $stmt->get_result();

$courses = [];
while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}

echo json_encode($courses);
$conn->close();
