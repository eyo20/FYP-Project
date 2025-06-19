<?php

session_start();
require_once "db_connection.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get current user's role
$current_user_query = "SELECT role FROM user WHERE user_id = ?";
$stmt = $conn->prepare($current_user_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$current_user_result = $stmt->get_result();
$current_user = $current_user_result->fetch_assoc();
$stmt->close();

// Get list of users you can message, separated by role
$query = "SELECT user_id, first_name, last_name, profile_image, role 
          FROM user 
          WHERE user_id != ? 
          ORDER BY 
            CASE 
                WHEN role = 'admin' THEN 1
                WHEN role = 'tutor' THEN 2
                WHEN role = 'student' THEN 3
                ELSE 4
            END, first_name";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Separate users by role
$admins = array_filter($users, function($user) {
    return $user['role'] === 'admin';
});
$tutors = array_filter($users, function($user) {
    return $user['role'] === 'tutor';
});
$students = array_filter($users, function($user) {
    return $user['role'] === 'student';
});

if ($current_user['role'] === 'student') {
    $back_url = 'student_main_page.php';
} elseif ($current_user['role'] === 'tutor') {
    $back_url = 'tutor_main_page.php';
} else {
    $back_url = 'home_page.html'; // Default fallback
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Your Messages</title>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="studentstylesheet">
    <link rel="stylesheet" href="studentstyle.css">
    <style>
        :root {
            --primary: #2B3990;
            --secondary: #00AEEF;
            --accent: #C4D600;
            --light-gray: #f5f7fa;
            --gray: #e9ecef;
            --dark-gray: #6c757d;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }
        
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: var(--light-gray);
            padding-top: 0; 
        }
        

                .main-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            height: 70px;
        }
        
        
        .danger {
            color: #ff4757;
        }
        
        .main-content {
            flex: 1;
            padding: 30px;
            margin-left: 10px;
        }
        
        .message-center-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .message-center-container h1 {
            font-size: 2.2rem;
            margin-bottom: 30px;
            color: var(--primary);
        }
        
        .user-section {
            margin-bottom: 30px;
            background-color: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        
        .user-section h2 {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: var(--primary);
            padding-bottom: 10px;
            border-bottom: 2px solid var(--gray);
        }
        
        .user-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .user-card {
            display: flex;
            align-items: center;
            padding: 15px;
            background-color: var(--light-gray);
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .user-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            background-color: white;
        }
        
        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            border: 3px solid white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .default-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin-right: 15px;
        }
        
        .user-info {
            flex-grow: 1;
        }
        
        .user-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .role-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .tutor-badge {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .student-badge {
            background-color: #e8f5e9;
            color: #388e3c;
        }
        
        .empty-message {
            text-align: center;
            padding: 20px;
            color: var(--dark-gray);
            font-size: 1rem;
        }
         .top-corner {
        position: fixed;
        top: 0;
        left: 0;
        padding: 10px;
        z-index: 1000;
        }
        .compact-logo {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .compact-logo img {
            width: 30px;
            height: 30px;
        }
        .compact-logo h2 {
            font-size: 1rem;
            margin: 0;
        }
        .danger {
            color: #ff4757;
        }
        .close-btn {
            position: absolute;
            top: 5px;
            right: 5px;
        }
        .close-btn span {
            font-size: 20px;
        }
        
        .admin-badge {
            background-color: #f3e5f5;
            color: #8e24aa;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            margin-bottom: 20px;
            padding: 8px 15px;
            background-color: var(--primary);
            color: white;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .back-button:hover {
            background-color: #C4D600;
            transform: translateY(-2px);
        }
        
        .back-button .material-symbols-sharp {
            margin-right: 5px;
            font-size: 20px;
        }
      </style>
</head>
<body>
    <?php 
    // Include the appropriate header based on user role
    if ($current_user['role'] === 'student') {
        include 'header/stud_head.php';
    } elseif ($current_user['role'] === 'tutor') {
        include 'header/tut_head.php';
    } else {
        // Default header for admin or other roles if needed
        include 'header/header.php'; // You might want to create this
    }
    ?>

    <script>
        // Dropdown functionality
        function toggleDropdown() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }

        // Mobile menu toggle
        document.querySelector('.menu-toggle').addEventListener('click', function() {
            document.querySelector('.nav-links').classList.toggle('show');
        });

        // Avatar upload preview and automatic submission
        document.getElementById('profile_image_upload').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewElement = document.getElementById('profile-image-preview');
                    const placeholderElement = document.getElementById('profile-image-placeholder');

                    if (previewElement) {
                        // If it is already an image, update src
                        previewElement.src = e.target.result;
                    } else if (placeholderElement) {
                        // If it is a placeholder, replace it with an image element
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.alt = "Profile";
                        img.className = "profile-image";
                        img.id = "profile-image-preview";
                        placeholderElement.parentNode.replaceChild(img, placeholderElement);
                    }

                    // Automatically submit forms
                    document.getElementById('image-upload-form').submit();
                }
                reader.readAsDataURL(file);
            }
        });

    </script>
    
    <div class="main-content">
        <div class="message-center-container">
            <h1>Your Messages</h1>  
            
            <?php if (!empty($admins)): ?>
                <div class="user-section">
                    <h2>Administrators</h2>
                    <div class="user-grid">
                        <?php foreach ($admins as $admin): ?>
                            <a href="messages.php?user_id=<?= $admin['user_id'] ?>" class="user-card">
                                <?php if ($admin['profile_image']): ?>
                                    <img src="<?= htmlspecialchars($admin['profile_image']) ?>" class="user-avatar" alt="Profile">
                                <?php else: ?>
                                    <div class="default-avatar">
                                        <?= substr($admin['first_name'], 0, 1) . substr($admin['last_name'], 0, 1) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="user-info">
                                    <div class="user-name"><?= htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']) ?></div>
                                    <span class="role-badge admin-badge">Admin</span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Tutors Section -->
            <div class="user-section">
                <h2>Tutors</h2>
                <?php if (!empty($tutors)): ?>
                    <div class="user-grid">
                        <?php foreach ($tutors as $tutor): ?>
                            <a href="messages.php?user_id=<?= $tutor['user_id'] ?>" class="user-card">
                                <?php if ($tutor['profile_image']): ?>
                                    <img src="<?= htmlspecialchars($tutor['profile_image']) ?>" class="user-avatar" alt="Profile">
                                <?php else: ?>
                                    <div class="default-avatar">
                                        <?= substr($tutor['first_name'], 0, 1) . substr($tutor['last_name'], 0, 1) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="user-info">
                                    <div class="user-name"><?= htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']) ?></div>
                                    <span class="role-badge tutor-badge">Tutor</span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="empty-message">No tutors available</p>
                <?php endif; ?>
            </div>
            
            <!-- Students Section (only visible to tutors) -->
            <?php if ($current_user['role'] === 'tutor'): ?>
                <div class="user-section">
                    <h2>Students</h2>
                    <?php if (!empty($students)): ?>
                        <div class="user-grid">
                            <?php foreach ($students as $student): ?>
                                <a href="messages.php?user_id=<?= $student['user_id'] ?>" class="user-card">
                                    <?php if ($student['profile_image']): ?>
                                        <img src="<?= htmlspecialchars($student['profile_image']) ?>" class="user-avatar" alt="Profile">
                                    <?php else: ?>
                                        <div class="default-avatar">
                                            <?= substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="user-info">
                                        <div class="user-name"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></div>
                                        <span class="role-badge student-badge">Student</span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="empty-message">No students available</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

             <div class="user-section">
            <h2>Community</h2>
            <div class="user-grid">
                <a href="community_chat.php" class="user-card">
                    <div class="default-avatar" style="background-color: var(--accent);">
                        <span class="material-symbols-sharp">groups</span>
                    </div>
                    <div class="user-info">
                        <div class="user-name">Community Chat</div>
                        <span class="role-badge admin-badge">Group Discussion</span>
                    </div>
                </a>
            </div>
        </div>
        

        <div class="message-center-container">
            <a href="<?php echo $back_url; ?>" class="back-button">
                <span class="material-symbols-sharp">arrow_back</span>
                Back
            </a>
        </div>
        
    </div>
</body>
</html>