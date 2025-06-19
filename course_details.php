<?php
// Start session and include database connection
session_start();
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

// Get course ID from URL
$course_id = $_GET['id'] ?? 0;

// Initialize variables
$edit_error = '';
$edit_success = '';
$edit_mode = isset($_GET['edit']) && $_GET['edit'] == '1';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['delete_course'])) {
        // Prepare delete statement
        $delete_sql = "DELETE FROM course WHERE id = ?";
        $stmt = $conn->prepare($delete_sql);
        $stmt->bind_param("i", $course_id);
        
        if ($stmt->execute()) {
            // Redirect to courses list after successful deletion
            header("Location: admin_course.php?deleted=1");
            exit();
        } else {
            $delete_error = "Error deleting course: " . $conn->error;
        }
    }
    elseif (isset($_POST['update_course'])) {
        // Get form data
        $course_name = $_POST['course_name'];
        $details = $_POST['details'];
        $status = $_POST['status'];
        
        // Prepare update statement
        $update_sql = "UPDATE course SET course_name = ?, details = ?, status = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sssi", $course_name, $details, $status, $course_id);
        
        if ($stmt->execute()) {
            $edit_success = "Course updated successfully!";
            $edit_mode = false; // Switch back to view mode after successful update
            // Refresh the course data
            $sql = "SELECT * FROM course WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $course_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $course = $result->fetch_assoc();
        } else {
            $edit_error = "Error updating course: " . $conn->error;
            $edit_mode = true; // Stay in edit mode if there was an error
        }
    }
}

