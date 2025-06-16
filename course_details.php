<?php
// Start session and include database connection
session_start();
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

// Get course ID from URL
$course_id = $_GET['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_course'])) {
    // Prepare delete statement
    $delete_sql = "DELETE FROM course WHERE id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $course_id);
    
    if ($stmt->execute()) {
        // Redirect to courses list after successful deletion
        header("Location: admin_course.php?deleted=1");
        exit();
    } else {
        $delete_error = "Error deleting course: " . $conn->error;
    }
}

// Fetch course details
$sql = "SELECT * FROM course WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();
$course = $result->fetch_assoc();

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Details - <?php echo htmlspecialchars($course['course_name'] ?? 'Course'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp" rel="stylesheet">
    <link rel="stylesheet" href="studentstyle.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            margin: 1;
            padding: 1;
            color: #333;
        }
        
        .profile-content {
            flex: 1;
            padding: 30px;
            max-width: 900px;
            margin: 0 auto;
            margin-left: 20px;
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .profile-icon {
            font-size: 60px;
            margin-right: 25px;
            color: #7380ec;
        }
        
        .profile-title h1 {
            margin: 0;
            font-size: 28px;
            color: #363949;
        }
        
        .profile-title p {
            margin: 5px 0 0;
            color: #7d8da1;
            font-size: 16px;
        }
        
        .profile-sections {
            display: grid;
            grid-template-columns: 1fr;
            gap: 25px;
        }
        
        .profile-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .profile-section h2 {
            margin-top: 0;
            color: #7380ec;
            font-size: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .detail-item {
            margin-bottom: 15px;
        }
        
        .detail-item strong {
            display: block;
            color: #7d8da1;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .detail-item p {
            margin: 0;
            font-size: 16px;
        }
        
        .full-width {
            grid-column: span 2;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            margin-top: 30px;
            padding: 10px 20px;
            background: #7380ec;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: background 0.3s;
        }
        
        .back-btn:hover {
            background: #6572ce;
        }
        
        .back-btn .material-symbols-sharp {
            margin-right: 8px;
            font-size: 20px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .status-active {
            background-color: #e3f9e5;
            color: #429e44;
        }
        
        .status-pending {
            background-color: #fff3bf;
            color: #8d6e00;
        }
        
        .status-inactive {
            background-color: #ffe3e3;
            color: #cc0000;
        }

        .delete-btn 
        {
            display: inline-flex;
            align-items: center;
            margin-top: 30px;
            padding: 10px 20px;
            background:rgb(236, 115, 115);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: background 0.3s;
        }
        

    </style>
</head>
<body>
    <div class="container">
        <aside>
            <div class="top">
                <div class="logo">
                    <img src="image/logo.png" alt="PeerLearn Logo">
                    <h2>PEER<span class="danger">LEARN</span></h2>
                </div>
                <div class="close" id="close-btn">
                    <span class="material-symbols-sharp">close</span>
                </div>
            </div>

            <div class="sidebar">
                <a href="admin.html"><span class="material-symbols-sharp">grid_view</span><h3>Dashboard</h3></a>
                <a href="#"></a>
                <a href="admin_student.php"><span class="material-symbols-sharp">person</span><h3>Students</h3></a>
                <a href="admin_tutors.php"><span class="material-symbols-sharp">eyeglasses</span><h3>Tutors</h3></a>
                <a href="admin_course.php" class="active"><span class="material-symbols-sharp">school</span><h3>Courses</h3></a>
                <a href="admin_message.php"><span class="material-symbols-sharp">chat</span><h3>Messages</h3></a>
                <a href="admin_review.php"><span class="material-symbols-sharp">star</span><h3>Reviews</h3></a>
                <a href="home_page.php"><span class="material-symbols-sharp">logout</span><h3>Logout</h3></a>
            </div>
        </aside>

        <div class="profile-content">
            <div class="profile-header">
                <span class="material-symbols-sharp profile-icon">school</span>
                <div class="profile-title">
                    <h1><?php echo htmlspecialchars($course['course_name'] ?? 'Course Name'); ?></h1>
                    <p>Course Details</p>
                </div>
            </div>
            
            <div class="profile-sections">
                <div class="profile-section">
                    <h2>Course Information</h2>
                    <div class="detail-item">
                        <strong>Course Name</strong>
                        <p><?php echo htmlspecialchars($course['course_name'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="detail-item">
                        <strong>Status</strong>
                        <p>
                            <span class="status-badge status-<?php echo strtolower(htmlspecialchars($course['status'] ?? 'pending')); ?>">
                                <?php echo htmlspecialchars($course['status'] ?? 'Pending'); ?>
                            </span>
                        </p>
                    </div>
                    <div class="detail-item">
                        <strong>Date Added</strong>
                        <p><?php echo htmlspecialchars($course['created_at'] ?? 'N/A'); ?></p>
                    </div>
                </div>
                
                <div class="profile-section">
                    <h2>Course Description</h2>
                    <div class="detail-item">
                        <p><?php echo nl2br(htmlspecialchars($course['details'] ?? 'No description provided')); ?></p>
                    </div>
                </div>
                <div class="profile-section">
                    <h2>Delete Course</h2>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this course? This action cannot be undone.');">
                        <div class="delete-confirmation">
                            <p>Warning: Deleting this course will permanently remove it from the system.</p>
                            <button type="submit" name="delete_course" class="delete-btn">
                                <span class="material-symbols-sharp">delete</span>
                                Delete This Course
                            </button>
                        </div>
                    </form>
                    <?php if (isset($delete_error)): ?>
                        <div class="error-message"><?php echo htmlspecialchars($delete_error); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            
            <a href="admin_course.php" class="back-btn">
                <span class="material-symbols-sharp">arrow_back</span>
                Back to Courses List
            </a>
        </div>
    </div>
</body>
</html>