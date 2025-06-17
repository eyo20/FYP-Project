<?php
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'db_connection.php';
if (!$conn) {
    $_SESSION['error'] = "Database connection failed.";
    header('Location: error.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle tab state
$active_tab = isset($_POST['active_tab']) ? $_POST['active_tab'] : (isset($_SESSION['active_tab']) ? $_SESSION['active_tab'] : 'pending');
$_SESSION['active_tab'] = $active_tab === 'processed' ? 'processed' : 'pending';

// Check if user is a tutor
$stmt = $conn->prepare("SELECT role FROM user WHERE user_id = ?");
if (!$stmt) {
    $_SESSION['error'] = "Error preparing user query: " . $conn->error;
    error_log("Error preparing user query: " . $conn->error);
    header('Location: error.php');
    exit();
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0 || $result->fetch_assoc()['role'] !== 'tutor') {
    header('Location: error.php?error_message=Access denied. You must be a tutor to view this page.');
    exit();
}

// Get user info
$stmt = $conn->prepare("SELECT first_name, last_name, profile_image FROM user WHERE user_id = ?");
if (!$stmt) {
    $_SESSION['error'] = "Error preparing user info query: " . $conn->error;
    error_log("Error preparing user info query: " . $conn->error);
    header('Location: error.php');
    exit();
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$first_name = $user['first_name'] ?? '';
$last_name = $user['last_name'] ?? '';
$profile_image = $user['profile_image'] ?? '';
$stmt->close();

// Handle request actions
$success_message = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$error_message = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['success'], $_SESSION['error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['request_id'])) {
    $action = $_POST['action'];
    $request_id = (int)$_POST['request_id'];

    // Validate request
    $stmt_validate = $conn->prepare("
        SELECT sr.student_id, sr.course_id, sr.selected_date, sr.time_slot, sr.location_id, c.course_name
        FROM session_requests sr
        JOIN course c ON sr.course_id = c.id
        WHERE sr.request_id = ? AND sr.tutor_id = ? AND sr.status = 'pending'
    ");
    if (!$stmt_validate) {
        $_SESSION['error'] = "Error preparing request validation: " . $conn->error;
        error_log("Error preparing request validation: " . $conn->error);
    } else {
        $stmt_validate->bind_param("ii", $request_id, $user_id);
        $stmt_validate->execute();
        $result = $stmt_validate->get_result();

        if ($result->num_rows > 0) {
            $request = $result->fetch_assoc();
            $stmt_validate->close();

            if ($action === 'accept') {
                $conn->begin_transaction();
                try {
                    // Update session_requests to confirmed
                    $stmt_update = $conn->prepare("UPDATE session_requests SET status = 'confirmed' WHERE request_id = ?");
                    if (!$stmt_update) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    $stmt_update->bind_param("i", $request_id);
                    $stmt_update->execute();
                    $stmt_update->close();

                    // Get session_requests data
                    $stmt_fetch = $conn->prepare("
                        SELECT selected_date, time_slot, location_id 
                        FROM session_requests WHERE request_id = ?
                    ");
                    if (!$stmt_fetch) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    $stmt_fetch->bind_param("i", $request_id);
                    $stmt_fetch->execute();
                    $request_data = $stmt_fetch->get_result()->fetch_assoc();
                    $stmt_fetch->close();

                    // Validate and calculate time
                    if (empty($request_data['time_slot']) || empty($request_data['selected_date'])) {
                        throw new Exception("Time slot or date not specified.");
                    }
                    list($start_time, $end_time) = explode('-', $request_data['time_slot']);
                    $start_datetime = $request_data['selected_date'] . ' ' . $start_time . ':00';
                    $end_datetime = $request_data['selected_date'] . ' ' . $end_time . ':00';
                    if (!strtotime($start_datetime) || !strtotime($end_datetime)) {
                        throw new Exception("Invalid date or time format.");
                    }
                    $location_id = (int)$request_data['location_id'];
                    // Validate location_id
                    $stmt_loc = $conn->prepare("SELECT location_id FROM location WHERE location_id = ?");
                    $stmt_loc->bind_param("i", $location_id);
                    $stmt_loc->execute();
                    if ($stmt_loc->get_result()->num_rows === 0) {
                        throw new Exception("Invalid location ID.");
                    }
                    $stmt_loc->close();

                    // Fetch conflicting requests
                    $stmt_conflict_fetch = $conn->prepare("
                        SELECT sr.request_id, sr.student_id, c.course_name
                        FROM session_requests sr
                        JOIN course c ON sr.course_id = c.id
                        WHERE sr.tutor_id = ? AND sr.request_id != ? 
                        AND sr.selected_date = ? AND sr.time_slot = ? AND sr.status = 'pending'
                    ");
                    if (!$stmt_conflict_fetch) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    $stmt_conflict_fetch->bind_param("iiss", $user_id, $request_id, $request_data['selected_date'], $request_data['time_slot']);
                    $stmt_conflict_fetch->execute();
                    $conflict_result = $stmt_conflict_fetch->get_result();
                    $conflicting_requests = [];
                    while ($row = $conflict_result->fetch_assoc()) {
                        $conflicting_requests[] = $row;
                    }
                    $stmt_conflict_fetch->close();

                    // Reject conflicting requests
                    $stmt_conflict = $conn->prepare("
                        UPDATE session_requests 
                        SET status = 'rejected'
                        WHERE tutor_id = ? AND request_id != ? 
                        AND selected_date = ? AND time_slot = ? AND status = 'pending'
                    ");
                    if (!$stmt_conflict) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    $stmt_conflict->bind_param("iiss", $user_id, $request_id, $request_data['selected_date'], $request_data['time_slot']);
                    $stmt_conflict->execute();
                    $stmt_conflict->close();

                    // Notify students of rejected conflicting requests
                    if ($conflicting_requests) {
                        $admin_id = 17;
                        foreach ($conflicting_requests as $conflict) {
                            $conflict_request_id = $conflict['request_id'];
                            $conflict_student_id = $conflict['student_id'];
                            $conflict_course_name = $conflict['course_name'];
                            $message_content = "Your {$conflict_course_name} course in the {$request_data['time_slot']} slot on " . date('M d, Y', strtotime($request_data['selected_date'])) . " has been cancelled. You can rebook another session or discuss alternative timings with your tutor.";

                            // Insert notification
                            $stmt_notify_conflict = $conn->prepare("
                                INSERT INTO notification (user_id, type, title, message, related_id, created_at)
                                VALUES (?, 'session', 'Request Rejected', ?, ?, NOW())
                            ");
                            if (!$stmt_notify_conflict) {
                                throw new Exception("Prepare failed: " . $conn->error);
                            }
                            $stmt_notify_conflict->bind_param("isi", $conflict_student_id, $message_content, $conflict_request_id);
                            $stmt_notify_conflict->execute();
                            $stmt_notify_conflict->close();

                            // Insert message
                            $stmt_message = $conn->prepare("
                                INSERT INTO message (sender_id, receiver_id, content, is_read, sent_datetime)
                                VALUES (?, ?, ?, 0, NOW())
                            ");
                            if (!$stmt_message) {
                                throw new Exception("Prepare failed: " . $conn->error);
                            }
                            $stmt_message->bind_param("iis", $admin_id, $conflict_student_id, $message_content);
                            $stmt_message->execute();
                            $stmt_message->close();
                        }
                    }

                    // Insert session
                    $stmt_insert = $conn->prepare("
                        INSERT INTO session (tutor_id, student_id, course_id, location_id, status, start_datetime, end_datetime, created_at)
                        VALUES (?, ?, ?, ?, 'confirmed', ?, ?, NOW())
                    ");
                    if (!$stmt_insert) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    $stmt_insert->bind_param("iiiiss", $user_id, $request['student_id'], $request['course_id'], $location_id, $start_datetime, $end_datetime);
                    $stmt_insert->execute();
                    $stmt_insert->close();

                    // Send notification for accepted request
                    $stmt_notify = $conn->prepare("
                        INSERT INTO notification (user_id, type, title, message, related_id, created_at)
                        VALUES (?, 'session', 'Request Accepted', 'Your tutoring request has been accepted. Please discuss timing with your tutor.', ?, NOW())
                    ");
                    if (!$stmt_notify) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    $stmt_notify->bind_param("ii", $request['student_id'], $request_id);
                    $stmt_notify->execute();
                    $stmt_notify->close();

                    $conn->commit();
                    $_SESSION['success'] = 'Request accepted successfully! Please discuss timing with the student via messages.';
                } catch (Exception $e) {
                    $conn->rollback();
                    $_SESSION['error'] = 'Failed to accept request: ' . $e->getMessage();
                    error_log("Failed to accept request: " . $e->getMessage());
                }
            } elseif ($action === 'reject') {
                $conn->begin_transaction();
                try {
                    // Update session request status
                    $stmt_reject = $conn->prepare("UPDATE session_requests SET status = 'rejected' WHERE request_id = ?");
                    if (!$stmt_reject) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    $stmt_reject->bind_param("i", $request_id);
                    $stmt_reject->execute();
                    $stmt_reject->close();

                    // Send cancellation message to message table from admin (user_id=17)
                    $admin_id = 17;
                    $time_slot = $request['time_slot'] ?? 'Not specified';
                    $selected_date = $request['selected_date'] ?? 'Not specified';
                    $course_name = $request['course_name'] ?? 'Unknown Course';
                    $message_content = "Your {$course_name} course in the {$time_slot} slot on " . date('M d, Y', strtotime($selected_date)) . " has been cancelled. You can rebook another session or discuss alternative timings with your tutor.";

                    // Insert notification
                    $stmt_notify = $conn->prepare("
                        INSERT INTO notification (user_id, type, title, message, related_id, created_at)
                        VALUES (?, 'session', 'Request Rejected', ?, ?, NOW())
                    ");
                    if (!$stmt_notify) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    $stmt_notify->bind_param("isi", $request['student_id'], $message_content, $request_id);
                    $stmt_notify->execute();
                    $stmt_notify->close();

                    // Insert message
                    $stmt_message = $conn->prepare("
                        INSERT INTO message (sender_id, receiver_id, content, is_read, sent_datetime)
                        VALUES (?, ?, ?, 0, NOW())
                    ");
                    if (!$stmt_message) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    $stmt_message->bind_param("iis", $admin_id, $request['student_id'], $message_content);
                    $stmt_message->execute();
                    $stmt_message->close();

                    $conn->commit();
                    $_SESSION['success'] = 'Request rejected successfully';
                } catch (Exception $e) {
                    $conn->rollback();
                    $_SESSION['error'] = 'Failed to reject request: ' . $e->getMessage();
                    error_log("Failed to reject request: " . $e->getMessage());
                }
            } else {
                $_SESSION['error'] = 'Invalid action: ' . $action;
                error_log("Invalid action: " . $action);
            }
        } else {
            $_SESSION['error'] = 'Invalid request ID or no permission.';
            error_log("Invalid request ID: $request_id or no permission for tutor_id: $user_id");
        }
    }

    header("Location: tutor_requests.php?tab=" . urlencode($active_tab));
    exit();
}

// Handle search
$search_results = [];
$search_date = isset($_POST['search_date']) ? trim($_POST['search_date']) : '';
$search_time_slot = isset($_POST['search_time_slot']) ? trim($_POST['search_time_slot']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_search'])) {
    $search_date = '';
    $search_time_slot = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $stmt_search = $conn->prepare("
        SELECT sr.request_id, sr.tutor_id, sr.student_id, sr.course_id, sr.status, sr.created_at, 
               sr.time_slot, sr.selected_date, c.course_name, u.first_name, u.last_name, u.profile_image, l.location_name
        FROM session_requests sr
        JOIN course c ON sr.course_id = c.id
        JOIN user u ON sr.student_id = u.user_id
        JOIN location l ON sr.location_id = l.location_id
        WHERE sr.tutor_id = ? 
        AND (? = '' OR sr.selected_date = ?)
        AND (? = '' OR sr.time_slot = ?)
    ");
    if (!$stmt_search) {
        $_SESSION['error'] = "Error preparing search query: " . $conn->error;
        error_log("Error preparing search query: " . $conn->error);
    } else {
        $stmt_search->bind_param("isssi", $user_id, $search_date, $search_date, $search_time_slot, $search_time_slot);
        $stmt_search->execute();
        $result = $stmt_search->get_result();
        while ($row = $result->fetch_assoc()) {
            $search_results[] = $row;
        }
        $stmt_search->close();
    }
} else {
    // Default view for pending requests
    $stmt = $conn->prepare("
        SELECT sr.request_id, sr.tutor_id, sr.student_id, sr.course_id, sr.status, sr.created_at, 
               sr.time_slot, sr.selected_date, c.course_name, u.first_name, u.last_name, u.profile_image, l.location_name
        FROM session_requests sr
        JOIN course c ON sr.course_id = c.id
        JOIN user u ON sr.student_id = u.user_id
        JOIN location l ON sr.location_id = l.location_id
        WHERE sr.tutor_id = ? AND sr.status = 'pending'
    ");
    if (!$stmt) {
        $_SESSION['error'] = "Error preparing pending requests: " . $conn->error;
        error_log("Error preparing pending requests: " . $conn->error);
    } else {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $search_results[] = $row;
        }
        $stmt->close();
    }
}

// Get processed requests with search filters if applicable
$processed_requests = [];
$processed_query = "
    SELECT sr.request_id, sr.tutor_id, sr.student_id, sr.course_id, sr.status, sr.created_at, 
           sr.time_slot, sr.selected_date, c.course_name, u.first_name, u.last_name, u.profile_image, l.location_name
    FROM session_requests sr
    JOIN course c ON sr.course_id = c.id
    JOIN user u ON sr.student_id = u.user_id
    JOIN location l ON sr.location_id = l.location_id
    WHERE sr.tutor_id = ? AND sr.status IN ('confirmed', 'rejected')
";
if ($search_date !== '' || $search_time_slot !== '') {
    $processed_query .= " AND (? = '' OR sr.selected_date = ?)
                         AND (? = '' OR sr.time_slot = ?)";
}
$processed_query .= " ORDER BY created_at DESC";
$stmt = $conn->prepare($processed_query);
if (!$stmt) {
    $_SESSION['error'] = "Error preparing processed requests: " . $conn->error;
    error_log("Error preparing processed requests: " . $conn->error);
} else {
    if ($search_date !== '' || $search_time_slot !== '') {
        $stmt->bind_param("isssi", $user_id, $search_date, $search_date, $search_time_slot, $search_time_slot);
    } else {
        $stmt->bind_param("i", $user_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $processed_requests[] = $row;
    }
    $stmt->close();
}

$confirmed_requests = array_filter($processed_requests, fn($r) => $r['status'] === 'confirmed');
$rejected_requests = array_filter($processed_requests, fn($r) => $r['status'] === 'rejected');

// Debug log
error_log("Total processed requests: " . count($processed_requests));
error_log("Confirmed requests: " . count($confirmed_requests));
error_log("Rejected requests: " . count($rejected_requests));

// Get unread messages count
$unread_messages = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM message WHERE receiver_id = ? AND is_read = 0");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $unread_messages = $result->fetch_assoc()['count'];
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Requests - PeerLearn</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
    :root {
        --primary: #2B3990;
        --secondary: #00AEEF;
        --accent: #C4D600;
        --gray: #e0e0e0;
        --light-gray: #f5f5f5;
        --dark-gray: #777;
    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
        background-color: var(--light-gray);
        color: #333;
        line-height: 1.6;
    }

    .navbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background-color: var(--primary);
        color: white;
        padding: 1rem 2rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .logo span {
        font-size: 1.5rem;
        font-weight: bold;
    }

    .nav-links {
        display: flex;
        gap: 1.5rem;
    }

    .nav-links a {
        color: white;
        text-decoration: none;
        font-weight: 500;
        position: relative;
    }

    .nav-links a:hover {
        color: var(--accent);
    }

    .nav-links a.active {
        color: var(--accent);
    }

    .notification-badge {
        position: absolute;
        top: -8px;
        right: -12px;
        background-color: var(--accent);
        color: var(--primary);
        border-radius: 50%;
        width: 18px;
        height: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        font-weight: bold;
    }

    .user-menu {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: var(--secondary);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        overflow: hidden;
        border: 2px solid white;
    }

    .profile-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    main {
        max-width: 1200px;
        margin: 2rem auto;
        padding: 0 1rem;
    }

    .section-title {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--primary);
        border-bottom: 3px solid var(--accent);
        padding-bottom: 0.5rem;
        margin-bottom: 1rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .search-form {
        display: flex;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .search-form input[type="date"],
    .search-form select {
        padding: 0.5rem;
        border: 1px solid var(--gray);
        border-radius: 4px;
        font-size: 1rem;
    }

    .search-form button {
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 1rem;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .search-form .btn-search {
        background-color: var(--secondary);
        color: white;
    }

    .search-form .btn-search:hover {
        background-color: #0099cc;
    }

    .search-form .btn-clear {
        background-color: var(--gray);
        color: var(--primary);
    }

    .search-form .btn-clear:hover {
        background-color: #d0d0d0;
    }

    .alert {
        padding: 1rem;
        border-radius: 4px;
        margin-bottom: 1.5rem;
    }

    .alert-success {
        background-color: rgba(196, 214, 0, 0.1);
        border-left: 4px solid var(--accent);
        color: #5a6400;
    }

    .alert-danger {
        background-color: rgba(220, 53, 69, 0.1);
        border-left: 4px solid #dc3545;
        color: #dc3545;
    }

    .tabs {
        display: flex;
        border-bottom: 1px solid var(--gray);
        margin-bottom: 1.5rem;
    }

    .tab {
        padding: 0.75rem 1.5rem;
        cursor: pointer;
        font-weight: 500;
        border-bottom: 3px solid transparent;
        color: var(--dark-gray);
        position: relative;
    }

    .tab.active {
        border-bottom-color: var(--accent);
        color: var(--primary);
        font-weight: 600;
    }

    .tab:hover {
        background-color: rgba(0, 174, 239, 0.05);
        color: var(--primary);
    }

    .tab-content {
        display: none;
        padding: 0;
    }

    .tab-content.active {
        display: block;
    }

    .request-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 1.5rem;
        margin-top: 0;
    }

    #processed-tab .request-list {
        margin-top: 0;
    }

    .request-card {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        padding: 1.5rem;
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .request-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .student-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background-color: var(--secondary);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        overflow: hidden;
        border-right: 2px solid var(--light-gray);
    }

    .student-avatar img.profile-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .request-info {
        flex: 1;
    }

    .student-name {
        font-weight: 600;
        color: var(--primary);
    }

    .request-subject {
        color: var(--dark-gray);
        font-size: 0.9rem;
    }

    .request-details {
        margin-bottom: 1.5rem;
        background-color: rgba(0, 174, 239, 0.03);
        padding: 1rem;
        border-radius: 6px;
        border-left: 3px solid var(--secondary);
    }

    .detail-item {
        display: flex;
        margin-bottom: 0.5rem;
    }

    .detail-label {
        width: 120px;
        color: var(--dark-gray);
        font-size: 0.9rem;
    }

    .detail-value {
        flex: 1;
        font-weight: 500;
        color: var(--primary);
    }

    .request-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        justify-content: center;
    }

    .btn {
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
    }

    .btn-success {
        background-color: var(--accent);
        color: white;
    }

    .btn-success:hover {
        background-color: #b1c100;
    }

    .btn-danger {
        background-color: #dc3545;
        color: white;
    }

    .btn-danger:hover {
        background-color: #c82333;
    }

    .btn-message {
        background-color: var(--secondary);
        color: white;
        padding: 0.5rem 2.5rem;
        min-width: 120px;
        text-align: center;
    }

    .btn-message:hover {
        background-color: #0099cc;
    }

    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        margin-left: auto;
    }

    .status-confirmed {
        background-color: rgba(196, 214, 0, 0.1);
        color: var(--accent);
    }

    .status-rejected {
        background-color: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }

    .separator {
        border: 0;
        height: 1px;
        background: #C4D600;
        margin: 1rem 0;
    }

    .empty-state {
        text-align: center;
        padding: 3rem;
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .empty-icon {
        font-size: 3rem;
        color: var(--secondary);
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    .empty-text {
        color: var(--dark-gray);
        margin-bottom: 1.5rem;
    }

    @media (max-width: 768px) {
        .navbar { padding: 1rem; }
        .nav-links { gap: 1rem; }
        .request-list { grid-template-columns: 1fr; }
        .request-actions { flex-direction: column; }
        .btn { width: 100%; justify-content: center; }
        .search-form { flex-direction: column; gap: 0.5rem; }
    }
</style>
</head>
<body>
    <?php include 'header/tut_head.php'; ?>

    <main>
        <h1 class="section-title">Tutoring Requests</h1>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form method="post" action="tutor_requests.php" class="search-form">
            <input type="date" name="search_date" value="<?php echo htmlspecialchars($search_date); ?>">
            <select name="search_time_slot">
                <option value="">Select Time Slot</option>
                <option value="08:00-10:00" <?php echo $search_time_slot === '08:00-10:00' ? 'selected' : ''; ?>>08:00 - 10:00</option>
                <option value="10:00-12:00" <?php echo $search_time_slot === '10:00-12:00' ? 'selected' : ''; ?>>10:00 - 12:00</option>
                <option value="12:00-14:00" <?php echo $search_time_slot === '12:00-14:00' ? 'selected' : ''; ?>>12:00 - 14:00</option>
            </select>
            <input type="hidden" name="active_tab" value="<?php echo htmlspecialchars($active_tab); ?>">
            <button type="submit" name="search" class="btn-search"><i class="fas fa-search"></i> Search</button>
            <button type="submit" name="clear_search" class="btn-clear"><i class="fas fa-undo"></i> Clear Search</button>
        </form>

        <div class="tabs">
            <div class="tab <?php echo $active_tab === 'pending' ? 'active' : ''; ?>" data-tab="pending">Pending Requests
                <?php if (count(array_filter($search_results, fn($r) => $r['status'] === 'pending')) > 0): ?>
                    <span class="notification-badge"><?php echo count(array_filter($search_results, fn($r) => $r['status'] === 'pending')); ?></span>
                <?php endif; ?>
            </div>
            <div class="tab <?php echo $active_tab === 'processed' ? 'active' : ''; ?>" data-tab="processed">Processed Requests
                <?php if (count($processed_requests) > 0): ?>
                    <span class="notification-badge"><?php echo count($processed_requests); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div id="pending-tab" class="tab-content <?php echo $active_tab === 'pending' ? 'active' : ''; ?>">
            <?php $pending_requests = array_filter($search_results, fn($r) => $r['status'] === 'pending'); ?>
            <?php if ($pending_requests): ?>
                <div class="request-list">
                    <?php foreach ($pending_requests as $request): ?>
                        <div class="request-card">
                            <div class="request-header">
                                <div class="student-avatar">
                                    <?php if ($request['profile_image']): ?>
                                        <img src="<?php echo htmlspecialchars($request['profile_image']); ?>" alt="Student" class="profile-image">
                                    <?php else: ?>
                                        <?php echo htmlspecialchars(substr($request['first_name'] ?? '', 0, 1) . substr($request['last_name'] ?? '', 0, 1)); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="request-info">
                                    <div class="student-name"><?php echo htmlspecialchars(($request['first_name'] ?? '') . ' ' . ($request['last_name'] ?? '')); ?></div>
                                    <div class="request-subject"><?php echo htmlspecialchars($request['course_name'] ?? 'Unknown Course'); ?></div>
                                </div>
                            </div>
                            <div class="request-details">
                                <div class="detail-item">
                                    <div class="detail-label">Course:</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($request['course_name'] ?? 'Unknown Course'); ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Date:</div>
                                    <div class="detail-value">
                                        <?php echo !empty($request['selected_date']) 
                                            ? date('M d, Y', strtotime($request['selected_date'])) 
                                            : 'Not specified'; ?>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Time Slot:</div>
                                    <div class="detail-value">
                                        <?php echo htmlspecialchars($request['time_slot'] ?? 'Not specified'); ?>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Location:</div>
                                    <div class="detail-value">
                                        <?php echo htmlspecialchars($request['location_name'] ?? 'Not specified'); ?>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Requested On:</div>
                                    <div class="detail-value">
                                        <?php echo !empty($request['created_at']) 
                                            ? date('M d, Y', strtotime($request['created_at'])) 
                                            : 'Not specified'; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="request-actions">
                                <form method="post" action="tutor_requests.php">
                                    <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request['request_id'] ?? ''); ?>">
                                    <input type="hidden" name="action" value="accept">
                                    <input type="hidden" name="active_tab" value="<?php echo htmlspecialchars($active_tab); ?>">
                                    <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Accept</button>
                                </form>
                                <form method="post" action="tutor_requests.php">
                                    <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request['request_id'] ?? ''); ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="active_tab" value="<?php echo htmlspecialchars($active_tab); ?>">
                                    <button type="submit" class="btn btn-danger"><i class="fas fa-times"></i> Reject</button>
                                </form>
                                <a href="messages.php?student_id=<?php echo htmlspecialchars($request['student_id'] ?? ''); ?>" class="btn btn-message"><i class="fas fa-envelope"></i> Message</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-inbox"></i></div>
                    <h3>No Pending Requests</h3>
                    <p class="empty-text">You don't have any pending tutoring requests at the moment.</p>
                </div>
            <?php endif; ?>
        </div>

        <div id="processed-tab" class="tab-content <?php echo $active_tab === 'processed' ? 'active' : ''; ?>">
            <?php if ($confirmed_requests || $rejected_requests): ?>
                <?php if ($confirmed_requests): ?>
                    <h3 class="section-title">Confirmed Requests</h3>
                    <div class="request-list">
                        <?php foreach ($confirmed_requests as $request): ?>
                            <div class="request-card">
                                <div class="request-header">
                                    <div class="student-avatar">
                                        <?php if ($request['profile_image']): ?>
                                            <img src="<?php echo htmlspecialchars($request['profile_image']); ?>" alt="Student" class="profile-image">
                                        <?php else: ?>
                                            <?php echo htmlspecialchars(substr($request['first_name'] ?? '', 0, 1) . substr($request['last_name'] ?? '', 0, 1)); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="request-info">
                                        <div class="student-name"><?php echo htmlspecialchars(($request['first_name'] ?? '') . ' ' . ($request['last_name'] ?? '')); ?></div>
                                        <div class="request-subject"><?php echo htmlspecialchars($request['course_name'] ?? 'Unknown Course'); ?></div>
                                    </div>
                                    <span class="status-badge status-confirmed">Confirmed</span>
                                </div>
                                <div class="request-details">
                                    <div class="detail-item">
                                        <div class="detail-label">Course:</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($request['course_name'] ?? 'Unknown Course'); ?></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-label">Date:</div>
                                        <div class="detail-value">
                                            <?php echo !empty($request['selected_date']) 
                                                ? date('M d, Y', strtotime($request['selected_date'])) 
                                                : 'Not specified'; ?>
                                        </div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-label">Time Slot:</div>
                                        <div class="detail-value">
                                            <?php echo htmlspecialchars($request['time_slot'] ?? 'Not specified'); ?>
                                        </div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-label">Location:</div>
                                        <div class="detail-value">
                                            <?php echo htmlspecialchars($request['location_name'] ?? 'Not specified'); ?>
                                        </div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-label">Requested On:</div>
                                        <div class="detail-value">
                                            <?php echo !empty($request['created_at']) 
                                                ? date('M d, Y', strtotime($request['created_at'])) 
                                                : 'Not specified'; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="request-actions">
                                    <a href="messages.php?student_id=<?php echo htmlspecialchars($request['student_id'] ?? ''); ?>" class="btn btn-message"><i class="fas fa-envelope"></i> Message</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($rejected_requests): ?>
                    <h3 class="section-title">Rejected Requests</h3>
                    <div class="request-list">
                        <?php foreach ($rejected_requests as $request): ?>
                            <div class="request-card">
                                <div class="request-header">
                                    <div class="student-avatar">
                                        <?php if ($request['profile_image']): ?>
                                            <img src="<?php echo htmlspecialchars($request['profile_image']); ?>" alt="Student" class="profile-image">
                                        <?php else: ?>
                                            <?php echo htmlspecialchars(substr($request['first_name'] ?? '', 0, 1) . substr($request['last_name'] ?? '', 0, 1)); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="request-info">
                                        <div class="student-name"><?php echo htmlspecialchars(($request['first_name'] ?? '') . ' ' . ($request['last_name'] ?? '')); ?></div>
                                        <div class="request-subject"><?php echo htmlspecialchars($request['course_name'] ?? 'Unknown Course'); ?></div>
                                    </div>
                                    <span class="status-badge status-rejected">Rejected</span>
                                </div>
                                <div class="request-details">
                                    <div class="detail-item">
                                        <div class="detail-label">Course:</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($request['course_name'] ?? 'Unknown Course'); ?></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-label">Date:</div>
                                        <div class="detail-value">
                                            <?php echo !empty($request['selected_date']) 
                                                ? date('M d, Y', strtotime($request['selected_date'])) 
                                                : 'Not specified'; ?>
                                        </div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-label">Time Slot:</div>
                                        <div class="detail-value">
                                            <?php echo htmlspecialchars($request['time_slot'] ?? 'Not specified'); ?>
                                        </div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-label">Location:</div>
                                        <div class="detail-value">
                                            <?php echo htmlspecialchars($request['location_name'] ?? 'Not specified'); ?>
                                        </div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-label">Requested On:</div>
                                        <div class="detail-value">
                                            <?php echo !empty($request['created_at']) 
                                                ? date('M d, Y', strtotime($request['created_at'])) 
                                                : 'Not specified'; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="request-actions">
                                    <a href="messages.php?student_id=<?php echo htmlspecialchars($request['student_id'] ?? ''); ?>" class="btn btn-message"><i class="fas fa-envelope"></i> Message</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-history"></i></div>
                    <h3>No Processed Requests</h3>
                    <p class="empty-text">You haven't processed any tutoring requests yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        const tabs = document.querySelectorAll('.tab');
        const tabContents = document.querySelectorAll('.tab-content');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                tab.classList.add('active');
                document.getElementById(`${tab.getAttribute('data-tab')}-tab`).classList.add('active');

                // Update session via form submission
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'tutor_requests.php';
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'active_tab';
                input.value = tab.getAttribute('data-tab');
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            });
        });
    </script>
</body>
</html>