<?php
// Database connection
$servername = "localhost";
$username = "root"; // replace with your MySQL username
$password = ""; // replace with your MySQL password
$dbname = "mine_fyp";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form data
$faculty = $_POST['faculty'];
$programme = $_POST['programme'];
$course_code = $_POST['course_code'];
$requirement = $_POST['requirement'];

// Insert data into database
$sql = "INSERT INTO courses (faculty, programme, course_code, requirement)
VALUES ('$faculty', '$programme', '$course_code', '$requirement')";

if ($conn->query($sql) === TRUE) {
    echo "New course added successfully";
    // Redirect back to course.php
    header("Location: course.php");
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>