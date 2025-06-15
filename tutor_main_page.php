<?php
session_start();
require_once "db_connection.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user information
$user_query = "SELECT username, email, role, first_name, last_name, phone, profile_image, created_at FROM user WHERE user_id = ?";
$stmt = $conn->prepare($user_query);
if (!$stmt) {
    error_log("Error preparing user query: " . $conn->error);
    die("Error preparing user query: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();

if (!$user_data || $user_data['role'] != 'tutor') {
    header("Location: login.php");
    exit();
}

$username = $user_data['username'];
$email = $user_data['email'];
$first_name = $user_data['first_name'] ?: '';
$last_name = $user_data['last_name'] ?: '';
$phone = $user_data['phone'] ?: '';
$profile_image = $user_data['profile_image'];
$created_at = $user_data['created_at'];
$stmt->close();

// Get tutor profile
$tutor_query = "SELECT major, year, bio, qualifications, is_verified, rating FROM tutorprofile WHERE user_id = ?";
$stmt = $conn->prepare($tutor_query);
if (!$stmt) {
    error_log("Error preparing tutor query: " . $conn->error);
    die("Error preparing tutor query: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tutor_result = $stmt->get_result();

if ($tutor_result->num_rows > 0) {
    $tutor_data = $tutor_result->fetch_assoc();
    $major = $tutor_data['major'] ?: 'Not set';
    $year = $tutor_data['year'] ?: 'Not set';
    $bio = $tutor_data['bio'] ?: '';
    $qualifications = $tutor_data['qualifications'] ?: '';
    $is_verified = $tutor_data['is_verified'];
    $rating = $tutor_data['rating'];
} else {
    $major = 'Not set';
    $year = 'Not set';
    $bio = '';
    $qualifications = '';
    $is_verified = 0;
    $rating = 0;
}
$stmt->close();

// Get unread messages count
$unread_messages_query = "SELECT COUNT(*) as unread_count FROM message WHERE receiver_id = ? AND is_read = 0";
$stmt = $conn->prepare($unread_messages_query);
if (!$stmt) {
    error_log("Error preparing messages query: " . $conn->error);
    die("Error preparing messages query: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$messages_result = $stmt->get_result();
$messages_data = $messages_result->fetch_assoc();
$unread_messages = $messages_data['unread_count'];
$stmt->close();

// Get statistics
// 1. Total sessions
$total_sessions_query = "SELECT COUNT(*) as total_count FROM session WHERE tutor_id = ?";
$stmt = $conn->prepare($total_sessions_query);
if (!$stmt) {
    error_log("Error preparing total sessions query: " . $conn->error);
    die("Error preparing total sessions query: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_result = $stmt->get_result();
$total_data = $total_result->fetch_assoc();
$total_sessions = $total_data['total_count'];
$stmt->close();

// 2. Sessions this month
$month_sessions_query = "SELECT COUNT(*) as month_count
                        FROM session
                        WHERE tutor_id = ? 
                        AND MONTH(start_datetime) = MONTH(CURRENT_DATE())
                        AND YEAR(start_datetime) = YEAR(CURRENT_DATE())";
$stmt = $conn->prepare($month_sessions_query);
if (!$stmt) {
    error_log("Error preparing month sessions query: " . $conn->error);
    die("Error preparing month sessions query: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$month_result = $stmt->get_result();
$month_data = $month_result->fetch_assoc();
$month_sessions = $month_data['month_count'];
$stmt->close();

// 3. Total students
$students_count_query = "SELECT COUNT(DISTINCT student_id) as student_count
                        FROM session
                        WHERE tutor_id = ?";
$stmt = $conn->prepare($students_count_query);
if (!$stmt) {
    error_log("Error preparing students query: " . $conn->error);
    die("Error preparing students query: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$students_result = $stmt->get_result();
$students_data = $students_result->fetch_assoc();
$total_students = $students_data['student_count'];
$stmt->close();

// 4. Pending requests count
$pending_requests_query = "SELECT COUNT(*) as pending_count
                          FROM session_requests
                          WHERE tutor_id = ? AND status = 'pending'";
$stmt = $conn->prepare($pending_requests_query);
if (!$stmt) {
    error_log("Error preparing pending requests query: " . $conn->error);
    die("Database error: Failed to prepare pending requests query.");
}
$stmt->bind_param("i", $user_id);
if (!$stmt->execute()) {
    error_log("Error executing pending requests query: " . $stmt->error);
    die("Database error: Failed to execute pending requests query.");
}
$pending_result = $stmt->get_result();
$pending_data = $pending_result->fetch_assoc();
$pending_requests = $pending_data['pending_count'] ?? 0;
error_log("Pending requests for tutor_id $user_id: $pending_requests");
$stmt->close();

// Get approved sessions for calendar with details
$approved_sessions_query = "
    SELECT s.session_id, DATE_FORMAT(s.start_datetime, '%Y-%m-%d') as date,
           s.start_datetime, s.end_datetime, s.student_id, s.course_id, s.location_id,
           CONCAT(u.first_name, ' ', u.last_name) as student_name,
           c.course_name, l.location_name as location_name
    FROM session s
    JOIN user u ON s.student_id = u.user_id
    JOIN course c ON s.course_id = c.id
    JOIN location l ON s.location_id = l.location_id
    WHERE s.tutor_id = ? AND s.status = 'confirmed'
    AND YEAR(s.start_datetime) = ? AND MONTH(s.start_datetime) = ?
    ORDER BY s.start_datetime";
$stmt = $conn->prepare($approved_sessions_query);
if (!$stmt) {
    error_log("Error preparing approved sessions query: " . $conn->error);
    die("Error preparing approved sessions query: " . $conn->error);
}
$current_month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$current_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$stmt->bind_param("iii", $user_id, $current_year, $current_month);
$stmt->execute();
$approved_result = $stmt->get_result();
$approved_sessions = [];
while ($row = $approved_result->fetch_assoc()) {
    $date = $row['date'];
    if (!isset($approved_sessions[$date])) {
        $approved_sessions[$date] = [];
    }
    $approved_sessions[$date][] = [
        'session_id' => $row['session_id'],
        'student_name' => $row['student_name'],
        'course_name' => $row['course_name'],
        'location_name' => $row['location_name'],
        'start_datetime' => $row['start_datetime'],
        'end_datetime' => $row['end_datetime'],
    ];
}
$stmt->close();

// Get calendar data
$days_in_month = date('t', mktime(0, 0, 0, $current_month, 1, $current_year));
$first_day_of_month = date('N', mktime(0, 0, 0, $current_month, 1, $current_year));

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peer Tutoring Platform - Tutor Dashboard</title>
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
            position: relative;
        }

        .nav-links a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .nav-links a.active {
            background-color: var(--accent);
            color: white;
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
            position: absolute;
            top: -5px;
            right: -5px;
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
            cursor: pointer;
            overflow: hidden;
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        main {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background-color: rgba(196, 214, 0, 0.1);
            border-left: 4px solid var(--accent);
            color: #5a6400;
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            border-left: 4px solid #dc3545;
            color: #dc3545;
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
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .verified-badge {
            background-color: var(--accent);
            color: white;
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            display: inline-block;
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

        .calendar-container {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .calendar-nav {
            display: flex;
            gap: 10px;
        }

        .calendar-nav button {
            background-color: var(--light-gray);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .calendar-nav button:hover {
            background-color: var(--gray);
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
        }

        .calendar-day-header {
            text-align: center;
            font-weight: 600;
            padding: 0.5rem;
            background-color: var(--light-gray);
            border-radius: 4px;
        }

        .calendar-day {
            min-height: 100px;
            border: 1px solid var(--gray);
            border-radius: 4px;
            padding: 0.5rem;
            position: relative;
        }

        .calendar-day.today {
            background-color: rgba(196, 214, 0, 0.1);
            border-color: var(--accent);
        }

        .calendar-day.other-month {
            background-color: #f9f9f9;
            color: var(--dark-gray);
        }

        .day-number {
            position: absolute;
            top: 5px;
            right: 5px;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
        }

        .today .day-number {
            background-color: var(--accent);
            color: white;
            border-radius: 50%;
        }

        .booking-indicator {
            margin-top: 25px;
            padding: 0.3rem;
            border-radius: 4px;
            font-size: 0.8rem;
            background-color: rgba(0, 174, 239, 0.2);
            border-left: 3px solid var(--secondary);
            text-align: center;
            cursor: pointer;
            margin-bottom: 5px;
        }

        .booking-indicator:hover {
            background-color: rgba(0, 174, 239, 0.3);
        }

        .booking-details {
            display: none;
            background-color: white;
            border: 1px solid var(--gray);
            border-radius: 4px;
            padding: 0.5rem;
            margin-top: 5px;
            font-size: 0.75rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .booking-details.active {
            display: block;
        }

        .booking-details p {
            margin-bottom: 0.3rem;
        }

        .booking-details strong {
            color: var(--primary);
        }

        footer {
            background-color: var(--primary);
            color: white;
            text-align: center;
            padding: 1.5rem;
            margin-top: 2rem;
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

            .welcome-section {
                flex-direction: column;
                text-align: center;
            }

            .calendar-grid {
                grid-template-columns: repeat(1, 1fr);
            }

            .calendar-day-header {
                display: none;
            }

            .calendar-day {
                display: flex;
                flex-direction: column;
                min-height: auto;
                padding: 1rem;
            }

            .day-number:before {
                content: attr(data-day);
                margin-right: 5px;
            }

            .booking-indicator {
                font-size: 0.9rem;
                padding: 0.5rem;
            }

            .booking-details {
                font-size: 0.85rem;
                padding: 0.75rem;
            }
        }
    </style>
</head>

<body>
    <?php include 'header/tut_head.php'; ?>

    <main>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['error']); ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="welcome-section">
            <div class="profile-image-container">
                <?php if ($profile_image): ?>
                    <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile" class="profile-image">
                <?php else: ?>
                    <div class="profile-image-placeholder"><?php echo strtoupper(substr($first_name, 0, 1)); ?></div>
                <?php endif; ?>
            </div>
            <div class="welcome-info">
                <h1 class="welcome-title">
                    Welcome back, <?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>
                    <?php if ($is_verified): ?>
                        <span class="verified-badge">Verified</span>
                    <?php endif; ?>
                </h1>
                <p>You can view your tutoring schedule here, view appointment requests, and interact with students.</p>

                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-icon">📚</div>
                        <div class="stat-value"><?php echo $total_sessions; ?></div>
                        <div class="stat-label">Total Sessions</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">📅</div>
                        <div class="stat-value"><?php echo $month_sessions; ?></div>
                        <div class="stat-label">Sessions This Month</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">👨‍🎓</div>
                        <div class="stat-value"><?php echo $total_students; ?></div>
                        <div class="stat-label">Number of Students</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🔔</div>
                        <a href="tutor_requests.php" style="text-decoration: none; color: inherit;">
                            <div class="stat-value"><?php echo $pending_requests; ?></div>
                            <div class="stat-label">Pending Requests</div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="section-title">
            <h2>Approved Bookings</h2>
        </div>

        <div class="calendar-container">
            <div class="calendar-header">
                <h3 id="current-month"><?php echo date('F Y', mktime(0, 0, 0, $current_month, 1, $current_year)); ?></h3>
                <div class="calendar-nav">
                    <button id="prev-month">Previous Month</button>
                    <button id="next-month">Next Month</button>
                </div>
            </div>

            <div class="calendar-grid">
                <div class="calendar-day-header">Sun</div>
                <div class="calendar-day-header">Mon</div>
                <div class="calendar-day-header">Tue</div>
                <div class="calendar-day-header">Wed</div>
                <div class="calendar-day-header">Thu</div>
                <div class="calendar-day-header">Fri</div>
                <div class="calendar-day-header">Sat</div>

                <?php
                // Fill blanks before first day
                for ($i = 1; $i < $first_day_of_month; $i++) {
                    $prev_month_day = date('j', strtotime('-' . ($first_day_of_month - $i) . ' days', strtotime("$current_year-$current_month-01")));
                    echo '<div class="calendar-day other-month"><div class="day-number">' . $prev_month_day . '</div></div>';
                }

                // Fill current month days
                $today = date('j');
                for ($day = 1; $day <= $days_in_month; $day++) {
                    $is_today = ($day == $today && $current_month == date('n') && $current_year == date('Y'));
                    $day_class = $is_today ? 'calendar-day today' : 'calendar-day';
                    $date_string = sprintf("%04d-%02d-%02d", $current_year, $current_month, $day);
                    echo "<div class=\"$day_class\" data-day=\"". date('D', strtotime($date_string)) ."\">";
                    echo "<div class=\"day-number\">$day</div>";
                    if (isset($approved_sessions[$date_string])) {
                        foreach ($approved_sessions[$date_string] as $index => $session) {
                            $booking_number = $index + 1;
                            echo "<div class=\"booking-indicator\" data-session-id=\"{$session['session_id']}\">Booking $booking_number</div>";
                            echo "<div class=\"booking-details\" id=\"details-{$session['session_id']}\">";
                            echo "<p><strong>Student:</strong> " . htmlspecialchars($session['student_name']) . "</p>";
                            echo "<p><strong>Course:</strong> " . htmlspecialchars($session['course_name']) . "</p>";
                            echo "<p><strong>Time:</strong> " . date('h:i A', strtotime($session['start_datetime'])) . " - " . date('h:i A', strtotime($session['end_datetime'])) . "</p>";
                            echo "<p><strong>Location:</strong> " . htmlspecialchars($session['location_name']) . "</p>";
                            echo "</div>";
                        }
                    }
                    echo "</div>";
                }

                // Fill blanks after last day
                $days_after = (7 - (($first_day_of_month - 1 + $days_in_month) % 7)) % 7;
                for ($i = 1; $i <= $days_after; $i++) {
                    $next_day = date('j', strtotime("+$i days", strtotime("$current_year-$current_month-$days_in_month")));
                    echo '<div class="calendar-day other-month"><div class="day-number">' . $next_day . '</div></div>';
                }
                ?>
            </div>
        </div>
    </main>

    <footer>
        <p>© <?php echo date('Y'); ?> PeerLearn Platform. All rights reserved.</p>
    </footer>

    <script>
        let m = <?php echo $current_month; ?>,
            y = <?php echo $current_year; ?>;
        document.getElementById('prev-month').onclick = () => {
            if (--m < 1) {
                m = 12;
                y--;
            }
            updateCalendar();
        };
        document.getElementById('next-month').onclick = () => {
            if (++m > 12) {
                m = 1;
                y++;
            }
            updateCalendar();
        };

        function updateCalendar() {
            location.href = `tutor_main_page.php?month=${m}&year=${y}`;
        }

        // Toggle booking details
        document.querySelectorAll('.booking-indicator').forEach(indicator => {
            indicator.addEventListener('click', () => {
                const sessionId = indicator.getAttribute('data-session-id');
                const details = document.getElementById(`details-${sessionId}`);
                
                // Close all other details panels
                document.querySelectorAll('.booking-details.active').forEach(activeDetails => {
                    if (activeDetails !== details) {
                        activeDetails.classList.remove('active');
                    }
                });

                // Toggle the clicked details panel
                details.classList.toggle('active');
            });
        });
    </script>
</body>
</html>