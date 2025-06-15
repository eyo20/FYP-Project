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
    <title>Tutor Management System</title>
    <style>
        body {
            font-family: poppins, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        
        .current_students {
            padding: 20px;
            background: white;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin: 20px;
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
