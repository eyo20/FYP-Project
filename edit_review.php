<?php
// Start session and check admin login
session_start();
if (!isset($_SESSION['admin_logged_in']) {
    header("Location: admin_login.php");
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'mine_fyp');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if review ID is provided
if (!isset($_GET['id']) {
    header("Location: review.php");
    exit();
}

$review_id = $_GET['id'];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_name = $conn->real_escape_string($_POST['user_name']);
    $user_rating = intval($_POST['user_rating']);
    $user_review = $conn->real_escape_string($_POST['user_review']);
    
    // Update review in database
    $sql = "UPDATE reviews SET 
            user_name = '$user_name',
            user_rating = $user_rating,
            user_review = '$user_review'
            WHERE review_id = $review_id";
    
    if ($conn->query($sql) === TRUE) {
        $_SESSION['message'] = "Review updated successfully";
        header("Location: review.php");
        exit();
    } else {
        $error = "Error updating review: " . $conn->error;
    }
}

// Get current review data
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
    <title>Edit Review - PeerLearn</title>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp" rel="stylesheet">
    <link rel="stylesheet" href="adminstyle.css">
</head>
<body>
    <div class="container">
        <aside>
            <!-- Your admin sidebar from review.php -->
        </aside>

        <main>
            <div class="edit-review-form">
                <h2>Edit Review #<?php echo $review_id; ?></h2>
                
                <?php if (isset($error)): ?>
                    <div class="alert error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="user_name">User Name:</label>
                        <input type="text" id="user_name" name="user_name" 
                               value="<?php echo htmlspecialchars($review['user_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="user_rating">Rating:</label>
                        <select id="user_rating" name="user_rating" required>
                            <option value="1" <?php echo $review['user_rating'] == 1 ? 'selected' : ''; ?>>1 Star</option>
                            <option value="2" <?php echo $review['user_rating'] == 2 ? 'selected' : ''; ?>>2 Stars</option>
                            <option value="3" <?php echo $review['user_rating'] == 3 ? 'selected' : ''; ?>>3 Stars</option>
                            <option value="4" <?php echo $review['user_rating'] == 4 ? 'selected' : ''; ?>>4 Stars</option>
                            <option value="5" <?php echo $review['user_rating'] == 5 ? 'selected' : ''; ?>>5 Stars</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="user_review">Review:</label>
                        <textarea id="user_review" name="user_review" required><?php 
                            echo htmlspecialchars($review['user_review']); 
                        ?></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <a href="review.php" class="btn cancel">Cancel</a>
                        <button type="submit" class="btn save">Save Changes</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>

<?php $conn->close(); ?>