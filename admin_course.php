<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE-edge">
    <meta name="viewport" content="width-device-width, initial-scale=1.0">
    <title>Peer Tutoring Website - Courses</title>

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
                <a href="admin_tutors.php"><span class="material-symbols-sharp">eyeglasses</span><h3>Tutors</h3></a>
                <a href="admin_course.php" class="active"><span class="material-symbols-sharp">school</span><h3>Courses</h3></a>
                <a href="admin_message.php"><span class="material-symbols-sharp">chat</span><h3>Messages</h3></a>
                <a href="admin_review.php"><span class="material-symbols-sharp">star</span><h3>Reviews</h3></a>
                <a href="home_page.html"><span class="material-symbols-sharp">logout</span><h3>Logout</h3></a>
            </div>
        </aside>

        <main>
            <div class="current_students">
                <h2>Available Courses</h2>

                 <di style="margin-left: 500px ; padding:100px;"> 


                    <form action="" method="GET">
                        <input type="text" name="my_search" placeholder="Search Courses ...">
                        <input type="submit" name="search" value="Search">

                    </form>
                <table>
                    <thead>
                        <tr>
                            <th>COURSE NAME</th>
                            <th>DETAILS</th>
                            <th>STATUS</th>
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

                        $sql = "SELECT * FROM course";

                        if(isset($_GET['search']) && !empty($_GET['my_search']))
                        {
                            $search_value = $conn->real_escape_string($_GET['my_search']);
                            
                            $sql .= " WHERE course_name LIKE '%$search_value%' 
                                    OR details LIKE '%$search_value%' 
                                    OR status LIKE '%$search_value%'";
                        }

                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            // Output data of each row
                            while($row = $result->fetch_assoc()) {
                                echo "<tr>
                                    <td>".htmlspecialchars($row["course_name"])."</td>

                                     <td>
                                    <a href='course_details.php?id=".$row["id"]."' class='details-btn'>Details</a>
                                    </td>   
                                    <th>
                                        <a>Available</a>
                                    </th>
                                </tr>";
                            }
                        } else {
                            $no_results_message = isset($_GET['search']) ? 
                                "No courses found matching '".htmlspecialchars($_GET['my_search'])."'" : 
                                "No courses found";
                            echo "<tr><td colspan='4'>$no_results_message</td></tr>";
                        }
                        $conn->close();
                        ?>
                    </tbody>
                </table>
                <a href="#">Show All</a>
            </div>
        </main>

        <!-------------------END OF COURSES------------------->
            
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
                                <label for="Course">Course:</label>
                                <input type="text" id="course" name="course_name" placeholder="Enter Course" required>
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
    </div>
    
</body>
</html>