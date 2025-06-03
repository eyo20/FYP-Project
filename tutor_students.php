<?php
session_start();
require_once "db_connection.php";

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 获取用户信息
$user_query = "SELECT username, email, role, first_name, last_name, phone, profile_image, created_at FROM user WHERE user_id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();

// 检查用户是否为导师
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

// 获取未读消息数量
$unread_messages_query = "SELECT COUNT(*) as unread_count FROM message WHERE receiver_id = ? AND is_read = 0";
$stmt = $conn->prepare($unread_messages_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$messages_result = $stmt->get_result();
$messages_data = $messages_result->fetch_assoc();
$unread_messages = $messages_data['unread_count'];
$stmt->close();

// 获取待处理的预约请求数量
$pending_requests_query = "SELECT COUNT(*) as pending_count 
                          FROM session_requests 
                          WHERE tutor_id = ? AND status = 'pending'";
$stmt = $conn->prepare($pending_requests_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending_result = $stmt->get_result();
$pending_data = $pending_result->fetch_assoc();
$pending_requests = $pending_data['pending_count'];
$stmt->close();

// 获取当前预约的学生列表（状态为scheduled）
$current_sessions_query = "SELECT s.session_id, s.start_datetime, s.end_datetime, s.status,
                          u.user_id, u.first_name, u.last_name, u.email, u.phone, u.profile_image,
                          c.course_name, c.course_code, l.location_name
                          FROM session s
                          JOIN user u ON s.student_id = u.user_id
                          JOIN course c ON s.course_id = c.course_id
                          LEFT JOIN location l ON s.location_id = l.location_id
                          WHERE s.tutor_id = ? AND s.status = 'scheduled'
                          ORDER BY s.start_datetime ASC";
$stmt = $conn->prepare($current_sessions_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$current_sessions_result = $stmt->get_result();
$current_sessions = [];
while ($row = $current_sessions_result->fetch_assoc()) {
    $current_sessions[] = $row;
}
$stmt->close();

// 获取历史预约的学生列表（状态为completed或cancelled）
$past_sessions_query = "SELECT s.session_id, s.start_datetime, s.end_datetime, s.status, s.cancellation_reason,
                        u.user_id, u.first_name, u.last_name, u.email, u.phone, u.profile_image,
                        c.course_name, c.course_code, l.location_name,
                        r.rating, r.comment
                        FROM session s
                        JOIN user u ON s.student_id = u.user_id
                        JOIN course c ON s.course_id = c.course_id
                        LEFT JOIN location l ON s.location_id = l.location_id
                        LEFT JOIN review r ON s.session_id = r.session_id AND r.tutor_id = s.tutor_id
                        WHERE s.tutor_id = ? AND (s.status = 'completed' OR s.status = 'cancelled')
                        ORDER BY s.start_datetime DESC";
$stmt = $conn->prepare($past_sessions_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$past_sessions_result = $stmt->get_result();
$past_sessions = [];
while ($row = $past_sessions_result->fetch_assoc()) {
    $past_sessions[] = $row;
}
$stmt->close();

// 获取所有学生的统计信息
$student_stats_query = "SELECT 
                        s.student_id,
                        u.first_name,
                        u.last_name,
                        u.profile_image,
                        COUNT(s.session_id) as total_sessions,
                        SUM(CASE WHEN s.status = 'completed' THEN 1 ELSE 0 END) as completed_sessions,
                        SUM(CASE WHEN s.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_sessions,
                        MAX(s.start_datetime) as last_session_date
                        FROM session s
                        JOIN user u ON s.student_id = u.user_id
                        WHERE s.tutor_id = ?
                        GROUP BY s.student_id
                        ORDER BY total_sessions DESC";
$stmt = $conn->prepare($student_stats_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student_stats_result = $stmt->get_result();
$student_stats = [];
while ($row = $student_stats_result->fetch_assoc()) {
    $student_stats[$row['student_id']] = $row;
}
$stmt->close();

// 获取学生详细信息
$student_details_query = "SELECT 
                          u.user_id, u.first_name, u.last_name, u.email, u.phone, u.profile_image, u.created_at,
                          sp.major, sp.year, sp.school
                          FROM user u
                          LEFT JOIN studentprofile sp ON u.user_id = sp.user_id
                          WHERE u.user_id IN (
                              SELECT DISTINCT student_id FROM session WHERE tutor_id = ?
                          )";
$stmt = $conn->prepare($student_details_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student_details_result = $stmt->get_result();
$student_details = [];
while ($row = $student_details_result->fetch_assoc()) {
    $student_details[$row['user_id']] = $row;
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Students - Peer Tutoring Platform</title>
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

        .page-title {
            margin-bottom: 2rem;
            color: var(--primary);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .tabs {
            display: flex;
            margin-bottom: 2rem;
            border-bottom: 1px solid var(--gray);
        }

        .tab {
            padding: 1rem 2rem;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            font-weight: 600;
            transition: all 0.3s;
        }

        .tab.active {
            border-bottom-color: var(--accent);
            color: var(--primary);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .student-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .student-card {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }

        .student-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .student-header {
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            border-bottom: 1px solid var(--gray);
        }

        .student-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: var(--secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            overflow: hidden;
        }

        .student-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .student-info h3 {
            margin-bottom: 0.25rem;
            color: var(--primary);
        }

        .student-info p {
            color: var(--dark-gray);
            font-size: 0.9rem;
        }

        .student-stats {
            padding: 1.5rem;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--dark-gray);
        }

        .session-list {
            margin-top: 2rem;
        }

        .session-card {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
        }

        .session-student {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex: 1;
            min-width: 250px;
        }

        .session-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            overflow: hidden;
        }

        .session-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .session-details {
            flex: 2;
            min-width: 300px;
        }

        .session-time {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .session-time i {
            color: var(--dark-gray);
        }

        .session-course {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .session-course i {
            color: var(--dark-gray);
        }

        .session-location {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .session-location i {
            color: var(--dark-gray);
        }

        .session-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
            flex: 1;
            min-width: 150px;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: #232e73;
        }

        .btn-secondary {
            background-color: var(--secondary);
            color: white;
        }

        .btn-secondary:hover {
            background-color: #0095cc;
        }

        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background-color: var(--primary);
            color: white;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-scheduled {
            background-color: #e3f2fd;
            color: #0d47a1;
        }

        .status-completed {
            background-color: #e8f5e9;
            color: #1b5e20;
        }

        .status-cancelled {
            background-color: #ffebee;
            color: #b71c1c;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .empty-state h3 {
            margin-bottom: 1rem;
            color: var(--primary);
        }

        .empty-state p {
            color: var(--dark-gray);
            margin-bottom: 1.5rem;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            overflow-y: auto;
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 8px;
            width: 90%;
            max-width: 800px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .close-modal {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--dark-gray);
        }

        .modal-header {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray);
        }

        .student-profile {
            display: flex;
            gap: 2rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 8px;
            overflow: hidden;
            background-color: var(--secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            font-weight: bold;
        }

        .profile-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-details {
            flex: 1;
            min-width: 300px;
        }

        .profile-details h3 {
            margin-bottom: 1rem;
            color: var(--primary);
        }

        .detail-item {
            display: flex;
            margin-bottom: 0.75rem;
        }

        .detail-label {
            width: 120px;
            font-weight: 600;
            color: var(--dark-gray);
        }

        .detail-value {
            flex: 1;
        }

        .session-history {
            margin-top: 2rem;
        }

        .session-history h3 {
            margin-bottom: 1rem;
            color: var(--primary);
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
        }

        .history-table th,
        .history-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--gray);
        }

        .history-table th {
            background-color: var(--light-gray);
            font-weight: 600;
            color: var(--dark-gray);
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
            gap: 0.5rem;
        }

        .page-item {
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .page-item:hover {
            background-color: var(--gray);
        }

        .page-item.active {
            background-color: var(--primary);
            color: white;
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 1rem;
            }

            .logo {
                font-size: 1.2rem;
            }

            .nav-links {
                gap: 10px;
            }

            .student-grid {
                grid-template-columns: 1fr;
            }

            .session-card {
                flex-direction: column;
                align-items: flex-start;
            }

            .session-actions {
                width: 100%;
                justify-content: flex-start;
            }

            .modal-content {
                width: 95%;
                margin: 5% auto;
                padding: 1.5rem;
            }

            .profile-image {
                width: 100px;
                height: 100px;
                font-size: 2rem;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <?php include 'header/tut_head.php'; ?>

    <main>
        <div class="page-title">
            <h1>My Students</h1>
        </div>

        <div class="tabs">
            <div class="tab active" data-tab="current-sessions">Current Sessions</div>
            <div class="tab" data-tab="past-sessions">Past Sessions</div>
            <div class="tab" data-tab="all-students">All Students</div>
        </div>

        <!-- Current Sessions Tab -->
        <div class="tab-content active" id="current-sessions">
            <?php if (count($current_sessions) > 0): ?>
                <div class="session-list">
                    <?php foreach ($current_sessions as $session): ?>
                        <div class="session-card">
                            <div class="session-student">
                                <div class="session-avatar">
                                    <?php if ($session['profile_image']): ?>
                                        <img src="<?php echo $session['profile_image']; ?>" alt="Student Image">
                                    <?php else: ?>
                                        <?php echo strtoupper(substr($session['first_name'], 0, 1) . substr($session['last_name'], 0, 1)); ?>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h3><?php echo $session['first_name'] . ' ' . $session['last_name']; ?></h3>
                                    <p><?php echo $session['email']; ?></p>
                                </div>
                            </div>
                            <div class="session-details">
                                <div class="session-time">
                                    <i class="far fa-calendar-alt"></i>
                                    <span>
                                        <?php
                                        $start = new DateTime($session['start_datetime']);
                                        $end = new DateTime($session['end_datetime']);
                                        echo $start->format('M j, Y') . ' | ' . $start->format('g:i A') . ' - ' . $end->format('g:i A');
                                        ?>
                                    </span>
                                </div>
                                <div class="session-course">
                                    <i class="fas fa-book"></i>
                                    <span><?php echo $session['course_code'] . ' - ' . $session['course_name']; ?></span>
                                </div>
                                <div class="session-location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo $session['location_name'] ?: 'Not specified'; ?></span>
                                </div>
                            </div>
                            <div class="session-actions">
                                <button class="btn btn-primary view-student" data-id="<?php echo $session['user_id']; ?>">View Profile</button>
                                <a href="tutor_messages.php?student_id=<?php echo $session['user_id']; ?>" class="btn btn-outline">Message</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No Current Sessions</h3>
                    <p>You don't have any upcoming sessions with students.</p>
                    <a href="tutor_availability.php" class="btn btn-primary">Set Your Availability</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Past Sessions Tab -->
        <div class="tab-content" id="past-sessions">
            <?php if (count($past_sessions) > 0): ?>
                <div class="session-list">
                    <?php foreach ($past_sessions as $session): ?>
                        <div class="session-card">
                            <div class="session-student">
                                <div class="session-avatar">
                                    <?php if ($session['profile_image']): ?>
                                        <img src="<?php echo $session['profile_image']; ?>" alt="Student Image">
                                    <?php else: ?>
                                        <?php echo strtoupper(substr($session['first_name'], 0, 1) . substr($session['last_name'], 0, 1)); ?>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h3><?php echo $session['first_name'] . ' ' . $session['last_name']; ?></h3>
                                    <p><?php echo $session['email']; ?></p>
                                </div>
                            </div>
                            <div class="session-details">
                                <div class="session-time">
                                    <i class="far fa-calendar-alt"></i>
                                    <span>
                                        <?php
                                        $start = new DateTime($session['start_datetime']);
                                        $end = new DateTime($session['end_datetime']);
                                        echo $start->format('M j, Y') . ' | ' . $start->format('g:i A') . ' - ' . $end->format('g:i A');
                                        ?>
                                    </span>
                                </div>
                                <div class="session-course">
                                    <i class="fas fa-book"></i>
                                    <span><?php echo $session['course_code'] . ' - ' . $session['course_name']; ?></span>
                                </div>
                                <div class="session-location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo $session['location_name'] ?: 'Not specified'; ?></span>
                                </div>
                                <div class="mt-2">
                                    <span class="status-badge status-<?php echo strtolower($session['status']); ?>">
                                        <?php echo $session['status']; ?>
                                    </span>
                                    <?php if ($session['status'] == 'cancelled' && $session['cancellation_reason']): ?>
                                        <span class="ml-2 text-muted">Reason: <?php echo $session['cancellation_reason']; ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($session['rating']): ?>
                                    <div class="mt-2">
                                        <i class="fas fa-star text-warning"></i>
                                        <span>Rating: <?php echo $session['rating']; ?>/5</span>
                                        <?php if ($session['comment']): ?>
                                            <p class="text-muted mt-1">"<?php echo $session['comment']; ?>"</p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="session-actions">
                                <button class="btn btn-primary view-student" data-id="<?php echo $session['user_id']; ?>">View Profile</button>
                                <a href="tutor_messages.php?student_id=<?php echo $session['user_id']; ?>" class="btn btn-outline">Message</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No Past Sessions</h3>
                    <p>You haven't completed any sessions with students yet.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- All Students Tab -->
        <div class="tab-content" id="all-students">
            <?php if (count($student_stats) > 0): ?>
                <div class="student-grid">
                    <?php foreach ($student_stats as $student_id => $student): ?>
                        <div class="student-card view-student" data-id="<?php echo $student_id; ?>">
                            <div class="student-header">
                                <div class="student-avatar">
                                    <?php if ($student['profile_image']): ?>
                                        <img src="<?php echo $student['profile_image']; ?>" alt="Student Image">
                                    <?php else: ?>
                                        <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="student-info">
                                    <h3><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></h3>
                                    <p>
                                        <?php
                                        if (isset($student_details[$student_id]) && $student_details[$student_id]['major']) {
                                            echo $student_details[$student_id]['major'];
                                            if ($student_details[$student_id]['year']) {
                                                echo ' - ' . $student_details[$student_id]['year'];
                                            }
                                        } else {
                                            echo 'Student';
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>
                            <div class="student-stats">
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $student['total_sessions']; ?></div>
                                    <div class="stat-label">Total Sessions</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $student['completed_sessions']; ?></div>
                                    <div class="stat-label">Completed</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $student['cancelled_sessions']; ?></div>
                                    <div class="stat-label">Cancelled</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value">
                                        <?php
                                        if ($student['last_session_date']) {
                                            $last_date = new DateTime($student['last_session_date']);
                                            echo $last_date->format('M j');
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </div>
                                    <div class="stat-label">Last Session</div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No Students Yet</h3>
                    <p>You haven't had any sessions with students yet.</p>
                    <a href="tutor_availability.php" class="btn btn-primary">Set Your Availability</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Student Details Modal -->
    <div class="modal" id="student-modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <div class="modal-header">
                <h2>Student Profile</h2>
            </div>
            <div class="student-profile">
                <div class="profile-image" id="modal-profile-image">
                    <!-- Profile image will be inserted here -->
                </div>
                <div class="profile-details">
                    <h3 id="modal-student-name"><!-- Student name will be inserted here --></h3>
                    <div class="detail-item">
                        <div class="detail-label">Email:</div>
                        <div class="detail-value" id="modal-student-email"><!-- Email will be inserted here --></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Phone:</div>
                        <div class="detail-value" id="modal-student-phone"><!-- Phone will be inserted here --></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Major:</div>
                        <div class="detail-value" id="modal-student-major"><!-- Major will be inserted here --></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Year:</div>
                        <div class="detail-value" id="modal-student-year"><!-- Year will be inserted here --></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">School:</div>
                        <div class="detail-value" id="modal-student-school"><!-- School will be inserted here --></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Member Since:</div>
                        <div class="detail-value" id="modal-student-joined"><!-- Join date will be inserted here --></div>
                    </div>
                </div>
            </div>

            <div class="session-history">
                <h3>Session History</h3>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Course</th>
                            <th>Location</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="modal-session-history">
                        <!-- Session history will be inserted here -->
                    </tbody>
                </table>
            </div>

            <div class="modal-footer mt-4">
                <a href="#" id="message-student-link" class="btn btn-primary">Send Message</a>
            </div>
        </div>
    </div>

    <script>
        // Tab switching functionality
        const tabs = document.querySelectorAll('.tab');
        const tabContents = document.querySelectorAll('.tab-content');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const tabId = tab.getAttribute('data-tab');

                // Remove active class from all tabs and contents
                tabs.forEach(t => t.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));

                // Add active class to clicked tab and corresponding content
                tab.classList.add('active');
                document.getElementById(tabId).classList.add('active');
            });
        });

        // Student modal functionality
        const modal = document.getElementById('student-modal');
        const closeModal = document.querySelector('.close-modal');
        const viewStudentButtons = document.querySelectorAll('.view-student');

        // Student details data
        const studentDetails = <?php echo json_encode($student_details); ?>;

        // Current sessions data
        const currentSessions = <?php echo json_encode($current_sessions); ?>;

        // Past sessions data
        const pastSessions = <?php echo json_encode($past_sessions); ?>;

        // Close modal when clicking the X
        closeModal.addEventListener('click', () => {
            modal.style.display = 'none';
        });

        // Close modal when clicking outside the modal content
        window.addEventListener('click', (event) => {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });

        // Open modal with student details when clicking view button
        viewStudentButtons.forEach(button => {
            button.addEventListener('click', () => {
                const studentId = button.getAttribute('data-id');
                const student = studentDetails[studentId];

                if (student) {
                    // Set student details in modal
                    document.getElementById('modal-student-name').textContent = student.first_name + ' ' + student.last_name;
                    document.getElementById('modal-student-email').textContent = student.email;
                    document.getElementById('modal-student-phone').textContent = student.phone || 'Not provided';
                    document.getElementById('modal-student-major').textContent = student.major || 'Not specified';
                    document.getElementById('modal-student-year').textContent = student.year || 'Not specified';
                    document.getElementById('modal-student-school').textContent = student.school || 'Not specified';

                    // Format join date
                    const joinDate = new Date(student.created_at);
                    document.getElementById('modal-student-joined').textContent = joinDate.toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });

                    // Set profile image
                    const profileImageContainer = document.getElementById('modal-profile-image');
                    if (student.profile_image) {
                        profileImageContainer.innerHTML = `<img src="${student.profile_image}" alt="Profile Image">`;
                    } else {
                        const initials = student.first_name.charAt(0).toUpperCase() + student.last_name.charAt(0).toUpperCase();
                        profileImageContainer.innerHTML = initials;
                    }

                    // Set message link
                    document.getElementById('message-student-link').href = `tutor_messages.php?student_id=${studentId}`;

                    // Populate session history
                    const sessionHistoryContainer = document.getElementById('modal-session-history');
                    sessionHistoryContainer.innerHTML = '';

                    // Combine current and past sessions for this student
                    const allSessions = [...currentSessions, ...pastSessions].filter(session => session.user_id == studentId);

                    if (allSessions.length > 0) {
                        allSessions.forEach(session => {
                            const startDate = new Date(session.start_datetime);
                            const endDate = new Date(session.end_datetime);

                            const row = document.createElement('tr');
                            row.innerHTML = `
                                    <td>${startDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</td>
                                    <td>${startDate.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })} - ${endDate.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })}</td>
                                    <td>${session.course_code} - ${session.course_name}</td>
                                    <td>${session.location_name || 'Not specified'}</td>
                                    <td><span class="status-badge status-${session.status.toLowerCase()}">${session.status}</span></td>
                                `;
                            sessionHistoryContainer.appendChild(row);
                        });
                    } else {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                                <td colspan="5" class="text-center">No session history available</td>
                            `;
                        sessionHistoryContainer.appendChild(row);
                    }

                    // Show modal
                    modal.style.display = 'block';
                }
            });
        });

        // User menu dropdown toggle
        const userAvatar = document.querySelector('.user-avatar');
        const dropdown = document.querySelector('.dropdown');

        userAvatar.addEventListener('click', () => {
            dropdown.classList.toggle('active');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (event) => {
            if (!event.target.closest('.user-menu')) {
                dropdown.classList.remove('active');
            }
        });
    </script>
</body>

</html>