// Fetch course details
$sql = "SELECT * FROM course WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();
$course = $result->fetch_assoc();

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Details - <?php echo htmlspecialchars($course['course_name'] ?? 'Course'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp" rel="stylesheet">
    <link rel="stylesheet" href="studentstyle.css">
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

        .container {
            display: flex;
            min-height: 100vh;
        }
        
        .profile-content {
            flex: 1;
            padding: 2rem;
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .profile-icon {
            font-size: 3rem;
            margin-right: 1.5rem;
            color: #7380ec;
        }
        
        .profile-title h1 {
            margin: 0;
            font-size: 1.8rem;
        }
        
        .profile-section {
            background: white;
            border-radius: 0.8rem;
            padding: 2rem;
            box-shadow: 0 0.2rem 0.5rem rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .detail-item {
            margin-bottom: 15px;
        }
        
        .detail-item strong {
            display: block;
            color: #7d8da1;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .detail-item p {
            margin: 0;
            font-size: 16px;
        }
        
        .full-width {
            grid-column: span 2;
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
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .status-active {
            background-color: #e3f9e5;
            color: #429e44;
        }
        
        .status-pending {
            background-color: #fff3bf;
            color: #8d6e00;
        }
        
        .status-inactive {
            background-color: #ffe3e3;
            color: #cc0000;
        }

        .delete-btn {
            display: inline-flex;
            align-items: center;
            margin-top: 30px;
            padding: 10px 20px;
            background:rgb(236, 115, 115);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: background 0.3s;
            border: none;
            cursor: pointer;
        }
        
        .edit-btn {
            display: inline-flex;
            align-items: center;
            margin-top: 30px;
            padding: 10px 20px;
            background: #41f1b6;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: background 0.3s;
            border: none;
            cursor: pointer;
        }
        
        .edit-btn:hover {
            background: #3ad8a4;
        }
        
        .delete-btn:hover {
            background: rgb(216, 85, 85);
        }
        
        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #7d8da1;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #dce1eb;
            border-radius: 6px;
            font-size: 16px;
        }
        
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .success-message {
            color: #429e44;
            background-color: #e3f9e5;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .error-message {
            color: #cc0000;
            background-color: #ffe3e3;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .toggle-edit {
            display: inline-flex;
            align-items: center;
            padding: 8px 15px;
            background: #7380ec;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: background 0.3s;
            cursor: pointer;
            border: none;
            margin-bottom: 20px;
        }
        
        .toggle-edit:hover {
            background: #6572ce;
        }
        
        .toggle-edit .material-symbols-sharp {
            margin-right: 5px;
            font-size: 18px;
        }

    </style>
</head>
<body>
    <div class="container">
        <aside>
            <div class="top">
                <div class="logo">
                    <img src="image/logo.png" alt="PeerLearn Logo">
                    <h2>PEER<span class="danger">LEARN</span></h2>
                </div>
                <div class="close" id="close-btn">
                    <span class="material-symbols-sharp">close</span>
                </div>
            </div>

            <div class="sidebar">
                <a href="admin.html"><span class="material-symbols-sharp">grid_view</span><h3>Dashboard</h3></a>
                <a href="#"></a>
                <a href="admin_staff.php"><span class="material-symbols-sharp">badge</span><h3>Staff</h3></a>
                <a href="admin_student.php"><span class="material-symbols-sharp">person</span><h3>Students</h3></a>
                <a href="admin_tutors.php"><span class="material-symbols-sharp">eyeglasses</span><h3>Tutors</h3></a>
                <a href="admin_course.php" class="active"><span class="material-symbols-sharp">school</span><h3>Courses</h3></a>
                <a href="admin_message.php"><span class="material-symbols-sharp">chat</span><h3>Messages</h3></a>
               <a href="admin_report.php"><span class="material-symbols-sharp">description</span><h3>Reports</h3></a>
                <a href="home_page.html"><span class="material-symbols-sharp">logout</span><h3>Logout</h3></a>
            </div>
        </aside>

        <div class="profile-content">
            <div class="profile-header">
                <span class="material-symbols-sharp profile-icon">school</span>
                <div class="profile-title">
                    <h1><?php echo htmlspecialchars($course['course_name'] ?? 'Course Name'); ?></h1>
                    <p>Course Details</p>
                </div>
            </div>
            
            <?php if (!$edit_mode): ?>
                <a href="?id=<?php echo $course_id; ?>&edit=1" class="toggle-edit">
                    <span class="material-symbols-sharp">edit</span>
                    Edit Course
                </a>
            <?php else: ?>
                <a href="?id=<?php echo $course_id; ?>" class="toggle-edit">
                    <span class="material-symbols-sharp">close</span>
                    Cancel Edit
                </a>
            <?php endif; ?>
            
            <?php if ($edit_success): ?>
                <div class="success-message"><?php echo htmlspecialchars($edit_success); ?></div>
            <?php endif; ?>
            
            <?php if ($edit_error): ?>
                <div class="error-message"><?php echo htmlspecialchars($edit_error); ?></div>
            <?php endif; ?>
            
            <div class="profile-sections">
                <?php if ($edit_mode): ?>
                    <!-- Edit Form -->
                    <form method="POST" class="profile-section">
                        <h2>Edit Course Information</h2>
                        
                        <div class="form-group">
                            <label for="course_name">Course Name</label>
                            <input type="text" id="course_name" name="course_name" value="<?php echo htmlspecialchars($course['course_name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" required>
                                <option value="Active" <?php echo ($course['status'] ?? '') == 'Active' ? 'selected' : ''; ?>>Active</option>
                                <option value="Pending" <?php echo ($course['status'] ?? '') == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="Inactive" <?php echo ($course['status'] ?? '') == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="details">Course Description</label>
                            <textarea id="details" name="details" required><?php echo htmlspecialchars($course['details'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="btn-group">
                            <button type="submit" name="update_course" class="edit-btn">
                                <span class="material-symbols-sharp">save</span>
                                Save Changes
                            </button>
                            <a href="?id=<?php echo $course_id; ?>" class="back-btn">
                                <span class="material-symbols-sharp">close</span>
                                Cancel
                            </a>
                        </div>
                    </form>
                <?php else: ?>
                    <!-- View Mode -->
                    <div class="profile-section">
                        <h2>Course Information</h2>
                        <div class="detail-item">
                            <strong>Course Name</strong>
                            <p><?php echo htmlspecialchars($course['course_name'] ?? 'N/A'); ?></p>
                        </div>
                        <div class="detail-item">
                            <strong>Status</strong>
                            <p>
                                <span class="status-badge status-<?php echo strtolower(htmlspecialchars($course['status'] ?? 'pending')); ?>">
                                    <?php echo htmlspecialchars($course['status'] ?? 'Pending'); ?>
                                </span>
                            </p>
                        </div>
                        <div class="detail-item">
                            <strong>Date Added</strong>
                            <p><?php echo htmlspecialchars($course['created_at'] ?? 'N/A'); ?></p>
                        </div>
                    </div>
                    
                    <div class="profile-section">
                        <h2>Course Description</h2>
                        <div class="detail-item">
                            <p><?php echo nl2br(htmlspecialchars($course['details'] ?? 'No description provided')); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="profile-section">
                    <h2>Delete Course</h2>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this course? This action cannot be undone.');">
                        <div class="delete-confirmation">
                            <p>Warning: Deleting this course will permanently remove it from the system.</p>
                            <button type="submit" name="delete_course" class="delete-btn">
                                <span class="material-symbols-sharp">delete</span>
                                Delete This Course
                            </button>
                        </div>
                    </form>
                    <?php if (isset($delete_error)): ?>
                        <div class="error-message"><?php echo htmlspecialchars($delete_error); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <a href="admin_course.php" class="back-btn">
                <span class="material-symbols-sharp">arrow_back</span>
                Back to Courses List
            </a>
        </div>
    </div>
</body>
</html>