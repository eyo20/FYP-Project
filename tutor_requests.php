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
$stmt = $conn->prepare("SELECT role FROM user WHERE user_id = ?");
if (!$stmt) {
    die("Error preparing user query: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0 || $result->fetch_assoc()['role'] !== 'tutor') {
    header('Location: error.php?error_message=Access denied. You must be a tutor to view this page.');
    exit();
}

// 获取用户基本信息
$stmt = $conn->prepare("SELECT first_name, last_name, profile_image FROM user WHERE user_id = ?");
if (!$stmt) {
    die("Error preparing user info query: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$first_name = $user['first_name'] ?? '';
$last_name = $user['last_name'] ?? '';
$profile_image = $user['profile_image'] ?? '';
$stmt->close();

// 处理请求操作
$success_message = '';
$error_message = '';

if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}
if (isset($_GET['error'])) {
    $error_message = $_GET['error'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['request_id'])) {
    $action = $_POST['action'];
    $request_id = (int)$_POST['request_id'];
   }

    // 验证请求
    $stmt = $conn->prepare("SELECT student_id, course_id FROM session_requests WHERE request_id = ? AND tutor_id = ?");
    if (!$stmt) {
        $error_message = "Error preparing request validation: " . $conn->error;
        header("Location: tutor_requests.php?error=" . urlencode($error_message));
        exit();
    }
    $stmt->bind_param("ii", $request_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $request = $result->fetch_assoc();

    if ($action === 'accept') {
        // 接受请求
        $stmt = $conn->prepare("UPDATE session_requests SET status = 'confirmed' WHERE request_id = ?");
        if (!$stmt) {
            $error_message = "Error preparing update request: " . $conn->error;
        } else {
            $stmt->bind_param("i", $request_id);
            if ($stmt->execute()) {
                // 插入 session 表
                $stmt = $conn->prepare("INSERT INTO session (tutor_id, student_id, course_id, location_id, status, start_datetime, end_datetime, created_at)
                                        VALUES (?, ?, ?, ?, 'scheduled', ?, ?, NOW())");
                if (!$stmt) {
                    $error_message = "Error preparing session insert: " . $conn->error;
                } else {
                    // 获取 session_requests 数据
                    $request_stmt = $conn->prepare("SELECT selected_date, duration, location_id FROM session_requests WHERE request_id = ?");
                    $request_stmt->bind_param("i", $request_id);
                    $request_stmt->execute();
                    $request_result = $request_stmt->get_result();
                    $request_data = $request_result->fetch_assoc();
                    $request_stmt->close();

                    // 计算开始和结束时间
                    $start_datetime = $request_data['selected_date'] . ' 09:00:00'; // 默认 9 点开始
                    $duration = (int)$request_data['duration'];
                    $end_datetime = date('Y-m-d H:i:s', strtotime($start_datetime . " + $duration hours"));
                    $location_id = (int)$request_data['location_id']; // 确保是整数

                    // 修正 bind_param，匹配 6 个变量
                    $stmt->bind_param("iiiiss", $user_id, $request['student_id'], $request['course_id'], $location_id, $start_datetime, $end_datetime);
                    if ($stmt->execute()) {
                        $success_message = 'Request accepted successfully! Please discuss timing with the student via messages.';
                        // 发送通知
                        $stmt = $conn->prepare("INSERT INTO notification (user_id, type, title, message, related_id, created_at)
                                                VALUES (?, 'session', 'Request Accepted', 'Your tutoring request has been accepted. Please discuss timing with your tutor.', ?, NOW())");
                        if ($stmt) {
                            $stmt->bind_param("ii", $request['student_id'], $request_id);
                            $stmt->execute();
                        }
                    } else {
                        $error_message = 'Failed to create session: ' . $conn->error;
                    }
                }
            } else {
                $error_message = 'Failed to update request: ' . $conn->error;
            }
        }
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE session_requests SET status = 'rejected' WHERE request_id = ?");
        if (!$stmt) {
            $error_message = 'Error preparing reject: ' . $conn->error;
        } else {
            $stmt->bind_param("i", $request_id);
            if ($stmt->execute()) {
                $success_message = 'Request rejected successfully';
                // 发送通知
                $stmt = $conn->prepare("INSERT INTO notification (user_id, type, title, message, related_id, created_at)
                                        VALUES (?, 'session', 'Request Rejected', 'Your tutoring request has been rejected.', ?, NOW())");
                if ($stmt) {
                    $stmt->bind_param("ii", $request['student_id'], $request_id);
                    $stmt->execute();
                }
            } else {
                $error_message = 'Failed to reject request: ' . $conn->error;
            }
        }
    } else {
        $error_message = 'Invalid request ID or no permission.';
    }
    if (isset($stmt) && is_object($stmt)) {
        $stmt->close();
    }

    // 重定向
    $redirect = $success_message ? "?success=" . urlencode($success_message) : "?error=" . urlencode($error_message);
    header("Location: tutor_requests.php$redirect");
    exit();
}

// 获取待处理请求
$pending_requests = [];
$pending_count = 0;

$stmt = $conn->prepare("SELECT sr.request_id, sr.tutor_id, sr.student_id, sr.course_id, sr.status, sr.created_at, c.course_name, u.first_name, u.last_name, u.profile_image
                        FROM session_requests sr
                        JOIN course c ON sr.course_id = c.id
                        JOIN user u ON sr.student_id = u.user_id
                        WHERE sr.tutor_id = ? AND sr.status = 'pending'");
if (!$stmt) {
    $error_message = "Error preparing pending requests: " . $conn->error;
} else {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $pending_requests[] = $row;
    }
    $pending_count = count($pending_requests);
    $stmt->close();
}

// 获取已处理请求
$processed_requests = [];
$stmt = $conn->prepare("SELECT sr.request_id, sr.tutor_id, sr.student_id, sr.course_id, sr.status, sr.created_at, c.course_name, u.first_name, u.last_name, u.profile_image
                        FROM session_requests sr
                        JOIN course c ON sr.course_id = c.id
                        JOIN user u ON sr.student_id = u.user_id
                        WHERE sr.tutor_id = ? AND sr.status != 'pending'
                        ORDER BY created_at DESC
                        LIMIT 20");
if (!$stmt) {
    $error_message = "Error preparing processed requests: " . $conn->error;
} else {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $processed_requests[] = $row;
    }
    $stmt->close();
}

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
            border: 1px solid rgba(0, 0, 0, 0.05);
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
            border-right: 2px solid var(--light-gray);
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
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-success {
            background-color: var(--accent);
            color: white;
        }

        .btn-success:hover {
            background-color: #b1c100;
        }

        .btn-danger {
            background-color: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .btn-message {
            background-color: var(--secondary);
            color: white;
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

        .status-rejected {
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

        @media (max-width: 768px) {
            .navbar { padding: 1rem; }
            .nav-links { gap: 1rem; }
            .request-list { grid-template-columns: 1fr; }
            .request-actions { flex-direction: column; }
            .btn { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>
    <?php include 'header/tut_head.php'; ?>

    <main>
        <h1 class="page-title">Tutoring Requests</h1>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="tabs">
            <div class="tab active" data-tab="pending">Pending Requests<?php if ($pending_count > 0): ?>
                <span class="notification-badge"><?php echo $pending_count; ?></span><?php endif; ?></div>
            <div class="tab" data-tab="processed">Processed Requests</div>
        </div>

        <div id="pending-tab" class="tab-content active">
            <?php if ($pending_requests): ?>
                <div class="request-list">
                    <?php foreach ($pending_requests as $request): ?>
                        <div class="request-card">
                            <div class="request-header">
                                <div class="student-avatar">
                                    <?php if ($request['profile_image']): ?>
                                        <img src="<?php echo htmlspecialchars($request['profile_image']); ?>" alt="Student" class="profile-image">
                                    <?php else: ?>
                                        <?php echo htmlspecialchars(substr($request['first_name'] ?? '', 0, 1) . substr($request['last_name'] ?? '', 0, 1)); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="request-info">
                                    <div class="student-name"><?php echo htmlspecialchars(($request['first_name'] ?? '') . ' ' . ($request['last_name'] ?? '')); ?></div>
                                    <div class="request-subject"><?php echo htmlspecialchars($request['course_name'] ?? 'Unknown Course'); ?></div>
                                </div>
                            </div>
                            <div class="request-details">
                                <div class="detail-item">
                                    <div class="detail-label">Course:</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($request['course_name'] ?? 'Unknown Course'); ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Requested On:</div>
                                    <div class="detail-value">
                                        <?php echo !empty($request['created_at']) 
                                            ? date('M d, Y', strtotime($request['created_at'])) 
                                            : 'Not specified'; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="request-actions">
                                <form method="post" action="tutor_requests.php">
                                    <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request['request_id'] ?? ''); ?>">
                                    <input type="hidden" name="action" value="accept">
                                    <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Accept</button>
                                </form>
                                <form method="post" action="tutor_requests.php">
                                    <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request['request_id'] ?? ''); ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn btn-danger"><i class="fas fa-times"></i> Reject</button>
                                </form>
                                <a href="messages.php?student_id=<?php echo htmlspecialchars($request['student_id'] ?? ''); ?>" class="btn btn-message"><i class="fas fa-envelope"></i> Message</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-inbox"></i></div>
                    <h3>No Pending Requests</h3>
                    <p class="empty-text">You don't have any pending tutoring requests at the moment.</p>
                </div>
            <?php endif; ?>
        </div>

        <div id="processed-tab" class="tab-content">
            <?php if ($processed_requests): ?>
                <div class="request-list">
                    <?php foreach ($processed_requests as $request): ?>
                        <div class="request-card">
                            <div class="request-header">
                                <div class="student-avatar">
                                    <?php if ($request['profile_image']): ?>
                                        <img src="<?php echo htmlspecialchars($request['profile_image']); ?>" alt="Student" class="profile-image">
                                    <?php else: ?>
                                        <?php echo htmlspecialchars(substr($request['first_name'] ?? '', 0, 1) . substr($request['last_name'] ?? '', 0, 1)); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="request-info">
                                    <div class="student-name"><?php echo htmlspecialchars(($request['first_name'] ?? '') . ' ' . ($request['last_name'] ?? '')); ?></div>
                                    <div class="request-subject"><?php echo htmlspecialchars($request['course_name'] ?? 'Unknown Course'); ?></div>
                                </div>
                                <?php
                                $statusClass = $statusText = '';
                                switch ($request['status'] ?? '') {
                                    case 'confirmed':
                                        $statusClass = 'status-confirmed';
                                        $statusText = 'Confirmed';
                                        break;
                                    case 'rejected':
                                        $statusClass = 'status-rejected';
                                        $statusText = 'Rejected';
                                        break;
                                    default:
                                        $statusClass = 'status-pending';
                                        $statusText = $request['status'] ?? 'Unknown';
                                }
                                ?>
                                <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                            </div>
                            <div class="request-details">
                                <div class="detail-item">
                                    <div class="detail-label">Course:</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($request['course_name'] ?? 'Unknown Course'); ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Requested On:</div>
                                    <div class="detail-value">
                                        <?php echo !empty($request['created_at']) 
                                            ? date('M d, Y', strtotime($request['created_at'])) 
                                            : 'Not specified'; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="request-actions">
                                <a href="messages.php?student_id=<?php echo htmlspecialchars($request['student_id'] ?? ''); ?>" class="btn btn-message"><i class="fas fa-envelope"></i> Message</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-history"></i></div>
                    <h3>No Processed Requests</h3>
                    <p class="empty-text">You haven't processed any tutoring requests yet.</p>
                </div>
            <?php endif; ?>
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
                document.getElementById(`${tab.getAttribute('data-tab')}-tab`).classList.add('active');
            });
        });
    </script>
</body>
</html>
</html>