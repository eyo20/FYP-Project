<<<<<<< HEAD:booking_html(templete).php

<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "peer_tutoring_platform";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get tutor ID from URL parameter
$tutor_id = isset($_GET['tutor_id']) ? intval($_GET['tutor_id']) : 1; // Default to first tutor if not specified

// Fetch tutor information
$tutor_query = "SELECT t.*, u.email FROM tutor t 
                JOIN User u ON t.user_id = u.user_id
                WHERE t.tutors_id = ?";
$stmt = $conn->prepare($tutor_query);
$stmt->bind_param("i", $tutor_id);
$stmt->execute();
$tutor_result = $stmt->get_result();
$tutor = $tutor_result->fetch_assoc();

// Fetch tutor subject
$subjects_query = "SELECT * FROM subject WHERE tutor_id = ?";
$stmt = $conn->prepare($subjects_query);
$stmt->bind_param("i", $tutor_id);
$stmt->execute();
$subjects_result = $stmt->get_result();
$subjects = [];
while ($row = $subjects_result->fetch_assoc()) {
    $subjects[] = $row;
}

// Fetch available time slots
$time_slots_query = "SELECT * FROM time_slots WHERE tutor_id = ?";
$stmt = $conn->prepare($time_slots_query);
$stmt->bind_param("i", $tutor_id);
$stmt->execute();
$time_slots_result = $stmt->get_result();
$time_slots = [];
while ($row = $time_slots_result->fetch_assoc()) {
    $time_slots[] = $row;
}

// Fetch locations
$locations_query = "SELECT * FROM locations";
$locations_result = $conn->query($locations_query);
$locations = [];
while ($row = $locations_result->fetch_assoc()) {
    $locations[] = $row;
}

// Calculate current date information for the calendar
$current_month = date('n');
$current_year = date('Y');
$days_in_month = date('t');
$first_day_of_month = date('w', strtotime(date('Y-m-01')));

