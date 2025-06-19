<?php 
session_start();

// Verify admin access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Unauthorized access!";
    header("Location: admin_staff.php");
    exit();
}

// Preserve form data if there was an error
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Staff - Peer Tutoring Website</title>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp" rel="stylesheet" />
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

            .recent-updates {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .recent-updates h2 {
            color: #7380ec;
            margin-bottom: 15px;
            text-align:justify;
        }
        
        .updates {
            display: flex;
            gap: 15px;
            margin-bottom: 10px;
        }
        
        .update {
            background: #7380ec;
            color: white;
            border-radius: 70%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-left: 20px;
        }
        
        .update .material-symbols-sharp {
            margin-left: 12px;
        }
        
        .message p {
            margin-bottom: 20px;
            color: #7d8da1;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #363949;
            font-weight: 500;
        }
        
        .form-group input, 
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .form-actions input[type="submit"],
        .form-actions input[type="reset"] {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            flex: 1;
        }
        
        .form-actions input[type="submit"] {
            background: #7380ec;
            color: white;
        }
        
        .form-actions input[type="reset"] {
            background: #f1f1f1;
            color: #363949;
        }
        
        .alert {
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .alert.success {
            background: #d4edda;
            color: #155724;
        }
        
        .alert.error {
            background: #f8d7da;
            color: #721c24;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 12%;
            margin-top: 20px;
            padding: 10px;
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

        .password-strength {
            margin-top: 5px;
            font-size: 12px;
            color: #666;
        }
        
        .password-strength.weak {
            color: #ff4444;
        }
        
        .password-strength.medium {
            color: #ffbb33;
        }
        
        .password-strength.strong {
            color: #00C851;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
    </style>
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
            <div class="recent-updates">
                <h2>Add Staff</h2>
                <div class="updates">
                    <div class="update">
                        <span class="material-symbols-sharp">badge</span>
                    </div>
                    <div class="message">
                        <p>Admin can add new staff members here</p>
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                        <?php endif; ?>
                        <form action="staff_action.php?action=add" method="POST" onsubmit="return validateForm()">
                            <input type="hidden" name="csrf_token" value="<?php echo bin2hex(random_bytes(32)); ?>">
                            
                            <div class="form-group">
                                <label for="username">Username:</label>
                                <input type="text" id="username" name="username" 
                                       placeholder="Enter username" required
                                       value="<?php echo htmlspecialchars($formData['username'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email:</label>
                                <input type="email" id="email" name="email" 
                                       placeholder="Enter email" required
                                       value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="password">Password:</label>
                                <input type="password" id="password" name="password" 
                                       placeholder="Enter password" required
                                       oninput="checkPasswordStrength(this.value)">
                                <div id="password-strength" class="password-strength"></div>
                            </div>
                            
                            <div class="form-group">
                                <label for="role">Role:</label>
                                <select id="role" name="role" required>
                                    <option value="staff" <?php echo (($formData['role'] ?? 'staff') === 'staff' ? 'selected' : ''); ?>>Staff</option>
                                    <option value="admin" <?php echo (($formData['role'] ?? '') === 'admin' ? 'selected' : ''); ?>>Admin</option>
                                </select>
                            </div>
                            
                            
                            <div class="form-actions">
                                <input type="reset" value="Reset">
                                <input type="submit" value="Add Staff">
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <a href="admin_staff.php" class="back-btn">
                <span class="material-symbols-sharp">arrow_back</span>
                Back to Staff List
            </a>
        </main>
    </div>
    
    <script>
        function checkPasswordStrength(password) {
            const strengthText = document.getElementById('password-strength');
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            strengthText.className = 'password-strength';
            if (password.length === 0) {
                strengthText.textContent = '';
            } else if (strength <= 2) {
                strengthText.textContent = 'Weak password';
                strengthText.className += ' weak';
            } else if (strength <= 4) {
                strengthText.textContent = 'Medium strength password';
                strengthText.className += ' medium';
            } else {
                strengthText.textContent = 'Strong password';
                strengthText.className += ' strong';
            }
        }
        
        function validateForm() {
            const password = document.getElementById('password').value;
            if (password.length < 8) {
                alert('Password must be at least 8 characters long');
                return false;
            }
            return true;
        }
    </script>
</body>
</html>