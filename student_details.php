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
      :root {
            --primary: #7380ec;
            --danger: #ff7782;
            --success: #41f1b6;
            --warning: #ffbb55;
            --white: #fff;
            --info-dark: #7d8da1;
            --info-light: #dce1eb;
            --dark: #363949;
            --light: rgba(132, 139, 200, 0.18);
            --primary-variant: #111e88;
            --dark-variant: #677483;
            --color-background: #f6f6f9;
            
            --card-border-radius: 2rem;
            --border-radius-1: 0.4rem;
            --border-radius-2: 0.8rem;
            --border-radius-3: 1.2rem;
            
            --card-padding: 1.8rem;
            --padding-1: 1.2rem;
            
            --box-shadow: 0 2rem 3rem var(--light);
        }
        
        * {
            margin: 0;
            padding: 0;
            outline: 0;
            appearance: none;
            border: 0;
            text-decoration: none;
            list-style: none;
            box-sizing: border-box;
        }
        
        html {
            font-size: 14px;
        }
        
        body {
            margin: 0;
            padding: 0;
        }
        
        
       .container {
            width: 100%;
            margin: 0;
            gap: 1.8rem;
            grid-template-columns: 14rem auto 23rem;
        }
        

        a{
            color: #363949;
        }

        img {
            display: block;
            width: 100%;
        }

        h1{
            font-weight: 800;
            font-size: 1.8rem;
        }

        h2{
            font-size: 1.4rem;
        }

        h3{
            font-size: 0.87rem;
        }

        h4{
            font-size: 0.8rem;
        }

        h5{
            font-size: 0.77rem;
        }

        small {
            font-size: 0.75rem;
        }

        .profile-photo{
            width: 2.8rem;
            height: 2.8rem;
            border-radius: 50%;
            overflow: hidden;
        }

        .text-muted {
            color: #dce1eb;
        }

        p{
            color:#677483;
        }

        b{
            color: #363949;
        }
        .primary{
            color: #7380ec;
        }
        .danger{
            color: #ff7782;
        }
        .success{
            color: #41f1b6;
        }
        .warning{
            color: #ffbb55;
        }


        aside {
            width: 210px;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            height: 100vh;
            margin-left: 0;
            padding-left: 0;
            left: 0;
        }


        aside .top {
            margin-left: 0;
            padding-left: 1rem;
        }

        aside .logo {
            display: flex;
            gap: 0.8rem;
        }

        aside .logo img{
            width: 2rem;
            height: 2rem;
        }

        aside .close{
            display: none;
        }

        /* ======================== Side Bar ================================ */
       aside .sidebar {
            margin-left: 0;
            padding-left: 0;
        }


        aside h3 {
            font-weight: 500;
        }

        aside .sidebar a{
            display: flex;
            color:  #7d8da1;
            margin-left: 2rem;
            gap: 1rem;
            align-items: center;
            position: relative;
            height: 3.7rem;
            transition: all 300ms ease;
        }

        aside .sidebar a span{
            font-size: 1.6rem;
            transition: all 300ms ease;
        }

        aside .sidebar  a:last-child{
            position: absolute;
            bottom: 2rem;
            width: 100%;

        }

        aside .sidebar a.active {
            background: rgba(132, 139, 200, 0.18);
            color: #7380ec;
            margin-left: 0;
        }

        aside .sidebar a.active:before{
            content: "";
            width: 6px;
            height: 100%;
            background: #7380ec;

        }

        aside .sidebar a.active span{
            color: #7380ec;
            margin-left: calc(1rem -3 px);
        }

        aside .sidebar a:hover {
            color: #7380ec;
        }

        aside .sidebar a:hover span{
            margin-left: 1rem;
        }

        aside .sidebar .message-count {
            background: #ff7782;
            color: #fff;
            padding: 2px 10px;
            font-size: 11px;
            border-radius: 0.4rem;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }
        
        .profile-content {
            flex: 1;
            padding: 2rem;
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .profile-icon {
            font-size: 3rem;
            margin-right: 1.5rem;
            color: #7380ec;
        }
        
        .profile-title h1 {
            margin: 0;
            font-size: 1.8rem;
        }
        
        .profile-section {
            background: white;
            border-radius: 0.8rem;
            padding: 2rem;
            box-shadow: 0 0.2rem 0.5rem rgba(0,0,0,0.1);
            margin-bottom: 2rem;
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
                <a></a>
                <a href="admin_staff.php"><span class="material-symbols-sharp">badge</span><h3>Staff</h3></a>
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