// Handle form submission if POST data exists
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Process booking form submission
    $subject_id = $_POST['subject'];
    $selected_date = $_POST['selected_date'];
    $time_slot_id = $_POST['time_slot'];
    $duration = $_POST['duration'];
    $location = $_POST['location'];
    $notes = $_POST['notes'];
    
    // Here you would add code to:
    // 1. Insert into the Session table
    // 2. Update Availability status
    // 3. Create a Payment record
    // 4. Redirect to payment page or confirmation page
    
    // Redirect to make payment page
    header("Location: make_payment.php?session_id=123");
    exit();
}
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>预约学习伙伴 - 学生同伴辅导平台</title>
    <style>
        :root {
            --primary-color: #2B3990;
            --secondary-color: #00AEEF;
            --accent-color: #C4D600;
            --light-gray: #f5f5f5;
            --dark-gray: #333333;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: white;
            color: var(--dark-gray);
        }
        
        header {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem;
            text-align: center;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .appointment-section {
            display: flex;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .tutor-info {
            flex: 1;
            background-color: var(--light-gray);
            padding: 1.5rem;
            border-radius: 8px;
        }
        
        .booking-form {
            flex: 2;
            background-color: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .tutor-profile {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .tutor-image {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-right: 1rem;
        }
        
        .tutor-details h3 {
            margin: 0;
            color: var(--primary-color);
        }
        
        .tutor-details p {
            margin: 0.2rem 0;
            font-size: 0.9rem;
        }
        
        .rating {
            color: gold;
            margin-top: 0.5rem;
        }
        
        h2 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        .calendar {
            margin-bottom: 1.5rem;
        }
        
        .time-slots {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .time-slot {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .time-slot:hover {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .time-slot.selected {
            background-color: var(--primary-color);
            color: white;
        }
        
        .price-summary {
            background-color: var(--light-gray);
            padding: 1rem;
            border-radius: 4px;
            margin-top: 1.5rem;
        }
        
        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .total-price {
            font-weight: bold;
            color: var(--primary-color);
            font-size: 1.2rem;
        }
        
        .btn {
            background-color: var(--accent-color);
            color: var(--dark-gray);
            border: none;
            padding: 1rem 2rem;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 1rem;
            display: inline-block;
        }
        
        .btn:hover {
            background-color: #b5c500;
        }
        
        .btn-block {
            display: block;
            width: 100%;
        }
        
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .calendar-nav {
            display: flex;
            gap: 0.5rem;
        }
        
        .calendar-nav button {
            background-color: var(--secondary-color);
            color: white;
            border: none;
            padding: 0.5rem;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.3rem;
        }
        
        .calendar-day {
            padding: 0.5rem;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .calendar-day:hover {
            background-color: var(--light-gray);
        }
        
        .calendar-day.selected {
            background-color: var(--primary-color);
            color: white;
        }
        
        .calendar-day.disabled {
            color: #ccc;
            background-color: #f9f9f9;
            cursor: not-allowed;
        }
        
        .day-name {
            font-weight: bold;
            background-color: var(--light-gray);
            padding: 0.5rem;
            text-align: center;
        }
        
        .community-box {
            background-color: rgba(0, 174, 239, 0.1);
            padding: 1rem;
            border-radius: 4px;
            margin-top: 1.5rem;
            border-left: 4px solid var(--secondary-color);
        }
        
        .community-box h4 {
            color: var(--primary-color);
            margin-top: 0;
        }
        
        @media (max-width: 768px) {
            .appointment-section {
                flex-direction: column;
            }
            
            .time-slots {
                grid-template-columns: repeat(3, 1fr);
            }
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Calculate price based on duration
            const durationSelect = document.getElementById('duration');
            const hourlyRate = <?php echo $tutor['hourly_rate']; ?>;
            
            function updatePrice() {
                const duration = parseFloat(durationSelect.value);
                const tutorFee = hourlyRate * duration;
                const platformFee = Math.ceil(tutorFee * 0.05); // 5% platform fee
                const totalFee = tutorFee + platformFee;
                
                document.getElementById('tutor-fee').innerHTML = 'RM' + tutorFee.toFixed(2);
                document.getElementById('platform-fee').innerHTML = 'RM' + platformFee.toFixed(2);
                document.getElementById('total-fee').innerHTML = 'RM' + totalFee.toFixed(2);
            }
            
            // Initialize price calculation
            updatePrice();
            
            // Update price when duration changes
            durationSelect.addEventListener('change', updatePrice);
            
            // Time slot selection
            const timeSlots = document.querySelectorAll('.time-slot');
            timeSlots.forEach(slot => {
                slot.addEventListener('click', function() {
                    timeSlots.forEach(s => s.classList.remove('selected'));
                    this.classList.add('selected');
                    document.getElementById('selected_time_slot').value = this.dataset.id;
                });
            });
            
            // Calendar day selection
            const calendarDays = document.querySelectorAll('.calendar-day:not(.disabled)');
            calendarDays.forEach(day => {
                day.addEventListener('click', function() {
                    calendarDays.forEach(d => d.classList.remove('selected'));
                    this.classList.add('selected');
                    const selectedDay = this.textContent;
                    const month = <?php echo $current_month; ?>;
                    const year = <?php echo $current_year; ?>;
                    document.getElementById('selected_date').value = `${year}-${month.toString().padStart(2, '0')}-${selectedDay.toString().padStart(2, '0')}`;
                });
            });
        });
    </script>
</head>
<body>
    <header>
        <h1>学生同伴辅导平台</h1>
    </header>
    
    <div class="container">
        <h2>预约学习伙伴</h2>
        
        <div class="appointment-section">
            <div class="tutor-info">
                <div class="tutor-profile">
                    <img src="<?php echo htmlspecialchars($tutor['image_url']); ?>" alt="学习伙伴头像" class="tutor-image">
                    <div class="tutor-details">
                        <h3><?php echo htmlspecialchars($tutor['name']); ?></h3>
                        <p><?php echo htmlspecialchars($tutor['major']); ?> | <?php echo htmlspecialchars($tutor['year']); ?>学生</p>
                        <div class="rating">
                            <?php 
                            $rating = $tutor['rating'];
                            for($i = 1; $i <= 5; $i++) {
                                if($i <= floor($rating)) {
                                    echo "★";
                                } elseif($i - 0.5 <= $rating) {
                                    echo "★"; // Half star would be better but using full star for simplicity
                                } else {
                                    echo "☆";
                                }
                            }
                            echo " ({$rating})";
                            ?>
                        </div>
                    </div>
                </div>
                
                <div class="tutor-subjects">
                    <h4>擅长科目</h4>
                    <p>
                        <?php 
                        if(count($subjects) > 0) {
                            $subject_names = array_column($subjects, 'subject_name');
                            echo htmlspecialchars(implode(', ', $subject_names));
                        } else {
                            echo "暂无科目信息";
                        }
                        ?>
                    </p>
                </div>
                
                <div class="tutor-pricing">
                    <h4>辅导价格</h4>
                    <p>RM<?php echo htmlspecialchars($tutor['hourly_rate']); ?>/小时</p>
                </div>
                
                <div class="tutor-bio">
                    <h4>个人简介</h4>
                    <p><?php echo htmlspecialchars($tutor['bio']); ?></p>
                </div>
            </div>
            
            <div class="booking-form">
                <form id="appointmentForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?tutor_id=" . $tutor_id; ?>">
                    <input type="hidden" id="selected_date" name="selected_date" value="<?php echo date('Y-m-d'); ?>">
                    <input type="hidden" id="selected_time_slot" name="time_slot" value="">
                    
                    <div class="form-group">
                        <label for="subject">选择科目</label>
                        <select id="subject" name="subject" required>
                            <option value="">请选择科目</option>
                            <?php foreach($subjects as $subject): ?>
                                <option value="<?php echo htmlspecialchars($subject['subject_id']); ?>">
                                    <?php echo htmlspecialchars($subject['subject_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>选择日期</label>
                        <div class="calendar">
                            <div class="calendar-header">
                                <h4><?php echo date('Y年n月'); ?></h4>
                                <div class="calendar-nav">
                                    <button type="button" id="prev-month">&lt;</button>
                                    <button type="button" id="next-month">&gt;</button>
                                </div>
                            </div>
                            
                            <div class="calendar-grid">
                                <div class="day-name">日</div>
                                <div class="day-name">一</div>
                                <div class="day-name">二</div>
                                <div class="day-name">三</div>
                                <div class="day-name">四</div>
                                <div class="day-name">五</div>
                                <div class="day-name">六</div>
                                
                                <?php 
                                // Display empty cells for days before the first day of month
                                for($i = 0; $i < $first_day_of_month; $i++) {
                                    $prev_month_day = date('t', strtotime('-1 month')) - $first_day_of_month + $i + 1;
                                    echo "<div class='calendar-day disabled'>{$prev_month_day}</div>";
                                }
                                
                                // Display days of current month
                                $today = intval(date('j'));
                                for($day = 1; $day <= $days_in_month; $day++) {
                                    if($day == $today) {
                                        echo "<div class='calendar-day selected'>{$day}</div>";
                                    } else {
                                        echo "<div class='calendar-day'>{$day}</div>";
                                    }
                                }
                                
                                // Fill remaining cells with next month's days
                                $remaining_cells = 42 - ($first_day_of_month + $days_in_month); // 6 rows of 7 days
                                for($i = 1; $i <= $remaining_cells; $i++) {
                                    echo "<div class='calendar-day disabled'>{$i}</div>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>选择时间段</label>
                        <div class="time-slots">
                            <?php foreach($time_slots as $slot): ?>
                                <div class="time-slot" data-id="<?php echo $slot['id']; ?>">
                                    <?php 
                                    echo date('H:i', strtotime($slot['start_time'])) . '-' . 
                                         date('H:i', strtotime($slot['end_time'])); 
                                    ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="duration">辅导时长</label>
                        <select id="duration" name="duration" required>
                            <option value="1">1小时</option>
                            <option value="1.5">1.5小时</option>
                            <option value="2" selected>2小时</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="location">学习地点</label>
                        <select id="location" name="location" required>
                            <option value="">请选择地点</option>
                            <?php foreach($locations as $location): ?>
                                <option value="<?php echo htmlspecialchars($location['location_value']); ?>">
                                    <?php echo htmlspecialchars($location['location_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">学习目标和问题描述</label>
                        <textarea id="notes" name="notes" rows="4" placeholder="请描述你的学习目标和具体需要帮助的内容..."></textarea>
                    </div>
                    
                    <div class="price-summary">
                        <h4>费用汇总</h4>
                        <div class="price-row">
                            <span>辅导费用 (RM<?php echo $tutor['hourly_rate']; ?> × 2小时)</span>
                            <span id="tutor-fee">RM<?php echo $tutor['hourly_rate'] * 2; ?></span>
                        </div>
                        <div class="price-row">
                            <span>平台服务费</span>
                            <span id="platform-fee">RM<?php echo ceil($tutor['hourly_rate'] * 2 * 0.05); ?></span>
                        </div>
                        <hr>
                        <div class="price-row total-price">
                            <span>总计</span>
                            <span id="total-fee">RM<?php echo $tutor['hourly_rate'] * 2 + ceil($tutor['hourly_rate'] * 2 * 0.05); ?></span>
                        </div>
                    </div>
                    
                    <div class="community-box">
                        <h4>加入学习社区</h4>
                        <p>预约完成后，你可以加入该学科的学习讨论群，与其他同学分享学习资料和讨论问题！</p>
                    </div>
                    
                    <div class="form-group" style="margin-top: 2rem;">
                        <button type="submit" class="btn btn-block">确认预约并前往支付</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
<?php
// Close database connection
$conn->close();
?>