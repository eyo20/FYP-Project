<?php
// messages_list.php
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
          ORDER BY role DESC, first_name"; // Tutors first (assuming 'tutor' comes after 'student' alphabetically)
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Separate users by role
$tutors = array_filter($users, function($user) {
    return $user['role'] === 'tutor';
});

$students = array_filter($users, function($user) {
    return $user['role'] === 'student';
});
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="msgstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Your Messages</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            text-decoration: none;
            font-family: 'Poppins', sans-serif;   
        }
        body {
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: #f7f7f7;
            padding: 20px;
        }
        .user-list { 
            max-width: 600px; 
            margin: 0 auto; 
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .user-section {
            margin-bottom: 30px;
        }
        .user-section h2 {
            padding: 10px 0;
            border-bottom: 2px solid #eee;
            margin-bottom: 15px;
            color: #555;
        }
        .user-card { 
            display: flex; 
            align-items: center; 
            padding: 12px; 
            border-bottom: 1px solid #eee; 
            transition: background 0.3s;
        }
        .user-card:hover {
            background: #f5f5f5;
        }
        .user-card img { 
            width: 50px; 
            height: 50px; 
            border-radius: 50%; 
            margin-right: 15px; 
            object-fit: cover;
        }
        .user-card a { 
            text-decoration: none; 
            color: #333; 
            flex-grow: 1;
            font-weight: 500;
        }
        .role-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        .tutor-badge {
            background: #e3f2fd;
            color: #1976d2;
        }
        .student-badge {
            background: #e8f5e9;
            color: #388e3c;
        }
        h1 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="user-list">
        <h1>Your Messages</h1>
        
        <!-- Tutors Section -->
        <div class="user-section">
            <h2>Tutors</h2>
            <?php if (!empty($tutors)): ?>
                <?php foreach ($tutors as $tutor): ?>
                    <div class="user-card">
                        <img src="<?= htmlspecialchars($tutor['profile_image'] ?: 'images/default_profile.jpg') ?>" alt="Profile">
                        <a href="student_messages.php?user_id=<?= $tutor['user_id'] ?>">
                            <?= htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']) ?>
                            <span class="role-badge tutor-badge">Tutor</span>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="padding: 10px; color: #666;">No tutors available</p>
            <?php endif; ?>
        </div>
        
        <!-- Students Section -->
        <?php if ($current_user['role'] === 'tutor'): ?>
            <div class="user-section">
                <h2>Students</h2>
                <?php if (!empty($students)): ?>
                    <?php foreach ($students as $student): ?>
                        <div class="user-card">
                            <img src="<?= htmlspecialchars($student['profile_image'] ?: 'images/default_profile.jpg') ?>" alt="Profile">
                            <a href="messages.php?user_id=<?= $student['user_id'] ?>">
                                <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                                <span class="role-badge student-badge">Student</span>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="padding: 10px; color: #666;">No students available</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>