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

// Get student ID from URL
$student_id = $_GET['id'] ?? 0;

// Fetch student details
$sql = "SELECT * FROM students WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile - <?php echo htmlspecialchars($student['student_name'] ?? 'Student'); ?></title>
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
            grid-template-columns: 1fr 1fr;
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
                <a href="admin.php"><span class="material-symbols-sharp">grid_view</span><h3>Dashboard</h3></a>
                <a href="#"></a>
                <a href="admin_student.php" class="active"><span class="material-symbols-sharp">person</span><h3>Students</h3></a>
                <a href="admin_tutors.php"><span class="material-symbols-sharp">eyeglasses</span><h3>Tutors</h3></a>
                <a href="admin_course.php"><span class="material-symbols-sharp">school</span><h3>Courses</h3></a>
                <a href="message.php"><span class="material-symbols-sharp">chat</span><h3>Messages</h3><span class="message-count">26</span></a>
                <a href="session.php"><span class="material-symbols-sharp">library_books</span><h3>Session</h3></a>
                <a href="admin_review.php"><span class="material-symbols-sharp">star</span><h3>Reviews</h3></a>
                <a href="sales.php"><span class="material-symbols-sharp">finance</span><h3>Sales</h3></a>
                <a href="home_page.php"><span class="material-symbols-sharp">logout</span><h3>Logout</h3></a>
            </div>
        </aside>

        <div class="profile-content">
            <div class="profile-header">
                <span class="material-symbols-sharp profile-icon">account_circle</span>
                <div class="profile-title">
                    <h1><?php echo htmlspecialchars($student['student_name'] ?? 'Student Name'); ?></h1>
                    <p>Student Profile</p>
                </div>
            </div>
            
            <div class="profile-sections">
                <div class="profile-section">
                    <h2>Academic Information</h2>
                    <div class="detail-item">
                        <strong>Level</strong>
                        <p><?php echo htmlspecialchars($student['level'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="detail-item">
                        <strong>Program</strong>
                        <p><?php echo htmlspecialchars($student['program'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="detail-item">
                        <strong>Course</strong>
                        <p><?php echo htmlspecialchars($student['course'] ?? 'N/A'); ?></p>
                    </div>
                </div>
                
                <div class="profile-section">
                    <h2>Contact Information</h2>
                    <div class="detail-item">
                        <strong>Email</strong>
                        <p><?php echo htmlspecialchars($student['email'] ?? 'Not provided'); ?></p>
                    </div>
                    <div class="detail-item">
                        <strong>Phone</strong>
                        <p><?php echo htmlspecialchars($student['phone'] ?? 'Not provided'); ?></p>
                    </div>
                    <div class="detail-item">
                        <strong>Learning Goals</strong>
                        <p><?php echo htmlspecialchars($student['learning_goals'] ?? 'Not specified'); ?></p>
                    </div>
                </div>
                
                <div class="profile-section full-width">
                    <h2>Additional Information</h2>
                    <div class="detail-item">
                        <p><?php echo nl2br(htmlspecialchars($student['additional_info'] ?? 'No additional information provided')); ?></p>
                    </div>
                </div>
            </div>
            
            <a href="admin_student.php" class="back-btn">
                <span class="material-symbols-sharp">arrow_back</span>
                Back to Students List
            </a>
        </div>
    </div>
</body>
</html>