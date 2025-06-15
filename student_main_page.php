<?php
session_start();
require_once "db_connection.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user info including first_name, last_name, and profile_image
$user_query = "SELECT username, email, role, first_name, last_name, profile_image, created_at FROM user WHERE user_id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();

if (!$user_data) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$username = $user_data['username'];
$email = $user_data['email'];
$role = $user_data['role'];
$first_name = $user_data['first_name'] ?: '';
$last_name = $user_data['last_name'] ?: '';
$profile_image = $user_data['profile_image'];
$created_at = $user_data['created_at'];
$stmt->close();

// Check if studentprofile table exists
$table_check = $conn->query("SHOW TABLES LIKE 'studentprofile'");
$table_exists = $table_check->num_rows > 0;

// If table exists, fetch student profile if any
$major = $year = 'Not set'; // default values
if ($table_exists) {
    $student_query = "SELECT major, year FROM studentprofile WHERE user_id = ?";
    $stmt = $conn->prepare($student_query);
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

// Initialize variables
$upcoming_sessions = [];
$completed_sessions = 0;
$subjects_count = 0;

// Fetch upcoming tutoring sessions
$upcoming_sessions_query = "
    SELECT s.session_id,
           s.created_at AS session_date,
           s.status,
           c.course_name AS subject,
           u.username AS tutor_name,
           s.start_datetime
    FROM session s
    JOIN user u ON s.tutor_id = u.user_id
    JOIN course c ON s.course_id = c.id
    WHERE s.student_id = ? 
      AND s.status = 'confirmed'
    ORDER BY s.start_datetime
    LIMIT 5";

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

// Fetch count of completed sessions
$completed_sessions_query = "
    SELECT COUNT(*) AS completed_count
    FROM session
    WHERE student_id = ? 
      AND status = 'completed'";

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

// Fetch count of distinct subjects learned
$subjects_query = "
    SELECT COUNT(DISTINCT course_id) AS subjects_count
    FROM session
    WHERE student_id = ?";

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

// Initialize variables
$unread_messages = 0;
$recommended_tutors = [];

// Fetch unread message count
$unread_messages_query = "SELECT COUNT(*) AS unread_count 
                         FROM message 
                         WHERE receiver_id = ? AND is_read = 0";

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

// Fetch recommended tutors sorted by average rating
$recommended_tutors_query = "
    SELECT 
        u.user_id AS tutor_id, 
        u.username, 
        c.course_name AS subject,
        COALESCE(AVG(tr.rating), 0) AS avg_rating
    FROM tutorsubject ts
    JOIN user u ON ts.tutor_id = u.user_id
    JOIN course c ON ts.course_id = c.id
    LEFT JOIN tutorrating tr ON u.user_id = tr.tutor_id
    WHERE c.course_name LIKE ?
    GROUP BY u.user_id, u.username, c.course_name
    ORDER BY avg_rating DESC, u.username ASC
    LIMIT 3";

$major_search = !empty($major) && $major != 'Not set' ? "%$major%" : "%";

try {
    $stmt = $conn->prepare($recommended_tutors_query);
    if ($stmt) {
        $stmt->bind_param("s", $major_search);
        $stmt->execute();
        $tutors_result = $stmt->get_result();

        while ($row = $tutors_result->fetch_assoc()) {
            $recommended_tutors[] = $row;
        }
        $stmt->close();
    } else {
        error_log("Failed to prepare recommended tutors query: " . $conn->error);
    }
} catch (Exception $e) {
    error_log("Error in recommended tutors query: " . $e->getMessage());
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peer Tutoring Platform - Student Profile</title>
    <style>
        :root {
            --primary: #2B3990;
            --secondary: #00AEEF;
            --accent: #C4D600;
            --light-gray: #f5f7fa;
            --gray: #e9ecef;
            --dark-gray: #6c757d;
            --success: #28a745;
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

        .navbar {
            background-color: var(--primary);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .logo {
            font-weight: bold;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo-icon {
            width: 30px;
            height: 30px;
            background-color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-weight: bold;
        }

        .nav-links {
            display: flex;
            gap: 20px;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 0.5rem;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .nav-links a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            background-color: var(--secondary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            overflow: hidden;
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .notification-badge {
            background-color: var(--accent);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
        }

        main {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .welcome-section {
            background-color: white;
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .profile-image-container {
            position: relative;
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

        .welcome-info {
            flex: 1;
        }

        .welcome-title {
            color: var(--primary);
            margin-bottom: 0.5rem;
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
        }

        .section-title {
            margin: 2rem 0 1rem;
            color: var(--primary);
            font-weight: 600;
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
            transition: transform 0.3s;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
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
            display: inline-block;
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

        .session-actions {
            display: flex;
            gap: 0.5rem;
        }

        .session-actions button {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            background-color: var(--secondary);
            color: white;
            cursor: pointer;
            transition: background-color 0.3s;
            font-size: 0.9rem;
        }

        .session-actions button:hover {
            background-color: #0099d9;
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
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                padding: 1rem;
            }

            .nav-links {
                margin-top: 1rem;
                width: 100%;
                justify-content: space-around;
            }

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
        }
    </style>
</head>

<body>
    <?php include 'header/stud_head.php'; ?>

    <main>
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
                    <div class="stat-card" onclick="window.location.href='messages.php'" 
                        title="Click to view messages">
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

            <div class="action-card" onclick="window.location.href='manage_appointment.php'">
                <div class="action-header">
                    <div class="action-icon">📅</div>
                    <div class="action-title">Manage Appointment</div>
                </div>
                <p class="action-description">Manage the booking sessions and view the completed sessions.</p>
                <a href="manage_appointment.php" class="btn">Manage Now</a>
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
                            <?php if ($date_display == "Today"): ?>
                                <div class="session-actions">
                                    <button onclick="joinSession(<?php echo $session['session_id']; ?>)">Join</button>
                                </div>
                            <?php endif; ?>
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
                            <div class="action-icon" style="background-color: var(--primary);">👨‍🏫</div>
                            <div class="action-title"><?php echo htmlspecialchars($tutor['username']); ?></div>
                        </div>
                        <p class="action-description"><?php echo htmlspecialchars($tutor['subject']); ?></p>
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
                        <a href="tutor_profile.php?id=<?php echo $tutor['tutor_id']; ?>" class="btn">View Profile</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="action-card">
                    <p class="empty-state">No recommended tutors for your major at this time.</p>
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