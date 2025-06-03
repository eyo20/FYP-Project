<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "peer_tutoring_platform";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$tutor_id = isset($_GET['tutor_id']) ? intval($_GET['tutor_id']) : 16;

// Fetch tutor information
$tutor_query = "SELECT u.*, tp.* FROM user u 
                JOIN tutorprofile tp ON u.user_id = tp.user_id 
                WHERE u.user_id = ? AND u.role = 'tutor'";
$stmt = $conn->prepare($tutor_query);
$stmt->bind_param("i", $tutor_id);
$stmt->execute();
$tutor_result = $stmt->get_result();
$tutor = $tutor_result->fetch_assoc();

// Fetch courses
$courses_query = "SELECT c.*, ts.hourly_rate FROM course c 
                 JOIN tutorsubject ts ON c.id = ts.course_id 
                 WHERE ts.tutor_id = ?";
$stmt = $conn->prepare($courses_query);
$stmt->bind_param("i", $tutor_id);
$stmt->execute();
$courses_result = $stmt->get_result();
$courses = [];
while ($row = $courses_result->fetch_assoc()) {
    $courses[] = $row;
}

// Fetch session count per date
$session_query = "SELECT selected_date, COUNT(*) as session_count 
                 FROM session_requests 
                 WHERE tutor_id = ? AND status != 'cancelled'
                 GROUP BY selected_date";
$stmt = $conn->prepare($session_query);
$stmt->bind_param("i", $tutor_id);
$stmt->execute();
$session_result = $stmt->get_result();
$session_counts = [];
while ($row = $session_result->fetch_assoc()) {
    $session_counts[$row['selected_date']] = $row['session_count'];
}

// Fetch reviews
$reviews_query = "SELECT r.*, u.first_name, u.last_name 
                 FROM review r 
                 JOIN user u ON r.student_id = u.user_id 
                 WHERE r.tutor_id = ? AND r.is_approved = 1";
$stmt = $conn->prepare($reviews_query);
$stmt->bind_param("i", $tutor_id);
$stmt->execute();
$reviews_result = $stmt->get_result();
$reviews = [];
while ($row = $reviews_result->fetch_assoc()) {
    $reviews[] = $row;
}

// Fetch locations
$locations_query = "SELECT * FROM location";
$locations_result = $conn->query($locations_query);
$locations = [];
while ($row = $locations_result->fetch_assoc()) {
    $locations[] = $row;
}

$current_month = date('n');
$current_year = date('Y');
$today = date('Y-m-d');
$page_title = "Book a Study Partner - PeerLearn";
?>

<?php include 'header/stud_head.php'; ?>

