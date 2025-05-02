<?php
require_once 'db_connection.php';

header('Content-Type: application/json');

if (isset($_GET['programme_id']) && is_numeric($_GET['programme_id'])) {
    $programme_id = $_GET['programme_id'];
    
    $query = "SELECT * FROM course WHERE programme_id = ? ORDER BY course_name";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $programme_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $courses = [];
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
    
    echo json_encode($courses);
} else {
    echo json_encode([]);
}

$conn->close();
?>
