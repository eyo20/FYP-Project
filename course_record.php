<?php
// Database connection
$servername = "localhost";
$username = "root"; 
$password = ""; //
$dbname = "peer_tutoring_platform";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form data
$course_name = $_POST['course_name'];

// Insert data into database
$sql = "INSERT INTO course (course_name)
VALUES ('$course_name')";

if ($conn->query($sql) === TRUE) {
    echo "New course added successfully";
    // Redirect back to admin_course.php
    header("Location: admin_course.php");
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>