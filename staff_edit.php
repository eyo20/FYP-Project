<?php
session_start();
require_once 'db_connection.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid staff ID";
    header("Location: admin_staff.php");
    exit();
}

$user_id = intval($_GET['id']);

// Fetch staff data
$stmt = $conn->prepare("SELECT user_id, username, email, role, is_active FROM user WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Staff member not found";
    header("Location: admin_staff.php");
    exit();
}

$staff = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $change_password = !empty($_POST['new_password']);

    if (empty($username) || empty($email)) {
        $_SESSION['error'] = "Username and email are required";
        header("Location: staff_edit.php?id=$user_id");
        exit();
    }

    $stmt = $conn->prepare("SELECT user_id FROM user WHERE (username = ? OR email = ?) AND user_id != ?");
    $stmt->bind_param("ssi", $username, $email, $user_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        $_SESSION['error'] = "Username or email already exists";
        header("Location: staff_edit.php?id=$user_id");
        exit();
    }

    if ($change_password) {
        $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE user SET username = ?, email = ?, role = ?, is_active = ?, password = ? WHERE user_id = ?");
        $stmt->bind_param("sssisi", $username, $email, $role, $is_active, $new_password, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE user SET username = ?, email = ?, role = ?, is_active = ? WHERE user_id = ?");
        $stmt->bind_param("sssii", $username, $email, $role, $is_active, $user_id);
    }

    if ($stmt->execute()) {
        $_SESSION['message'] = "Staff member updated successfully";
        header("Location: admin_staff.php");
    } else {
        $_SESSION['error'] = "Error updating staff member: " . $conn->error;
        header("Location: staff_edit.php?id=$user_id");
    }
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Staff Member</title>
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
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 0.4rem;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
        }
        
        .checkbox-group input {
            width: auto;
            margin-right: 0.5rem;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn {
            padding: 0.8rem 1.5rem;
            border-radius: 0.4rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            cursor: pointer;
        }
        
        .btn-primary {
            background: #7380ec;
            color: white;
            border: none;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
        }
        
        .btn .material-symbols-sharp {
            margin-right: 0.5rem;
            font-size: 1.2rem;
        }

        .danger{
            color: #ff7782;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
            border: none;
            display: inline-flex;
            align-items: center;
            padding: 0.8rem 1.5rem;
            border-radius: 0.4rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
        }

        .btn-danger:hover {
            background: #e66771;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 0.4rem;
            margin-bottom: 1.5rem;
        }
        
        .alert.error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .alert.success {
            background: #d4edda;
            color: #155724;
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
                <a></a>
                <a href="admin_staff.php" class="active"><span class="material-symbols-sharp">badge</span><h3>Staff</h3></a>
                <a href="admin_student.php"><span class="material-symbols-sharp">person</span><h3>Students</h3></a>
                <a href="admin_tutors.php"><span class="material-symbols-sharp">eyeglasses</span><h3>Tutors</h3></a>
                <a href="admin_course.php"><span class="material-symbols-sharp">school</span><h3>Courses</h3></a>
                <a href="admin_message.php"><span class="material-symbols-sharp">chat</span><h3>Messages</h3></a>
                <a href="admin_report.php"><span class="material-symbols-sharp">description</span><h3>Reports</h3></a>
                <a href="home_page.html"><span class="material-symbols-sharp">logout</span><h3>Logout</h3></a>
            </div>
        </aside>

        <div class="profile-content">
            <div class="profile-header">
                <span class="material-symbols-sharp profile-icon">badge</span>
                <div class="profile-title">
                    <h1>Edit Staff Member</h1>
                    <p>Update staff information</p>
                </div>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert error"><?= $_SESSION['error'] ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <div class="profile-section">
                <form method="POST">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" value="<?= htmlspecialchars($staff['username']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($staff['email']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Role</label>
                        <select id="role" name="role" required>
                            <option value="staff" <?= $staff['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
                            <option value="admin" <?= $staff['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password (leave blank to keep current)</label>
                        <input type="password" id="new_password" name="new_password">
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="is_active" name="is_active" value="1" <?= $staff['is_active'] ? 'checked' : '' ?>>
                        <label for="is_active">Active</label>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-sharp">save</span>
                            Save Changes
                        </button>
                        <a href="admin_staff.php" class="btn btn-secondary">
                            <span class="material-symbols-sharp">cancel</span>
                            Cancel
                        </a>
                        <a href="staff_action.php?id=<?= $user_id ?>&action=delete" class="btn btn-danger" onclick="return confirmDelete()">
                            <span class="material-symbols-sharp">delete</span>
                            Delete Staff
                        </a>
                    </div>

                    <script>
                        function confirmDelete() {
                            return confirm('Are you sure you want to delete this staff member? This action cannot be undone.');
                        }
                    </script>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>