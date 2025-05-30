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

// Initialize error message
$error = [];
$debug = [];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize form data
    $tutor_id = isset($_POST['tutor_id']) ? intval($_POST['tutor_id']) : 0;
    $course_id = isset($_POST['course']) ? intval($_POST['course']) : 0;
    $selected_date = isset($_POST['selected_date']) ? trim($_POST['selected_date']) : "";
    $availability_id = isset($_POST['availability_id']) ? intval($_POST['availability_id']) : 0;
    $duration = isset($_POST['duration']) ? floatval($_POST['duration']) : 0;
    $location_id = isset($_POST['location']) ? intval($_POST['location']) : 0;
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : "";

    // Debug: Log received form data
    $debug[] = "Received POST data: " . print_r($_POST, true);

    // Get student ID from session (replace with actual session variable)
    $student_id = 2; // For testing purposes

    // Validate required fields
    if ($tutor_id <= 0) $error[] = "Tutor ID is missing or invalid.";
    if ($course_id <= 0) $error[] = "Course is not selected.";
    if (empty($selected_date) || !preg_match("/^\d{4}-\d{2}-\d{2}$/", $selected_date)) $error[] = "Date is not selected or invalid.";
    if ($availability_id <= 0) $error[] = "Time slot is not selected.";
    if ($duration <= 0) $error[] = "Duration is not selected or invalid.";
    if ($location_id <= 0) $error[] = "Study venue is not selected.";

    // If no errors, proceed with booking
    if (empty($error)) {
        // Get hourly rate for the selected course
        $rate_query = "SELECT hourly_rate FROM tutorsubject WHERE tutor_id = ? AND course_id = ?";
        $stmt = $conn->prepare($rate_query);
        $stmt->bind_param("ii", $tutor_id, $course_id);
        $stmt->execute();
        $rate_result = $stmt->get_result();
        $rate_row = $rate_result->fetch_assoc();

        if (!$rate_row) {
            $error[] = "No hourly rate found for the selected course. Please contact support.";
        } else {
            $hourly_rate = $rate_row['hourly_rate'];

            // Get availability details
            $availability_query = "SELECT start_datetime, end_datetime FROM availability WHERE availability_id = ? AND tutor_id = ? AND status = 'open'";
            $stmt = $conn->prepare($availability_query);
            $stmt->bind_param("ii", $availability_id, $tutor_id);
            $stmt->execute();
            $availability_result = $stmt->get_result();
            $availability = $availability_result->fetch_assoc();

            if (!$availability || empty($availability['start_datetime']) || empty($availability['end_datetime'])) {
                $error[] = "Invalid or unavailable time slot selected. Please choose another time slot.";
            } else {
                $start_datetime = $availability['start_datetime'];
                $end_datetime = date('Y-m-d H:i:s', strtotime($start_datetime . " + {$duration} hours"));

                // Insert into session table
                $session_query = "INSERT INTO session (tutor_id, student_id, course_id, availability_id, location_id, status, start_datetime, end_datetime) 
                                 VALUES (?, ?, ?, ?, ?, 'scheduled', ?, ?)";
                $stmt = $conn->prepare($session_query);
                $stmt->bind_param("iiiiiss", $tutor_id, $student_id, $course_id, $availability_id, $location_id, $start_datetime, $end_datetime);

                if ($stmt->execute()) {
                    $session_id = $conn->insert_id;

                    // Update availability status to 'booked'
                    $update_availability = "UPDATE availability SET status = 'booked' WHERE availability_id = ?";
                    $stmt = $conn->prepare($update_availability);
                    $stmt->bind_param("i", $availability_id);
                    $stmt->execute();

                    // Calculate payment amount
                    $amount = $hourly_rate * $duration;
                    $platform_fee = ceil($amount * 0.05); // 5% platform fee
                    $total_amount = $amount + $platform_fee;

                    // Create a payment record
                    $payment_query = "INSERT INTO payment (session_id, amount, status, payment_method) 
                                     VALUES (?, ?, 'pending', 'online')";
                    $stmt = $conn->prepare($payment_query);
                    $stmt->bind_param("id", $session_id, $total_amount);
                    $stmt->execute();
                    $payment_id = $conn->insert_id;

                    // Redirect to payment page
                    header("Location: make_payment.php?payment_id={$payment_id}");
                    exit();
                } else {
                    $error[] = "Failed to create session. Please try again.";
                }
            }
        }
    }
} else {
    $error[] = "Invalid request method.";
}

// If there's an error, display it with debug info
if (!empty($error)) {
    // Log debug info to a file for troubleshooting
    file_put_contents('debug_log.txt', implode("\n", $debug) . "\nErrors: " . implode("; ", $error) . "\n", FILE_APPEND);

    echo "<!DOCTYPE html>
    <html lang='zh'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Error - Peer Tutoring Platform</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 2rem; text-align: center; }
            .error { color: red; font-size: 1.2rem; }
            .btn { background-color: #C4D600; padding: 0.8rem 1.5rem; text-decoration: none; color: #333; border-radius: 4px; }
        </style>
    </head>
    <body>
        <h1>Error</h1>
        <p class='error'>" . implode("<br>", $error) . "</p>
        <a href='appointments.php?tutor_id=$tutor_id' class='btn'>Back to Booking</a>
    </body>
    </html>";
    exit();
}

$conn->close();
