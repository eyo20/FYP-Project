<?php
session_start();
require_once "db_connection.php";

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

if (!$user_data || $user_data['role'] != 'tutor') {
    // 如果用户不是导师，重定向到学生页面或登录页面
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

// 获取导师资料
$tutor_query = "SELECT major, year, bio, qualifications, is_verified, rating FROM tutorprofile WHERE user_id = ?";
$stmt = $conn->prepare($tutor_query);
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
    // 如果没有导师资料，设置默认值
    $major = 'Not set';
    $year = 'Not set';
    $bio = '';
    $qualifications = '';
    $is_verified = 0;
    $rating = 0;
}
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

// 获取统计数据
// 1. 总课程数
$total_sessions_query = "SELECT COUNT(*) as total_count FROM session WHERE tutor_id = ?";
$stmt = $conn->prepare($total_sessions_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_result = $stmt->get_result();
$total_data = $total_result->fetch_assoc();
$total_sessions = $total_data['total_count'];
$stmt->close();

// 2. 本月预约数
$month_sessions_query = "SELECT COUNT(*) as month_count
                        FROM session
                        WHERE tutor_id = ? 
                         AND MONTH(start_datetime) = MONTH(CURRENT_DATE())
                        AND YEAR(start_datetime) = YEAR(CURRENT_DATE())";
$stmt = $conn->prepare($month_sessions_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$month_result = $stmt->get_result();
$month_data = $month_result->fetch_assoc();
$month_sessions = $month_data['month_count'];
$stmt->close();

// 3. 总学生数
$students_count_query = "SELECT COUNT(DISTINCT student_id) as student_count
                        FROM session
                        WHERE tutor_id = ?";
$stmt = $conn->prepare($students_count_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$students_result = $stmt->get_result();
$students_data = $students_result->fetch_assoc();
$total_students = $students_data['student_count'];
$stmt->close();

// 4. 待处理的预约请求数量
$pending_requests_query = "SELECT COUNT(*) as pending_count
                          FROM session
                          WHERE tutor_id = ? AND status = 'pending'";
$stmt = $conn->prepare($pending_requests_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending_result = $stmt->get_result();
$pending_data = $pending_result->fetch_assoc();
$pending_requests = $pending_data['pending_count'];
$stmt->close();

// 获取导师的可用时间
$availability_query = "SELECT availability_id, DATE_FORMAT(start_datetime, '%Y-%m-%d') as date,
                       DATE_FORMAT(start_datetime, '%H:%i') as start_time,
                      DATE_FORMAT(end_datetime, '%H:%i') as end_time,
                      DAYOFWEEK(start_datetime) as day_of_week,
                      CASE
                           WHEN EXISTS (SELECT 1 FROM session WHERE
                                       (start_datetime BETWEEN a.start_datetime AND a.end_datetime OR
                                       end_datetime BETWEEN a.start_datetime AND a.end_datetime) AND
                                      tutor_id = a.tutor_id AND status != 'cancelled')
                          THEN 'booked' ELSE 'available'
                       END as status
                      FROM availability a
                      WHERE tutor_id = ? AND start_datetime >= CURDATE()
                      ORDER BY start_datetime";
$stmt = $conn->prepare($availability_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$availability_result = $stmt->get_result();
$availabilities = [];
while ($row = $availability_result->fetch_assoc()) {
    $availabilities[] = $row;
}
$stmt->close();

// 按日期组织可用时间
$availability_by_date = [];
foreach ($availabilities as $slot) {
    $date = $slot['date'];
    if (!isset($availability_by_date[$date])) {
        $availability_by_date[$date] = [];
    }
    $availability_by_date[$date][] = $slot;
}

// 获取当前月份的日历数据
$current_month = date('n');
$current_year = date('Y');
$days_in_month = date('t', mktime(0, 0, 0, $current_month, 1, $current_year));
$first_day_of_month = date('N', mktime(0, 0, 0, $current_month, 1, $current_year));

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peer tutoring platform - tutor schedule management</title>
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

        .nav-links a.active {
            background-color: var(--accent);
            color: white;
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
        
        .feature-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
            margin-bottom: 2rem;
        }
        
        .feature-button {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            text-align: center;
            transition: transform 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: inherit;
        }
        
        .feature-button:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--primary);
        }
        
        .feature-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--primary);
        }
        
        .feature-description {
            color: var(--dark-gray);
        }
        
        .section-title {
            margin: 2rem 0 1rem;
            color: var(--primary);
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .section-title button {
            background-color: var(--accent);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .section-title button:hover {
            background-color: #b3c300;
        }
        
        .calendar-container {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
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
        
        .time-slot {
            margin-top: 25px;
            padding: 0.3rem;
            border-radius: 4px;
            font-size: 0.8rem;
            margin-bottom: 0.3rem;
            cursor: pointer;
        }
        
        .time-slot.available {
            background-color: rgba(196, 214, 0, 0.2);
            border-left: 3px solid var(--accent);
        }
        
        .time-slot.booked {
            background-color: rgba(0, 174, 239, 0.2);
            border-left: 3px solid var(--secondary);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background-color: white;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--gray);
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--dark-gray);
        }
        
        .form-group {
            margin-bottom: 1rem;
            padding: 0 1.5rem;
        }
        
        .form-group:last-child {
            padding-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--gray);
            border-radius: 4px;
            font-size: 1rem;
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
            display: block;
            width: 100%;
            margin: 0 1.5rem 1.5rem;
            max-width: calc(100% - 3rem);
        }
        
        .btn:hover {
            background-color: #b3c300;
        }
        
        footer {
            background-color: var(--primary);
            color: white;
            text-align: center;
            padding: 1.5rem;
            margin-top: 2rem;
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
            }
            
            .day-number:before {
                content: attr(data-day);
                margin-right: 5px;
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
            <a href="tutor_main_page.php" class="active">Main Page</a>
            <a href="tutor_profile.php">Profile</a>
            <a href="tutor_requests.php">Appointment Requests<?php if($pending_requests > 0): ?><span class="notification-badge"><?php echo $pending_requests; ?></span><?php endif; ?></a>
            <a href="tutor_students.php">My Students</a>
            <a href="messages.php">Messages<?php if($unread_messages > 0): ?><span class="notification-badge"><?php echo $unread_messages; ?></span><?php endif; ?></a>
        </div>
        <div class="user-menu">
            <div class="user-avatar">
                <?php if($profile_image): ?>
                <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile" class="profile-image">
                <?php else: ?>
                <?php echo strtoupper(substr($first_name, 0, 1)); ?>
                <?php endif; ?>
            </div>
            <a href="logout.php" style="color: white; text-decoration: none;">Logout</a>
        </div>
    </nav>
    
    <main>
        <div class="welcome-section">
            <div class="profile-image-container">
                <?php if($profile_image): ?>
                <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile" class="profile-image">
                <?php else: ?>
                <div class="profile-image-placeholder"><?php echo strtoupper(substr($first_name, 0, 1)); ?></div>
                <?php endif; ?>
            </div>
            <div class="welcome-info">
                <h1 class="welcome-title">
                    Welcome back, <?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>
                    <?php if($is_verified): ?>
                    <span class="verified-badge">Verified</span>
                    <?php endif; ?>
                </h1>
                <p>You can manage your tutoring schedule here, view appointment requests, and interact with students.</p>
                
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
                        <div class="stat-value"><?php echo $pending_requests; ?></div>
                        <div class="stat-label">Pending Requests</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="section-title">
            <h2>Availability</h2>
        </div>
        
        <div class="calendar-container">
            <div class="calendar-header">
                <h3 id="current-month"><?php echo date('Y-n'); ?></h3>
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
                // fill blanks before first day
                for ($i = 1; $i < $first_day_of_month; $i++) {
                    $prev_month_day = date('j', strtotime('-' . ($first_day_of_month - $i) . ' days', strtotime("$current_year-$current_month-01")));
                    echo '<div class="calendar-day other-month"><div class="day-number">' . $prev_month_day . '</div></div>';
                }
                
                // fill current month days
                $today = date('j');
                for ($day = 1; $day <= $days_in_month; $day++) {
                    $is_today = ($day == $today && $current_month == date('n') && $current_year == date('Y'));
                    $day_class = $is_today ? 'calendar-day today' : 'calendar-day';
                    $date_string = sprintf("%04d-%02d-%02d", $current_year, $current_month, $day);
                    echo "<div class=\"$day_class\">";
                    echo "<div class=\"day-number\">$day</div>";
                    if (isset($availability_by_date[$date_string])) {
                        foreach ($availability_by_date[$date_string] as $slot) {
                            $slot_class = $slot['status'] == 'available' ? 'time-slot available' : 'time-slot booked';
                            echo "<div class=\"$slot_class\">{$slot['start_time']} - {$slot['end_time']}</div>";
                        }
                    }
                    echo "</div>";
                }
                
                // fill blanks after last day
                $days_after = (7 - (($first_day_of_month - 1 + $days_in_month) % 7)) % 7;
                for ($i = 1; $i <= $days_after; $i++) {
                    $next_day = date('j', strtotime("+$i days", strtotime("$current_year-$current_month-$days_in_month")));
                    echo '<div class="calendar-day other-month"><div class="day-number">' . $next_day . '</div></div>';
                }
                ?>
            </div>
        </div>
    </main>
    
    <!-- Add Availability Modal -->
    <div class="modal" id="time-slot-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add Availability</h3>
                <button class="modal-close" onclick="closeModal('time-slot-modal')">×</button>
            </div>
            <form id="availability-form" method="post" action="add_availability.php">
                <label for="date">Date</label>
                <input type="date" id="date" name="date" required>
                
                <label for="start-time">Start Time</label>
                <input type="time" id="start-time" name="start_time" required>
                
                <label for="end-time">End Time</label>
                <input type="time" id="end-time" name="end_time" required>
                
                <label for="repeat">Repeat</label>
                <select id="repeat" name="repeat">
                    <option value="none">None</option>
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="biweekly">Biweekly</option>
                    <option value="monthly">Monthly</option>
                </select>
                
                <label for="repeat-until">Repeat Until</label>
                <input type="date" id="repeat-until" name="repeat_until" disabled>
                
                <button type="submit" class="btn">Save</button>
            </form>
        </div>
    </div>
    
    <footer>
        <p>&copy; <?php echo date('Y'); ?> PeerLearn Platform. All rights reserved.</p>
    </footer>
    
    <script>
        function openModal(id) { document.getElementById(id).style.display = 'flex'; }
        function closeModal(id) { document.getElementById(id).style.display = 'none'; }
        document.getElementById('repeat').addEventListener('change', function() {
            const until = document.getElementById('repeat-until');
            until.disabled = this.value === 'none';
            until.required = this.value !== 'none';
        });
        let m = <?php echo $current_month; ?>, y = <?php echo $current_year; ?>;
        document.getElementById('prev-month').onclick = () => { if(--m<1){m=12; y--;} updateCalendar(); };
        document.getElementById('next-month').onclick = () => { if(++m>12){m=1; y++;} updateCalendar(); };
        function updateCalendar(){ location.href=`tutor_main_page.php?month=${m}&year=${y}`; }
        document.getElementById('date').min = new Date().toISOString().split('T')[0];
        document.querySelectorAll('.time-slot').forEach(s => s.onclick = () => alert('Slot details: '+s.textContent.trim()));
    </script>
</body>
```
</html>
