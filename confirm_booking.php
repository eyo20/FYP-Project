<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit();
}

$page_title = "Booking Confirmation - PeerLearn";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tutor_id = intval($_POST['tutor_id']);
    $student_id = intval($_POST['student_id']);
    $course_id = intval($_POST['course_id']);
    $location_id = intval($_POST['location_id']);
    $time_slot = trim($_POST['time_slot']);
    $selected_date = $_POST['selected_date'];
    $notes = trim($_POST['notes']);

    // Validate inputs
    if (!$student_id || !$tutor_id || !$course_id || !$location_id || !$time_slot || !$selected_date) {
        header("Location: appointments.php?tutor_id=$tutor_id&error=All fields are required");
        exit;
    }

    // Validate time slot
    $valid_time_slots = ['08:00-10:00', '10:00-12:00', '12:00-14:00'];
    if (!in_array($time_slot, $valid_time_slots)) {
        header("Location: appointments.php?tutor_id=$tutor_id&error=Invalid time slot");
        exit;
    }

    // Check session limit
    $session_query = "SELECT COUNT(*) as session_count 
                     FROM session_requests 
                     WHERE tutor_id = ? AND selected_date = ? AND time_slot = ? AND status != 'cancelled'";
    $stmt = $conn->prepare($session_query);
    $stmt->bind_param("iss", $tutor_id, $selected_date, $time_slot);
    $stmt->execute();
    $session_result = $stmt->get_result();
    $session_count = $session_result->fetch_assoc()['session_count'];

    if ($session_count >= 3) {
        header("Location: appointments.php?tutor_id=$tutor_id&error=Maximum 3 sessions per time slot reached");
        exit;
    }

    // Fetch course name
    $course_query = "SELECT course_name FROM course WHERE id = ?";
    $stmt = $conn->prepare($course_query);
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $course_result = $stmt->get_result();
    $course = $course_result->fetch_assoc();

    // Fetch location name
    $location_query = "SELECT location_name FROM location WHERE location_id = ?";
    $stmt = $conn->prepare($location_query);
    $stmt->bind_param("i", $location_id);
    $stmt->execute();
    $location_result = $stmt->get_result();
    $location = $location_result->fetch_assoc();

    // Fetch hourly rate
    $rate_query = "SELECT hourly_rate FROM tutorsubject WHERE tutor_id = ? AND course_id = ?";
    $stmt = $conn->prepare($rate_query);
    $stmt->bind_param("ii", $tutor_id, $course_id);
    $stmt->execute();
    $rate_result = $stmt->get_result();
    $rate = $rate_result->fetch_assoc();
    $hourly_rate = $rate['hourly_rate'] ?? 0;

    // Insert into session_requests
    $insert_query = "INSERT INTO session_requests (tutor_id, student_id, course_id, location_id, time_slot, selected_date, notes, status, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("iiiisss", $tutor_id, $student_id, $course_id, $location_id, $time_slot, $selected_date, $notes);

    if ($stmt->execute()) {
        // Render confirmation page
?>
        <?php include 'header/stud_head.php'; ?>
        <div class="main">
            <div class="container" style="max-width: 600px; margin: 0 auto; padding: 2rem; text-align: center;">
                <h1 style="color: var(--primary-color);">Booking Request Submitted</h1>
                <div class="confirmation" style="background-color: var(--light-gray); padding: 1.5rem; border-radius: 8px;">
                    <p>Your booking is awaiting peer tutor confirmation.</p>
                    <div class="details" style="text-align: left; margin: 1rem 0;">
                        <h3 style="color: var(--primary-color); margin-bottom: 0.5rem;">Booking Details</h3>
                        <p style="margin: 0.5rem 0;"><strong>Course:</strong> <?php echo htmlspecialchars($course['course_name']); ?></p>
                        <p style="margin: 0.5rem 0;"><strong>Date:</strong> <?php echo htmlspecialchars($selected_date); ?></p>
                        <p style="margin: 0.5rem 0;"><strong>Time Slot:</strong> <?php echo htmlspecialchars($time_slot); ?></p>
                        <p style="margin: 0.5rem 0;"><strong>Study Venue:</strong> <?php echo htmlspecialchars($location['location_name']); ?></p>
                        <p style="margin: 0.5rem 0;"><strong>Notes:</strong> <?php echo empty($notes) ? "None" : htmlspecialchars($notes); ?></p>
                        <p style="margin: 0.5rem 0;"><strong>Total Fee:</strong> RM <?php echo number_format($hourly_rate, 2); ?></p>
                    </div>
                </div>
                <a href="student_main_page.php" class="btn" style="background-color: var(--accent-color); padding: 0.8rem 1.5rem; text-decoration: none; color: var(--dark-gray); border-radius: 4px; display: inline-block; margin-top: 1rem;">Back to Main Page</a>
            </div>
        </div>
        </body>

        </html>
<?php
        exit;
    } else {
        header("Location: appointments.php?tutor_id=$tutor_id&error=Failed to submit booking");
        exit;
    }
}

$conn->close();
?>