<?php
session_start();
// Database connection
require_once 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get tutor ID from URL parameter
$tutor_id = isset($_GET['tutor_id']) ? intval($_GET['tutor_id']) : 0;

if ($tutor_id == 0) {
    // Redirect if no valid tutor ID
    header("Location: find_tutors.php");
    exit();
}

// Fetch tutor information
$tutor_query = "SELECT u.user_id, u.first_name, u.last_name, u.email, u.profile_image,
                tp.rating, tp.bio, tp.qualifications, tp.is_verified
                FROM user u
                JOIN tutorprofile tp ON u.user_id = tp.user_id
                WHERE u.user_id = ? AND u.role = 'tutor'";
$stmt = $conn->prepare($tutor_query);
$stmt->bind_param("i", $tutor_id);
$stmt->execute();
$tutor_result = $stmt->get_result();

if ($tutor_result->num_rows == 0) {
    // Tutor not found
    header("Location: find_tutors.php");
    exit();
}

$tutor = $tutor_result->fetch_assoc();

// Fetch tutor subjects
$subjects_query = "SELECT s.subject_id, s.subject_name, ts.hourly_rate
                  FROM tutorsubject ts
                  JOIN subject s ON ts.subject_id = s.subject_id
                  WHERE ts.tutor_id = ?";
$stmt = $conn->prepare($subjects_query);
$stmt->bind_param("i", $tutor_id);
$stmt->execute();
$subjects_result = $stmt->get_result();
$subjects = [];
while ($row = $subjects_result->fetch_assoc()) {
    $subjects[] = $row;
}

// Fetch available time slots (you'll need to create this table)
$time_slots_query = "SELECT * FROM availability 
                    WHERE tutor_id = ? AND status = 0;
                    -- ORDER BY day_of_week, start_time";
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
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Session with <?php echo htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']); ?></title>
    <link rel="stylesheet" href="css/booking_2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- <link rel="stylesheet" href="css/style.css"> -->
    <link rel="stylesheet" href="css/booking.css">
</head>

<body>
    <?php include 'header.php'; ?>

    <div class="container mt-4">
        <h2>Book a Session with <?php echo htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']); ?></h2>

        <div class="appointment-section">
            <div class="tutor-info">
                <div class="tutor-profile">
                    <?php if (!empty($tutor['profile_image']) && file_exists($tutor['profile_image'])): ?>
                        <img src="<?php echo htmlspecialchars($tutor['profile_image']); ?>" alt="Tutor Profile" class="tutor-image">
                    <?php else: ?>
                        <img src="uploads/profile_images/default.jpg" alt="Default Profile" class="tutor-image">
                    <?php endif; ?>

                    <div class="tutor-details">
                        <h3><?php echo htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']); ?></h3>
                        <p><?php echo htmlspecialchars($tutor['qualifications']); ?></p>
                        <div class="rating">
                            <?php
                            $rating = $tutor['rating'];
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= floor($rating)) {
                                    echo '<i class="fas fa-star"></i>';
                                } elseif ($i - 0.5 <= $rating) {
                                    echo '<i class="fas fa-star-half-alt"></i>';
                                } else {
                                    echo '<i class="far fa-star"></i>';
                                }
                            }
                            echo " (" . number_format($rating, 1) . ")";
                            ?>
                        </div>
                    </div>
                </div>

                <div class="tutor-subjects">
                    <h4>Subjects</h4>
                    <p>
                        <?php
                        if (count($subjects) > 0) {
                            $subject_list = [];
                            foreach ($subjects as $subject) {
                                $subject_list[] = $subject['subject_name'] . ' ($' . number_format($subject['hourly_rate'], 2) . '/hr)';
                            }
                            echo htmlspecialchars(implode(', ', $subject_list));
                        } else {
                            echo "No subjects available";
                        }
                        ?>
                    </p>
                </div>

                <div class="tutor-pricing">
                    <h4>Hourly Rate</h4>
                    <p>$<?php echo number_format($tutor['hourly_rate'], 2); ?>/hour</p>
                </div>

                <div class="tutor-bio">
                    <h4>About Me</h4>
                    <p><?php echo htmlspecialchars($tutor['bio']); ?></p>
                </div>
            </div>

            <div class="booking-form">
                <form id="appointmentForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?tutor_id=" . $tutor_id; ?>">
                    <input type="hidden" id="selected_date" name="selected_date" value="<?php echo date('Y-m-d'); ?>">
                    <input type="hidden" id="selected_time_slot" name="time_slot" value="">

                    <div class="form-group">
                        <label for="subject">Select Subject</label>
                        <select id="subject" name="subject" required class="form-control">
                            <option value="">Please select a subject</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo htmlspecialchars($subject['subject_id']); ?>">
                                    <?php echo htmlspecialchars($subject['subject_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Rest of your form elements -->

                    <div class="price-summary">
                        <h4>Price Summary</h4>
                        <div class="price-row">
                            <span>Tutoring Fee ($<?php echo number_format($tutor['hourly_rate'], 2); ?> × <span id="hours-display">2</span> hours)</span>
                            <span id="tutor-fee">$<?php echo number_format($tutor['hourly_rate'] * 2, 2); ?></span>
                        </div>
                        <div class="price-row">
                            <span>Platform Fee (5%)</span>
                            <span id="platform-fee">$<?php echo number_format(ceil($tutor['hourly_rate'] * 2 * 0.05 * 100) / 100, 2); ?></span>
                        </div>
                        <hr>
                        <div class="price-row total-price">
                            <span>Total</span>
                            <span id="total-fee">$<?php echo number_format($tutor['hourly_rate'] * 2 + ceil($tutor['hourly_rate'] * 2 * 0.05 * 100) / 100, 2); ?></span>
                        </div>
                    </div>

                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary btn-block">Confirm Booking and Proceed to Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Your JavaScript for handling the booking form
        document.addEventListener('DOMContentLoaded', function() {
            // Calculate price based on duration
            const durationSelect = document.getElementById('duration');
            const hourlyRate = <?php echo $tutor['hourly_rate']; ?>;

            function updatePrice() {
                const duration = parseFloat(durationSelect.value);
                document.getElementById('hours-display').textContent = duration;
                const tutorFee = hourlyRate * duration;
                const platformFee = Math.ceil(tutorFee * 0.05 * 100) / 100; // 5% platform fee
                const totalFee = tutorFee + platformFee;

                document.getElementById('tutor-fee').innerHTML = '$' + tutorFee.toFixed(2);
                document.getElementById('platform-fee').innerHTML = '$' + platformFee.toFixed(2);
                document.getElementById('total-fee').innerHTML = '$' + totalFee.toFixed(2);
            }

            // Initialize price calculation
            if (durationSelect) {
                updatePrice();

                // Update price when duration changes
                durationSelect.addEventListener('change', updatePrice);
            }

            // Add the rest of your JavaScript for time slot selection and calendar
        });
    </script>
</body>

</html>
<?php
// Close database connection
$conn->close();
?>