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
$tutors_name = $_POST['tutors_name'];
$faculty = $_POST['faculty'];
$course = $_POST['course'];
$course_code = $_POST['course_code'];
$rating = $_POST['rating'];

// Insert data into database
$sql = "INSERT INTO tutors (tutors_name, faculty, course, course_code, rating)
VALUES ('$tutors_name', '$faculty', '$course', '$course_code', '$rating')";

if ($conn->query($sql) === TRUE) {
    echo "New tutor added successfully";
    // Redirect back to tutors.php
    header("Location: tutors.php");
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>