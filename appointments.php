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
$tutor_query = "SELECT u.*, tp.* FROM user u 
                JOIN tutorprofile tp ON u.user_id = tp.user_id 
                WHERE u.user_id = ? AND u.role = 'tutor'";
$stmt = $conn->prepare($tutor_query);
$stmt->bind_param("i", $tutor_id);
$stmt->execute();
$tutor_result = $stmt->get_result();
$tutor = $tutor_result->fetch_assoc();

// Fetch courses that the tutor teaches with their hourly rates
$courses_query = "SELECT c.*, ts.hourly_rate FROM courses c 
                 JOIN tutorsubject ts ON c.course_id = ts.course_id 
                 WHERE ts.tutor_id = ?";
$stmt = $conn->prepare($courses_query);
$stmt->bind_param("i", $tutor_id);
$stmt->execute();
$courses_result = $stmt->get_result();
$courses = [];
while ($row = $courses_result->fetch_assoc()) {
    $courses[] = $row;
}

// Fetch available time slots
$time_slots_query = "SELECT * FROM availability WHERE tutor_id = ? AND status = 'open'";
$stmt = $conn->prepare($time_slots_query);
$stmt->bind_param("i", $tutor_id);
$stmt->execute();
$time_slots_result = $stmt->get_result();
$time_slots = [];
while ($row = $time_slots_result->fetch_assoc()) {
    $time_slots[] = $row;
}

// Fetch locations
$locations_query = "SELECT * FROM location";
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
$today = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="zh">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a study partner - a peer tutoring platform</title>
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
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
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
            background-color: #ddd;
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

        h4 {
            color: var(--primary-color);
            font-weight: bold;
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }

        input,
        select,
        textarea {
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
            // Course selection
            const courseSelect = document.getElementById('course');

            // Store hourly rates for each course
            const hourlyRates = <?php echo json_encode(array_column($courses, 'hourly_rate', 'course_id')); ?>;

            // Calculate price based on duration and course
            const durationSelect = document.getElementById('duration');

            function updatePrice() {
                const courseId = courseSelect.value;
                const duration = parseFloat(durationSelect.value);
                const hourlyRate = courseId ? hourlyRates[courseId] || 0 : 0;
                const tutorFee = hourlyRate * duration;
                const platformFee = Math.ceil(tutorFee * 0.05); // 5% platform fee
                const totalFee = tutorFee + platformFee;

                document.getElementById('tutor-fee').innerHTML = 'RM' + tutorFee.toFixed(2);
                document.getElementById('platform-fee').innerHTML = 'RM' + platformFee.toFixed(2);
                document.getElementById('total-fee').innerHTML = 'RM' + totalFee.toFixed(2);
                document.getElementById('tutor-price').innerHTML = 'RM' + (hourlyRate ? hourlyRate.toFixed(2) : '0.00') + '/hour';
            }

            // Initialize price calculation
            updatePrice();

            // Update price when duration or course changes
            durationSelect.addEventListener('change', updatePrice);
            courseSelect.addEventListener('change', updatePrice);

            // Time slot selection
            const timeSlots = document.querySelectorAll('.time-slot');
            timeSlots.forEach(slot => {
                slot.addEventListener('click', function() {
                    timeSlots.forEach(s => s.classList.remove('selected'));
                    this.classList.add('selected');
                    document.getElementById('availability_id').value = this.dataset.id;
                });
            });

            // Calendar day selection
            const today = new Date('<?php echo $today; ?>');
            const calendarDays = document.querySelectorAll('.calendar-day:not(.disabled)');
            calendarDays.forEach(day => {
                day.addEventListener('click', function() {
                    const selectedDay = parseInt(this.textContent);
                    const month = <?php echo $current_month; ?>;
                    const year = <?php echo $current_year; ?>;
                    const selectedDate = new Date(year, month - 1, selectedDay);

                    // Prevent selection of dates before today
                    if (selectedDate < today.setHours(0, 0, 0, 0)) {
                        return;
                    }

                    calendarDays.forEach(d => d.classList.remove('selected'));
                    this.classList.add('selected');
                    document.getElementById('selected_date').value = `${year}-${month.toString().padStart(2, '0')}-${selectedDay.toString().padStart(2, '0')}`;
                });
            });
        });
    </script>
</head>

