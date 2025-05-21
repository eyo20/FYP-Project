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

    <main>
        <div class="current_students">
            <h2>STUDENTS</h2>
            <table>
                <thead>
                    <tr>
                        <th>STUDENTS</th>
                        <th>FACULTY</th>
                        <th>PROGRAMME</th>
                        <th>COURSE</th>
                        <th>STATUS</th>
                        <th></th>
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

                    // Fetch data from students table
                    $sql = "SELECT student_name, faculty, programme, course_code, status FROM students";
                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                        // Output data of each row
                        while($row = $result->fetch_assoc()) {
                            // Determine status class based on value
                            $statusClass = '';
                            if ($row["status"] == "ONLINE") {
                                $statusClass = "success";
                            } elseif ($row["status"] == "OFFLINE") {
                                $statusClass = "danger";
                            } elseif ($row["status"] == "IN CLASS") {
                                $statusClass = "warning";
                            }
                            
                            echo "<tr>
                                <td>".htmlspecialchars($row["student_name"])."</td>
                                <td>".htmlspecialchars($row["faculty"])."</td>
                                <td>".htmlspecialchars($row["programme"])."</td>
                                <td>".htmlspecialchars($row["course_code"])."</td>
                                <td class='".$statusClass."'>".htmlspecialchars($row["status"])."</td>
                                <td class='primary'>Details</td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6'>No students found</td></tr>";
                    }
                    $conn->close();
                    ?>
                </tbody>
            </table>
            <a href="#">Show All</a>
        </div>
    </main>

    <!-------------------END OF COURES------------------->

    <div class="right">
        <div class="top">
            <button id="menu-btn">
                <span class="material-symbols-sharp">menu</span>
            </button>
            <div class="theme-toggler">
                <span class="material-symbols-sharp active">light_mode</span>
                <span class="material-symbols-sharp">dark_mode</span>
            </div>
            <div class="profile">
                <div class="info">
                    <p>Hey, <b>MengWen</b></p>
                    <small class="text-muted">Admin</small>
                </div>
                <div class="profile-photo">
                    <img src="image/profile-1.jpg">
                </div>
            </div>
        </div>

        <!-----------------END OF RIGHT---------------------->
        
        <div class="recent-updates">
        <h2>Recent Updates</h2>
        <div class="updates">
            <div class="update">
                <span class="material-symbols-sharp">person</span>
                <h3>Add Students</h3>
            </div>
            <div class="message">
                <p>Admin can add Students here!</p>
                <br>
                <form action="student_add.php" method="POST">
                    <div>
                        <label for="sname">Students Name:</label>
                        <input type="text" id="sname" name="sname" placeholder="Enter Student Name" required>
                    </div>
                    <br>
                    <div>
                        <label for="faculty">Faculty:</label>
                        <select id="faculty" name="faculty" required>
                            <option value="Business">Business</option>
                            <option value="Information Technology">Information Technology</option>
                            <option value="Engineering">Engineering</option>
                            <option value="Law">Law</option>
                        </select>
                    </div>
                    <br>
                    <div>
                        <label for="programme">Programme:</label>
                        <input type="text" id="programme" name="programme" placeholder="Enter Programme" required>
                    </div>
                    <br>
                    <div>
                        <label for="course_code">Course Code:</label>
                        <input type="text" id="course_code" name="course_code" placeholder="Enter Course Code" required>
                    </div>
                    <br>
                    <div class="form-actions">
                        <button type="reset" class="reset-btn">
                            <span class="material-symbols-sharp">undo</span>
                            <span>Reset</span>
                        </button>
                        <button type="submit" class="add-students-btn">
                            <span class="material-symbols-sharp">add</span>
                            <span>ADD STUDENTS</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>