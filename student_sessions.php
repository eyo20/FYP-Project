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

// Handle session cancellation
if ($_POST && isset($_POST['cancel_session'])) {
    $session_id = (int)$_POST['session_id'];
    $cancellation_reason = trim($_POST['cancellation_reason']);
    
    try {
        // Check if session belongs to this student and can be cancelled
        $stmt = $pdo->prepare("
            SELECT s.*, u.first_name, u.last_name 
            FROM session s
            JOIN user u ON s.tutor_id = u.user_id
            WHERE s.session_id = ? AND s.student_id = ? AND s.status = 'scheduled'
            AND s.start_datetime > DATE_ADD(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->execute([$session_id, $student_id]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($session) {
            // Cancel the session
            $stmt = $pdo->prepare("
                UPDATE session 
                SET status = 'cancelled', 
                    cancellation_reason = ?, 
                    cancelled_by = ?
                WHERE session_id = ?
            ");
            $stmt->execute([$cancellation_reason, $student_id, $session_id]);
            
            // Update availability status back to open
            $stmt = $pdo->prepare("
                UPDATE availability 
                SET status = 'open' 
                WHERE availability_id = ?
            ");
            $stmt->execute([$session['availability_id']]);
            
            $success_message = "Session cancelled successfully.";
        } else {
            $error_message = "Cannot cancel this session.";
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $error_message = "An error occurred while cancelling the session.";
    }
}

// Get current/upcoming sessions
try {
    $stmt = $pdo->prepare("
        SELECT s.session_id, s.start_datetime, s.end_datetime, s.status, s.cancellation_reason,
               u.user_id as tutor_id, u.first_name as tutor_first_name, u.last_name as tutor_last_name, 
               u.email as tutor_email, u.profile_image as tutor_image,
               c.course_code, c.course_name,
               l.location_name,
               r.rating, r.comment
        FROM session s
        JOIN user u ON s.tutor_id = u.user_id
        JOIN course c ON s.course_id = c.course_id
        LEFT JOIN location l ON s.location_id = l.location_id
        LEFT JOIN review r ON s.session_id = r.session_id
        WHERE s.student_id = ? AND s.start_datetime >= NOW()
        ORDER BY s.start_datetime ASC
    ");
    $stmt->execute([$student_id]);
    $current_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $current_sessions = [];
}

// Get past sessions
try {
    $stmt = $pdo->prepare("
        SELECT s.session_id, s.start_datetime, s.end_datetime, s.status, s.cancellation_reason,
               u.user_id as tutor_id, u.first_name as tutor_first_name, u.last_name as tutor_last_name, 
               u.email as tutor_email, u.profile_image as tutor_image,
               c.course_code, c.course_name,
               l.location_name,
               r.rating, r.comment
        FROM session s
        JOIN user u ON s.tutor_id = u.user_id
        JOIN course c ON s.course_id = c.course_id
        LEFT JOIN location l ON s.location_id = l.location_id
        LEFT JOIN review r ON s.session_id = r.session_id
        WHERE s.student_id = ? AND s.start_datetime < NOW()
        ORDER BY s.start_datetime DESC
    ");
    $stmt->execute([$student_id]);
    $past_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $past_sessions = [];
}

// Get tutor statistics for all tutors this student has had sessions with
try {
    $stmt = $pdo->prepare("
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
    $stmt->execute([$student_id]);
    $tutor_stats_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert to associative array with tutor_id as key
    $tutor_stats = [];
    foreach ($tutor_stats_raw as $tutor) {
        $tutor_stats[$tutor['user_id']] = $tutor;
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $tutor_stats = [];
}

// Get tutor details for the tutors
$tutor_details = [];
if (!empty($tutor_stats)) {
    try {
        $tutor_ids = array_keys($tutor_stats);
        $placeholders = str_repeat('?,', count($tutor_ids) - 1) . '?';
        
        $stmt = $pdo->prepare("
            SELECT user_id, major, year
            FROM tutorprofile
            WHERE user_id IN ($placeholders)
        ");
        $stmt->execute($tutor_ids);
        $tutor_details_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($tutor_details_raw as $detail) {
            $tutor_details[$detail['user_id']] = $detail;
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
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
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --success: #27ae60;
            --danger: #e74c3c;
            --warning: #f39c12;
            --light: #ecf0f1;
            --dark: #34495e;
            --gray: #bdc3c7;
            --light-gray: #f8f9fa;
            --dark-gray: #7f8c8d;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light);
            color: var(--dark);
            line-height: 1.6;
        }

        .header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            transition: opacity 0.3s;
        }

        .nav-links a:hover {
            opacity: 0.8;
        }

        .user-menu {
            position: relative;
            cursor: pointer;
        }

        .dropdown {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            min-width: 200px;
            z-index: 1000;
        }

        .dropdown a {
            display: block;
            padding: 0.75rem 1rem;
            color: var(--dark);
            text-decoration: none;
            border-bottom: 1px solid var(--light);
        }

        .dropdown a:hover {
            background-color: var(--light);
        }

        .main {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .page-header h1 {
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            transition: opacity 0.3s;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .tabs {
            display: flex;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .tab {
            flex: 1;
            padding: 1rem 2rem;
            background: white;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            color: var(--dark-gray);
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
        }

        .tab:hover {
            background-color: var(--light-gray);
        }

        .tab.active {
            color: var(--primary);
            border-bottom-color: var(--secondary);
            background-color: var(--light-gray);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .session-list {
            display: grid;
            gap: 1.5rem;
        }

        .session-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 1.5rem;
            align-items: start;
        }

        .session-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(0,0,0,0.15);
        }

        .session-tutor {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .session-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
            overflow: hidden;
        }

        .session-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .session-details {
            flex: 1;
        }

        .session-details h3 {
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .session-time, .session-course, .session-location {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            color: var(--dark-gray);
        }

        .session-time i, .session-course i, .session-location i {
            width: 16px;
            color: var(--secondary);
        }

        .session-actions {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            font-size: 0.9rem;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

                .btn-primary {
            background: var(--secondary);
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-warning:hover {
            background: #e67e22;
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #229954;
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--secondary);
            color: var(--secondary);
        }

        .btn-outline:hover {
            background: var(--secondary);
            color: white;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-scheduled {
            background: #e3f2fd;
            color: #1976d2;
        }

        .status-completed {
            background: #e8f5e8;
            color: #2e7d32;
        }

        .status-cancelled {
            background: #ffebee;
            color: #c62828;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--dark-gray);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--light);
        }

        .modal-header h3 {
            color: var(--primary);
            margin: 0;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--dark-gray);
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .close-btn:hover {
            color: var(--danger);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--light);
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--secondary);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }

        .rating {
            display: flex;
            gap: 0.25rem;
            margin-bottom: 0.5rem;
        }

        .star {
            color: #ffd700;
        }

        .star.empty {
            color: var(--gray);
        }

        @media (max-width: 768px) {
            .header-content {
                padding: 0 1rem;
            }

            .main {
                padding: 0 1rem;
            }

            .session-card {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .session-actions {
                flex-direction: row;
                justify-content: center;
            }

            .tabs {
                flex-direction: column;
            }

            .modal-content {
                margin: 1rem;
                width: calc(100% - 2rem);
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-graduation-cap"></i>
                Peer Tutoring Platform
            </div>
            <nav class="nav-links">
                <a href="student_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="find_tutors.php"><i class="fas fa-search"></i> Find Tutors</a>
                <a href="student_sessions.php"><i class="fas fa-calendar"></i> My Sessions</a>
                <a href="student_messages.php"><i class="fas fa-envelope"></i> Messages</a>
                <div class="user-menu" onclick="toggleDropdown()">
                    <i class="fas fa-user-circle"></i>
                    <?php echo htmlspecialchars($_SESSION['first_name']); ?>
                    <i class="fas fa-chevron-down"></i>
                    <div class="dropdown" id="userDropdown">
                        <a href="student_profile.php"><i class="fas fa-user"></i> Profile</a>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main">
        <div class="page-header">
            <h1><i class="fas fa-calendar-alt"></i> My Tutoring Sessions</h1>
            <p>Manage your current and past tutoring sessions</p>
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
            <button class="tab active" onclick="showTab('current')">
                <i class="fas fa-clock"></i>
                Current & Upcoming (<?php echo count($current_sessions); ?>)
            </button>
            <button class="tab" onclick="showTab('past')">
                <i class="fas fa-history"></i>
                Past Sessions (<?php echo count($past_sessions); ?>)
            </button>
        </div>

        <!-- Current Sessions Tab -->
        <div id="current-tab" class="tab-content active">
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
                                    <span><?php echo htmlspecialchars($session['course_code'] . ' - ' . $session['course_name']); ?></span>
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
                                    <button class="btn btn-danger" onclick="cancelSession(<?php echo $session['session_id']; ?>)">
                                        <i class="fas fa-times"></i>
                                        Cancel
                                    </button>
                                <?php endif; ?>
                                
                                <a href="student_messages.php?tutor_id=<?php echo $session['tutor_id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-envelope"></i>
                                    Message
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Past Sessions Tab -->
        <div id="past-tab" class="tab-content">
            <div class="session-list">
                <?php if (empty($past_sessions)): ?>
                    <div class="empty-state">
                        <i class="fas fa-history"></i>
                        <h3>No past sessions</h3>
                        <p>You haven't completed any sessions yet.</p>
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
                                    <span><?php echo htmlspecialchars($session['course_code'] . ' - ' . $session['course_name']); ?></span>
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
                                
                                                                <?php if ($session['status'] === 'completed' && !$session['rating']): ?>
                                    <button class="btn btn-warning" onclick="rateSession(<?php echo $session['session_id']; ?>, <?php echo $session['tutor_id']; ?>)">
                                        <i class="fas fa-star"></i>
                                        Rate Session
                                    </button>
                                <?php endif; ?>
                                
                                <a href="student_messages.php?tutor_id=<?php echo $session['tutor_id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-envelope"></i>
                                    Message
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Cancel Session Modal -->
    <div id="cancelModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-times-circle"></i> Cancel Session</h3>
                <button class="close-btn" onclick="closeCancelModal()">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" id="cancel_session_id" name="session_id">
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
                <button class="close-btn" onclick="closeRateModal()">&times;</button>
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
                <button class="close-btn" onclick="closeTutorModal()">&times;</button>
            </div>
            <div id="tutorDetails">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>

    <style>
        .rating-input {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 0.25rem;
            margin: 0.5rem 0;
        }

        .rating-input input[type="radio"] {
            display: none;
        }

        .star-label {
            font-size: 2rem;
            color: var(--gray);
            cursor: pointer;
            transition: color 0.3s;
        }

        .rating-input input[type="radio"]:checked ~ .star-label,
        .rating-input .star-label:hover,
        .rating-input .star-label:hover ~ .star-label {
            color: #ffd700;
        }
    </style>

    <script>
        // Tab functionality
        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
        }

        // Dropdown functionality
        function toggleDropdown() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const userMenu = document.querySelector('.user-menu');
            const dropdown = document.getElementById('userDropdown');
            
            if (!userMenu.contains(event.target)) {
                dropdown.style.display = 'none';
            }
        });

        // Cancel session functionality
        function cancelSession(sessionId) {
            document.getElementById('cancel_session_id').value = sessionId;
            document.getElementById('cancelModal').classList.add('show');
        }

        function closeCancelModal() {
            document.getElementById('cancelModal').classList.remove('show');
            document.getElementById('cancellation_reason').value = '';
        }

        // Rate session functionality
        function rateSession(sessionId, tutorId) {
            document.getElementById('rate_session_id').value = sessionId;
            document.getElementById('rate_tutor_id').value = tutorId;
            document.getElementById('rateModal').classList.add('show');
        }

        function closeRateModal() {
            document.getElementById('rateModal').classList.remove('show');
            document.getElementById('rateForm').reset();
        }

        // View tutor details
        function viewTutorDetails(tutorId) {
            document.getElementById('tutorModal').classList.add('show');
            document.getElementById('tutorDetails').innerHTML = '<div style="text-align: center; padding: 2rem;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
            
            fetch('get_tutor_details.php?id=' + tutorId)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('tutorDetails').innerHTML = data;
                })
                .catch(error => {
                    document.getElementById('tutorDetails').innerHTML = '<div style="text-align: center; padding: 2rem; color: var(--danger);"><i class="fas fa-exclamation-triangle"></i> Error loading tutor details</div>';
                });
        }

        function closeTutorModal() {
            document.getElementById('tutorModal').classList.remove('show');
        }

        // Close modals when clicking outside
        document.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        });

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.remove();
                    }, 300);
                }, 5000);
            });
        });
    </script>
</body>
</html>


