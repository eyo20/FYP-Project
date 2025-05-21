<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE-edge">
    <meta name="viewport" content="width-device-width, initial-scale=1.0">
    <title>Peer Tutoring Website - Tutors</title>

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
                <a href="admin_student.php"><span class="material-symbols-sharp">person</span><h3>Students</h3></a>
                <a href="admin_tutors.php" class="active"><span class="material-symbols-sharp">eyeglasses</span><h3>Tutors</h3></a>
                <a href="admin_course.php"><span class="material-symbols-sharp">school</span><h3>Courses</h3></a>
                <a href="message.php"><span class="material-symbols-sharp">chat</span><h3>Messages</h3><span class="message-count">26</span></a>
                <a href="session.php"><span class="material-symbols-sharp">library_books</span><h3>Session</h3></a>
                <a href="admin_review.php"><span class="material-symbols-sharp">star</span><h3>Reviews</h3></a>
                <a href="sales.php"><span class="material-symbols-sharp">finance</span><h3>Sales</h3></a>
                <a href="home_page.php"><span class="material-symbols-sharp">logout</span><h3>Logout</h3></a>
            </div>
        </aside>

        <main>
            <div class="available_tutors">
                <h2>Available Tutors</h2>
                <table>
                    <thead>
                        <tr>
                            <th>TUTOR NAME</th>
                            <th>FACULTY</th>
                            <th>COURSE</th>
                            <th>COURSE CODE</th>
                            <th>RATING</th>
                            <th>ACTIONS</th>
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

                        // Fetch tutors from database
                        $sql = "SELECT * FROM tutors";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            // Output data of each row
                            while($row = $result->fetch_assoc()) {
                                echo "<tr>
                                    <td>".$row["tutors_name"]."</td>
                                    <td>".$row["faculty"]."</td>
                                    <td>".$row["course"]."</td>
                                    <td>".$row["course_code"]."</td>
                                    <td>".$row["rating"]."</td>
                                    <td>
                                        <a href='edit_tutor.php?id=".$row["id"]."' class='edit-btn'>Edit</a>
                                        <a href='copy_tutor.php?id=".$row["id"]."' class='copy-btn'>Copy</a>
                                        <a href='delete_tutor.php?id=".$row["id"]."' class='delete-btn'>Delete</a>
                                        <a href='tutor_details.php?id=".$row["id"]."' class='details-btn'>Details</a>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6'>No tutors found</td></tr>";
                        }
                        $conn->close();
                        ?>
                    </tbody>
                </table>
                <a href="#">Show All</a>
            </div>
        </main>

        <!-------------------END OF TUTORS------------------->
        
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
                        <h3>Add Tutor</h3>
                    </div>
                    <div class="message">
                        <p>Admin can add tutor here!</p>
                        <form action="tutor_record.php" method="POST">
                            <div>
                                <label for="tutors_name">Tutor Name:</label>
                                <input type="text" id="tutors_name" name="tutors_name" placeholder="Enter Tutor Name" required>
                            </div>

                            <br>

                            <div>
                                <label for="faculty">Faculty:</label>
                                <input type="text" id="faculty" name="faculty" placeholder="Enter Faculty" required>
                            </div>

                            <br>

                            <div>
                                <label for="course">Course:</label>
                                <input type="text" id="course" name="course" placeholder="Enter Course" required>
                            </div>

                            <br>

                            <div>
                                <label for="course_code">Course Code:</label>
                                <input type="text" id="course_code" name="course_code" placeholder="Enter Course Code" required>
                            </div>
                            
                            <br>
                            
                            <div>
                                <label for="rating">Rating:</label>
                                <input type="number" step="0.01" id="rating" name="rating" placeholder="Enter Rating" required>
                            </div>
                            
                            <br>
                            <div>
                                <input type="reset" value="Reset">
                            </div>

                            <br>
                            <div>
                                <input type="submit" value="Add Tutor">
                            </div>
                        </form>
                        <div class="item add-tutor">
                            <div>
                                <span class="material-symbols-sharp">add</span>
                                <h3>ADD TUTOR</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>