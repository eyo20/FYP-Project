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
$user_name = $_POST['user_name'];
$user_rating = $_POST['user_rating'];
$user_review = $_POST['user_review'];
$datetime = time(); // Current timestamp

// Insert data into database
$sql = "INSERT INTO reviews (user_name, user_rating, user_review, datetime)
VALUES ('$user_name', '$user_rating', '$user_review', '$datetime')";

if ($conn->query($sql) === TRUE) {
    echo "New review added successfully";
    // Redirect back to review.php
    header("Location: review.php");
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>