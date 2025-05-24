<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE-edge">
    <meta name="viewport" content="width-device-width, initial-scale=1.0">
    <title>Peer Tutoring Website</title>

    <!--Material Cdn-->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp" rel="stylesheet" />

    <!--Style Sheet-->
    <link rel="stylesheet" href="coursestyle.css">
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
                <a href="admin_student.php"><span class="material-symbols-sharp">person</span><h3>Students</h3></a>
                <a href="admin_tutors.php"><span class="material-symbols-sharp">eyeglasses</span><h3>Tutors</h3></a>
                <a href="admin_course.php" class="active"><span class="material-symbols-sharp">school</span><h3>Courses</h3></a>
                <a href="message.php"><span class="material-symbols-sharp">chat</span><h3>Messages</h3><span class="message-count">26</span></a>
                <a href="session.php"><span class="material-symbols-sharp">library_books</span><h3>Session</h3></a>
                <a href="admin_review.php"><span class="material-symbols-sharp">star</span><h3>Reviews</h3></a>
                <a href="sales.php"><span class="material-symbols-sharp">finance</span><h3>Sales</h3></a>
                <a href="home_page.php"><span class="material-symbols-sharp">logout</span><h3>Logout</h3></a>
            </div>
        </aside>

        <main>
            <div class="available_courses">
                <h2>Available Courses</h2>
                <table>
                    <thead>
                        <tr>
                            <th>FACULTY</th>
                            <th>PROGRAMME</th>
                            <th>COURSES</th>
                            <th>REQUIREMENT</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Database connection
                        $servername = "localhost";
                        $username = "root"; // replace with your MySQL username
                        $password = ""; // replace with your MySQL password
                        $dbname = "peer_tutoring_platform";

                        // Create connection
                        $conn = new mysqli($servername, $username, $password, $dbname);

                        // Check connection
                        if ($conn->connect_error) {
                            die("Connection failed: " . $conn->connect_error);
                        }

                        // Fetch courses from database
                        $sql = "SELECT * FROM courses";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            // Output data of each row
                            while($row = $result->fetch_assoc()) {
                                echo "<tr>
                                    <td>".$row["faculty"]."</td>
                                    <td>".$row["programme"]."</td>
                                    <td>".$row["course_code"]."</td>
                                    <td class='danger'>".$row["requirement"]."</td>
                                    <td class='primary'>Details</td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5'>No courses found</td></tr>";
                        }
                        $conn->close();
                        ?>
                    </tbody>
                </table>
                <a href="#">Show All</a>
            </div>
        </main>

        <!-------------------END OF COURSES------------------->
        
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
                        <span class="material-symbols-sharp">school</span>
                        <h3>Add Course</h3>
                    </div>
                    <div class="message">
                        <p>Admin can add course here!</p>
                        <form action="course_record.php" method="POST">
                            <div>
                                <label for="faculty">Faculty:</label>
                                <input type="text" id="faculty" name="faculty" placeholder="Enter Faculty" required>
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

                            <div>
                                <label for="requirement">Requirement:</label>
                                <input type="text" id="requirement" name="requirement" placeholder="Enter Requirement" required>
                            </div>
                            
                            <br>
                            <div>
                                <input type="reset" value="Reset">
                            </div>

                            <br>
                            <div>
                                <input type="submit" value="Add Course">
                            </div>
                        </form>
                        <div class="item add-course">
                            <div>
                                <span class="material-symbols-sharp">add</span>
                                <h3>ADD COURSE</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>