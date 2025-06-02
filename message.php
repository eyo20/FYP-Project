<!DOCTYPE html>
<html lang="en">

<head>

</head>

<body>
    <?php include 'header/stud_head.php'; ?>

    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-graduation-cap"></i>
                Peer Tutoring Platform
            </div>
            <nav class="nav-links">
                <a href="student_main_page.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="find_tutors.php"><i class="fas fa-search"></i> Find Tutors</a>
                <a href="student_sessions.php"><i class="fas fa-calendar"></i> My Sessions</a>
                <a href="messages.php"><i class="fas fa-envelope"></i> Messages</a>
                <div class="user-menu" onclick="toggleDropdown()">
                    <i class="fas fa-user-circle"></i>
                    <?php echo htmlspecialchars(isset($_SESSION['first_name']) ? $_SESSION['first_name'] : 'User'); ?>
                    <i class="fas fa-chevron-down"></i>
                    <div class="dropdown" id="userDropdown">
                        <a href="student_profile.php"><i class="fas fa-user"></i> Profile</a>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </nav>
        </div>
    </header>

    <script>
        //header dropdown
        function toggleDropdown() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }
    </script>
</body>

</html>