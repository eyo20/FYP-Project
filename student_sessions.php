<?php
session_start();
require_once 'db_connection.php';
error_log("Starting student_sessions.php: user_id=" . ($_SESSION['user_id'] ?? 'unset') . ", role=" . ($_SESSION['role'] ?? 'unset'));

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    error_log("Session check failed: redirecting to login.php");
    header('Location: login.php');
    exit();
}

$student_id = $_SESSION['user_id'];

// Auto-update completed sessions
try {
    $stmt = $conn->prepare("
        SELECT session_id, status
        FROM session
        WHERE student_id = ? AND end_datetime < NOW() AND status NOT IN ('completed', 'cancelled')
    ");
    if ($stmt) {
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $sessions_to_update = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (!empty($sessions_to_update)) {
            $stmt = $conn->prepare("
                UPDATE session
                SET status = 'completed'
                WHERE session_id = ?
            ");
            if ($stmt) {
                foreach ($sessions_to_update as $session) {
                    $stmt->bind_param("i", $session['session_id']);
                    $stmt->execute();
                }
                $stmt->close();
                error_log("Auto-updated " . count($sessions_to_update) . " sessions to 'completed' for student_id=$student_id");
            } else {
                error_log("Failed to prepare update statement: " . $conn->error);
            }
        }
    } else {
        error_log("Failed to prepare select statement for auto-update: " . $conn->error);
    }
} catch (Exception $e) {
    error_log("Error in auto-update logic: " . $e->getMessage());
}

// Retrieve session messages
$success_message = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$error_message = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['success'], $_SESSION['error']); // Clear messages after retrieval
error_log("Messages retrieved: success_message='$success_message', error_message='$error_message'");

// Handle tab state
$active_tab = isset($_POST['active_tab']) ? $_POST['active_tab'] : (isset($_SESSION['active_tab']) ? $_SESSION['active_tab'] : 'pending');
$_SESSION['active_tab'] = in_array($active_tab, ['pending', 'current', 'history']) ? $active_tab : 'pending';

// Handle search
$search_date = isset($_POST['search_date']) ? trim($_POST['search_date']) : '';
$search_time_slot = isset($_POST['search_time_slot']) ? trim($_POST['search_time_slot']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_search'])) {
    $search_date = '';
    $search_time_slot = '';
}

// Handle session cancellation (for both session and session_requests)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_session'])) {
    $session_id = (int)$_POST['session_id'];
    $type = $_POST['type'];
    $cancellation_reason = trim($_POST['cancellation_reason']);

    try {
        if ($type === 'session') {
            $stmt = $conn->prepare("
                SELECT s.*, u.first_name, u.last_name 
                FROM session s
                JOIN user u ON s.tutor_id = u.user_id
                WHERE s.session_id = ? AND s.student_id = ? AND s.status = 'scheduled'
                AND s.start_datetime > DATE_ADD(NOW(), INTERVAL 24 HOUR)
            ");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("ii", $session_id, $student_id);
            $stmt->execute();
            $session_result = $stmt->get_result();
            $session = $session_result->fetch_assoc();
            $stmt->close();

            if ($session) {
                $stmt = $conn->prepare("
                    UPDATE session 
                    SET status = 'cancelled', 
                        cancellation_reason = ?, 
                        cancelled_by = ?
                    WHERE session_id = ?
                ");
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                $stmt->bind_param("sii", $cancellation_reason, $student_id, $session_id);
                $stmt->execute();
                $stmt->close();

                $success_message = "Session cancelled successfully.";
                $_SESSION['success'] = $success_message;
            } else {
                $error_message = "Cannot cancel this session.";
                $_SESSION['error'] = $error_message;
            }
        } elseif ($type === 'request') {
            $stmt = $conn->prepare("
                SELECT sr.*, u.first_name, u.last_name 
                FROM session_requests sr
                JOIN user u ON sr.tutor_id = u.user_id
                WHERE sr.request_id = ? AND sr.student_id = ? AND sr.status = 'pending'
            ");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("ii", $session_id, $student_id);
            $stmt->execute();
            $request_result = $stmt->get_result();
            $request = $request_result->fetch_assoc();
            $stmt->close();

            if ($request) {
                $stmt = $conn->prepare("
                    UPDATE session_requests 
                    SET status = 'cancelled',
                        notes = CONCAT(IFNULL(notes, ''), '\nCancellation reason: ', ?)
                    WHERE request_id = ?
                ");
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                $stmt->bind_param("si", $cancellation_reason, $session_id);
                $stmt->execute();
                $stmt->close();

                $success_message = "Session request cancelled successfully.";
                $_SESSION['success'] = $success_message;
            } else {
                $error_message = "Cannot cancel this session request.";
                $_SESSION['error'] = $error_message;
            }
        }
    } catch (Exception $e) {
        error_log("Cancellation error: " . $e->getMessage());
        $error_message = "An error occurred while cancelling the session.";
        $_SESSION['error'] = $error_message;
    }
}

// Get pending session requests
try {
    $query = "
        SELECT sr.request_id, sr.selected_date, sr.time_slot, sr.status, sr.notes,
               u.user_id as tutor_id, u.first_name as tutor_first_name, u.last_name as tutor_last_name, 
               u.email as tutor_email, u.profile_image as tutor_image,
               c.course_name,
               l.location_name
        FROM session_requests sr
        JOIN user u ON sr.tutor_id = u.user_id
        JOIN course c ON sr.course_id = c.id
        LEFT JOIN location l ON sr.location_id = l.location_id
        WHERE sr.student_id = ? AND sr.status = 'pending'
    ";
    if ($search_date !== '' || $search_time_slot !== '') {
        $query .= " AND (? = '' OR sr.selected_date = ?)
                    AND (? = '' OR sr.time_slot = ?)";
    }
    $query .= " ORDER BY sr.created_at ASC";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("Pending requests prepare failed: " . $conn->error);
        throw new Exception("Database error: Unable to fetch pending requests.");
    }
    if ($search_date !== '' || $search_time_slot !== '') {
        $stmt->bind_param("isssi", $student_id, $search_date, $search_date, $search_time_slot, $search_time_slot);
    } else {
        $stmt->bind_param("i", $student_id);
    }
    $stmt->execute();
    $pending_requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    error_log("Pending requests error: " . $e->getMessage());
    $pending_requests = [];
    $error_message = $error_message ?: "Failed to load pending requests.";
    $_SESSION['error'] = $error_message;
}

// Get current/upcoming sessions
try {
    $query = "
        SELECT s.session_id, s.start_datetime, s.end_datetime, s.status, s.cancellation_reason,
               u.user_id as tutor_id, u.first_name as tutor_first_name, u.last_name as tutor_last_name, 
               u.email as tutor_email, u.profile_image as tutor_image,
               c.course_name,
               l.location_name,
               r.rating, r.comment  
        FROM session s
        JOIN user u ON s.tutor_id = u.user_id
        JOIN course c ON s.course_id = c.id
        LEFT JOIN location l ON s.location_id = l.location_id
        LEFT JOIN review r ON s.session_id = r.session_id AND r.student_id = ?
        WHERE s.student_id = ? AND s.start_datetime >= NOW()
    ";
    if ($search_date !== '' || $search_time_slot !== '') {
        $query .= " AND (? = '' OR DATE(s.start_datetime) = ?)
                    AND (? = '' OR TIME_FORMAT(TIME(s.start_datetime), '%H:%i') = SUBSTRING_INDEX(?, '-', 1))";
    }
    $query .= " ORDER BY s.start_datetime ASC";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("Current sessions prepare failed: " . $conn->error);
        throw new Exception("Database error: Unable to fetch current sessions.");
    }
    if ($search_date !== '' || $search_time_slot !== '') {
        $stmt->bind_param("iisssi", $student_id, $student_id, $search_date, $search_date, $search_time_slot, $search_time_slot);
    } else {
        $stmt->bind_param("ii", $student_id, $student_id);
    }
    $stmt->execute();
    $current_sessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    error_log("Current sessions error: " . $e->getMessage());
    $current_sessions = [];
    $error_message = $error_message ?: "Failed to load current sessions.";
    $_SESSION['error'] = $error_message;
}

// Get session history (past sessions)
try {
    $query = "
        SELECT s.session_id, s.start_datetime, s.end_datetime, s.status, s.cancellation_reason,
               u.user_id as tutor_id, u.first_name as tutor_first_name, u.last_name as tutor_last_name, 
               u.email as tutor_email, u.profile_image as tutor_image,
               c.course_name,
               l.location_name,
               r.rating, r.comment
        FROM session s
        JOIN user u ON s.tutor_id = u.user_id
        JOIN course c ON s.course_id = c.id
        LEFT JOIN location l ON s.location_id = l.location_id
        LEFT JOIN review r ON s.session_id = r.session_id AND r.student_id = ?
        WHERE s.student_id = ? AND (s.start_datetime < NOW() OR s.status IN ('completed', 'cancelled'))
    ";
    if ($search_date !== '' || $search_time_slot !== '') {
        $query .= " AND (? = '' OR DATE(s.start_datetime) = ?)
                    AND (? = '' OR TIME_FORMAT(TIME(s.start_datetime), '%H:%i') = SUBSTRING_INDEX(?, '-', 1))";
    }
    $query .= " ORDER BY s.start_datetime DESC";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("Past sessions prepare failed: " . $conn->error);
        throw new Exception("Database error: Unable to fetch past sessions.");
    }
    if ($search_date !== '' || $search_time_slot !== '') {
        $stmt->bind_param("iisssi", $student_id, $student_id, $search_date, $search_date, $search_time_slot, $search_time_slot);
    } else {
        $stmt->bind_param("ii", $student_id, $student_id);
    }
    $stmt->execute();
    $past_sessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    error_log("Past sessions error: " . $e->getMessage());
    $past_sessions = [];
    $error_message = $error_message ?: "Failed to load past sessions.";
    $_SESSION['error'] = $error_message;
}

// Get tutor statistics
try {
    $stmt = $conn->prepare("
        SELECT u.user_id, u.first_name, u.last_name, u.profile_image,
               COUNT(s.session_id) as total_sessions,
               SUM(CASE WHEN s.status = 'completed' THEN 1 ELSE 0 END) as completed_sessions,
               SUM(CASE WHEN s.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_sessions,
               MAX(s.start_datetime) as last_session_date
        FROM user u
        JOIN session s ON u.user_id = s.tutor_id
        WHERE s.student_id = ?
        GROUP BY u.user_id, u.first_name, u.last_name, u.profile_image
        ORDER BY last_session_date DESC
    ");
    if (!$stmt) {
        error_log("Tutor stats prepare failed: " . $conn->error);
        throw new Exception("Database error: Unable to fetch tutor statistics.");
    }
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $tutor_stats_raw = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $tutor_stats = [];
    foreach ($tutor_stats_raw as $tutor) {
        $tutor_stats[$tutor['user_id']] = $tutor;
    }
} catch (Exception $e) {
    error_log("Tutor stats error: " . $e->getMessage());
    $tutor_stats = [];
}

// Get tutor details
        $tutor_details = [];
        if (!empty($tutor_stats)) {
            try {
                $tutor_ids = array_keys($tutor_stats);
                $placeholders = implode(',', array_fill(0, count($tutor_ids), '?'));
        $stmt = $conn->prepare("
            SELECT user_id, major
            FROM tutorprofile
            WHERE user_id IN ($placeholders)
        ");
        if (!$stmt) {
            error_log("Tutor details prepare failed: " . $conn->error);
            throw new Exception("Database error: Failed to fetch tutor details.");
        }
        $stmt->bind_param(str_repeat('i', count($tutor_ids)), ...$tutor_ids);
        $stmt->execute();
        $tutor_details_raw = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($tutor_details_raw as $detail) {
            $tutor_details[$detail['user_id']] = $detail;
        }
    } catch (Exception $e) {
        error_log("Tutor details error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Sessions - Peer Tutoring System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="css/stud_session.css">
    <style>
        .alert {
            padding: 16px;
            margin-bottom: 24px;
            border-radius: 8px;
            font-size: 1rem;
            display: flex;
            align-items: center;
            position: relative;
            transition: opacity 0.3s ease;
            width: 100%;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }
        .alert-success {
            background-color: rgba(196, 214, 39, 0.1);
            border-left: 4px solid #C4D600;
            color: #2c5c00;
        }
        .alert-error {
            background-color: rgba(255, 77, 77, 0.1);
            border-left: 4px solid #dc3545;
            color: #c82333;
        }
        .alert i {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        .alert::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: currentColor;
            opacity: 0.2;
        }
        .search-form {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        .search-form input[type="date"],
        .search-form select {
            padding: 0.75rem;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 1rem;
            background-color: #fff;
            min-width: 150px;
        }
        .search-form button {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: background-color 0.2s;
        }
        .search-form .btn-search {
            background-color: #00AEEF;
            color: white;
        }
        .search-form .btn-search:hover {
            background-color: #0099cc;
        }
        .search-form .btn-clear {
            background-color: #e0e0e0;
            color: #2B3990;
        }
        .search-form .btn-clear:hover {
            background-color: #d0d0d0;
        }
    </style>
</head>
<body>
    <?php
    // Check if header file exists before including
    $header_file = 'header/stud_head.php';
    if (file_exists($header_file)) {
        include $header_file;
    } else {
        error_log("Header file not found: $header_file");
        echo '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Header file not found. Please contact support.</div>';
    }
    ?>

    <!-- Main Content -->
    <main class="main">
        <div class="page-header">
            <h1><i class="fas fa-calendar-alt"></i> My Tutoring Sessions</h1>
            <p>Manage your pending, current, and past tutoring sessions</p>
        </div>

        <!-- Alerts -->
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Search Form -->
        <form method="post" action="student_sessions.php" class="search-form">
            <input type="date" name="search_date" value="<?php echo htmlspecialchars($search_date); ?>">
            <select name="search_time_slot">
                <option value="">Select Time Slot</option>
                <option value="08:00-10:00" <?php echo $search_time_slot === '08:00-10:00' ? 'selected' : ''; ?>>08:00 - 10:00</option>
                <option value="10:00-12:00" <?php echo $search_time_slot === '10:00-12:00' ? 'selected' : ''; ?>>10:00 - 12:00</option>
                <option value="12:00-15:00" <?php echo $search_time_slot === '12:00-15:00' ? 'selected' : ''; ?>>12:00 - 15:00</option>
            </select>
            <input type="hidden" name="active_tab" value="<?php echo htmlspecialchars($active_tab); ?>">
            <button type="submit" name="search" class="btn btn-search"><i class="fas fa-search"></i> Search</button>
            <button type="submit" name="clear_search" class="btn btn-clear"><i class="fas fa-undo"></i> Clear</button>
        </form>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab <?php echo $active_tab === 'pending' ? 'active' : ''; ?>" onclick="showTab('pending')">
                <i class="fas fa-hourglass-half"></i>
                Pending Requests (<?php echo count($pending_requests); ?>)
            </button>
            <button class="tab <?php echo $active_tab === 'current' ? 'active' : ''; ?>" onclick="showTab('current')">
                <i class="fas fa-clock"></i>
                Current & Upcoming (<?php echo count($current_sessions); ?>)
            </button>
            <button class="tab <?php echo $active_tab === 'history' ? 'active' : ''; ?>" onclick="showTab('history')">
                <i class="fas fa-history"></i>
                Session History (<?php echo count($past_sessions); ?>)
            </button>
        </div>

        <!-- Pending Requests Tab -->
        <div id="pending-tab" class="tab-content <?php echo $active_tab === 'pending' ? 'active' : ''; ?>">
            <div class="session-list">
                <?php if (empty($pending_requests)): ?>
                    <div class="empty-state">
                        <i class="fas fa-hourglass-start"></i>
                        <h3>No pending session requests</h3>
                        <p>You don't have any pending session requests.</p>
                        <a href="find_tutors.php" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i>
                            Find a Tutor
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($pending_requests as $request): ?>
                        <div class="session-card">
                            <div class="session-tutor">
                                <div class="session-avatar">
                                    <?php if ($request['tutor_image']): ?>
                                        <img src="<?php echo htmlspecialchars($request['tutor_image']); ?>"
                                            alt="<?php echo htmlspecialchars($request['tutor_first_name']); ?>">
                                    <?php else: ?>
                                        <?php echo strtoupper(substr($request['tutor_first_name'], 0, 1)); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="session-details">
                                <h3><?php echo htmlspecialchars($request['tutor_first_name'] . ' ' . $request['tutor_last_name']); ?></h3>
                                <div class="session-time">
                                    <i class="fas fa-clock"></i>
                                    <span><?php echo date('M j, Y', strtotime($request['selected_date'])); ?> (<?php echo htmlspecialchars($request['time_slot']); ?>)</span>
                                </div>
                                <div class="session-course">
                                    <i class="fas fa-book"></i>
                                    <span><?php echo htmlspecialchars($request['course_name']); ?></span>
                                </div>
                                <?php if ($request['location_name']): ?>
                                    <div class="session-location">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?php echo htmlspecialchars($request['location_name']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($request['notes']): ?>
                                    <p><strong>Notes:</strong> <?php echo htmlspecialchars($request['notes']); ?></p>
                                <?php endif; ?>
                                <span class="status-badge status-<?php echo $request['status']; ?>">
                                    <?php echo ucfirst($request['status']); ?>
                                </span>
                            </div>
                            <div class="session-actions">
                                <button class="btn btn-outline" onclick="viewTutorDetails(<?php echo $request['tutor_id']; ?>)">
                                    <i class="fas fa-user"></i>
                                    View Tutor
                                </button>
                                <button class="btn btn-danger" onclick="cancelSession(<?php echo $request['request_id']; ?>, 'request')">
                                    <i class="fas fa-times"></i>
                                    Cancel
                                </button>
                                <a href="messages.php?tutor_id=<?php echo $request['tutor_id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-envelope"></i>
                                    Message
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Current Sessions Tab -->
        <div id="current-tab" class="tab-content <?php echo $active_tab === 'current' ? 'active' : ''; ?>">
            <div class="session-list">
                <?php if (empty($current_sessions)): ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <h3>No upcoming sessions</h3>
                        <p>You don't have any scheduled sessions yet.</p>
                        <a href="find_tutors.php" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i>
                            Find a Tutor
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($current_sessions as $session): ?>
                        <div class="session-card">
                            <div class="session-tutor">
                                <div class="session-avatar">
                                    <?php if ($session['tutor_image']): ?>
                                        <img src="<?php echo htmlspecialchars($session['tutor_image']); ?>"
                                            alt="<?php echo htmlspecialchars($session['tutor_first_name']); ?>">
                                    <?php else: ?>
                                        <?php echo strtoupper(substr($session['tutor_first_name'], 0, 1)); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="session-details">
                                <h3><?php echo htmlspecialchars($session['tutor_first_name'] . ' ' . $session['tutor_last_name']); ?></h3>
                                <div class="session-time">
                                    <i class="fas fa-clock"></i>
                                    <span><?php echo date('M j, Y g:i A', strtotime($session['start_datetime'])); ?> - 
                                        <?php echo date('g:i A', strtotime($session['end_datetime'])); ?></span>
                                </div>
                                <div class="session-course">
                                    <i class="fas fa-book"></i>
                                    <span><?php echo htmlspecialchars($session['course_name']); ?></span>
                                </div>
                                <?php if ($session['location_name']): ?>
                                    <div class="session-location">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?php echo htmlspecialchars($session['location_name']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <span class="status-badge status-<?php echo $session['status']; ?>">
                                    <?php echo ucfirst($session['status']); ?>
                                </span>
                            </div>
                            <div class="session-actions">
                                <button class="btn btn-outline" onclick="viewTutorDetails(<?php echo $session['tutor_id']; ?>)">
                                    <i class="fas fa-user"></i>
                                    View Tutor
                                </button>
                                <?php if ($session['status'] === 'scheduled' && strtotime($session['start_datetime']) > strtotime('+24 hours')): ?>
                                    <button class="btn btn-danger" onclick="cancelSession(<?php echo $session['session_id']; ?>, 'session')">
                                        <i class="fas fa-times"></i>
                                        Cancel
                                    </button>
                                <?php endif; ?>
                                <a href="messages.php?tutor_id=<?php echo $session['tutor_id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-envelope"></i>
                                    Message
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Session History Tab -->
        <div id="history-tab" class="tab-content <?php echo $active_tab === 'history' ? 'active' : ''; ?>">
            <div class="session-list">
                <?php if (empty($past_sessions)): ?>
                    <div class="empty-state">
                        <i class="fas fa-history"></i>
                        <h3>No session history</h3>
                        <p>You haven't completed or cancelled any sessions yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($past_sessions as $session): ?>
                        <div class="session-card">
                            <div class="session-tutor">
                                <div class="session-avatar">
                                    <?php if ($session['tutor_image']): ?>
                                        <img src="<?php echo htmlspecialchars($session['tutor_image']); ?>"
                                            alt="<?php echo htmlspecialchars($session['tutor_first_name']); ?>">
                                    <?php else: ?>
                                        <?php echo strtoupper(substr($session['tutor_first_name'], 0, 1)); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="session-details">
                                <h3><?php echo htmlspecialchars($session['tutor_first_name'] . ' ' . $session['tutor_last_name']); ?></h3>
                                <div class="session-time">
                                    <i class="fas fa-clock"></i>
                                    <span><?php echo date('M j, Y g:i A', strtotime($session['start_datetime'])); ?> - 
                                        <?php echo date('g:i A', strtotime($session['end_datetime'])); ?></span>
                                </div>
                                <div class="session-course">
                                    <i class="fas fa-book"></i>
                                    <span><?php echo htmlspecialchars($session['course_name']); ?></span>
                                </div>
                                <?php if ($session['location_name']): ?>
                                    <div class="session-location">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?php echo htmlspecialchars($session['location_name']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <span class="status-badge status-<?php echo $session['status']; ?>">
                                    <?php echo ucfirst($session['status']); ?>
                                </span>
                                <?php if ($session['rating']): ?>
                                    <div class="rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo $i <= $session['rating'] ? 'star' : 'star empty'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <?php if ($session['comment']): ?>
                                        <p><strong>Comment:</strong> <?php echo htmlspecialchars($session['comment']); ?></p>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if ($session['cancellation_reason']): ?>
                                    <p><strong>Cancellation reason:</strong> <?php echo htmlspecialchars($session['cancellation_reason']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="session-actions">
                                <button class="btn btn-outline" onclick="viewTutorDetails(<?php echo $session['tutor_id']; ?>)">
                                    <i class="fas fa-user"></i>
                                    View Tutor
                                </button>
                                <?php if ($session['status'] === 'completed' && !$session['rating']): ?>
                                    <button class="btn btn-warning" onclick="rateSession(<?php echo $session['session_id']; ?>, <?php echo $session['tutor_id']; ?>)">
                                        <i class="fas fa-star"></i>
                                        Rate Session
                                    </button>
                                <?php elseif ($session['rating']): ?>
                                    <span class="btn btn-warning" style="opacity: 0.7; cursor: default;">
                                        <i class="fas fa-check"></i>
                                        Reviewed
                                    </span>
                                <?php endif; ?>
                                <a href="messages.php?tutor_id=<?php echo $session['tutor_id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-envelope"></i>
                                    Message
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Cancel Session Modal -->
        <div id="cancelModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3><i class="fas fa-times-circle"></i> Cancel Session</h3>
                    <button class="close-btn" onclick="closeCancelModal()">×</button>
                </div>
                <form method="POST" id="cancel-form" action="student_sessions.php">
                    <input type="hidden" id="cancel_session_id" name="session_id">
                    <input type="hidden" id="cancel_type" name="type">
                    <input type="hidden" name="cancel_session" value="1">
                    <input type="hidden" name="active_tab" value="<?php echo htmlspecialchars($active_tab); ?>">
                    <div class="form-group">
                        <label for="cancellation_reason">Reason for cancellation:</label>
                        <textarea id="cancellation_reason" name="cancellation_reason" class="form-control"
                            placeholder="Please provide a reason for cancelling this session..." required></textarea>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-outline" onclick="closeCancelModal()">
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-check"></i>
                            Confirm Cancellation
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Rate Session Modal -->
        <div id="rateModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3><i class="fas fa-star"></i> Rate Your Session</h3>
                    <button class="close-btn" onclick="closeRateModal()">×</button>
                </div>
                <form id="rateForm" method="POST" action="submit_review.php">
                    <input type="hidden" id="rate_session_id" name="session_id">
                    <input type="hidden" id="rate_tutor_id" name="tutor_id">
                    <input type="hidden" name="active_tab" value="history">
                    <div class="form-group">
                        <label>Rating:</label>
                        <div class="rating-input">
                            <input type="radio" id="star5" name="rating" value="5" required>
                            <label for="star5" class="star-label">★</label>
                            <input type="radio" id="star4" name="rating" value="4">
                            <label for="star4" class="star-label">★</label>
                            <input type="radio" id="star3" name="rating" value="3">
                            <label for="star3" class="star-label">★</label>
                            <input type="radio" id="star2" name="rating" value="2">
                            <label for="star2" class="star-label">★</label>
                            <input type="radio" id="star1" name="rating" value="1">
                            <label for="star1" class="star-label">★</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="review_comment">Comment (optional):</label>
                        <textarea id="review_comment" name="comment" class="form-control"
                            placeholder="Share your experience with this tutor..."></textarea>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-outline" onclick="closeRateModal()">
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check"></i>
                            Submit Review
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tutor Details Modal -->
        <div id="tutorModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3><i class="fas fa-user"></i> Tutor Details</h3>
                    <button class="close-btn" onclick="closeTutorModal()">×</button>
                </div>
                <div id="tutorDetails">
                    <!-- Content will be loaded via AJAX -->
                </div>
            </div>
        </div>

        <script>
            function showTab(tabName) {
                document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
                document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
                document.getElementById(tabName + '-tab').classList.add('active');
                document.querySelector(`.tab[onclick="showTab('${tabName}')"]`).classList.add('active');

                // Update session via form submission
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'student_sessions.php';
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'active_tab';
                input.value = tabName;
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }

            function toggleDropdown() {
                const dropdown = document.getElementById('userDropdown');
                dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
            }

            document.addEventListener('click', function(event) {
                const userMenu = document.querySelector('.user-menu');
                const dropdown = document.getElementById('userDropdown');
                if (!userMenu.contains(event.target)) {
                    dropdown.style.display = 'none';
                }
            });

            function cancelSession(sessionId, type) {
                document.getElementById('cancel_session_id').value = sessionId;
                document.getElementById('cancel_type').value = type;
                document.getElementById('cancelModal').classList.add('show');
            }

            function closeCancelModal() {
                document.getElementById('cancelModal').classList.remove('show');
                document.getElementById('cancellation_reason').value = '';
            }

            function rateSession(sessionId, tutorId) {
                document.getElementById('rate_session_id').value = sessionId;
                document.getElementById('rate_tutor_id').value = tutorId;
                document.getElementById('rateModal').classList.add('show');
            }

            function closeRateModal() {
                document.getElementById('rateModal').classList.remove('show');
                document.getElementById('rateForm').reset();
            }

            function viewTutorDetails(tutorId) {
                document.getElementById('tutorModal').classList.add('show');
                document.getElementById('tutorDetails').innerHTML = '<div style="text-align: center; padding: 2rem;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
                fetch('get_tutor_details.php?id=' + tutorId)
                    .then(response => response.text())
                    .then(data => document.getElementById('tutorDetails').innerHTML = data)
                    .catch(error => document.getElementById('tutorDetails').innerHTML = '<div style="text-align: center; padding: 2rem; color: var(--danger);"><i class="fas fa-exclamation-triangle"></i> Error loading tutor details</div>');
            }

            function closeTutorModal() {
                document.getElementById('tutorModal').classList.remove('show');
            }

            document.addEventListener('click', function(event) {
                if (event.target.classList.contains('modal')) {
                    event.target.classList.remove('show');
                }
            });

            document.addEventListener('DOMContentLoaded', function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    setTimeout(() => {
                        alert.style.opacity = '0';
                        setTimeout(() => alert.remove(), 300);
                    }, 10000); // Extended to 10 seconds
                });
            });
        </script>
</body>
</html>
<?php $conn->close(); ?>