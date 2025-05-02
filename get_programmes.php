<?php
require_once 'db_connection.php';

header('Content-Type: application/json');

if (isset($_GET['subject_id']) && is_numeric($_GET['subject_id'])) {
    $subject_id = $_GET['subject_id'];
    
    $query = "SELECT * FROM programme WHERE subject_id = ? ORDER BY programme_name";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $programmes = [];
    while ($row = $result->fetch_assoc()) {
        $programmes[] = $row;
    }
    
    echo json_encode($programmes);
} else {
    echo json_encode([]);
}

$conn->close();
?>
