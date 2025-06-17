<?php
session_start();
date_default_timezone_set('Asia/Kuala_Lumpur');
require_once "db_connection.php";

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    $_SESSION['error'] = "Please log in as a student.";
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$user_query = "SELECT username, email, role, first_name, last_name, profile_image, created_at FROM user WHERE user_id = ?";
$stmt = $conn->prepare($user_query);
if (!$stmt) {
    error_log("Error preparing user query: " . $conn->error);
    $_SESSION['error'] = "Database error. Please try again later.";
    header("Location: login.php");
    exit();
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();

if (!$user_data) {
    error_log("User not found: user_id=$user_id");
    $_SESSION['error'] = "User not found.";
    header("Location: login.php");
    exit();
}

$username = $user_data['username'];
$email = $user_data['email'];
$first_name = $user_data['first_name'] ?: '';
$last_name = $user_data['last_name'] ?: '';
$profile_image = $user_data['profile_image'];
$created_at = $user_data['created_at'];
$stmt->close();

// Fetch student profile
$major = $year = 'Not set';
$table_check = $conn->query("SHOW TABLES LIKE 'studentprofile'");
if ($table_check->num_rows > 0) {
    $student_query = "SELECT major, year FROM studentprofile WHERE user_id = ?";
    $stmt = $conn->prepare($student_query);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $student_result = $stmt->get_result();
        if ($student_result->num_rows > 0) {
            $student_data = $student_result->fetch_assoc();
            $major = $student_data['major'] ?: 'Not set';
            $year = $student_data['year'] ?: 'Not set';
        }
        $stmt->close();
    }
}

// Fetch upcoming sessions
$upcoming_sessions = [];
$upcoming_sessions_query = "
    SELECT s.session_id, s.created_at AS session_date, s.status, c.course_name AS subject,
           u.username AS tutor_name, s.start_datetime, s.end_datetime
    FROM session s
    JOIN user u ON s.tutor_id = u.user_id
    JOIN course c ON s.course_id = c.id
    WHERE s.student_id = ? AND s.status = 'confirmed' AND s.end_datetime > NOW()
    ORDER BY s.start_datetime";
try {
    $stmt = $conn->prepare($upcoming_sessions_query);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $upcoming_sessions_result = $stmt->get_result();
        while ($row = $upcoming_sessions_result->fetch_assoc()) {
            $upcoming_sessions[] = $row;
        }
        $stmt->close();
    } else {
        error_log("Failed to prepare upcoming sessions query: " . $conn->error);
    }
} catch (Exception $e) {
    error_log("Error in upcoming sessions query: " . $e->getMessage());
}

// Fetch completed sessions count
$completed_sessions = 0;
$completed_sessions_query = "SELECT COUNT(*) AS completed_count FROM session WHERE student_id = ? AND status = 'completed'";
try {
    $stmt = $conn->prepare($completed_sessions_query);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $completed_result = $stmt->get_result();
        $completed_data = $completed_result->fetch_assoc();
        $completed_sessions = $completed_data['completed_count'];
        $stmt->close();
    } else {
        error_log("Failed to prepare completed sessions query: " . $conn->error);
    }
} catch (Exception $e) {
    error_log("Error in completed sessions query: " . $e->getMessage());
}

// Fetch distinct subjects count
$subjects_count = 0;
$subjects_query = "SELECT COUNT(DISTINCT course_id) AS subjects_count FROM session WHERE student_id = ?";
try {
    $stmt = $conn->prepare($subjects_query);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $subjects_result = $stmt->get_result();
        $subjects_data = $subjects_result->fetch_assoc();
        $subjects_count = $subjects_data['subjects_count'];
        $stmt->close();
    } else {
        error_log("Failed to prepare subjects count query: " . $conn->error);
    }
} catch (Exception $e) {
    error_log("Error in subjects count query: " . $e->getMessage());
}

// Fetch unread messages count
$unread_messages = 0;
$unread_messages_query = "SELECT COUNT(*) AS unread_count FROM message WHERE receiver_id = ? AND is_read = 0";
try {
    $stmt = $conn->prepare($unread_messages_query);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $messages_result = $stmt->get_result();
        $messages_data = $messages_result->fetch_assoc();
        $unread_messages = $messages_data['unread_count'];
        $stmt->close();
    } else {
        error_log("Failed to prepare unread messages query: " . $conn->error);
    }
} catch (Exception $e) {
    error_log("Error in unread messages query: " . $e->getMessage());
}

