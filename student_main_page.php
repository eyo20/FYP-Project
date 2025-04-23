<?php
session_start();
require_once "db_connection.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 获取用户信息
$user_query = "SELECT username, email, role, created_at FROM user WHERE user_id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();
$username = $user_data['username'];
$stmt->close();

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $email = $role = $created_at = '';
$major = $year = 'Not set';  // 设置默认值

// 获取用户信息
$user_query = "SELECT username, email, role, created_at FROM user WHERE user_id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();

if ($user_result->num_rows > 0) {
    $user_data = $user_result->fetch_assoc();
    $username = $user_data['username'];
    $email = $user_data['email'];
    $role = $user_data['role'];
    $created_at = $user_data['created_at'];
} else {
    // 用户不存在，重定向到登录页面
    session_destroy();
    header("Location: login.php");
    exit();
}
$stmt->close();

// 检查studentprofile表是否存在
$table_check = $conn->query("SHOW TABLES LIKE 'studentprofile'");
$table_exists = $table_check->num_rows > 0;

// 如果表存在，尝试获取学生资料（如果有）
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

// 初始化变量
$upcoming_sessions = [];
$completed_sessions = 0;
$subjects_count = 0;

// 获取即将到来的辅导课程
$upcoming_sessions_query = "
    SELECT s.session_id, s.created_at as session_date, s.status,
           c.course_title as subject,
           u.username as tutor_name,
           sl.start_time
    FROM session s
    JOIN user u ON s.tutor_id = u.user_id  /* 修改为user表而不是users */
    JOIN courses c ON s.course_id = c.courses_id
    JOIN time_slots sl ON s.slot_id = sl.slot_id
    WHERE s.student_id = ? AND s.status = 'scheduled' 
    ORDER BY s.created_at, sl.start_time
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

// 获取已完成课程数量
$completed_sessions_query = "
    SELECT COUNT(*) as completed_count
    FROM session
    WHERE student_id = ? AND status = 'completed'";

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

// 获取所学科目数量
$subjects_query = "
    SELECT COUNT(DISTINCT course_id) as subjects_count
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

// 初始化变量
$goals_completion = 0;
$unread_messages = 0;
$recommended_tutors = [];

// 计算完成率
$goals_query = "
    SELECT 
        CASE 
            WHEN COUNT(*) = 0 THEN 0
            ELSE ROUND((SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) / COUNT(*)) * 100)
        END as completion_rate
    FROM session
    WHERE student_id = ? AND (status = 'completed' OR status = 'cancelled')";

$stmt = $conn->prepare($goals_query);
if ($stmt === false) {
    error_log("Prepare failed for goals query: " . $conn->error);
} else {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $goals_result = $stmt->get_result();
    $goals_data = $goals_result->fetch_assoc();
    $goals_completion = $goals_data['completion_rate'];
    $stmt->close();
}

// 获取未读消息数量
$unread_messages_query = "
    SELECT COUNT(*) as unread_count
    FROM message
    WHERE receiver_id = ? AND is_read = 0";

$stmt = $conn->prepare($unread_messages_query);
if ($stmt === false) {
    error_log("Prepare failed for unread messages query: " . $conn->error);
} else {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $messages_result = $stmt->get_result();
    $messages_data = $messages_result->fetch_assoc();
    $unread_messages = $messages_data['unread_count'];
    $stmt->close();
}

// 获取推荐导师
// 注意：这里有一个字段名称问题 - tutor表中的字段名可能不一致
// 原查询使用了t.subject和t.subjects两个不同的字段名
$recommended_tutors_query = "
    SELECT t.tutor_id, u.username, t.expertise as subject, t.availability
    FROM tutor t
    JOIN user u ON t.user_id = u.user_id
    WHERE t.expertise LIKE ?
    LIMIT 3";

// 如果major为空，使用一个通用搜索词
$major_search = !empty($major) ? "%$major%" : "%";

$stmt = $conn->prepare($recommended_tutors_query);
if ($stmt === false) {
    error_log("Prepare failed for recommended tutors query: " . $conn->error);
} else {
    $stmt->bind_param("s", $major_search);
    $stmt->execute();
    $tutors_result = $stmt->get_result();
    
    while ($row = $tutors_result->fetch_assoc()) {
        $recommended_tutors[] = $row;
    }
    $stmt->close();
}

