<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE-edge">
    <meta name="viewport" content="width-device-width, initial-scale=1.0">
    <title>Peer Tutoring Website - Reviews</title>

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
                <a href="admin_course.php"><span class="material-symbols-sharp">school</span><h3>Courses</h3></a>
                <a href="admin_message.php"><span class="material-symbols-sharp">chat</span><h3>Messages</h3></a>
                <a href="admin_review.php" class="active"><span class="material-symbols-sharp">star</span><h3>Reviews</h3></a>
                <a href="admin_report.php"><span class="material-symbols-sharp">description</span><h3>Reports</h3></a>
                <a href="home_page.html"><span class="material-symbols-sharp">logout</span><h3>Logout</h3></a>
            </div>
        </aside>

        <main>
            <div class="reviews">
                <h2>Customer Reviews</h2>
                <table>
                    <thead>
                        <tr>
                            <th>REVIEW ID</th>
                            <th>USER NAME</th>
                            <th>RATING</th>
                            <th>REVIEW</th>
                            <th>DATE/TIME</th>
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

                        // Fetch reviews from database
                        $sql = "SELECT * FROM review_table";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            // Output data of each row
                            while($row = $result->fetch_assoc()) {
                                // Convert timestamp to readable date/time
                                $formatted_date = date('Y-m-d H:i:s', $row['datetime']);
                                
                                echo "<tr>
                                    <td>".$row["review_id"]."</td>
                                    <td>".$row["user_name"]."</td>
                                    <td>";
                                
                                // Display star rating
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $row["user_rating"]) {
                                        echo "<span class='material-symbols-sharp'>star</span>";
                                    } else {
                                        echo "<span class='material-symbols-sharp'>star_outline</span>";
                                    }
                                }
                                
                                echo "</td>
                                    <td>".$row["user_review"]."</td>
                                    <td>".$formatted_date."</td>
                                    <td>
                                        <a href='edit_review.php?id=".$row["review_id"]."' class='edit-btn'>Edit</a>
                                        <a href='delete_review.php?id=".$row["review_id"]."' class='delete-btn'>Delete</a>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6'>No reviews found</td></tr>";
                        }
                        $conn->close();
                        ?>
                    </tbody>
                </table>
                <a href="#">Show All</a>
            </div>
        </main>
            <!-----------------END OF RIGHT---------------------->
            
            <div class="recent-updates">
                <h2>Add Review</h2>
                <div class="updates">
                    <div class="update">
                        <span class="material-symbols-sharp">star</span>
                    </div>
                    <div class="message">
                        <p>Admin can add review here!</p>
                        <form action="review_record.php" method="POST">
                            <div>
                                <label for="user_name">User Name:</label>
                                <input type="text" id="user_name" name="user_name" placeholder="Enter User Name" required>
                            </div>

                            <br>

                            <div>
                                <label for="user_rating">Rating (1-5):</label>
                                <select id="user_rating" name="user_rating" required>
                                    <option value="">Select Rating</option>
                                    <option value="1">1 Star</option>
                                    <option value="2">2 Stars</option>
                                    <option value="3">3 Stars</option>
                                    <option value="4">4 Stars</option>
                                    <option value="5">5 Stars</option>
                                </select>
                            </div>

                            <br>

                            <div>
                                <label for="user_review">Review:</label>
                                <textarea id="user_review" name="user_review" placeholder="Enter Review" required></textarea>
                            </div>
                            
                            <br>
                            <div>
                                <input type="reset" value="Reset">
                            </div>

                            <br>
                            <div>
                                <input type="submit" value="Add Review">
                            </div>
                        </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>