// Fetch recommended tutors
$recommended_tutors = [];
$recommended_tutors_query = "
    SELECT 
        u.user_id AS tutor_id, 
        u.username, 
        u.profile_image,
        u.first_name,
        GROUP_CONCAT(DISTINCT c.course_name ORDER BY c.course_name SEPARATOR ', ') AS subjects,
        COALESCE(AVG(r.rating), 0) AS avg_rating
    FROM user u
    JOIN tutorsubject ts ON u.user_id = ts.tutor_id
    JOIN course c ON ts.course_id = c.id
    LEFT JOIN review r ON u.user_id = r.tutor_id
    WHERE u.role = 'tutor'
    GROUP BY u.user_id, u.username, u.profile_image, u.first_name
    ORDER BY avg_rating DESC, u.username ASC
    LIMIT 3";
try {
    $stmt = $conn->prepare($recommended_tutors_query);
    if ($stmt) {
        $stmt->execute();
        $tutors_result = $stmt->get_result();
        while ($row = $tutors_result->fetch_assoc()) {
            $recommended_tutors[] = $row;
            error_log("Tutor recommended: user_id={$row['tutor_id']}, username={$row['username']}, rating={$row['avg_rating']}");
        }
        if (empty($recommended_tutors)) {
            error_log("No tutors found for recommendation. Check user, tutorsubject, course tables.");
        }
        $stmt->close();
    } else {
        error_log("Failed to prepare recommended tutors query: " . $conn->error);
        $_SESSION['error'] = "Failed to load recommended tutors.";
    }
} catch (Exception $e) {
    error_log("Error in recommended tutors query: " . $e->getMessage());
    $_SESSION['error'] = "Error loading tutors.";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peer Tutoring Platform - Student Dashboard</title>
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --accent: #C4D600;
            --success: #27ae60;
            --danger: #e74c3c;
            --warning: #f39c12;
            --light: #ecf0f1;
            --dark: #34495e;
            --gray: #bdc3c7;
            --light-gray: #f8f9fa;
            --dark-gray: #7f8c8d;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            background-color: var(--light-gray);
            color: #333;
        }
        main {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
            position: relative;
            z-index: 1;
        }
        .welcome-section {
            background-color: white;
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
            display: flex;
            align-items: center;
            gap: 2rem;
            position: relative;
            z-index: 0;
        }
        .profile-image-container {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary);
        }
        .profile-image-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: var(--secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            border: 3px solid var(--primary);
        }
        .tutor-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
        }
        .tutor-avatar-placeholder {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
            border: 2px solid var(--primary);
        }
        .welcome-info {
            flex: 1;
        }
        .welcome-title {
            color: var(--primary);
            margin-bottom: 0.5rem;
            font-size: 1.8rem;
        }
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }
        .stat-card {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            text-align: center;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .stat-icon {
            width: 50px;
            height: 50px;
            background-color: var(--light-gray);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: var(--primary);
            font-size: 1.5rem;
        }
        .stat-value {
            font-size: 1.75rem;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        .stat-label {
            color: var(--dark-gray);
            font-size: 0.9rem;
        }
        .section-title {
            margin: 2rem 0 1rem;
            color: var(--primary);
            font-weight: 600;
            font-size: 1.5rem;
        }
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .action-card {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            gap: 1rem;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .action-card .btn {
            display: inline-block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        .action-card:hover .btn {
            display: inline-block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        .action-header {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .action-icon {
            width: 40px;
            height: 40px;
            background-color: var(--secondary);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
        }
        .action-title {
            font-weight: 600;
            color: var(--primary);
            font-size: 1.1rem;
        }
        .action-description {
            color: var(--dark-gray);
            font-size: 0.9rem;
        }
        .rating {
            color: var(--accent);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        .btn {
            background-color: var(--accent);
            color: white;
            border: none;
            padding: 0.75rem 1rem;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
            text-align: center;
            text-decoration: none;
            display: inline-block !important;
            visibility: visible !important;
            opacity: 1 !important;
            font-size: 0.95rem;
        }
        .btn:hover {
            background-color: #b3c300;
        }
        .upcoming-sessions {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        .session-list {
            margin-top: 1rem;
            display: grid;
            gap: 1rem;
        }
        .session-card {
            background-color: var(--light-gray);
            border-radius: 8px;
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
        }
        .session-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .session-status {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: var(--success);
            position: absolute;
            top: 1rem;
            left: 1rem;
        }
        .session-time {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            min-width: 100px;
            text-align: center;
            font-size: 0.9rem;
        }
        .session-time .date {
            font-weight: 600;
            color: var(--primary);
        }
        .session-time .time {
            color: var(--dark-gray);
        }
        .session-time .icon {
            font-size: 1.2rem;
            color: var(--secondary);
            margin-bottom: 0.25rem;
        }
        .session-info {
            flex-grow: 1;
        }
        .session-subject {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.25rem;
        }
        .session-tutor {
            color: var(--dark-gray);
            font-size: 0.9rem;
        }
        footer {
            background-color: var(--primary);
            color: white;
            text-align: center;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--dark-gray);
        }
        .empty-state p {
            margin-bottom: 1rem;
            font-size: 1rem;
        }
        .alert-danger {
            color: #dc3545;
            padding: 10px;
            background-color: rgba(220, 53, 69, 0.1);
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .alert-success {
            color: #28a745;
            padding: 10px;
            background-color: rgba(40, 167, 69, 0.1);
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        @media (max-width: 768px) {
            .stats-container,
            .quick-actions {
                grid-template-columns: 1fr;
            }
            .welcome-section {
                flex-direction: column;
                text-align: center;
            }
            .session-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            .session-time {
                text-align: left;
            }
            .section-title {
                font-size: 1.3rem;
            }
            .welcome-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'header/stud_head.php'; ?>

    <main>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-success"><?php echo htmlspecialchars($_SESSION['success']); ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-danger"><?php echo htmlspecialchars($_SESSION['error']); ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        <section class="welcome-section">
            <div class="profile-image-container">
                <?php if ($profile_image): ?>
                    <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile" class="profile-image">
                <?php else: ?>
                    <div class="profile-image-placeholder"><?php echo strtoupper(substr($first_name, 0, 1)); ?></div>
                <?php endif; ?>
            </div>
            <div class="welcome-info">
                <h1 class="welcome-title">Welcome back, <?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>!</h1>
                <p>Welcome to your student dashboard! Here you can view your upcoming tutoring sessions, find and book tutors, manage your appointments, and communicate with tutors via messages.</p>
                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-icon">📚</div>
                        <div class="stat-value"><?php echo $completed_sessions; ?></div>
                        <div class="stat-label">Completed Sessions</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">📖</div>
                        <div class="stat-value"><?php echo $subjects_count; ?></div>
                        <div class="stat-label">Courses Learned</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">📅</div>
                        <div class="stat-value"><?php echo count($upcoming_sessions); ?></div>
                        <div class="stat-label">Upcoming Sessions</div>
                    </div>
                    <div class="stat-card" onclick="window.location.href='messages.php'" title="Click to view messages">
                        <div class="stat-icon">💬</div>
                        <div class="stat-value" style="color: <?php echo ($unread_messages > 0) ? 'var(--accent)' : 'var(--dark-gray)'; ?>">
                            <?php echo ($unread_messages > 0) ? $unread_messages : '-'; ?>
                        </div>
                        <div class="stat-label">
                            Unread Messages
                            <?php if ($unread_messages > 0): ?>
                                <span style="font-size: 0.8rem; color: var(--accent)">(New!)</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <h2 class="section-title">Quick Actions</h2>
        <div class="quick-actions">
            <div class="action-card" onclick="window.location.href='student_profile.php'">
                <div class="action-header">
                    <div class="action-icon">⭐</div>
                    <div class="action-title">Edit Profile</div>
                </div>
                <p class="action-description">Add your personal information and related settings.</p>
                <a href="student_profile.php" class="btn">Edit Now</a>
            </div>
            <div class="action-card" onclick="window.location.href='find_tutors.php'">
                <div class="action-header">
                    <div class="action-icon">🔍</div>
                    <div class="action-title">Find Tutors</div>
                </div>
                <p class="action-description">Filter tutors by subject, availability, reviews and book a new session.</p>
                <a href="find_tutors.php" class="btn">Search Now</a>
            </div>
            <div class="action-card" onclick="window.location.href='student_sessions.php'">
                <div class="action-header">
                    <div class="action-icon">📅</div>
                    <div class="action-title">Manage Appointment</div>
                </div>
                <p class="action-description">Manage the booking sessions and view the completed sessions.</p>
                <a href="student_sessions.php" class="btn">Manage Now</a>
            </div>
            <div class="action-card" onclick="window.location.href='messages.php'">
                <div class="action-header">
                    <div class="action-icon">💬</div>
                    <div class="action-title">Messages</div>
                </div>
                <p class="action-description">View your messages and communicate with tutors.</p>
                <a href="messages.php" class="btn">View Messages <?php if ($unread_messages > 0): ?><span class="notification-badge"><?php echo $unread_messages; ?></span><?php endif; ?></a>
            </div>
        </div>

        <h2 class="section-title">Upcoming Sessions</h2>
        <div class="upcoming-sessions">
            <div class="session-list">
                <?php if (count($upcoming_sessions) > 0): ?>
                    <?php foreach ($upcoming_sessions as $session): ?>
                        <?php
                        $session_date = new DateTime($session['start_datetime']);
                        $today = new DateTime('today');
                        $tomorrow = new DateTime('tomorrow');
                        if ($session_date->format('Y-m-d') == $today->format('Y-m-d')) {
                            $date_display = "Today";
                        } elseif ($session_date->format('Y-m-d') == $tomorrow->format('Y-m-d')) {
                            $date_display = "Tomorrow";
                        } else {
                            $date_display = $session_date->format('M d');
                        }
                        ?>
                        <div class="session-card">
                            <div class="session-status"></div>
                            <div class="session-time">
                                <span class="icon">📅</span>
                                <div class="date"><?php echo $date_display; ?></div>
                                <div class="time"><?php echo $session_date->format('H:i'); ?></div>
                            </div>
                            <div class="session-info">
                                <div class="session-subject"><?php echo htmlspecialchars($session['subject']); ?></div>
                                <div class="session-tutor">with <?php echo htmlspecialchars($session['tutor_name']); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <p>You have no scheduled sessions.</p>
                        <a href="find_tutors.php" class="btn">Find Tutors</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <h2 class="section-title">Recommended Tutors</h2>
        <div class="quick-actions">
            <?php if (count($recommended_tutors) > 0): ?>
                <?php foreach ($recommended_tutors as $tutor): ?>
                    <div class="action-card">
                        <div class="action-header">
                            <?php if ($tutor['profile_image']): ?>
                                <img src="<?php echo htmlspecialchars($tutor['profile_image']); ?>" alt="Tutor Avatar" class="tutor-avatar">
                            <?php else: ?>
                                <div class="tutor-avatar-placeholder"><?php echo strtoupper(substr($tutor['first_name'], 0, 1)); ?></div>
                            <?php endif; ?>
                            <div class="action-title"><?php echo htmlspecialchars($tutor['username']); ?></div>
                        </div>
                        <p class="action-description">Teaching courses: <?php echo htmlspecialchars($tutor['subjects']); ?></p>
                        <p class="rating">
                            ⭐ 
                            <?php 
                            if ($tutor['avg_rating'] > 0) {
                                echo number_format($tutor['avg_rating'], 1) . '/5';
                            } else {
                                echo 'No ratings yet';
                            }
                            ?>
                        </p>
                        <a href="find_tutors.php?tutor_id=<?php echo $tutor['tutor_id']; ?>" class="btn">View Profile</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="action-card">
                    <p class="empty-state">No recommended tutors available at this time.</p>
                    <a href="find_tutors.php" class="btn">Browse All Tutors</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <p>© <?php echo date('Y'); ?> PeerTutor Platform. All rights reserved.</p>
    </footer>
    <script>
        function toggleDropdown() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }

        function joinSession(sessionId) {
            alert("Joining session #" + sessionId);
        }
    </script>
</body>
</html>