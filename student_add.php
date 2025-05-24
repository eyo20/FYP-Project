<?php
// Start session and check admin login
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "peer_tutoring_platform";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get and sanitize form data
    $student_name = $conn->real_escape_string($_POST['sname']);
    $faculty = $conn->real_escape_string($_POST['faculty']);
    $programme = $conn->real_escape_string($_POST['programme']);
    $course_code = $conn->real_escape_string($_POST['course_code']);
    
    // Default status for new students
    $status = "OFFLINE";
    
    // Prepare and execute SQL statement
    $sql = "INSERT INTO students (student_name, faculty, programme, course_code, status)
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $student_name, $faculty, $programme, $course_code, $status);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Student added successfully";
    } else {
        $_SESSION['error'] = "Error adding student: " . $stmt->error;
    }
    
    $stmt->close();
    $conn->close();
    
    // Redirect back to admin_student.php
    header("Location: admin_student.php");
    exit();
} else {
    // If someone tries to access this page directly
    header("Location: admin_student.php");
    exit();
}
?>