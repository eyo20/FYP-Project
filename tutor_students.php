<?php
session_start();

$user_id = isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if ($user_id <= 0) {
    header("Location: login.php");
    exit();
}

require_once "db_connection.php";
if ($conn->connect_error) {
    handle_db_error($conn, "Database connection failed");
}

function handle_db_error($conn, $message) {
    error_log($message . ": " . $conn->error);
    $_SESSION['error'] = "An unexpected error occurred. Please try again later.";
    header("Location: error.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 获取用户信息
$stmt = $conn->prepare("SELECT username, email, role, first_name, last_name, phone, profile_image, created_at FROM user WHERE user_id = ?");
if (!$stmt) {
    error_log("Error preparing user query: " . $conn->error);
    $_SESSION['error'] = "Error preparing user query: " . $conn->error;
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();

// 检查用户是否为导师
if (!$user_data || $user_data['role'] != 'tutor') {
    header("Location: error.php?error_message=Access denied. You must be a tutor to view this page.");
    exit();
}

$username = $user_data['username'];
$email = $user_data['email'];
$first_name = $user_data['first_name'] ?: '';
$last_name = $user_data['last_name'] ?: '';
$phone = $user_data['phone'] ?: '';
$profile_image = $user_data['profile_image'] ?: 'assets/default-avatar.png'; // 默认头像
$created_at = $user_data['created_at'];
$stmt->close();

// 获取未读消息数量
$unread_messages = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM message WHERE receiver_id = ? AND is_read = 0");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $unread_messages = $result->fetch_assoc()['count'];
    $stmt->close();
}

// 获取待处理的预约请求数量
$pending_requests = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as pending_count FROM session_requests WHERE tutor_id = ? AND status = 'pending'");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $pending_requests = $result->fetch_assoc()['pending_count'];
    $stmt->close();
}

// 获取当前预约的学生列表（状态为 confirmed）
$current_sessions = []; // 初始化为空数组
$current_datetime = date('Y-m-d H:i:00');
$stmt = $conn->prepare("
    SELECT s.session_id, s.start_datetime, s.end_datetime, s.status, s.cancellation_reason,
           u.user_id, u.first_name, u.last_name, u.email, u.phone, u.profile_image,
           c.course_name, l.location_name,
           r.rating, r.comment,
           CONCAT(DATE_FORMAT(s.start_datetime, '%H:%i'), '-', DATE_FORMAT(s.end_datetime, '%H:%i')) as time_slot
    FROM session s
    JOIN user u ON s.student_id = u.user_id
    JOIN course c ON s.course_id = c.id
    LEFT JOIN location l ON s.location_id = l.location_id
    LEFT JOIN review r ON s.session_id = r.session_id AND r.tutor_id = s.tutor_id
    WHERE s.tutor_id = ? AND s.status = 'confirmed'
    AND s.start_datetime >= ?  -- 只显示当前时间之后的会话
    ORDER BY s.start_datetime ASC
");
if (!$stmt) {
    handle_db_error($conn, "Error preparing current sessions query");
} else {
    $stmt->bind_param("is", $user_id, $current_datetime);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $row['profile_image'] = $row['profile_image'] ?: 'assets/default-avatar.png';
        $current_sessions[] = $row;
    }
    $stmt->close();
}

$current_datetime = date('Y-m-d H:i:00');
$stmt_check = $conn->prepare("
    SELECT session_id, start_datetime, end_datetime
    FROM session
    WHERE tutor_id = ? AND status = 'confirmed' AND end_datetime < ?
");
if ($stmt_check) {
    $stmt_check->bind_param("is", $user_id, $current_datetime);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    while ($row = $result->fetch_assoc()) {
        $stmt_update = $conn->prepare("
            UPDATE session 
            SET status = 'completed'
            WHERE session_id = ?
        ");
        if ($stmt_update) {
            $stmt_update->bind_param("i", $row['session_id']);
            $stmt_update->execute();
            $stmt_update->close();
        }
    }
    $stmt_check->close();
}

// 获取历史预约的学生列表（状态为 completed 或 cancelled）
$stmt = $conn->prepare("
    SELECT DISTINCT s.session_id, s.start_datetime, s.end_datetime, s.status, s.cancellation_reason,
           u.user_id, u.first_name, u.last_name, u.email, u.phone, u.profile_image,
           c.course_name, l.location_name,
           r.rating, r.comment,
           CONCAT(DATE_FORMAT(s.start_datetime, '%H:%i'), '-', DATE_FORMAT(s.end_datetime, '%H:%i')) as time_slot
    FROM session s
    JOIN user u ON s.student_id = u.user_id
    JOIN course c ON s.course_id = c.id
    LEFT JOIN location l ON s.location_id = l.location_id
    LEFT JOIN review r ON s.session_id = r.session_id AND r.tutor_id = s.tutor_id
    WHERE s.tutor_id = ? AND s.status = 'completed'
    ORDER BY s.start_datetime DESC
");
if (!$stmt) {
    handle_db_error($conn, "Error preparing past sessions query");
} else {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        error_log("Past session row: " . print_r($row, true));
        $row['profile_image'] = $row['profile_image'] ?: 'assets/default-avatar.png';
        $past_sessions[] = $row;
    }
    $stmt->close();
}

// 获取所有学生的统计信息
$student_stats = [];
$stmt = $conn->prepare("
    SELECT 
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
    ORDER BY total_sessions DESC
");
if (!$stmt) {
    error_log("Error preparing student stats query: " . $conn->error);
    $_SESSION['error'] = "Error preparing student stats query: " . $conn->error;
} else {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $row['profile_image'] = $row['profile_image'] ?: 'assets/default-avatar.png'; // 默认头像
        $student_stats[$row['student_id']] = $row;
    }
    $stmt->close();
}

// 获取学生详细信息
$student_details = [];
$stmt = $conn->prepare("
    SELECT 
        u.user_id, u.first_name, u.last_name, u.email, u.phone, u.profile_image, u.created_at
    FROM user u
    WHERE u.user_id IN (
        SELECT DISTINCT student_id FROM session WHERE tutor_id = ?
    )
");
if (!$stmt) {
    error_log("Error preparing student details query: " . $conn->error);
    $_SESSION['error'] = "Error preparing student details query: " . $conn->error;
} else {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $row['profile_image'] = $row['profile_image'] ?: 'assets/default-avatar.png'; // 默认头像
        $student_details[$row['user_id']] = $row;
    }
    $stmt->close();
}

// 渲染会话卡片函数
function renderSessionCard($session, $student_details) {
    $statusClass = $session['status'] == 'cancelled' ? 'status-cancelled' : 
                   ($session['status'] == 'completed' ? 'status-completed' : 'status-confirmed');
    ?>
    <div class="request-card">
        <div class="request-header">
            <div class="student-avatar" data-id="<?php echo htmlspecialchars($session['user_id'] ?? ''); ?>">
                <img src="<?php echo htmlspecialchars($session['profile_image']); ?>" alt="Student" class="profile-image" 
                     onerror="this.src='assets/default-avatar.png'; this.onerror=null;">
                <span style="display:none;"><?php echo htmlspecialchars(substr($session['first_name'] ?? '', 0, 1) . substr($session['last_name'] ?? '', 0, 1)); ?></span>
            </div>
            <div class="request-info">
                <div class="student-name"><?php echo htmlspecialchars(($session['first_name'] ?? '') . ' ' . ($session['last_name'] ?? '')); ?></div>
            </div>
            <span class="status-badge <?php echo $statusClass; ?>">
                <?php echo ucfirst($session['status'] ?? 'Unknown'); ?>
            </span>
        </div>
        <div class="request-details">
            <div class="detail-item">
                <div class="detail-label">Course:</div>
                <div class="detail-value"><?php echo htmlspecialchars($session['course_name'] ?? 'Unknown Course'); ?></div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Date:</div>
                <div class="detail-value">
                    <?php echo !empty($session['start_datetime']) 
                        ? date('M d, Y', strtotime($session['start_datetime'])) 
                        : 'Not specified'; ?>
                </div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Time Slot:</div>
                <div class="detail-value">
                    <?php echo htmlspecialchars($session['time_slot'] ?? 
                        (date('H:i', strtotime($session['start_datetime'])) . '-' . date('H:i', strtotime($session['end_datetime'])))) ?: 'Not specified'; ?>
                </div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Location:</div>
                <div class="detail-value"><?php echo htmlspecialchars($session['location_name'] ?? 'Not specified'); ?></div>
            </div>
            <?php if ($session['status'] == 'cancelled' && $session['cancellation_reason']): ?>
                <div class="detail-item">
                    <div class="detail-label">Reason:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($session['cancellation_reason'] ?? 'Not specified'); ?></div>
                </div>
            <?php endif; ?>
            <?php if (isset($session['rating']) && $session['rating']): ?>
                <div class="detail-item">
                    <div class="detail-label">Rating:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($session['rating']); ?>/5
                        <?php if (isset($session['comment']) && $session['comment']): ?>
                            <span class="text-muted">"<?php echo htmlspecialchars($session['comment']); ?>"</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="request-actions">
            <button class="btn btn-profile view-student" 
                    data-id="<?php echo htmlspecialchars($session['user_id'] ?? ''); ?>" 
                    data-name="<?php echo htmlspecialchars(($session['first_name'] ?? '') . ' ' . ($session['last_name'] ?? '')); ?>"
                    data-email="<?php echo htmlspecialchars($student_details[$session['user_id']]['email'] ?? ''); ?>"
                    data-phone="<?php echo htmlspecialchars($student_details[$session['user_id']]['phone'] ?? ''); ?>"
                    data-image="<?php echo htmlspecialchars($session['profile_image'] ?? ''); ?>">
                <i class="fas fa-eye"></i> View Profile
            </button>
            <a href="messages.php?student_id=<?php echo htmlspecialchars($session['user_id'] ?? ''); ?>" class="btn btn-message"><i class="fas fa-envelope"></i> Message</a>
        </div>
    </div>
    <?php
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Students - PeerLearn</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    :root {
        --primary: #2B3990;
        --secondary: #00AEEF;
        --accent: #C4D600;
        --gray: #e0e0e0;
        --light-gray: #f5f5f5;
        --dark-gray: #777;
    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
        background-color: var(--light-gray);
        color: #333;
        line-height: 1.6;
    }

    .navbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background-color: var(--primary);
        color: white;
        padding: 1rem 2rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .nav-links {
        display: flex;
        gap: 1.5rem;
    }

    .nav-links a {
        color: white;
        text-decoration: none;
        font-weight: 500;
    }

    .nav-links a:hover {
        color: var(--accent);
    }

    .nav-links a.active {
        color: var(--accent);
    }

    main {
        max-width: 1200px;
        margin: 4rem auto;
        padding: 0 3rem;
    }

    .page-title {
        margin-bottom: 2rem;
        color: var(--primary);
        font-weight: 600;
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

    .tabs {
        display: flex;
        border-bottom: 1px solid var(--gray);
        margin-bottom: 2.5rem;
    }

    .tab {
        padding: 0.75rem 1.5rem;
        cursor: pointer;
        font-weight: 500;
        border-bottom: 3px solid transparent;
        color: var(--dark-gray);
    }

    .tab.active {
        border-bottom-color: var(--accent);
        color: var(--primary);
        font-weight: 600;
    }

    .tab:hover {
        background-color: rgba(0, 174, 239, 0.05);
        color: var(--primary);
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    .session-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 2rem;
        padding: 2.5rem 0;
    }

    .request-card {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        padding: 1.5rem;
        border: 1px solid rgba(0, 0, 0, 0.05);
        display: flex;
        flex-direction: column;
        height: 100%;
        min-height: 350px;
    }

    .request-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
        flex-shrink: 0;
    }

    .student-avatar {
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
        cursor: pointer;
    }

    .profile-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .request-info {
        flex: 1;
    }

    .student-name {
        font-weight: 600;
        color: var(--primary);
    }

    .request-details {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        margin-bottom: 1.5rem;
        background-color: rgba(0, 174, 239, 0.03);
        padding: 1rem;
        border-radius: 6px;
        border-left: 3px solid var(--secondary);
        overflow: hidden;
    }

    .detail-item {
        display: flex;
        margin-bottom: 0.5rem;
        flex-wrap: wrap;
    }

    .detail-label {
        width: 100px;
        color: var(--dark-gray);
        font-size: 0.9rem;
    }

    .detail-value {
        flex: 1;
        font-weight: 500;
        color: var(--primary);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: normal;
        line-height: 1.2;
        max-width: 200px;
    }

    .request-actions {
        margin-top: auto;
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        justify-content: center;
    }

    .btn {
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
        min-width: 120px;
        justify-content: center;
        transition: background-color 0.3s;
        color: white;
    }

    .btn-profile {
        background-color: var(--accent);
    }

    .btn-profile:hover {
        background-color: #b1c100;
    }

    .btn-message {
        background-color: var(--secondary);
    }

    .btn-message:hover {
        background-color: #0099cc;
    }

    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        margin-left: auto;
    }

    .status-confirmed {
        background-color: rgba(196, 214, 0, 0.1);
        color: var(--accent);
    }

    .status-completed {
        background-color: rgba(40, 167, 69, 0.1);
        color: #28a745;
    }

    .status-cancelled {
        background-color: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }

    .empty-state {
        text-align: center;
        padding: 3rem;
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .empty-icon {
        font-size: 3rem;
        color: var(--secondary);
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    .empty-text {
        color: var(--dark-gray);
        margin-bottom: 1.5rem;
    }

    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1000;
    }

    .modal-content {
        background-color: white;
        margin: 5% auto;
        padding: 1rem;
        border-radius: 8px;
        width: 90%;
        max-width: 400px;
        position: relative;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        overflow-y: auto;
        max-height: 80vh;
    }

    .close-modal {
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
        font-size: 1.5rem;
        cursor: pointer;
        color: var(--dark-gray);
    }

    .modal-body {
        padding: 2rem 1rem 1rem;
        text-align: center;
    }

    .profile-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background-color: var(--secondary);
        margin: 0 auto 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        overflow: hidden;
    }

    .profile-name {
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--primary);
        margin-bottom: 0.5rem;
    }

    .profile-detail {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        color: var(--dark-gray);
        margin-bottom: 0.5rem;
    }

    .profile-detail i {
        color: var(--primary);
    }

    @media (max-width: 768px) {
        main { padding: 0 1.5rem; }
        .session-list { grid-template-columns: 1fr; }
        .request-actions { flex-direction: column; }
        .btn { width: 100%; justify-content: center; }
        .modal-content { margin: 10% auto; }
    }
    </style>
</head>
<body>
    <?php include 'header/tut_head.php'; ?>

    <main>
        <h1 class="page-title">My Students</h1>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['error']); ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Tabs for navigating between Current Sessions, Past Sessions, and All Students -->
        <div class="tabs">
            <div class="tab active" data-tab="current-sessions">Current Sessions</div>
            <div class="tab" data-tab="past-sessions">Past Sessions</div>
            <div class="tab" data-tab="all-students">All Students</div>
        </div>

        <!-- Current Sessions Tab -->
        <div class="tab-content active" id="current-sessions">
            <?php if (!empty($current_sessions)): ?>
                <div class="session-list">
                    <?php foreach ($current_sessions as $session): ?>
                        <?php renderSessionCard($session, $student_details); ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-calendar-alt"></i></div>
                    <h3>No Records Found</h3>
                    <p class="empty-text">There are no current sessions to display yet.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Past Sessions Tab -->
        <div class="tab-content" id="past-sessions">
            <?php if (!empty($past_sessions)): ?>
                <div class="session-list">
                    <?php foreach ($past_sessions as $session): ?>
                        <?php renderSessionCard($session, $student_details); ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-history"></i></div>
                    <h3>No Records Found</h3>
                    <p class="empty-text">There are no past sessions to display yet.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- All Students Tab -->
        <div class="tab-content" id="all-students">
            <?php if (!empty($student_stats)): ?>
                <div class="session-list">
                    <?php foreach ($student_stats as $student_id => $student): ?>
                        <div class="request-card view-student" data-id="<?php echo htmlspecialchars($student_id); ?>">
                            <div class="request-header">
                                <div class="student-avatar" data-id="<?php echo htmlspecialchars($student_id); ?>">
                                    <img src="<?php echo htmlspecialchars($student_details[$student_id]['profile_image']); ?>" alt="Student" class="profile-image" 
                                        onerror="this.src='assets/default-avatar.png'; this.onerror=null;">
                                    <span style="display:none;"><?php echo htmlspecialchars(substr($student['first_name'] ?? '', 0, 1) . substr($student['last_name'] ?? '', 0, 1)); ?></span>
                                </div>
                                <div class="request-info">
                                    <div class="student-name"><?php echo htmlspecialchars(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')); ?></div>
                                </div>
                            </div>
                            <div class="request-details">
                                <div class="detail-item">
                                    <div class="detail-label">Total Sessions:</div>
                                    <div class="detail-value"><?php echo $student['total_sessions']; ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Completed:</div>
                                    <div class="detail-value"><?php echo $student['completed_sessions']; ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Last Session:</div>
                                    <div class="detail-value">
                                        <?php
                                        if ($student['last_session_date']) {
                                            echo date('M d', strtotime($student['last_session_date']));
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <div class="request-actions">
                                <button class="btn btn-profile view-student" 
                                        data-id="<?php echo htmlspecialchars($student_id); ?>" 
                                        data-name="<?php echo htmlspecialchars(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')); ?>"
                                        data-email="<?php echo htmlspecialchars($student_details[$student_id]['email'] ?? ''); ?>"
                                        data-phone="<?php echo htmlspecialchars($student_details[$student_id]['phone'] ?? ''); ?>"
                                        data-image="<?php echo htmlspecialchars($student_details[$student_id]['profile_image'] ?? ''); ?>">
                                    <i class="fas fa-eye"></i> View Profile
                                </button>
                                <a href="messages.php?student_id=<?php echo htmlspecialchars($student_id); ?>" class="btn btn-message"><i class="fas fa-envelope"></i> Message</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-users"></i></div>
                    <h3>No Records Found</h3>
                    <p class="empty-text">There are no students to display yet.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- 模态框用于显示学生资料 -->
        <div id="profile-modal" class="modal">
            <div class="modal-content">
                <span class="close-modal">×</span>
                <div class="modal-body">
                    <div class="profile-avatar">
                        <img src="" alt="Profile" id="modal-avatar" onerror="this.src='assets/default-avatar.png'; this.onerror=null;">
                    </div>
                    <div class="profile-name" id="modal-name"></div>
                    <div class="profile-detail"><i class="fas fa-envelope"></i> <span id="modal-email"></span></div>
                    <div class="profile-detail"><i class="fas fa-phone"></i> <span id="modal-phone"></span></div>
                </div>
            </div>
        </div>
    </main>

    <script>
        const tabs = document.querySelectorAll('.tab');
        const tabContents = document.querySelectorAll('.tab-content');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                tab.classList.add('active');
                document.getElementById(tab.getAttribute('data-tab')).classList.add('active');
            });
        });

        // 模态框逻辑
        const modal = document.getElementById('profile-modal');
        const closeModal = document.querySelector('.close-modal');

        document.querySelectorAll('.view-student, .student-avatar').forEach(button => {
            button.addEventListener('click', (e) => {
                const id = e.target.getAttribute('data-id') || e.target.closest('[data-id]').getAttribute('data-id');
                const name = e.target.getAttribute('data-name') || e.target.closest('[data-name]').getAttribute('data-name');
                const email = e.target.getAttribute('data-email') || e.target.closest('[data-email]').getAttribute('data-email') || '';
                const phone = e.target.getAttribute('data-phone') || e.target.closest('[data-phone]').getAttribute('data-phone') || '';
                const image = e.target.getAttribute('data-image') || e.target.closest('[data-image]').getAttribute('data-image');

                document.getElementById('modal-name').textContent = name || '';
                document.getElementById('modal-email').textContent = email || '';
                document.getElementById('modal-phone').textContent = phone || '';
                document.getElementById('modal-avatar').src = image || 'assets/default-avatar.png';

                modal.style.display = 'block';
            });
        });

        closeModal.addEventListener('click', () => {
            modal.style.display = 'none';
        });

        window.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });

        // Ensure 'Current Sessions' tab is active on page load
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.querySelector('[data-tab="current-sessions"]').classList.add('active');
            document.getElementById('current-sessions').classList.add('active');
        });
    </script>
</body>
</html>