// 若无推荐导师则使用备选推荐
if (count($recommended_tutors) == 0) {
    $backup_tutors_query = "
        SELECT 
            s.tutor_id,
            u.username,
            COUNT(s.session_id) as session_count,
            GROUP_CONCAT(DISTINCT c.course_title) as subjects
        FROM session s
        JOIN user u ON s.tutor_id = u.user_id  /* 修改为user表而不是users */
        JOIN courses c ON s.course_id = c.courses_id
        WHERE c.course_title LIKE ?
        GROUP BY s.tutor_id
        ORDER BY session_count DESC
        LIMIT 3
    ";
    
    // 如果major为空，使用一个通用搜索词
    $major_search = !empty($major) ? "%$major%" : "%";
    
    $stmt = $conn->prepare($backup_tutors_query);
    if ($stmt === false) {
        error_log("Prepare failed for backup tutors query: " . $conn->error);
    } else {
        $stmt->bind_param("s", $major_search);
        $stmt->execute();
        $backup_result = $stmt->get_result();
        
        while ($row = $backup_result->fetch_assoc()) {
            $row['availability'] = "请联系查询具体可用时间";  // 中文提示，可能需要改为英文
            $recommended_tutors[] = $row;
        }
        $stmt->close();
    }
}



$conn->close();
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>同伴辅导平台 - 学生仪表盘</title>
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
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
            background-color: rgba(255,255,255,0.1);
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
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .welcome-title {
            color: var(--primary);
            margin-bottom: 1rem;
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
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            text-align: center;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
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
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            gap: 1rem;
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
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
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        
        .session-list {
            margin-top: 1rem;
        }
        
        .session-item {
            display: flex;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid var(--gray);
        }
        
        .session-item:last-child {
            border-bottom: none;
        }
        
        .session-time {
            background-color: var(--light-gray);
            padding: 0.5rem;
            border-radius: 4px;
            min-width: 100px;
            text-align: center;
        }
        
        .session-info {
            margin-left: 1rem;
            flex-grow: 1;
        }
        
        .session-subject {
            font-weight: 600;
            color: var(--primary);
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
            padding: 0.5rem;
            border: none;
            border-radius: 4px;
            background-color: var(--light-gray);
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .session-actions button:hover {
            background-color: var(--gray);
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

        /* Responsive adjustments */
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
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <div class="logo-icon">P</div>
            <span>PeerTutor</span>
        </div>
        <div class="nav-links">
            <a href="student_dashboard.php">仪表盘</a>
            <a href="find_tutors.php">寻找导师</a>
            <a href="appointments.php">预约管理</a>
            <a href="review.php">提交评价</a>
            <a href="message.php">消息 <?php if($unread_messages > 0): ?><span class="notification-badge"><?php echo $unread_messages; ?></span><?php endif; ?></a>
        </div>
        <div class="user-menu">
            <?php if($unread_messages > 0): ?>
            <div class="notification-badge"><?php echo $unread_messages; ?></div>
            <?php endif; ?>
            <div class="user-avatar"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
        </div>
    </nav>
    
    <main>
        <section class="welcome-section">
            <h1 class="welcome-title">欢迎回来，<?php echo htmlspecialchars($username); ?>！</h1>
            <p>您是 <?php echo htmlspecialchars($major); ?> 专业 <?php echo htmlspecialchars($year); ?> 年级的学生。
               <?php if(count($upcoming_sessions) > 0): ?>
               您有 <?php echo count($upcoming_sessions); ?> 个待进行的辅导课程。继续努力！
               <?php else: ?>
               您目前没有安排辅导课程。是否要找一个导师开始学习呢？
               <?php endif; ?>
            </p>
            
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon">📚</div>
                    <div class="stat-value"><?php echo $completed_sessions; ?></div>
                    <div class="stat-label">已完成课程</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📝</div>
                    <div class="stat-value"><?php echo $subjects_count; ?></div>
                    <div class="stat-label">学习科目</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🎯</div>
                    <div class="stat-value"><?php echo $goals_completion; ?>%</div>
                    <div class="stat-label">目标达成率</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💬</div>
                    <div class="stat-value"><?php echo $unread_messages; ?></div>
                    <div class="stat-label">未读消息</div>
                </div>
            </div>
        </section>
        
        <h2 class="section-title">快捷操作</h2>
        <div class="quick-actions">
            <div class="action-card" onclick="window.location.href='find_tutors.php'">
                <div class="action-header">
                    <div class="action-icon">🔍</div>
                    <div class="action-title">寻找导师</div>
                </div>
                <p class="action-description">根据科目、可用性和评价筛选合适的导师。</p>
                <a href="find_tutors.php" class="btn">立即搜索</a>
            </div>
            
            <div class="action-card" onclick="window.location.href='appointments.php'">
                <div class="action-header">
                    <div class="action-icon">📅</div>
                    <div class="action-title">预约课程</div>
                </div>
                <p class="action-description">与您喜欢的导师安排新的辅导课程。</p>
                <a href="appointments.php" class="btn">立即预约</a>
            </div>
            
            <div class="action-card" onclick="window.location.href='reviews.php'">
                <div class="action-header">
                    <div class="action-icon">⭐</div>
                    <div class="action-title">提交评价</div>
                </div>
                <p class="action-description">分享您对最近辅导体验的反馈。</p>
                <a href="reviews.php" class="btn">写评价</a>
            </div>
            
            <div class="action-card" onclick="window.location.href='messages.php'">
                <div class="action-header">
                    <div class="action-icon">💬</div>
                    <div class="action-title">消息</div>
                </div>
                <p class="action-description">查看您的消息并与导师沟通。</p>
                <a href="messages.php" class="btn">查看消息 <?php if($unread_messages > 0): ?><span class="notification-badge"><?php echo $unread_messages; ?></span><?php endif; ?></a>
            </div>
        </div>
        
        <h2 class="section-title">即将到来的课程</h2>
        <div class="upcoming-sessions">
            <div class="session-list">
                <?php if(count($upcoming_sessions) > 0): ?>
                    <?php foreach($upcoming_sessions as $session): ?>
                        <?php 
                            $session_date = new DateTime($session['session_date']);
                            $today = new DateTime('today');
                            $tomorrow = new DateTime('tomorrow');
                            
                            if($session_date->format('Y-m-d') == $today->format('Y-m-d')) {
                                $date_display = "今天";
                            } elseif($session_date->format('Y-m-d') == $tomorrow->format('Y-m-d')) {
                                $date_display = "明天";
                            } else {
                                $date_display = $session_date->format('m月d日');
                            }
                        ?>
                        <div class="session-item">
                            <div class="session-time">
                                <div><?php echo $date_display; ?></div>
                                <div><?php echo substr($session['start_time'], 0, 5); ?></div>
                            </div>
                            <div class="session-info">
                                <div class="session-subject"><?php echo htmlspecialchars($session['subject']); ?></div>
                                <div class="session-tutor">与 <?php echo htmlspecialchars($session['tutor_name']); ?> 一起</div>
                            </div>
                            <div class="session-actions">
                                <?php if($date_display == "今天"): ?>
                                <button onclick="joinSession(<?php echo $session['session_id']; ?>)">加入</button>
                                <?php endif; ?>
                                <button onclick="rescheduleSession(<?php echo $session['session_id']; ?>)">重新安排</button>
                                <button onclick="cancelSession(<?php echo $session['session_id']; ?>)">取消</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <p>您目前没有安排的课程。</p>
                        <a href="find_tutors.php" class="btn">寻找导师</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <h2 class="section-title">推荐导师</h2>
        <div class="quick-actions">
            <?php if(count($recommended_tutors) > 0): ?>
                <?php foreach($recommended_tutors as $tutor): ?>
                    <div class="action-card">
                        <div class="action-header">
                            <div class="action-icon" style="background-color: var(--primary);">👨‍🏫</div>
                            <div class="action-title"><?php echo htmlspecialchars($tutor['username']); ?></div>
                        </div>
                        <p class="action-description"><?php echo htmlspecialchars($tutor['subjects']); ?></p>
                        <?php if(isset($tutor['availability'])): ?>
                        <p class="action-description">可用时间: <?php echo htmlspecialchars($tutor['availability']); ?></p>
                        <?php endif; ?>
                        <a href="tutor_profile.php?id=<?php echo $tutor['tutor_id']; ?>" class="btn">查看资料</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="action-card">
                    <p class="empty-state">暂时没有适合您专业的推荐导师。</p>
                    <a href="find_tutors.php" class="btn">浏览所有导师</a>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <footer>
        <p>&copy; <?php echo date('Y'); ?> PeerTutor平台. 保留所有权利.</p>
    </footer>
    
    <script>
        function joinSession(sessionId) {
            // 实现加入课程的功能
            alert("准备加入课程 #" + sessionId);
            // 这里可以重定向到视频会议页面或其他相关页面
            // window.location.href = "join_session.php?id=" + sessionId;
        }
        
        function rescheduleSession(sessionId) {
            // 实现重新安排课程的功能
            alert("重新安排课程 #" + sessionId);
            // window.location.href = "reschedule.php?id=" + sessionId;
        }
        
        function cancelSession(sessionId) {
            // 实现取消课程的功能
            if(confirm("确定要取消此课程吗？")) {
                // 使用AJAX发送请求到服务器取消课程
                var xhr = new XMLHttpRequest();
                xhr.open("POST", "cancel_session.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function() {
                    if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
                        alert("课程已取消");
                        location.reload();
                    }
                }
                xhr.send("session_id=" + sessionId);
            }
        }
    </script>
</body>
</html>
