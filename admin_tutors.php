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
                <a href="admin.html"><span class="material-symbols-sharp">grid_view</span><h3>Dashboard</h3></a>
                <a href="#"></a>
                <a href="admin_student.php"><span class="material-symbols-sharp">person</span><h3>Students</h3></a>
                <a href="admin_tutors.php" class="active"><span class="material-symbols-sharp">eyeglasses</span><h3>Tutors</h3></a>
                <a href="admin_course.php"><span class="material-symbols-sharp">school</span><h3>Courses</h3></a>
                <a href="message.php"><span class="material-symbols-sharp">chat</span><h3>Messages</h3><span class="message-count">26</span></a>
                <a href="admin_review.php"><span class="material-symbols-sharp">star</span><h3>Reviews</h3></a>
                <a href="home_page.php"><span class="material-symbols-sharp">logout</span><h3>Logout</h3></a>
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
                            <th>TUTOR NAME</th>
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

               
                    $sql = "SELECT * FROM tutors";

                
                    if(isset($_GET['search']) && !empty($_GET['my_search']))
                    {
                       
                        $search_value = $conn->real_escape_string($_GET['my_search']);
                        
                        
                        $sql .= " WHERE tutor_name LIKE '%$search_value%' 
                                OR program LIKE '%$search_value%' 
                                OR course_year LIKE '%$search_value%' 
                                OR level LIKE '%$search_value%'";
                    }

                    
                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                        // Output data of each row
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>
                                <td>".htmlspecialchars($row["tutor_name"])."</td>
                                <td>".htmlspecialchars($row["level"])."</td>
                                <td>".htmlspecialchars($row["program"])."</td>
                                <td>".htmlspecialchars($row["course_year"])."</td>                                    
                                <td>
                                    <a href='tutor_details.php?id=".$row["id"]."' class='details-btn'>Details</a>
                                </td>
                                <td>
                                    <a href='tutor_action.php?id=".$row["id"]."&action=approve' class='approve-btn'>Approve</a>
                                    <a href='tutor_action.php?id=".$row["id"]."&action=reject' class='reject-btn'>Reject</a>
                                </td>
                            </tr>";
                        }
                    } else {
                        $no_results_message = isset($_GET['search']) ? 
                            "No tutors found matching '".htmlspecialchars($_GET['my_search'])."'" : 
                            "No tutors found";
                        echo "<tr><td colspan='6'>$no_results_message</td></tr>";
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

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>