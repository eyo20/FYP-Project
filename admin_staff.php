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
    <body>
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

        .edit-btn {
            padding: 5px 10px;
            background-color: #fff;
            color: white;
            text-decoration: none;
            border-radius: 4；
        }

    </style>
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
                <a href="admin_staff.php" class="active"><span class="material-symbols-sharp">badge</span><h3>Staff</h3></a>
                <a href="admin_student.php"><span class="material-symbols-sharp">person</span><h3>Students</h3></a>
                <a href="admin_tutors.php"><span class="material-symbols-sharp">eyeglasses</span><h3>Tutors</h3></a>
                <a href="admin_course.php"><span class="material-symbols-sharp">school</span><h3>Courses</h3></a>
                <a href="admin_message.php"><span class="material-symbols-sharp">chat</span><h3>Messages</h3></a>
                <a href="admin_report.php"><span class="material-symbols-sharp">description</span><h3>Reports</h3></a>
                <a href="home_page.html"><span class="material-symbols-sharp">logout</span><h3>Logout</h3></a>
            </div>
        </aside>

        <main>
            <div class="current_students">
                <h2>Available Staff</h2>

                <div> 
                    <form action="" method="GET">
                        <input type="text" name="my_search" placeholder="Search Staff...">
                        <input type="submit" name="search" value="Search">
                    </form>
                
                    <table>
                        <thead>
                            <tr>
                                <th>USERNAME</th>
                                <th>EMAIL</th>
                                <th>ROLE</th>
                                <th>REGISTRATION DATE</th>
                                <th>STATUS</th>
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

                            $sql = "SELECT user_id, username, email, role, created_at, is_active 
                                    FROM user 
                                    WHERE role = 'staff' OR role = 'admin'";

                            if(isset($_GET['search']) && !empty($_GET['my_search'])) {
                                $search_value = $conn->real_escape_string($_GET['my_search']);
                                
                                $sql .= " AND (username LIKE '%$search_value%' 
                                        OR email LIKE '%$search_value%' 
                                        OR role LIKE '%$search_value%')";
                            }

                            $result = $conn->query($sql);

                            if ($result->num_rows > 0) {
                                // Output data of each row
                                while($row = $result->fetch_assoc()) {
                                    $status = $row["is_active"] ? "Active" : "Inactive";
                                    echo "<tr>
                                        <td>".htmlspecialchars($row["username"])."</td>
                                        <td>".htmlspecialchars($row["email"])."</td>
                                        <td>".htmlspecialchars($row["role"])."</td>
                                        <td>".htmlspecialchars($row["created_at"])."</td>
                                        <td>".$status."</td>                                    
                                        <td>
                                            <a href='staff_edit.php?id=".$row["user_id"]."' class='edit-btn'>Edit</a>
                                            <a href='staff_action.php?id=".$row["user_id"]."&action=".($row["is_active"]?"deactivate":"activate")."' class='".($row["is_active"]?"deactivate-btn":"activate-btn")."'>".($row["is_active"]?"Deactivate":"Activate")."</a>
                                        </td>
                                    </tr>";
                                }
                            } else {
                                $no_results_message = isset($_GET['search']) ? 
                                    "No staff members found matching '".htmlspecialchars($_GET['my_search'])."'" : 
                                    "No staff members found";
                                echo "<tr><td colspan='6'>$no_results_message</td></tr>";
                            }
                            $conn->close();
                            ?>
                        </tbody>
                </table>
                <a href="#">Show All</a>
            </div>
        </main>
<div class="recent-updates">
    <h2>Staff Management</h2>
    <div class="updates">
        <a href="add_staff.php" class="add-staff-btn">
            <span class="material-symbols-sharp">person_add</span>
            <span>Add New Staff</span>
        </a>
    </div>
</div>
</body>
</html>