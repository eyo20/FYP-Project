<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "peer tutoring platform";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get student ID from session (assuming you have login system)
 // Replace with actual session-based ID once implemented

// Fetch student information
$student_query = "SELECT * FROM students WHERE students_id = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("i", $students_id);
$stmt->execute();
$student_result = $stmt->get_result();
$student = $student_result->fetch_assoc();

// Fetch upcoming sessions
$upcoming_query = "SELECT s.*, t.name as tutor_name, t.image_url as tutor_image, sub.subject_name
                  FROM sessions s
                  JOIN tutors t ON s.tutor_id = t.tutors_id
                  JOIN subjects sub ON s.subject_id = sub.subject_id
                  WHERE s.students_id = ? AND s.date >= CURDATE()
                  ORDER BY s.date, s.start_time
                  LIMIT 5";
$stmt = $conn->prepare($upcoming_query);
$stmt->bind_param("i", $students_id);
$stmt->execute();
$upcoming_result = $stmt->get_result();

// Fetch past sessions that need reviews
$review_query = "SELECT s.*, t.name as tutor_name, t.image_url as tutor_image, sub.subject_name
                FROM sessions s
                JOIN tutors t ON s.tutor_id = t.tutors_id
                JOIN subjects sub ON s.subject_id = sub.subject_id
                WHERE s.students_id = ? AND s.date < CURDATE() AND s.is_reviewed = 0
                ORDER BY s.date DESC
                LIMIT 3";
$stmt = $conn->prepare($review_query);
$stmt->bind_param("i", $students_id);
$stmt->execute();
$review_result = $stmt->get_result();

// Fetch unread messages count
$message_query = "SELECT COUNT(*) as unread FROM messages 
                 WHERE receiver_id = ? AND is_read = 0";
