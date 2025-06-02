<?php
session_start();

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// 数据库连接
require_once 'db_connection.php';
$user_id = $_SESSION['user_id'];

// 检查用户是否是导师
$stmt = $conn->prepare("SELECT * FROM user WHERE user_id = ? AND role = 'tutor'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    // 如果不是导师，重定向到错误页面
    header('Location: error.php?message=Access denied. You must be a tutor to view this page.');
    exit();
}

// 获取用户基本信息
$stmt = $conn->prepare("SELECT first_name, last_name, profile_image FROM user WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$first_name = $user['first_name'];
$last_name = $user['last_name'];
$profile_image = $user['profile_image'];

// 处理请求操作
$success_message = '';
$error_message = '';

// 从GET参数获取消息（如果有）
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}
if (isset($_GET['error'])) {
    $error_message = $_GET['error'];
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['request_id'])) {
        $action = $_POST['action'];
        $request_id = $_POST['request_id'];

        // 验证请求是否属于当前导师
        // 注意：这里需要根据您的实际表结构修改
        // 假设您有一个session_requests表
        $stmt = $conn->prepare("SELECT * FROM session_requests WHERE id = ? AND tutor_id = ?");
        $stmt->bind_param("ii", $request_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $request = $result->fetch_assoc();

            if ($action === 'accept') {
                // 接受请求
                $stmt = $conn->prepare("UPDATE session_requests SET status = 'accepted', updated_at = NOW() WHERE id = ?");
                $stmt->bind_param("i", $request_id);

                if ($stmt->execute()) {
                    // 创建会话记录
                    // 注意：这里需要根据您的session表结构修改
                    $stmt = $conn->prepare("INSERT INTO session (tutor_id, student_id, course_id, start_datetime, end_datetime, status, created_at)
                                    VALUES (?, ?, ?, ?, ?, 'scheduled', NOW())");
                    $stmt->bind_param("iiiss", $user_id, $request['student_id'], $request['course_id'], $request['preferred_time'], $request['end_time']);

                    if ($stmt->execute()) {
                        $success_message = 'Request accepted successfully!';

                        // 向学生发送通知
                        $stmt = $conn->prepare("INSERT INTO notification (user_id, type, title, message, related_id, created_at)
                                        VALUES (?, 'session', 'Request Accepted', 'Your tutoring request has been accepted', ?, NOW())");
                        $stmt->bind_param("ii", $request['student_id'], $request_id);
                        $stmt->execute();
                    } else {
                        $error_message = 'Failed to create session record: ' . $conn->error;
                    }
                } else {
                    $error_message = 'Failed to update request status: ' . $conn->error;
                }
            } elseif ($action === 'reject') {
                // 拒绝请求
                $stmt = $conn->prepare("UPDATE session_requests SET status = 'rejected', updated_at = NOW() WHERE id = ?");
                $stmt->bind_param("i", $request_id);

                if ($stmt->execute()) {
                    $success_message = 'Request rejected.';

                    // 向学生发送通知
                    $stmt = $conn->prepare("INSERT INTO notification (user_id, type, title, message, related_id, created_at)
                                    VALUES (?, 'session', 'Request Rejected', 'Your tutoring request has been rejected', ?, NOW())");
                    $stmt->bind_param("ii", $request['student_id'], $request_id);
                    $stmt->execute();
                } else {
                    $error_message = 'Failed to update request status: ' . $conn->error;
                }
            } elseif ($action === 'suggest') {
                // 建议替代时间
                $suggested_time = $_POST['suggested_time'];
                $suggested_end_time = date('Y-m-d H:i:s', strtotime($suggested_time) + (strtotime($request['end_time']) - strtotime($request['preferred_time'])));

                $stmt = $conn->prepare("UPDATE session_requests SET status = 'time_suggested', suggested_time = ?, suggested_end_time = ?, updated_at = NOW() WHERE id = ?");
                $stmt->bind_param("ssi", $suggested_time, $suggested_end_time, $request_id);

                if ($stmt->execute()) {
                    $success_message = 'Alternative time suggested successfully.';

                    // 向学生发送通知
                    $stmt = $conn->prepare("INSERT INTO notification (user_id, type, title, message, related_id, created_at)
                                    VALUES (?, 'session', 'Alternative Time Suggested', 'Your tutor has suggested an alternative time for your request', ?, NOW())");
                    $stmt->bind_param("ii", $request['student_id'], $request_id);
                    $stmt->execute();
                } else {
                    $error_message = 'Failed to update request status: ' . $conn->error;
                }
            }
        } else {
            $error_message = 'Invalid request ID or you do not have permission to perform this action.';
        }

        // 重定向以防止表单重复提交
        if ($success_message) {
            header('Location: tutor_requests.php?success=' . urlencode($success_message));
            exit();
        } elseif ($error_message) {
            header('Location: tutor_requests.php?error=' . urlencode($error_message));
            exit();
        }
    }
}

// 获取待处理请求
$pending_requests = [];
$pending_count = 0;

// 假设您有一个session_requests表，并且它有以下结构
try {
    $stmt = $conn->prepare("SELECT sr.*, s.subject_name, u.first_name, u.last_name, u.profile_image
                         FROM session_requests sr
                         JOIN subject s ON sr.course_id = s.subject_id
                         JOIN user u ON sr.student_id = u.user_id
                         WHERE sr.tutor_id = ? AND sr.status = 'pending'
                         ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $pending_requests_result = $stmt->get_result();

    while ($row = $pending_requests_result->fetch_assoc()) {
        $pending_requests[] = $row;
    }
    $pending_count = count($pending_requests);
} catch (Exception $e) {
    $error_message = "Error loading pending requests: " . $e->getMessage();
    // 如果表不存在，这里会捕获错误
}

// 获取已处理请求
$processed_requests = [];
try {
    $stmt = $conn->prepare("SELECT sr.*, s.subject_name, u.first_name, u.last_name, u.profile_image
                         FROM session_requests sr
                         JOIN subject s ON sr.course_id = s.subject_id
                         JOIN user u ON sr.student_id = u.user_id
                         WHERE sr.tutor_id = ? AND sr.status != 'pending'
                         ORDER BY sr.updated_at DESC
                         LIMIT 20");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $processed_requests_result = $stmt->get_result();

    while ($row = $processed_requests_result->fetch_assoc()) {
        $processed_requests[] = $row;
    }
} catch (Exception $e) {
    // 如果表不存在，这里会捕获错误
}

// 获取未读消息数量用于通知徽章
$unread_messages = 0;
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM message WHERE receiver_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $unread_messages = $result->fetch_assoc()['count'];
} catch (Exception $e) {
    // 如果查询失败，保持未读消息为0
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Requests - PeerLearn</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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

        .logo span {
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            position: relative;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: var(--accent);
        }

        .nav-links a.active {
            color: var(--accent);
        }

        .notification-badge {
            position: absolute;
            top: -8px;
            right: -12px;
            background-color: var(--accent);
            color: var(--primary);
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: bold;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            overflow: hidden;
            border: 2px solid white;
        }

        .profile-image {
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
            margin-bottom: 1.5rem;
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
            margin-bottom: 1.5rem;
        }

        .tab {
            padding: 0.75rem 1.5rem;
            cursor: pointer;
            font-weight: 500;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            color: var(--dark-gray);
            position: relative;
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

        .request-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .request-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            transition: transform 0.3s, box-shadow 0.3s;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .request-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-color: rgba(0, 174, 239, 0.2);
        }

        .request-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
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
            border: 2px solid var(--light-gray);
        }

        .request-info {
            flex: 1;
        }

        .student-name {
            font-weight: 600;
            color: var(--primary);
        }

        .request-subject {
            color: var(--dark-gray);
            font-size: 0.9rem;
        }

        .request-details {
            margin-bottom: 1.5rem;
            background-color: rgba(0, 174, 239, 0.03);
            padding: 1rem;
            border-radius: 6px;
            border-left: 3px solid var(--secondary);
        }

        .detail-item {
            display: flex;
            margin-bottom: 0.5rem;
        }

        .detail-label {
            width: 120px;
            color: var(--dark-gray);
            font-size: 0.9rem;
        }

        .detail-value {
            flex: 1;
            font-weight: 500;
            color: var(--primary);
        }

        .request-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background-color: var(--secondary);
            color: white;
        }

        .btn-primary:hover {
            background-color: #0095cc;
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(0, 174, 239, 0.3);
        }

        .btn-success {
            background-color: var(--accent);
            color: white;
        }

        .btn-success:hover {
            background-color: #b1c100;
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(196, 214, 0, 0.3);
        }

        .btn-danger {
            background-color: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background-color: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(220, 53, 69, 0.3);
        }

        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--secondary);
            color: var(--secondary);
        }

        .btn-outline:hover {
            background-color: rgba(0, 174, 239, 0.1);
            transform: translateY(-2px);
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: auto;
        }

        .status-pending {
            background-color: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }

        .status-accepted {
            background-color: rgba(196, 214, 0, 0.1);
            color: var(--accent);
        }

        .status-rejected {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        .status-suggested {
            background-color: rgba(0, 174, 239, 0.1);
            color: var(--secondary);
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
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: white;
            border-radius: 8px;
            width: 100%;
            max-width: 500px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-top: 5px solid var(--secondary);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--dark-gray);
            transition: color 0.3s;
        }

        .modal-close:hover {
            color: var(--primary);
        }

        .modal-body {
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--primary);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--gray);
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(0, 174, 239, 0.2);
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 1rem;
            }

            .nav-links {
                gap: 1rem;
            }

            .request-list {
                grid-template-columns: 1fr;
            }

            .request-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="logo">
            <span>PeerLearn</span>
        </div>
        <div class="nav-links">
            <a href="tutor_main_page.php">Main Page</a>
            <a href="tutor_profile.php">profile</a>
            <a href="tutor_requests.php" class="active">Requests <?php if ($pending_count > 0): ?><span class="notification-badge"><?php echo $pending_count; ?></span><?php endif; ?></a>
            <a href="tutor_sessions.php">Sessions</a>
            <a href="messages.php">Messages <?php if ($unread_messages > 0): ?><span class="notification-badge"><?php echo $unread_messages; ?></span><?php endif; ?></a>
        </div>
        <div class="user-menu">
            <div class="user-avatar">
                <?php if ($profile_image): ?>
                    <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile" class="profile-image">
                <?php else: ?>
                    <?php echo htmlspecialchars(substr($first_name, 0, 1) . substr($last_name, 0, 1)); ?>
                <?php endif; ?>
            </div>
            <a href="logout.php" style="color: white; text-decoration: none;">Logout</a>
        </div>
    </nav>

    <main>
        <h1 class="page-title">Tutoring Requests</h1>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="tabs">
            <div class="tab active" data-tab="pending">Pending Requests <?php if ($pending_count > 0): ?><span class="notification-badge"><?php echo $pending_count; ?></span><?php endif; ?></div>
            <div class="tab" data-tab="processed">Processed Requests</div>
        </div>

        <div id="pending-tab" class="tab-content active">
            <?php if (count($pending_requests) > 0): ?>
                <div class="request-list">
                    <?php foreach ($pending_requests as $request): ?>
                        <div class="request-card">
                            <div class="request-header">
                                <div class="student-avatar">
                                    <?php if ($request['profile_image']): ?>
                                        <img src="<?php echo htmlspecialchars($request['profile_image']); ?>" alt="Student" class="profile-image">
                                    <?php else: ?>
                                        <?php echo htmlspecialchars(substr($request['first_name'], 0, 1) . substr($request['last_name'], 0, 1)); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="request-info">
                                    <div class="student-name"><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></div>
                                    <div class="request-subject"><?php echo htmlspecialchars($request['subject_name']); ?></div>
                                </div>
                            </div>

                            <div class="request-details">
                                <div class="detail-item">
                                    <div class="detail-label">Course:</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($request['course_name'] ?? 'Not specified'); ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Requested Time:</div>
                                    <div class="detail-value"><?php echo date('M d, Y g:i A'); ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Duration:</div>
                                    <div class="detail-value">
                                        <?php
                                        $start = new DateTime($request['preferred_time']);
                                        $end = new DateTime($request['end_time']);
                                        $duration = $start->diff($end);
                                        echo $duration->format('%h hour(s) %i minute(s)');
                                        ?>
                                    </div>
                                </div>
                                <?php if (isset($request['message']) && !empty($request['message'])): ?>
                                    <div class="detail-item">
                                        <div class="detail-label">Message:</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($request['message']); ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="request-actions">
                                <form method="post" action="tutor_requests.php">
                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                    <input type="hidden" name="action" value="accept">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-check"></i> Accept
                                    </button>
                                </form>

                                <form method="post" action="tutor_requests.php">
                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </form>

                                <button type="button" class="btn btn-outline suggest-time-btn" data-request-id="<?php echo $request['id']; ?>">
                                    <i class="fas fa-clock"></i> Suggest Time
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <h3>No Pending Requests</h3>
                    <p class="empty-text">You don't have any pending tutoring requests at the moment.</p>
                </div>
            <?php endif; ?>
        </div>

        <div id="processed-tab" class="tab-content">
            <?php if (count($processed_requests) > 0): ?>
                <div class="request-list">
                    <?php foreach ($processed_requests as $request): ?>
                        <div class="request-card">
                            <div class="request-header">
                                <div class="student-avatar">
                                    <?php if ($request['profile_image']): ?>
                                        <img src="<?php echo htmlspecialchars($request['profile_image']); ?>" alt="Student" class="profile-image">
                                    <?php else: ?>
                                        <?php echo htmlspecialchars(substr($request['first_name'], 0, 1) . substr($request['last_name'], 0, 1)); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="request-info">
                                    <div class="student-name"><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></div>
                                    <div class="request-subject"><?php echo htmlspecialchars($request['subject_name']); ?></div>
                                </div>
                                <?php
                                $statusClass = '';
                                $statusText = '';
                                switch ($request['status']) {
                                    case 'accepted':
                                        $statusClass = 'status-accepted';
                                        $statusText = 'Accepted';
                                        break;
                                    case 'rejected':
                                        $statusClass = 'status-rejected';
                                        $statusText = 'Rejected';
                                        break;
                                    case 'time_suggested':
                                        $statusClass = 'status-suggested';
                                        $statusText = 'Time Suggested';
                                        break;
                                    default:
                                        $statusClass = 'status-pending';
                                        $statusText = 'Pending';
                                }
                                ?>
                                <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                            </div>

                            <div class="request-details">
                                <div class="detail-item">
                                    <div class="detail-label">Course:</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($request['course_name'] ?? 'Not specified'); ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Requested Time:</div>
                                    <div class="detail-value"><?php echo date('M d, Y g:i A', strtotime($request['preferred_time'])); ?></div>
                                </div>
                                <?php if ($request['status'] === 'time_suggested' && isset($request['suggested_time'])): ?>
                                    <div class="detail-item">
                                        <div class="detail-label">Suggested Time:</div>
                                        <div class="detail-value"><?php echo date('M d, Y g:i A', strtotime($request['suggested_time'])); ?></div>
                                    </div>
                                <?php endif; ?>
                                <div class="detail-item">
                                    <div class="detail-label">Duration:</div>
                                    <div class="detail-value">
                                        <?php
                                        $start = new DateTime($request['preferred_time']);
                                        $end = new DateTime($request['end_time']);
                                        $duration = $start->diff($end);
                                        echo $duration->format('%h hour(s) %i minute(s)');
                                        ?>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Processed On:</div>
                                    <div class="detail-value"><?php echo date('M d, Y g:i A', strtotime($request['updated_at'])); ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <h3>No Processed Requests</h3>
                    <p class="empty-text">You haven't processed any tutoring requests yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Suggest Time Modal -->
    <div id="suggest-time-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Suggest Alternative Time</h2>
                <button type="button" class="modal-close">&times;</button>
            </div>
            <form method="post" action="tutor_requests.php" id="suggest-time-form">
                <div class="modal-body">
                    <input type="hidden" name="request_id" id="modal-request-id">
                    <input type="hidden" name="action" value="suggest">
                    <div class="form-group">
                        <label for="suggested_time" class="form-label">Suggested Date & Time:</label>
                        <input type="datetime-local" id="suggested_time" name="suggested_time" class="form-control" required>
                    </div>
                    <p>Please select a date and time that works better for your schedule. The student will be notified of your suggestion.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline modal-close-btn">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send Suggestion</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Tab switching
        const tabs = document.querySelectorAll('.tab');
        const tabContents = document.querySelectorAll('.tab-content');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const tabId = tab.getAttribute('data-tab');

                // Remove active class from all tabs and contents
                tabs.forEach(t => t.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));

                // Add active class to clicked tab and corresponding content
                tab.classList.add('active');
                document.getElementById(`${tabId}-tab`).classList.add('active');
            });
        });

        // Modal functionality
        const modal = document.getElementById('suggest-time-modal');
        const suggestButtons = document.querySelectorAll('.suggest-time-btn');
        const closeButtons = document.querySelectorAll('.modal-close, .modal-close-btn');
        const requestIdInput = document.getElementById('modal-request-id');

        suggestButtons.forEach(button => {
            button.addEventListener('click', () => {
                const requestId = button.getAttribute('data-request-id');
                requestIdInput.value = requestId;
                modal.style.display = 'flex';

                // Set minimum date to today
                const now = new Date();
                const year = now.getFullYear();
                const month = String(now.getMonth() + 1).padStart(2, '0');
                const day = String(now.getDate()).padStart(2, '0');
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');

                const minDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
                document.getElementById('suggested_time').min = minDateTime;
            });
        });

        closeButtons.forEach(button => {
            button.addEventListener('click', () => {
                modal.style.display = 'none';
            });
        });

        // Close modal when clicking outside
        window.addEventListener('click', (event) => {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    </script>
</body>

</html>