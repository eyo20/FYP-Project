<?php
// Database connection setup
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

// Process actions if any
$message = '';
if (isset($_GET['id']) && isset($_GET['action'])) {
    $student_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($student_id > 0 && in_array($action, ['approve', 'reject'])) {
        if ($action === 'approve') {
            $sql = "UPDATE studentprofile SET status = 'approved' WHERE user_id = ?";
        } elseif ($action === 'reject') {
            $sql = "UPDATE studentprofile SET status = 'rejected' WHERE user_id = ?";
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $student_id);
        
        if ($stmt->execute()) {
            $message = "Action completed successfully!";
            // Refresh the page to show updated status
            header("Location: ".$_SERVER['PHP_SELF']);
            exit();
        } else {
            $message = "Error: " . $conn->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp" rel="stylesheet">
    <title>Student Management System</title>
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
        body {
            font-family: poppins, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        
        .current_students {
            padding: 50px;
            background: white;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin: 0px;
            width: 80%;
            height: 80%;
        }
        
        .tab-container {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            background: #f1f1f1;
            margin-right: 5px;
            border-radius: 5px 5px 0 0;
        }
        
        .tab.active {
            background: #7380ec;
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table, th, td {
            border: 1px solid #ddd;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
        }
        
        th {
            background-color: #7380ec;
            color: white;
        }
        
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        
        .details-btn, .approve-btn, .reject-btn {
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 3px;
            font-size: 14px;
        }
        
        .details-btn {
            background-color: #2196F3;
            color: white;
        }
        
        .approve-btn {
            background-color: #4CAF50;
            color: white;
        }
        
        .reject-btn {
            background-color: #f44336;
            color: white;
        }
        
        .status-badge {
            background-color: #4CAF50;
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 14px;
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
        
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            background-color: #dff0d8;
            color: #3c763d;
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
                <a href="admin_staff.php"><span class="material-symbols-sharp">badge</span><h3>Staff</h3></a>
                <a href="admin_student.php" class="active"><span class="material-symbols-sharp">person</span><h3>Students</h3></a>
                <a href="admin_tutors.php"><span class="material-symbols-sharp">eyeglasses</span><h3>Tutors</h3></a>
                <a href="admin_course.php"><span class="material-symbols-sharp">school</span><h3>Courses</h3></a>
                <a href="admin_message.php"><span class="material-symbols-sharp">chat</span><h3>Messages</h3></a>
               <a href="admin_report.php"><span class="material-symbols-sharp">description</span><h3>Reports</h3></a>
                <a href="home_page.html"><span class="material-symbols-sharp">logout</span><h3>Logout</h3></a>
            </div>
        </aside>
        
    <div class="current_students">
        <?php if (!empty($message)): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <h2>Students Management</h2>
        
        <!-- Tab Navigation -->
        <div class="tab-container">
            <div class="tab active" onclick="showTab('approved')">Approved Students</div>
            <div class="tab" onclick="showTab('pending')">Pending Approval</div>
            <div class="tab" onclick="showTab('rejected')">Rejected Students</div>
        </div>
        
        <!-- Approved Students Tab -->
        <div id="approved-tab" class="tab-content active">
            <h3>Approved Students</h3>
            <table>
                <thead>
                    <tr>
                        <th>STUDENT NAME</th>
                        <th>LEVEL</th>
                        <th>PROGRAM</th>
                        <th>COURSE</th>
                        <th>DETAILS</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT sp.*, u.username 
                            FROM studentprofile sp
                            JOIN user u ON sp.user_id = u.user_id
                            WHERE sp.status = 'approved'";
                    $result = $conn->query($sql);
                    
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>
                                <td>".htmlspecialchars($row["username"])."</td>
                                <td>".htmlspecialchars($row["year"])."</td>
                                <td>".htmlspecialchars($row["program"])."</td>
                                <td>".htmlspecialchars($row["major"])."</td>
                                <td><a href='student_details.php?id=".$row["user_id"]."' class='details-btn'>Details</a></td>
                                <td>
                                    <a href='?id=".$row["user_id"]."&action=reject' class='reject-btn' onclick='return confirm(\"Are you sure you want to reject this student?\");'>Remove</a>
                                </td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6'>No approved students found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pending Students Tab -->
        <div id="pending-tab" class="tab-content">
            <h3>Pending Approval</h3>
            <table>
                <thead>
                    <tr>
                        <th>STUDENT NAME</th>
                        <th>LEVEL</th>
                        <th>PROGRAM</th>
                        <th>COURSE</th>
                        <th>DETAILS</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT sp.*, u.username 
                            FROM studentprofile sp
                            JOIN user u ON sp.user_id = u.user_id
                            WHERE sp.status = 'pending'";
                    $result = $conn->query($sql);
                    
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>
                                <td>".htmlspecialchars($row["username"])."</td>
                                <td>".htmlspecialchars($row["year"])."</td>
                                <td>".htmlspecialchars($row["program"])."</td>
                                <td>".htmlspecialchars($row["major"])."</td>
                                <td><a href='student_details.php?id=".$row["user_id"]."' class='details-btn'>Details</a></td>
                                <td>
                                    <a href='?id=".$row["user_id"]."&action=approve' class='approve-btn'>Approve</a>
                                    <a href='?id=".$row["user_id"]."&action=reject' class='reject-btn' onclick='return confirm(\"Are you sure you want to reject this student?\");'>Reject</a>
                                </td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6'>No pending students found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <!-- Rejected Students Tab -->
        <div id="rejected-tab" class="tab-content">
            <h3>Rejected Students</h3>
            <table>
                <thead>
                    <tr>
                        <th>STUDENT NAME</th>
                        <th>LEVEL</th>
                        <th>PROGRAM</th>
                        <th>COURSE</th>
                        <th>DETAILS</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT sp.*, u.username 
                            FROM studentprofile sp
                            JOIN user u ON sp.user_id = u.user_id
                            WHERE sp.status = 'rejected'";
                    $result = $conn->query($sql);
                    
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>
                                <td>".htmlspecialchars($row["username"])."</td>
                                <td>".htmlspecialchars($row["year"])."</td>
                                <td>".htmlspecialchars($row["program"])."</td>
                                <td>".htmlspecialchars($row["major"])."</td>
                                <td><a href='student_details.php?id=".$row["user_id"]."' class='details-btn'>Details</a></td>
                                <td>
                                    <span class='status-badge' style='background-color:#f44336;'>Rejected</span>
                                    <a href='?id=".$row["user_id"]."&action=approve' class='approve-btn'>Approve</a>
                                </td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6'>No rejected students found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <a href="admin_student.php" class="back-btn">
            Back to Students List
        </a>
    </div>
    
    <script>
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            document.querySelector(`.tab[onclick="showTab('${tabName}')"]`).classList.add('active');
        }
    </script>
</body>
</html>
