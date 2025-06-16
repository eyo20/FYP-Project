<?php
session_start();
// Display messages if they exist
if (isset($_SESSION['message'])) {
    echo '<div class="alert success">' . $_SESSION['message'] . '</div>';
    unset($_SESSION['message']);
}
if (isset($_SESSION['error'])) {
    echo '<div class="alert error">' . $_SESSION['error'] . '</div>';
    unset($_SESSION['error']);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE-edge">
    <meta name="viewport" content="width-device-width, initial-scale=1.0 ">
    <title>Peer Tutoring Website</title>

    <!--Material Cdn-->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp" rel="stylesheet" />

    <!--Style Sheet-->
    <link rel="stylesheet" href="studentstyle.css">
</head>

<body>
    <div class="container">
        <aside>
            <div class="top">
                <div class="logo">
                    <img src="image/logo.png">
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
                <a href="admin_tutors.php" class="active"><span class="material-symbols-sharp">eyeglasses</span><h3>Tutors</h3></a>
                <a href="admin_course.php"><span class="material-symbols-sharp">school</span><h3>Courses</h3></a>
                <a href="admin_message.php"><span class="material-symbols-sharp">chat</span><h3>Messages</h3></a>
                <a href="admin_report.php"><span class="material-symbols-sharp">description</span><h3>Reports</h3></a>
                <a href="home_page.html"><span class="material-symbols-sharp">logout</span><h3>Logout</h3></a>
            </div>
        </aside>

    <main>
       <div class="current_students">
                <h2>Available Tutors</h2>

                <di style="margin-left: 500px ; padding:100px;"> 

                    <form action="" method="GET">
                        <input type="text" name="my_search" placeholder="Search Tutors ...">

                        <input  type="submit" name="search" value="Search">
                    </form>
                <table>
                    <thead>
                        <tr>
                            <th>Tutors NAME</th>
                            <th>LEVEL</th>
                            <th>PROGRAM</TH>
                            <th>COURSE</th>
                            <th>DETIALS</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
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

               
                     $sql = "SELECT sp.*, u.username 
                                FROM tutorprofile sp
                                JOIN user u ON sp.user_id = u.user_id";

                        if(isset($_GET['search']) && !empty($_GET['my_search']))
                        {
                            $search_value = $conn->real_escape_string($_GET['my_search']);
                            
                            $sql .= " WHERE u.username LIKE '%$search_value%' 
                                    OR sp.program LIKE '%$search_value%' 
                                    OR sp.major LIKE '%$search_value%' 
                                    OR sp.year LIKE '%$search_value%'";
                        }

                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            // Output data of each row
                            while($row = $result->fetch_assoc()) {
                                echo "<tr>
                                    <td>".htmlspecialchars($row["username"])."</td>
                                    <td>".htmlspecialchars($row["year"])."</td>
                                    <td>".htmlspecialchars($row["program"])."</td>
                                    <td>".htmlspecialchars($row["major"])."</td>                                    
                                    <td>
                                        <a href='tutor_details.php?id=".$row["user_id"]."' class='details-btn'>Details</a>
                                    </td>
                                    <td>
                                        <a href='tutor_action.php?id=".$row["user_id"]."&action=approve' class='approve-btn'>Approve</a>
                                        <a href='tutor_action.php?id=".$row["user_id"]."&action=reject' class='reject-btn'>Reject</a>
                                    </td>
                                </tr>";
                            }
                        } else {
                            $no_results_message = isset($_GET['search']) ? 
                                "No students found matching '".htmlspecialchars($_GET['my_search'])."'" : 
                                "No students found";
                            echo "<tr><td colspan='6'>$no_results_message</td></tr>";
                        }
                        $conn->close();
                        ?>
                    </tbody>
                </table>
                <a href="#">Show All</a>
            </div>
        </main>
    </div>
</body>
</html>