<div class="main">
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>
    <h1>Book a Study Partner</h1>
    <div class="appointment-section" style="display: flex; gap: 2rem; margin-bottom: 2rem;">
        <div class="tutor-info" style="flex: 1; background: #f5f5f5; padding: 1.5rem; border-radius: 8px;">
            <div class="tutor-profile" style="display: flex; align-items: center; margin-bottom: 1rem;">
                <div class="tutor-image" style="width: 80px; height: 80px; border-radius: 50%; margin-right: 1rem; background: #ddd;">
                    <?php if (!empty($tutor['profile_image'])): ?>
                        <img src="<?php echo htmlspecialchars($tutor['profile_image']); ?>" alt="Tutor Avatar" style="width: 100%; height: 100%; border-radius: 50%;">
                    <?php endif; ?>
                </div>
                <div class="tutor-details">
                    <h3 style="margin: 0; color: #2B3990;"><?php echo htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']); ?></h3>
                    <p style="margin: 0.2rem 0; font-size: 0.9rem;"><?php echo htmlspecialchars($tutor['major']); ?> | <?php echo htmlspecialchars($tutor['year']); ?> Student</p>
                    <p style="margin: 0.2rem 0; font-size: 0.9rem; color: <?php echo $tutor['is_verified'] ? 'green' : 'orange'; ?>;">
                        <?php echo $tutor['is_verified'] ? 'Verified' : 'Pending Verification'; ?>
                    </p>
                    <div class="rating" style="color: gold; margin-top: 0.5rem;">
                        <?php
                        $rating = $tutor['rating'] ?? 0;
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= floor($rating)) {
                                echo '<i class="fas fa-star"></i>';
                            } elseif ($i - 0.5 <= $rating) {
                                echo '<i class="fas fa-star-half-alt"></i>';
                            } else {
                                echo '<i class="far fa-star"></i>';
                            }
                        }
                        echo " ({$rating})";
                        ?>
                    </div>
                </div>
            </div>
            <div class="tutor-subjects">
                <h4 style="color: #2B3990; font-weight: bold; margin: 1.5rem 0 0.5rem;">Course Options</h4>
                <ul style="list-style: none; padding: 0;">
                    <?php foreach ($courses as $course): ?>
                        <li style="margin-bottom: 0.5rem;">
                            <?php echo htmlspecialchars($course['course_name']); ?> - RM <?php echo number_format($course['hourly_rate'], 2); ?>/hr
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="tutor-bio">
                <h4 style="color: #2B3990; font-weight: bold; margin: 1.5rem 0 0.5rem;">Personal Profile</h4>
                <p><?php echo htmlspecialchars($tutor['bio'] ?? 'No bio provided'); ?></p>
            </div>
            <div class="tutor-qualifications">
                <h4 style="color: #2B3990; font-weight: bold; margin: 1.5rem 0 0.5rem;">Qualifications</h4>
                <p><?php echo htmlspecialchars($tutor['qualifications'] ?? 'No qualifications provided'); ?></p>
            </div>
            <div class="tutor-reviews">
                <h4 style="color: #2B3990; font-weight: bold; margin: 1.5rem 0 0.5rem;">Reviews</h4>
                <?php if (count($reviews) > 0): ?>
                    <?php foreach ($reviews as $review): ?>
                        <div style="margin-bottom: 1rem;">
                            <p><strong><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?>:</strong>
                                <?php echo htmlspecialchars($review['comment']); ?> (Rating: <?php echo $review['rating']; ?>)</p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No reviews yet</p>
                <?php endif; ?>
            </div>
            <div class="tutor-contact">
                <h4 style="color: #2B3990; font-weight: bold; margin: 1.5rem 0 0.5rem;">Contact</h4>
                <p>Email: <?php echo htmlspecialchars($tutor['email']); ?></p>
                <p>Phone: <?php echo htmlspecialchars($tutor['phone'] ?? 'Not provided'); ?></p>
            </div>
        </div>
        <div class="booking-form" style="flex: 2; background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);">
            <form id="appointmentForm" method="POST" action="confirm_booking.php">
                <input type="hidden" name="tutor_id" value="<?php echo $tutor_id; ?>">
                <input type="hidden" id="selected_date" name="selected_date" value="<?php echo date('Y-m-d'); ?>">
                <input type="hidden" name="student_id" value="<?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : ''; ?>">

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="course" style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Select Course</label>
                    <select id="course" name="course_id" required style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">Select a Course</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo htmlspecialchars($course['id']); ?>" data-rate="<?php echo $course['hourly_rate']; ?>">
                                <?php echo htmlspecialchars($course['course_name']); ?> (RM <?php echo number_format($course['hourly_rate'], 2); ?>/hr)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Select Date</label>
                    <div class="calendar" style="margin-bottom: 1.5rem;">
                        <div class="calendar-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <h4 style="color: #2B3990;"><?php echo date('F Y'); ?></h4>
                            <div class="calendar-nav" style="display: flex; gap: 0.5rem;">
                                <button type="button" id="prev-month" style="background: #00AEEF; color: white; border: none; padding: 0.5rem; border-radius: 4px; cursor: pointer;">←</button>
                                <button type="button" id="next-month" style="background: #00AEEF; color: white; border: none; padding: 0.5rem; border-radius: 4px; cursor: pointer;">→</button>
                            </div>
                        </div>
                        <div class="calendar-grid" style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 0.3rem;"></div>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="duration" style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Session Duration</label>
                    <select id="duration" name="duration" required style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="1">1 hour</option>
                        <option value="1.5">1.5 hours</option>
                        <option value="2" selected>2 hours</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="location" style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Study Venue</label>
                    <select id="location" name="location_id" required style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">Select a Venue</option>
                        <?php foreach ($locations as $location): ?>
                            <option value="<?php echo htmlspecialchars($location['location_id']); ?>">
                                <?php echo htmlspecialchars($location['location_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="notes" style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Learning Objectives</label>
                    <textarea id="notes" name="notes" rows="4" placeholder="Describe your learning goals and specific needs..." style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 4px;"></textarea>
                </div>

                <div class="price-summary" style="background: #f5f5f5; padding: 1rem; border-radius: 4px; margin-top: 1.5rem;">
                    <h4 style="color: #2B3990; margin-bottom: 0.5rem;">Fees Summary</h4>
                    <div class="price-row" style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span>Tutor Fees</span>
                        <span id="tutor-fee">RM0.00</span>
                    </div>
                    <div class="price-row" style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span>Platform Charge</span>
                        <span id="platform-fee">RM0.00</span>
                    </div>
                    <hr>
                    <div class="price-row" style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-weight: bold; color: #2B3990; font-size: 1.2rem;">
                        <span>Total</span>
                        <span id="total-fee">RM0.00</span>
                    </div>
                </div>

                <div class="community-box" style="background: rgba(0, 174, 239, 0.1); padding: 1rem; border-radius: 4px; margin-top: 1.5rem; border-left: 4px solid #00AEEF;">
                    <h4 style="color: #2B3990; margin: 0;">Join the Learning Community</h4>
                    <p>After your session, join the subject’s study group to share materials and discuss with peers!</p>
                </div>

                <div class="form-group" style="margin-top: 2rem;">
                    <button type="submit" class="btn btn-success btn-block" style="width: 100%; background: #27ae60; color: white; padding: 1rem; border-radius: 4px; border: none; cursor: pointer;">Confirm Your Reservation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const courseSelect = document.getElementById('course');
        const hourlyRates = <?php echo json_encode(array_column($courses, 'hourly_rate', 'id')); ?>;
        const durationSelect = document.getElementById('duration');

        function updatePrice() {
            const courseId = courseSelect.value;
            const duration = parseFloat(durationSelect.value) || 0;
            const hourlyRate = courseId && hourlyRates[courseId] ? parseFloat(hourlyRates[courseId]) : 0;
            const tutorFee = hourlyRate * duration;
            const platformFee = Math.ceil(tutorFee * 0.05);
            const totalFee = tutorFee;

            document.getElementById('tutor-fee').innerHTML = `RM${tutorFee.toFixed(2)}`;
            document.getElementById('platform-fee').innerHTML = `RM${platformFee.toFixed(2)}`;
            document.getElementById('total-fee').innerHTML = `RM${totalFee.toFixed(2)}`;
        }

        // Initialize price
        updatePrice();
        courseSelect.addEventListener('change', updatePrice);
        durationSelect.addEventListener('change', updatePrice);

        const sessionCounts = <?php echo json_encode($session_counts); ?>;
        let currentMonth = <?php echo $current_month; ?>;
        let currentYear = <?php echo $current_year; ?>;
        const today = new Date('<?php echo $today; ?>');
        const calendarGrid = document.querySelector('.calendar-grid');
        const calendarHeader = document.querySelector('.calendar-header h4');

        function renderCalendar(month, year) {
            calendarGrid.innerHTML = `
            <div style="font-weight: bold; background: #f5f5f5; padding: 0.5rem; text-align: center;">Sun</div>
            <div style="font-weight: bold; background: #f5f5f5; padding: 0.5rem; text-align: center;">Mon</div>
            <div style="font-weight: bold; background: #f5f5f5; padding: 0.5rem; text-align: center;">Tue</div>
            <div style="font-weight: bold; background: #f5f5f5; padding: 0.5rem; text-align: center;">Wed</div>
            <div style="font-weight: bold; background: #f5f5f5; padding: 0.5rem; text-align: center;">Thu</div>
            <div style="font-weight: bold; background: #f5f5f5; padding: 0.5rem; text-align: center;">Fri</div>
            <div style="font-weight: bold; background: #f5f5f5; padding: 0.5rem; text-align: center;">Sat</div>
        `;
            const firstDay = new Date(year, month - 1, 1).getDay();
            const daysInMonth = new Date(year, month, 0).getDate();

            // Previous month days
            const prevMonthDays = new Date(year, month - 1, 0).getDate();
            for (let i = firstDay - 1; i >= 0; i--) {
                calendarGrid.innerHTML += `<div style="padding: 0.5rem; text-align: center; border: 1px solid #ddd; border-radius: 4px; color: #ccc; background: #f9f9f9; cursor: not-allowed;">${prevMonthDays - i}</div>`;
            }

            // Current month days
            for (let day = 1; day <= daysInMonth; day++) {
                const dateStr = `${year}-${month.toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;
                const date = new Date(year, month - 1, day);
                const isPast = date < today.setHours(0, 0, 0, 0);
                const sessionCount = sessionCounts[dateStr] || 0;
                const isUnavailable = sessionCount >= 3;
                const isSelected = dateStr === document.getElementById('selected_date').value;
                const className = isPast || isUnavailable ? 'disabled' : (isSelected ? 'selected' : '');
                const style = `
                padding: 0.5rem; 
                text-align: center; 
                border: 1px solid #ddd; 
                border-radius: 4px; 
                cursor: ${isPast || isUnavailable ? 'not-allowed' : 'pointer'};
                color: ${isPast || isUnavailable ? '#ccc' : '#333'};
                background: ${isPast || isUnavailable ? '#f9f9f9' : isSelected ? '#2B3990' : 'white'};
                color: ${isSelected ? 'white' : ''};
            `;
                calendarGrid.innerHTML += `<div style="${style}" class="${className}" data-date="${dateStr}">${day}</div>`;
            }

            // Next month days
            const remainingCells = 42 - (firstDay + daysInMonth);
            for (let i = 1; i <= remainingCells; i++) {
                calendarGrid.innerHTML += `<div style="padding: 0.5rem; text-align: center; border: 1px solid #ddd; border-radius: 4px; color: #ccc; background: #f9f9f9; cursor: not-allowed;">${i}</div>`;
            }

            calendarHeader.textContent = new Date(year, month - 1).toLocaleString('en-US', {
                month: 'long',
                year: 'numeric'
            });

            // Bind click events
            const calendarDays = document.querySelectorAll('.calendar-grid div:not(.disabled)');
            calendarDays.forEach(day => {
                day.addEventListener('click', function() {
                    document.querySelectorAll('.calendar-grid div').forEach(d => {
                        d.classList.remove('selected');
                        d.style.background = d.classList.contains('disabled') ? '#f9f9f9' : 'white';
                        d.style.color = d.classList.contains('disabled') ? '#ccc' : '#333';
                    });
                    this.classList.add('selected');
                    this.style.background = '#2B3990';
                    this.style.color = 'white';
                    document.getElementById('selected_date').value = this.dataset.date;
                });
            });
        }

        renderCalendar(currentMonth, currentYear);

        document.getElementById('prev-month').addEventListener('click', () => {
            currentMonth--;
            if (currentMonth < 1) {
                currentMonth = 12;
                currentYear--;
            }
            renderCalendar(currentMonth, currentYear);
        });

        document.getElementById('next-month').addEventListener('click', () => {
            currentMonth++;
            if (currentMonth > 12) {
                currentMonth = 1;
                currentYear++;
            }
            renderCalendar(currentMonth, currentYear);
        });

        function toggleDropdown() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }
    });
</script>

<?php
$conn->close();
?>