$stmt = $conn->prepare($message_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$message_result = $stmt->get_result();
$message_count = $message_result->fetch_assoc()['unread'];

// Fetch recommended tutors based on student's subjects of interest
$recommended_query = "SELECT t.*, AVG(r.rating) as avg_rating
                     FROM tutors t
                     LEFT JOIN reviews r ON t.tutors_id = r.tutor_id
                     JOIN subjects s ON t.tutors_id = s.tutor_id
                     WHERE s.subject_name IN (SELECT interest FROM student_interests WHERE student_id = ?)
                     GROUP BY t.tutors_id
                     ORDER BY avg_rating DESC
                     LIMIT 3";
$stmt = $conn->prepare($recommended_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$recommended_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>学生仪表盘 - 学生同伴辅导平台</title>
    <style>
        :root {
            --primary-color: #2B3990;
            --secondary-color: #00AEEF;
            --accent-color: #C4D600;
            --light-gray: #f5f5f5;
            --dark-gray: #333333;
            --medium-gray: #cccccc;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--light-gray);
            color: var(--dark-gray);
        }
        
        header {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .notification-icon {
            position: relative;
            cursor: pointer;
        }
        
        .notification-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: var(--accent-color);
            color: var(--dark-gray);
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 0.7rem;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
            display: grid;
            grid-template-columns: 240px 1fr;
            gap: 2rem;
        }
        
        .sidebar {
            background-color: white;
            border-radius: 8px;
            padding: 1rem;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
            height: fit-content;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li {
            margin-bottom: 0.5rem;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            text-decoration: none;
            color: var(--dark-gray);
            padding: 0.8rem;
            border-radius: 6px;
            transition: all 0.2s;
        }
        
        .sidebar-menu a:hover {
            background-color: var(--light-gray);
        }
        
        .sidebar-menu a.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        .sidebar-menu .menu-icon {
            width: 20px;
            height: 20px;
            opacity: 0.7;
        }
        
        .dashboard-content {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .greeting {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .dashboard-actions {
            display: flex;
            gap: 1rem;
        }
        
        .btn {
            background-color: var(--accent-color);
            color: var(--dark-gray);
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn:hover {
            background-color: #b5c500;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #232d75;
        }
        
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
        }
        
        .stat-title {
            font-size: 0.9rem;
            color: var(--dark-gray);
            opacity: 0.7;
            margin-bottom: 0.5rem;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .stat-footer {
            margin-top: 1rem;
            font-size: 0.8rem;
            color: var(--dark-gray);
            opacity: 0.7;
        }
        
        .dashboard-section {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .section-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--primary-color);
            margin: 0;
        }
        
        .see-all {
            color: var(--secondary-color);
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .upcoming-sessions {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
        }
        
        .session-card {
            background-color: var(--light-gray);
            border-radius: 8px;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            transition: all 0.3s;
        }
        
        .session-card:hover {
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .session-header {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .tutor-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .session-details {
            flex: 1;
        }
        
        .tutor-name {
            font-weight: bold;
            margin: 0;
        }
        
        .subject-name {
            font-size: 0.9rem;
            color: var(--secondary-color);
            margin: 0;
        }
        
        .session-info {
            display: flex;
            justify-content: space-between;
            margin-top: 0.5rem;
        }
        
        .session-info-item {
            display: flex;
            flex-direction: column;
            font-size: 0.8rem;
        }
        
        .info-label {
            color: var(--dark-gray);
            opacity: 0.7;
        }
        
        .info-value {
            font-weight: bold;
        }
        
        .session-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--secondary-color);
            color: var(--secondary-color);
        }
        
        .btn-outline:hover {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .recommended-tutors {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
        }
        
        .tutor-card {
            background-color: var(--light-gray);
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .tutor-card:hover {
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            transform: translateY(-3px);
        }
        
        .tutor-card-header {
            height: 80px;
            background-color: var(--secondary-color);
            position: relative;
        }
        
        .tutor-card-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            position: absolute;
            bottom: -40px;
            left: 50%;
            transform: translateX(-50%);
            border: 4px solid white;
        }
        
        .tutor-card-body {
            padding: 2.5rem 1rem 1rem 1rem;
            text-align: center;
        }
        
        .tutor-card-name {
            font-weight: bold;
            margin-bottom: 0.2rem;
        }
        
        .tutor-card-subjects {
            font-size: 0.8rem;
            color: var(--dark-gray);
            opacity: 0.7;
            margin-bottom: 0.5rem;
        }
        
        .tutor-card-rating {
            color: gold;
            margin-bottom: 1rem;
        }
        
        .need-review {
            background-color: rgba(255, 193, 7, 0.1);
            border-left: 4px solid var(--warning-color);
        }
        
        .pending-review {
            margin-top: 1rem;
        }
        
        .review-reminder {
            background-color: rgba(255, 193, 7, 0.1);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid var(--warning-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .reminder-text {
            flex: 1;
        }
        
        .reminder-title {
            font-weight: bold;
            margin-bottom: 0.3rem;
        }
        
        .reminder-session {
            font-size: 0.9rem;
        }
        
        @media (max-width: 992px) {
            .container {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                display: none;
            }
            
            .dashboard-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 576px) {
            .dashboard-stats {
                grid-template-columns: 1fr;
            }
            
            .upcoming-sessions, .recommended-tutors {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">学生同伴辅导平台</div>
        <div class="user-menu">
            <div class="notification-icon">
                <i class="fas fa-bell"></i>
                <?php if($message_count > 0): ?>
                <span class="notification-count"><?php echo $message_count; ?></span>
                <?php endif; ?>
            </div>
            <div class="user-profile">
                <img src="<?php echo isset($student['image_url']) ? htmlspecialchars($student['image_url']) : 'assets/default-avatar.png'; ?>" alt="用户头像" class="user-avatar">
                <span><?php echo htmlspecialchars($student['name'] ?? 'Student'); ?></span>
            </div>
        </div>
    </header>
    
    <div class="container">
        <nav class="sidebar">
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt menu-icon"></i> 仪表盘</a></li>
                <li><a href="tutor-search.php"><i class="fas fa-search menu-icon"></i> 寻找辅导</a></li>
                <li><a href="appointments.php"><i class="fas fa-calendar-alt menu-icon"></i> 我的预约</a></li>
                <li><a href="messages.php"><i class="fas fa-comments menu-icon"></i> 消息中心</a></li>
                <li><a href="reviews.php"><i class="fas fa-star menu-icon"></i> 我的评价</a></li>
                <li><a href="profile.php"><i class="fas fa-user menu-icon"></i> 个人资料</a></li>
                <li><a href="settings.php"><i class="fas fa-cog menu-icon"></i> 设置</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt menu-icon"></i> 退出登录</a></li>
            </ul>
        </nav>
        
        <div class="dashboard-content">
            <div class="dashboard-header">
                <div class="greeting">你好，<?php echo htmlspecialchars($student['name'] ?? 'Student'); ?>！</div>
                <div class="dashboard-actions">
                    <a href="tutor-search.php" class="btn btn-primary"><i class="fas fa-search"></i> 寻找辅导</a>
                </div>
            </div>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-title">完成的辅导</div>
                    <div class="stat-value">18</div>
                    <div class="stat-footer">本学期</div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">即将到来的辅导</div>
                    <div class="stat-value"><?php echo $upcoming_result->num_rows; ?></div>
                    <div class="stat-footer">未来7天</div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">平均评分</div>
                    <div class="stat-value">4.8</div>
                    <div class="stat-footer">从辅导者收到</div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">学习时间</div>
                    <div class="stat-value">36</div>
                    <div class="stat-footer">小时</div>
                </div>
            </div>
            
            <?php if($review_result->num_rows > 0): ?>
            <div class="pending-review">
                <div class="review-reminder">
                    <div class="reminder-text">
                        <div class="reminder-title">您有 <?php echo $review_result->num_rows; ?> 个辅导需要评价</div>
                        <div class="reminder-session">您的反馈对我们很重要，请花一点时间评价您的辅导体验。</div>
                    </div>
                    <a href="reviews.php" class="btn btn-sm">立即评价</a>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="dashboard-section">
                <div class="section-header">
                    <h3 class="section-title">即将到来的辅导</h3>
                    <a href="appointments.php" class="see-all">查看全部</a>
                </div>
                
                <div class="upcoming-sessions">
                    <?php if($upcoming_result->num_rows > 0): ?>
                        <?php while($session = $upcoming_result->fetch_assoc()): ?>
                            <div class="session-card">
                                <div class="session-header">
                                    <img src="<?php echo htmlspecialchars($session['tutor_image']); ?>" alt="辅导者头像" class="tutor-avatar">
                                    <div class="session-details">
                                        <h4 class="tutor-name"><?php echo htmlspecialchars($session['tutor_name']); ?></h4>
                                        <p class="subject-name"><?php echo htmlspecialchars($session['subject_name']); ?></p>
                                    </div>
                                </div>
                                <div class="session-info">
                                    <div class="session-info-item">
                                        <span class="info-label">日期</span>
                                        <span class="info-value"><?php echo date('Y-m-d', strtotime($session['date'])); ?></span>
                                    </div>
                                    <div class="session-info-item">
                                        <span class="info-label">时间</span>
                                        <span class="info-value"><?php echo date('H:i', strtotime($session['start_time'])) . ' - ' . date('H:i', strtotime($session['end_time'])); ?></span>
                                    </div>
                                    <div class="session-info-item">
                                        <span class="info-label">地点</span>
                                        <span class="info-value"><?php echo htmlspecialchars($session['location']); ?></span>
                                    </div>
                                </div>
                                <div class="session-actions">
                                    <a href="session-details.php?id=<?php echo $session['session_id']; ?>" class="btn btn-sm">查看详情</a>
                                    <a href="#" class="btn btn-sm btn-outline">取消预约</a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>您目前没有即将到来的辅导预约。</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="dashboard-section">
                <div class="section-header">
                    <h3 class="section-title">推荐辅导者</h3>
                    <a href="tutor-search.php" class="see-all">查看更多</a>
                </div>
                
                <div class="recommended-tutors">
                    <?php if($recommended_result->num_rows > 0): ?>
                        <?php while($tutor = $recommended_result->fetch_assoc()): ?>
                            <div class="tutor-card">
                                <div class="tutor-card-header">
                                    <img src="<?php echo htmlspecialchars($tutor['image_url']); ?>" alt="辅导者头像" class="tutor-card-avatar">
                                </div>
                                <div class="tutor-card-body">
                                    <div class="tutor-card-name"><?php echo htmlspecialchars($tutor['name']); ?></div>
                                    <div class="tutor-card-subjects"><?php echo htmlspecialchars($tutor['major']); ?> | <?php echo htmlspecialchars($tutor['year']); ?>年级</div>
                                    <div class="tutor-card-rating">
                                        <?php 
                                        $rating = round($tutor['avg_rating'], 1);
                                        for($i = 1; $i <= 5; $i++) {
                                            if($i <= floor($rating)) {
                                                echo "★";
                                            } elseif($i - 0.5 <= $rating) {
                                                echo "★"; 
                                            } else {
                                                echo "☆";
                                            }
                                        }
                                        echo " ({$rating})";
                                        ?>
                                    </div>
                                    <a href="tutor-profile.php?id=<?php echo $tutor['tutors_id']; ?>" class="btn btn-sm">查看资料</a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>暂无推荐辅导者。</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>