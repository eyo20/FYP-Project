<?php
// Database connection setup (should be in a separate config file in production)
$servername = "localhost";
$username = "root"; // Replace with your MySQL username
$password = ""; // Replace with your MySQL password
$dbname = "peer_tutoring_platform";   // Replace with your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
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
        
    </style>
</head>
<body>
    <div class="current_students">
        <h2>Students Management</h2>
        
        <!-- Tab Navigation -->
        <div class="tab-container">
            <div class="tab active" onclick="showTab('approved')">Approved Students</div>
            <div class="tab" onclick="showTab('pending')">Pending Approval</div>
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
                    $sql = "SELECT * FROM students WHERE status = 'approved'";
                    $result = $conn->query($sql);
                    
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>
                                <td>".htmlspecialchars($row["student_name"])."</td>
                                <td>".htmlspecialchars($row["level"])."</td>
                                <td>".htmlspecialchars($row["program"])."</td>
                                <td>".htmlspecialchars($row["course"])."</td>
                                <td><a href='student_details.php?id=".$row["id"]."' class='details-btn'>Details</a></td>
                                <td>
                                    <span class='status-badge'>Approved</span>
                                    <a href='student_action.php?id=".$row["id"]."&action=reject' class='reject-btn'>Remove</a>
                                </td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6'>No approved tutors found</td></tr>";
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
                        <th>STUDENTS NAME</th>
                        <th>LEVEL</th>
                        <th>PROGRAM</th>
                        <th>COURSE</th>
                        <th>DETAILS</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT * FROM students WHERE status = 'pending'";
                    $result = $conn->query($sql);
                    
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>
                                <td>".htmlspecialchars($row["student_name"])."</td>
                                <td>".htmlspecialchars($row["level"])."</td>
                                <td>".htmlspecialchars($row["program"])."</td>
                                <td>".htmlspecialchars($row["course"])."</td>
                                <td><a href='student_details.php?id=".$row["id"]."' class='details-btn'>Details</a></td>
                                <td>
                                     <a href='student_action.php?id=".$row["id"]."&action=approve' class='approve-btn'>Approve</a>
                                    <a href='student_action.php?id=".$row["id"]."&action=delete' class='reject-btn' onclick='return confirm(\"Are you sure you want to delete this student?\");'>Delete</a>
                                </td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6'>No pending student found</td></tr>";
                    }
                    
                    if ($conn->connect_error) {
                    die("Connection failed: " . $conn->connect_error);
                }

                // Get the tutor ID and action from the URL
                $student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
                $action = isset($_GET['action']) ? $_GET['action'] : '';

                if ($student_id > 0 && in_array($action, ['approve', 'delete'])) {
                    if ($action === 'approve') {
                        // Update the tutor's status to 'approved'
                        $sql = "UPDATE tutors SET status = 'approved' WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $tutor_id);
                        
                        if ($stmt->execute()) {
                            $message = "Student approved successfully!";
                        } else {
                            $message = "Error approving tutor: " . $conn->error;
                        }
                        $stmt->close();
                    } elseif ($action === 'delete') {
                        // Delete the student record
                        $sql = "DELETE FROM students WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $student_id);
                        
                        if ($stmt->execute()) {
                            $message = "Student deleted successfully!";
                        } else {
                            $message = "Error deleting tutor: " . $conn->error;
                        }
                        $stmt->close();
                    }
                } else {
                    $message = "Invalid request!";
                }

                    ?>

                    
                </tbody>
            </table>
        </div>
    </div>

    <?php $conn->close(); ?>
    
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
            <a href="admin_student.php" class="back-btn">
                Back to Students List
            </a>
</html>