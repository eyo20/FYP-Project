<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit();
}

$student_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

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
            } else {
                $error_message = "Cannot cancel this session.";
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
            } else {
                $error_message = "Cannot cancel this session request.";
            }
        }
    } catch (Exception $e) {
        error_log("Cancellation error: " . $e->getMessage());
        $error_message = "An error occurred while cancelling the session.";
    }
}

// Get pending session requests
try {
    $stmt = $conn->prepare("
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
        ORDER BY sr.created_at ASC
    ");
    if (!$stmt) {
        error_log("Pending requests prepare failed: " . $conn->error);
        throw new Exception("Database error: Unable to fetch pending requests.");
    }
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $pending_requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    error_log("Pending requests error: " . $e->getMessage());
    $pending_requests = [];
    $error_message = $error_message ?: "Failed to load pending requests.";
}

// Get current/upcoming sessions
try {
    $stmt = $conn->prepare("
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
        LEFT JOIN review r ON s.session_id = r.session_id
        WHERE s.student_id = ? AND s.start_datetime >= NOW()
        ORDER BY s.start_datetime ASC
    ");
    if (!$stmt) {
        error_log("Current sessions prepare failed: " . $conn->error);
        throw new Exception("Database error: Unable to fetch current sessions.");
    }
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $current_sessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    error_log("Current sessions error: " . $e->getMessage());
    $current_sessions = [];
    $error_message = $error_message ?: "Failed to load current sessions.";
}

// Get session history (past sessions)
try {
    $stmt = $conn->prepare("
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
        LEFT JOIN review r ON s.session_id = r.session_id
        WHERE s.student_id = ? AND (s.start_datetime < NOW() OR s.status IN ('completed', 'cancelled'))
        ORDER BY s.start_datetime DESC
    ");
    if (!$stmt) {
        error_log("Past sessions prepare failed: " . $conn->error);
        throw new Exception("Database error: Unable to fetch past sessions.");
    }
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $past_sessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    error_log("Past sessions error: " . $e->getMessage());
    $past_sessions = [];
    $error_message = $error_message ?: "Failed to load past sessions.";
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
            SELECT user_id, major, tutor_id
            FROM tutor_profile
            WHERE user_id IN ($placeholders)
        ");
        if (!$stmt) {
            error_log("Tutor details prepare failed: " . $conn->error);
            throw new Exception("Database error: Unable to fetch tutor details.");
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
    <title>My Sessions - Peer Tutoring Platform</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/stud_session.css">
</head>

<body>
    <?php include 'header/stud_head.php'; ?>


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

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab active" onclick="showTab('pending')">
                <i class="fas fa-hourglass-half"></i>
                Pending Requests (<?php echo count($pending_requests); ?>)
            </button>
            <button class="tab" onclick="showTab('current')">
                <i class="fas fa-clock"></i>
                Current & Upcoming (<?php echo count($current_sessions); ?>)
            </button>
            <button class="tab" onclick="showTab('history')">
                <i class="fas fa-history"></i>
                Session History (<?php echo count($past_sessions); ?>)
            </button>
        </div>

        <!-- Pending Requests Tab -->
        <div id="pending-tab" class="tab-content active">
            <div class="session-list">
                <?php if (empty($pending_requests)): ?>
                    <div class="empty-state">
                        <i class="fas fa-hourglass-start"></i>
                        <h3>No pending session requests</h3>
                        <p>You don't have any pending session requests.</p>
                        <a href="find_tutors.php" class="btn btn-primary">
                            <i class="fas fa-search"></i>
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
                                    <span><?php echo date('M j, Y', strtotime($request['selected_date'])); ?> (<?php echo htmlspecialchars($request['time_slot']); ?> )</span>
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
        <div id="current-tab" class="tab-content">
            <div class="session-list">
                <?php if (empty($current_sessions)): ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <h3>No upcoming sessions</h3>
                        <p>You don't have any scheduled sessions yet.</p>
                        <a href="find_tutors.php" class="btn btn-primary">
                            <i class="fas fa-search"></i>
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
        <div id="history-tab" class="tab-content">
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

                                <button class="btn btn-warning" onclick="rateSession(<?php echo $session['session_id']; ?>, <?php echo $session['tutor_id']; ?>)">
                                    <i class="fas fa-star"></i>
                                    Rate Session
                                </button>

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
                <form method="POST" id="cancel-form" action="">
                    <input type="hidden" id="cancel_session_id" name="session_id">
                    <input type="hidden" id="cancel_type" name="type">
                    <input type="hidden" name="cancel_session" value="1">
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
                    <div class="form-group">
                        <label>Rating:</label>
                        <div class="rating-input">
                            <input type="radio" id="star5" name="rating" value="5">
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
                event.target.classList.add('active');
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
                    }, 5000);
                });
            });
        </script>
</body>

</html>
<?php $conn->close(); ?>