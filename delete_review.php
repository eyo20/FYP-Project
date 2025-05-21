<?php
// Start session and check admin login
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'mine_fyp');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if review ID is provided
if (!isset($_GET['id'])) {
    header("Location: review.php");
    exit();
}

$review_id = $_GET['id'];

// Process deletion if confirmation is received
if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
    $sql = "DELETE FROM reviews WHERE review_id = $review_id";
    
    if ($conn->query($sql) === TRUE) {
        $_SESSION['message'] = "Review deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting review: " . $conn->error;
    }
    
    header("Location: review.php");
    exit();
}

// Get review details for confirmation
$sql = "SELECT * FROM reviews WHERE review_id = $review_id";
$result = $conn->query($sql);
$review = $result->fetch_assoc();

if (!$review) {
    header("Location: review.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Review - PeerLearn</title>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp" rel="stylesheet">
    <link rel="stylesheet" href="adminstyle.css">
</head>
<body>
    <div class="container">
        <aside>
            <!-- Your admin sidebar from review.php -->
        </aside>

        <main>
            <div class="delete-confirmation">
                <h2>Delete Review</h2>
                <div class="confirmation-box">
                    <p>Are you sure you want to delete this review?</p>
                    
                    <div class="review-preview">
                        <h4>Review by <?php echo htmlspecialchars($review['user_name']); ?></h4>
                        <div class="rating">
                            <?php 
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= $review['user_rating'] ? 
                                    '<span class="material-symbols-sharp">star</span>' : 
                                    '<span class="material-symbols-sharp">star_outline</span>';
                            }
                            ?>
                        </div>
                        <p><?php echo htmlspecialchars($review['user_review']); ?></p>
                        <small>Posted on <?php echo date('M j, Y', $review['datetime']); ?></small>
                    </div>
                    
                    <div class="confirmation-actions">
                        <a href="review.php" class="btn cancel">Cancel</a>
                        <a href="delete_review.php?id=<?php echo $review_id; ?>&confirm=yes" class="btn delete">Delete Review</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

<?php $conn->close(); ?>