<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "peer_tutoring_platform";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tutor_id = intval($_POST['tutor_id']);
    $student_id = intval($_POST['student_id']);
    $course_id = intval($_POST['course_id']);
    $location_id = intval($_POST['location_id']);
    $duration = floatval($_POST['duration']);
    $selected_date = $_POST['selected_date'];
    $notes = trim($_POST['notes']);

    // Validate inputs
    if (!$student_id || !$tutor_id || !$course_id || !$location_id || !$duration || !$selected_date) {
        header("Location: appointments.php?tutor_id=$tutor_id&error=All fields are required");
        exit;
    }

    // Check session limit
    $session_query = "SELECT COUNT(*) as session_count 
                     FROM session_requests 
                     WHERE tutor_id = ? AND selected_date = ? AND status != 'cancelled'";
    $stmt = $conn->prepare($session_query);
    $stmt->bind_param("is", $tutor_id, $selected_date);
    $stmt->execute();
    $session_result = $stmt->get_result();
    $session_count = $session_result->fetch_assoc()['session_count'];

    if ($session_count >= 3) {
        header("Location: appointments.php?tutor_id=$tutor_id&error=Maximum 3 sessions per day reached");
        exit;
    }

    // Insert into session_requests
    $insert_query = "INSERT INTO session_requests (tutor_id, student_id, course_id, location_id, duration, selected_date, notes, status, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("iiiidss", $tutor_id, $student_id, $course_id, $location_id, $duration, $selected_date, $notes);

    if ($stmt->execute()) {
        header("Location: student_sessions.php?success=Booking request submitted");
    } else {
        header("Location: appointments.php?tutor_id=$tutor_id&error=Failed to submit booking");
    }
}

$conn->close();
