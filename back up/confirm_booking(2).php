<?php
// Start session
session_start();
require_once 'db_connection.php';

// Initialize error messages and debug info
$error = [];
$debug = [];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize form data
    $tutor_id = isset($_POST['tutor_id']) ? intval($_POST['tutor_id']) : 0;
    $course_id = isset($_POST['course']) ? intval($_POST['course']) : 0;
    $selected_date = isset($_POST['selected_date']) ? trim($_POST['selected_date']) : "";
    $duration = isset($_POST['duration']) ? floatval($_POST['duration']) : 0;
    $location_id = isset($_POST['location']) ? intval($_POST['location']) : 0;
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : "";

    // Debug: Log received form data
    $debug[] = "Received POST data: " . print_r($_POST, true);

    // Get student ID from session (fallback to a valid student ID for testing)
    $student_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 12; // Use user_id 12 (valid student)

    // Validate required fields
    if ($tutor_id <= 0) $error[] = "Tutor ID is missing or invalid.";
    if ($course_id <= 0) $error[] = "Course is not selected.";
    if (empty($selected_date) || !preg_match("/^\d{4}-\d{2}-\d{2}$/", $selected_date)) $error[] = "Date is not selected or invalid.";
    if ($duration <= 0) $error[] = "Duration is not selected or invalid.";
    if ($location_id <= 0) $error[] = "Study venue is not selected.";

    // Validate student_id exists in user table
    if (empty($error)) {
        $student_query = "SELECT user_id FROM user WHERE user_id = ? AND role = 'student'";
        $stmt = $conn->prepare($student_query);
        if (!$stmt) {
            $error[] = "Database error: Unable to validate student.";
            $debug[] = "Student query error: " . $conn->error;
        } else {
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $student_result = $stmt->get_result();
            if ($student_result->num_rows === 0) {
                $error[] = "Invalid student ID. Please ensure you are logged in as a valid student.";
            }
            $stmt->close();
        }
    }

    // If no errors, proceed with booking request
    if (empty($error)) {
        // Get course details for display
        $course_query = "SELECT course_name FROM course WHERE id = ?";
        $stmt = $conn->prepare($course_query);
        if (!$stmt) {
            $error[] = "Database error: Unable to fetch course.";
            $debug[] = "Course query error: " . $conn->error;
        } else {
            $stmt->bind_param("i", $course_id);
            $stmt->execute();
            $course_result = $stmt->get_result();
            $course = $course_result->fetch_assoc();
            $stmt->close();

            if (!$course) {
                $error[] = "Invalid course selected.";
            }
        }

        // Get hourly rate for the selected course
        if (empty($error)) {
            $rate_query = "SELECT hourly_rate FROM tutorsubject WHERE tutor_id = ? AND course_id = ?";
            $stmt = $conn->prepare($rate_query);
            if (!$stmt) {
                $error[] = "Database error: Unable to fetch hourly rate.";
                $debug[] = "Rate query error: " . $conn->error;
            } else {
                $stmt->bind_param("ii", $tutor_id, $course_id);
                $stmt->execute();
                $rate_result = $stmt->get_result();
                $rate_row = $rate_result->fetch_assoc();
                $stmt->close();

                if (!$rate_row) {
                    $error[] = "No hourly rate found for the selected course. Please contact support.";
                } else {
                    $hourly_rate = $rate_row['hourly_rate'];
                }
            }
        }

        // Get location details for display
        if (empty($error)) {
            $location_query = "SELECT location_name FROM location WHERE location_id = ?";
            $stmt = $conn->prepare($location_query);
            if (!$stmt) {
                $error[] = "Database error: Unable to fetch location.";
                $debug[] = "Location query error: " . $conn->error;
            } else {
                $stmt->bind_param("i", $location_id);
                $stmt->execute();
                $location_result = $stmt->get_result();
                $location = $location_result->fetch_assoc();
                $stmt->close();

                if (!$location) {
                    $error[] = "Invalid study venue selected.";
                }
            }
        }

        // Insert into session_requests table
        if (empty($error)) {
            $request_query = "INSERT INTO session_requests (tutor_id, student_id, course_id, location_id, duration, selected_date, notes, status, created_at) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
            $stmt = $conn->prepare($request_query);
            if (!$stmt) {
                $error[] = "Database error: Unable to prepare session request query.";
                $debug[] = "Session request query error: " . $conn->error;
            } else {
                $stmt->bind_param("iiiidss", $tutor_id, $student_id, $course_id, $location_id, $duration, $selected_date, $notes);
                if ($stmt->execute()) {
                    $request_id = $conn->insert_id;

                    // Display confirmation page
                    echo "<!DOCTYPE html>
                    <html lang='en'>
                    <head>
                        <meta charset='UTF-8'>
                        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                        <title>Booking Confirmation - Peer Tutoring Platform</title>
                        <style>
                            body { font-family: Arial, sans-serif; margin: 0; padding: 2rem; text-align: center; }
                            .container { max-width: 600px; margin: 0 auto; }
                            .confirmation { background-color: #f5f5f5; padding: 1.5rem; border-radius: 8px; }
                            h1 { color: #2B3990; }
                            .details { text-align: left; margin: 1rem 0; }
                            .details p { margin: 0.5rem 0; }
                            .btn { background-color: #C4D600; padding: 0.8rem 1.5rem; text-decoration: none; color: #333; border-radius: 4px; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <h1>Booking Request Submitted</h1>
                            <div class='confirmation'>
                                <p>Your booking is awaiting peer tutor confirmation.</p>
                                <div class='details'>
                                    <h3>Booking Details</h3>
                                    <p><strong>Course:</strong> " . htmlspecialchars($course['course_name']) . "</p>
                                    <p><strong>Date:</strong> " . htmlspecialchars($selected_date) . "</p>
                                    <p><strong>Duration:</strong> " . htmlspecialchars($duration) . " hours</p>
                                    <p><strong>Study Venue:</strong> " . htmlspecialchars($location['location_name']) . "</p>
                                    <p><strong>Notes:</strong> " . (empty($notes) ? "None" : htmlspecialchars($notes)) . "</p>
                                    <p><strong>Hourly Rate:</strong> RM " . number_format($hourly_rate, 2) . "</p>
                                </div>
                            </div>
                            <a href='student_main_page.php' class='btn'>Back to Main Page</a>
                        </div>
                    </body>
                    </html>";
                    exit();
                } else {
                    $error[] = "Failed to create booking request: " . $stmt->error;
                    $debug[] = "Session request execution error: " . $stmt->error;
                    $stmt->close();
                }
            }
        }
    }
} else {
    $error[] = "Invalid request method.";
}

// If there's an error, display it with debug info
if (!empty($error)) {
    // Log debug info to a file
    file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . "\n" . implode("\n", $debug) . "\nErrors: " . implode("; ", $error) . "\n\n", FILE_APPEND);

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
