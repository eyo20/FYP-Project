<?php
// student_details.php
session_start();

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

// Check if ID parameter exists
if(isset($_GET['id']) && !empty($_GET['id'])) {
    $user_id = $conn->real_escape_string($_GET['id']);
    
    // Query to get student details with email and phone from user table
    $sql = "SELECT sp.*,u.username, u.email, u.phone 
            FROM studentprofile sp
            JOIN user u ON sp.user_id = u.user_id
            WHERE sp.user_id = '$user_id'";
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile - <?php echo htmlspecialchars($student['username']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp" rel="stylesheet">
    <link rel="stylesheet" href="studentstyle.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            padding: 0;
            color: #333;
        }
        
        .container {
            display: flex;
            min-height: 100vh;
        }
        
        aside {
            width: 250px;
            background: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px;
        }
        
        .logo {
            display: flex;
            align-items: center;
        }
        
        .logo img {
            width: 40px;
            height: 40px;
            margin-right: 10px;
        }
        
        .logo h2 {
            font-size: 18px;
        }
        
        .danger {
            color: #ff7782;
        }
        
        .sidebar {
            padding: 20px;
        }
        
        .sidebar a {
            display: flex;
            align-items: center;
            color: #7d8da1;
            padding: 12px 10px;
            margin-bottom: 5px;
            text-decoration: none;
            transition: all 0.3s;
            border-radius: 6px;
        }
        
        .sidebar a.active {
            background: rgba(115, 128, 236, 0.1);
            color: #7380ec;
        }
        
        .sidebar a:hover:not(.active) {
            background: #f6f6f9;
        }
        
        .sidebar .material-symbols-sharp {
            margin-right: 10px;
            font-size: 22px;
        }
        
        .sidebar h3 {
            font-size: 15px;
            font-weight: 500;
            margin: 0;
        }
        
        .message-count {
            background: #ff7782;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
            margin-left: auto;
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
                .profile-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .edit-btn {
            display: inline-flex;
            align-items: center;
            padding: 10px 20px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: background 0.3s;
        }

        .edit-btn:hover {
            background: #3e8e41;
        }

        .edit-btn .material-symbols-sharp {
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
                <a href="admin.html"><span class="material-symbols-sharp">grid_view</span><h3>Dashboard</h3></a>
                <a href="admin_student.php" class="active"><span class="material-symbols-sharp">person</span><h3>Students</h3></a>
                <a href="admin_tutors.php"><span class="material-symbols-sharp">eyeglasses</span><h3>Tutors</h3></a>
                <a href="admin_course.php"><span class="material-symbols-sharp">school</span><h3>Courses</h3></a>
                <a href="admin_message.php"><span class="material-symbols-sharp">chat</span><h3>Messages</h3></a>
               <a href="admin_report.php"><span class="material-symbols-sharp">description</span><h3>Reports</h3></a>
                <a href="home_page.html"><span class="material-symbols-sharp">logout</span><h3>Logout</h3></a>
            </div>
        </aside>

         <div class="profile-content">
            <div class="profile-header">
            <span class="material-symbols-sharp profile-icon">account_circle</span>
            <div class="profile-title">
                <h1><?php echo htmlspecialchars($student['username']); ?></h1>
                <p>Student Profile</p>
            </div>
            </div>
            
            <div class="profile-sections">
                <div class="profile-section">
                    <h2>Academic Information</h2>
                    <div class="detail-item">
                        <strong>Level</strong>
                        <p><?php echo htmlspecialchars($student['year']); ?></p>
                    </div>
                    <div class="detail-item">
                        <strong>Program</strong>
                        <p><?php echo htmlspecialchars($student['program']); ?></p>
                    </div>
                    <div class="detail-item">
                        <strong>Major</strong>
                        <p><?php echo htmlspecialchars($student['major']); ?></p>
                    </div>
                </div>
                
                <div class="profile-section">
                    <h2>Contact Information</h2>
                    <div class="detail-item">
                        <strong>Email</strong>
                        <p><?php echo !empty($student['email']) ? htmlspecialchars($student['email']) : 'N/A'; ?></p>
                    </div>
                    <div class="detail-item">
                        <strong>Phone</strong>
                        <p><?php echo !empty($student['phone']) ? htmlspecialchars($student['phone']) : 'N/A'; ?></p>
                        
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

<?php
    } else {
        echo "Student not found";
    }
} else {
    echo "No student ID specified";
}

$conn->close();
?>