<body>
    <header>
        <h1>Peer Tutoring Platform</h1>
    </header>

    <div class="container">
        <h2>Book a study partner</h2>

        <div class="appointment-section">
            <div class="tutor-info">
                <div class="tutor-profile">
                    <div class="tutor-image">
                        <?php if (!empty($tutor['profile_image'])): ?>
                            <img src="<?php echo htmlspecialchars($tutor['profile_image']); ?>" alt="study partner avatar" class="tutor-image">
                        <?php endif; ?>
                    </div>
                    <div class="tutor-details">
                        <h3><?php echo htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']); ?></h3>
                        <p><?php echo htmlspecialchars($tutor['major']); ?> | <?php echo htmlspecialchars($tutor['year']); ?> Student</p>
                        <div class="rating">
                            <?php
                            $rating = $tutor['rating'];
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= floor($rating)) {
                                    echo "★";
                                } elseif ($i - 0.5 <= $rating) {
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
                    <h4>Course Options</h4>
                    <p>
                        <?php
                        if (count($courses) > 0) {
                            $course_names = array_column($courses, 'course_name');
                            echo htmlspecialchars(implode(', ', $course_names));
                        } else {
                            echo "No course information";
                        }
                        ?>
                    </p>
                </div>

                <div class="tutor-pricing">
                    <h4>Tutoring Price</h4>
                    <p id="tutor-price">Please select a course</p>
                </div>

                <div class="tutor-bio">
                    <h4>Personal Profile</h4>
                    <p><?php echo htmlspecialchars($tutor['bio']); ?></p>
                </div>
            </div>

            <div class="booking-form">
                <form id="appointmentForm" method="POST" action="confirm_booking.php">
                    <input type="hidden" name="tutor_id" value="<?php echo $tutor_id; ?>">
                    <input type="hidden" id="selected_date" name="selected_date" value="<?php echo date('Y-m-d'); ?>">
                    <input type="hidden" id="availability_id" name="availability_id" value="">

                    <div class="form-group">
                        <label for="course">Select Course</label>
                        <select id="course" name="course" required>
                            <option value="">Please Select A Course</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo htmlspecialchars($course['course_id']); ?>">
                                    <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Select Date</label>
                        <div class="calendar">
                            <div class="calendar-header">
                                <h4><?php echo date('Y M'); ?></h4>
                                <div class="calendar-nav">
                                    <button type="button" id="prev-month">
                                        << /button>
                                            <button type="button" id="next-month">></button>
                                </div>
                            </div>

                            <div class="calendar-grid">
                                <div class="day-name">S</div>
                                <div class="day-name">M</div>
                                <div class="day-name">T</div>
                                <div class="day-name">W</div>
                                <div class="day-name">T</div>
                                <div class="day-name">F</div>
                                <div class="day-name">S</div>

                                <?php
                                // Display empty cells for days before the first day of month
                                for ($i = 0; $i < $first_day_of_month; $i++) {
                                    $prev_month_day = date('t', strtotime('-1 month')) - $first_day_of_month + $i + 1;
                                    echo "<div class='calendar-day disabled'>{$prev_month_day}</div>";
                                }

                                // Display days of current month
                                $today_day = intval(date('j'));
                                for ($day = 1; $day <= $days_in_month; $day++) {
                                    $current_date = date('Y-m-d', strtotime("{$current_year}-{$current_month}-{$day}"));
                                    if ($current_date < $today) {
                                        echo "<div class='calendar-day disabled'>{$day}</div>";
                                    } elseif ($day == $today_day) {
                                        echo "<div class='calendar-day selected'>{$day}</div>";
                                    } else {
                                        echo "<div class='calendar-day'>{$day}</div>";
                                    }
                                }

                                // Fill remaining cells with next month's days
                                $remaining_cells = 42 - ($first_day_of_month + $days_in_month); // 6 rows of 7 days
                                for ($i = 1; $i <= $remaining_cells; $i++) {
                                    echo "<div class='calendar-day disabled'>{$i}</div>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Select Time-Slot</label>
                        <div class="time-slots">
                            <?php foreach ($time_slots as $slot): ?>
                                <div class="time-slot" data-id="<?php echo $slot['availability_id']; ?>">
                                    <?php
                                    echo date('H:i', strtotime($slot['start_datetime'])) . '-' .
                                        date('H:i', strtotime($slot['end_datetime']));
                                    ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="duration">Tutor duration</label>
                        <select id="duration" name="duration" required>
                            <option value="1">1 hour</option>
                            <option value="1.5">1.5 hours</option>
                            <option value="2" selected>2 hours</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="location">Study Venue</label>
                        <select id="location" name="location" required>
                            <option value="">Please select a study venue</option>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?php echo htmlspecialchars($location['location_id']); ?>">
                                    <?php echo htmlspecialchars($location['location_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="notes">Learning objectives and problem statement</label>
                        <textarea id="notes" name="notes" rows="4" placeholder="Please describe your learning goals and what specific help you need..."></textarea>
                    </div>

                    <div class="price-summary">
                        <h4>Fees Summary</h4>
                        <div class="price-row">
                            <span>Tutor Fees</span>
                            <span id="tutor-fee">RM0.00</span>
                        </div>
                        <div class="price-row">
                            <span>Platform Charge</span>
                            <span id="platform-fee">RM0.00</span>
                        </div>
                        <hr>
                        <div class="price-row total-price">
                            <span>Total</span>
                            <span id="total-fee">RM0.00</span>
                        </div>
                    </div>

                    <div class="community-box">
                        <h4>Join the Learning Community</h4>
                        <p>After the appointment is completed, you can join the study discussion group of the subject to share learning materials and discuss issues with other students!</p>
                    </div>

                    <div class="form-group" style="margin-top: 2rem;">
                        <button type="submit" class="btn btn-block">Confirm your reservation and proceed to payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Additional JavaScript code can be added here if needed
    </script>
</body>

</html>

<?php
// Close database connection
$conn->close();
?>