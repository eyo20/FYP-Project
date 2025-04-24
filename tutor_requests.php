<?php
session_start();

// Check if user is logged in and is a tutor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tutor') {
    header('Location: login.php');
    exit();
}

// Database connection
require_once 'db_connection.php';

$user_id = $_SESSION['user_id'];

// Get tutor ID
$stmt = $conn->prepare("SELECT id FROM tutor WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // If tutor info not found, redirect to error page
    header('Location: error.php?message=Tutor information not found');
    exit();
}

$tutor = $result->fetch_assoc();
$tutor_id = $tutor['id'];

// Get user basic info
$stmt = $conn->prepare("SELECT first_name, last_name, profile_image FROM user WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$first_name = $user['first_name'];
$last_name = $user['last_name'];
$profile_image = $user['profile_image'];

// Handle request actions
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['request_id'])) {
        $action = $_POST['action'];
        $request_id = $_POST['request_id'];
        
        // Verify request belongs to current tutor
        $stmt = $conn->prepare("SELECT * FROM session_requests WHERE id = ? AND tutor_id = ?");
        $stmt->bind_param("ii", $request_id, $tutor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $request = $result->fetch_assoc();
            
            if ($action === 'accept') {
                // Accept request
                $stmt = $conn->prepare("UPDATE session_requests SET status = 'accepted', updated_at = NOW() WHERE id = ?");
                $stmt->bind_param("i", $request_id);
                
                if ($stmt->execute()) {
                    // Create session record
                    $stmt = $conn->prepare("INSERT INTO sessions (student_id, tutor_id, subject_id, start_time, end_time, status, created_at) 
                                           VALUES (?, ?, ?, ?, ?, 'scheduled', NOW())");
                    $stmt->bind_param("iiiss", $request['student_id'], $tutor_id, $request['subject_id'], $request['preferred_time'], $request['end_time']);
                    
                    if ($stmt->execute()) {
                        $success_message = 'Request accepted successfully!';
                        
                        // Send notification to student
                        $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, content, related_id, created_at) 
                                               VALUES (?, 'request_accepted', 'Your tutoring request has been accepted', ?, NOW())");
                        $stmt->bind_param("ii", $request['student_id'], $request_id);
                        $stmt->execute();
                    } else {
                        $error_message = 'Failed to create session record: ' . $conn->error;
                    }
                } else {
                    $error_message = 'Failed to update request status: ' . $conn->error;
                }
            } elseif ($action === 'reject') {
                // Reject request
                $stmt = $conn->prepare("UPDATE session_requests SET status = 'rejected', updated_at = NOW() WHERE id = ?");
                $stmt->bind_param("i", $request_id);
                
                if ($stmt->execute()) {
                    $success_message = 'Request rejected.';
                    
                    // Send notification to student
                    $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, content, related_id, created_at) 
                                           VALUES (?, 'request_rejected', 'Your tutoring request has been rejected', ?, NOW())");
                    $stmt->bind_param("ii", $request['student_id'], $request_id);
                    $stmt->execute();
                } else {
                    $error_message = 'Failed to update request status: ' . $conn->error;
                }
            } elseif ($action === 'suggest') {
                // Suggest alternative time
                $suggested_time = $_POST['suggested_time'];
                $suggested_end_time = date('Y-m-d H:i:s', strtotime($suggested_time) + (strtotime($request['end_time']) - strtotime($request['preferred_time'])));
                
                $stmt = $conn->prepare("UPDATE session_requests SET status = 'time_suggested', suggested_time = ?, suggested_end_time = ?, updated_at = NOW() WHERE id = ?");
                $stmt->bind_param("ssi", $suggested_time, $suggested_end_time, $request_id);
                
                if ($stmt->execute()) {
                    $success_message = 'Alternative time suggested successfully.';
                    
                    // Send notification to student
                    $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, content, related_id, created_at) 
                                           VALUES (?, 'time_suggested', 'Your tutor has suggested an alternative time for your request', ?, NOW())");
                    $stmt->bind_param("ii", $request['student_id'], $request_id);
                    $stmt->execute();
                } else {
                    $error_message = 'Failed to update request status: ' . $conn->error;
                }
            }
        } else {
            $error_message = 'Invalid request ID or you do not have permission to perform this action.';
        }
    }
}

// Get pending requests
$stmt = $conn->prepare("SELECT sr.*, s.name as subject_name, u.first_name, u.last_name, u.profile_image 
                        FROM session_requests sr 
                        JOIN subjects s ON sr.subject_id = s.id 
                        JOIN students st ON sr.student_id = st.id 
                        JOIN users u ON st.user_id = u.id 
                        WHERE sr.tutor_id = ? AND sr.status = 'pending' 
                        ORDER BY sr.preferred_time ASC");
$stmt->bind_param("i", $tutor_id);
$stmt->execute();
$pending_requests_result = $stmt->get_result();
$pending_requests = [];
while ($row = $pending_requests_result->fetch_assoc()) {
    $pending_requests[] = $row;
}
$pending_count = count($pending_requests);

// Get processed requests
$stmt = $conn->prepare("SELECT sr.*, s.name as subject_name, u.first_name, u.last_name, u.profile_image 
                        FROM session_requests sr 
                        JOIN subjects s ON sr.subject_id = s.id 
                        JOIN students st ON sr.student_id = st.id 
                        JOIN users u ON st.user_id = u.id 
                        WHERE sr.tutor_id = ? AND sr.status != 'pending' 
                        ORDER BY sr.updated_at DESC 
                        LIMIT 20");
$stmt->bind_param("i", $tutor_id);
$stmt->execute();
$processed_requests_result = $stmt->get_result();
$processed_requests = [];
while ($row = $processed_requests_result->fetch_assoc()) {
    $processed_requests[] = $row;
}

// Get unread messages count for notification badge
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM messages WHERE recipient_id = ? AND recipient_type = 'tutor' AND is_read = 0");
$stmt->bind_param("i", $tutor_id);
$stmt->execute();
$result = $stmt->get_result();
$unread_messages = $result->fetch_assoc()['count'];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Requests - PeerLearn</title>
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #00aeef;
            --accent: #c4d600;
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
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .logo span {
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
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
        
        .notification-badge {
            position: absolute;
            top: -8px;
            right: -12px;
            background-color: var(--accent);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
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
            background-color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            overflow: hidden;
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
        
        .page-title {
            margin-bottom: 1.5rem;
            color: var(--primary);
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
            transition: all 0.3s;
        }
        
        .tab.active {
            border-bottom-color: var(--accent);
            color: var(--accent);
        }
        
        .tab:hover {
            background-color: var(--light-gray);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .request-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }
        
        .request-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .request-header {
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            border-bottom: 1px solid var(--gray);
        }
        
        .student-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            overflow: hidden;
        }
        
        .student-info h3 {
            margin: 0;
            font-size: 1.1rem;
        }
        
        .request-body {
            padding: 1rem;
        }
        
        .request-detail {
            margin-bottom: 0.75rem;
            display: flex;
            align-items: flex-start;
        }
        
        .request-detail:last-child {
            margin-bottom: 0;
        }
        
        .detail-icon {
            width: 24px;
            margin-right: 0.75rem;
            color: var(--dark-gray);
        }
        
        .request-actions {
            padding: 1rem;
            border-top: 1px solid var(--gray);
            display: flex;
            gap: 0.5rem;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
            flex: 1;
            text-align: center;
        }
        
        .btn-accept {
            background-color: var(--accent);
            color: white;
        }
        
        .btn-accept:hover {
            background-color: #b3c300;
        }
        
        .btn-reject {
            background-color: #f8f9fa;
            color: #dc3545;
            border: 1px solid #dc3545;
        }
        
        .btn-reject:hover {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-suggest {
            background-color: var(--secondary);
            color: white;
        }
        
        .btn-suggest:hover {
            background-color: #0095cc;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--dark-gray);
        }
        
        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .modal-content {
            background-color: white;
            border-radius: 8px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .modal-header {
            padding: 1rem;
            border-bottom: 1px solid var(--gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            margin: 0;
            color: var(--primary);
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--dark-gray);
        }
        
        .modal-body {
            padding: 1rem;
        }
        
        .modal-footer {
            padding: 1rem;
            border-top: 1px solid var(--gray);
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--gray);
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-accepted {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }
        
        .status-rejected {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        
        .status-time-suggested {
            background-color: rgba(0, 174, 239, 0.1);
            color: var(--secondary);
        }
        
        footer {
            background-color: var(--primary);
            color: white;
            text-align: center;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                padding: 1rem;
            }
            
            .nav-links {
                margin-top: 1rem;
                width: 100%;
                justify-content: space-around;
            }
            
            .request-list {
                grid-template-columns: 1fr;
            }
            
            .request-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <span>PeerLearn</span>
        </div>
        <div class="nav-links">
            <a href="tutor_main_page.php">Schedule Management</a>
            <a href="tutor_profile.php">Profile</a>
            <a href="tutor_requests.php">Appointment Requests<?php if($pending_count > 0): ?><span class="notification-badge"><?php echo $pending_count; ?></span><?php endif; ?></a>
            <a href="tutor_students.php">My Students</a>
            <a href="messages.php">Messages<?php if($unread_messages > 0): ?><span class="notification-badge"><?php echo $unread_messages; ?></span><?php endif; ?></a>
        </div>
        <div class="user-menu">
            <div class="user-avatar">
                <?php if($profile_image): ?>
                <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile" class="profile-image">
                <?php else: ?>
                <?php echo strtoupper(substr($first_name, 0, 1)); ?>
                <?php endif; ?>
            </div>
            <a href="logout.php" style="color: white; text-decoration: none;">Logout</a>
        </div>
    </nav>
    
    <main>
        <h1 class="page-title">Appointment Requests</h1>
        
        <?php if($success_message): ?>
        <div class="alert alert-success">
            <?php echo $success_message; ?>
        </div>
        <?php endif; ?>
        
        <?php if($error_message): ?>
        <div class="alert alert-danger">
            <?php echo $error_message; ?>
        </div>
        <?php endif; ?>
        
        <div class="tabs">
            <div class="tab active" data-tab="pending">Pending Requests (<?php echo $pending_count; ?>)</div>
            <div class="tab" data-tab="processed">Processed Requests</div>
        </div>
        
        <div class="tab-content active" id="pending-tab">
            <?php if(count($pending_requests) > 0): ?>
            <div class="request-list">
                <?php foreach($pending_requests as $request): ?>
                <div class="request-card">
                    <div class="request-header">
                        <div class="student-avatar">
                            <?php if($request['profile_image']): ?>
                            <img src="<?php echo htmlspecialchars($request['profile_image']); ?>" alt="Student" class="profile-image">
                            <?php else: ?>
                            <?php echo strtoupper(substr($request['first_name'], 0, 1)); ?>
                            <?php endif; ?>
                        </div>
                        <div class="student-info">
                            <h3><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></h3>
                            <p><?php echo htmlspecialchars($request['subject_name']); ?></p>
                        </div>
                    </div>
                    <div class="request-body">
                        <div class="request-detail">
                            <div class="detail-icon">📅</div>
                            <div>
                                <strong>Date:</strong> <?php echo date('F j, Y', strtotime($request['preferred_time'])); ?>
                            </div>
                        </div>
                        <div class="request-detail">
                            <div class="detail-icon">⏰</div>
                            <div>
                                <strong>Time:</strong> <?php echo date('g:i A', strtotime($request['preferred_time'])); ?> - <?php echo date('g:i A', strtotime($request['end_time'])); ?>
                            </div>
                        </div>
                        <div class="request-detail">
                            <div class="detail-icon">📝</div>
                            <div>
                                <strong>Notes:</strong> <?php echo htmlspecialchars($request['notes'] ?: 'No additional notes'); ?>
                            </div>
                        </div>
                        <div class="request-detail">
                            <div class="detail-icon">📆</div>
                            <div>
                                <strong>Requested on:</strong> <?php echo date('M j, Y', strtotime($request['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                    <div class="request-actions">
                        <form method="post" action="tutor_requests.php">
                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                            <input type="hidden" name="action" value="accept">
                            <button type="submit" class="btn btn-accept">Accept</button>
                        </form>
                        <form method="post" action="tutor_requests.php">
                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" class="btn btn-reject">Reject</button>
                        </form>
                        <button type="button" class="btn btn-suggest" onclick="openSuggestModal(<?php echo $request['id']; ?>, '<?php echo $request['preferred_time']; ?>')">Suggest Time</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <h3>No Pending Requests</h3>
                <p>You don't have any pending appointment requests at the moment.</p>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="tab-content" id="processed-tab">
            <?php if(count($processed_requests) > 0): ?>
            <div class="request-list">
                <?php foreach($processed_requests as $request): ?>
                <div class="request-card">
                    <div class="request-header">
                        <div class="student-avatar">
                            <?php if($request['profile_image']): ?>
                            <img src="<?php echo htmlspecialchars($request['profile_image']); ?>" alt="Student" class="profile-image">
                            <?php else: ?>
                            <?php echo strtoupper(substr($request['first_name'], 0, 1)); ?>
                            <?php endif; ?>
                        </div>
                        <div class="student-info">
                            <h3><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></h3>
                            <p>
                                <?php echo htmlspecialchars($request['subject_name']); ?>
                                <?php 
                                $status_class = '';
                                $status_text = '';
                                switch($request['status']) {
                                    case 'accepted':
                                        $status_class = 'status-accepted';
                                        $status_text = 'Accepted';
                                        break;
                                    case 'rejected':
                                        $status_class = 'status-rejected';
                                        $status_text = 'Rejected';
                                        break;
                                    case 'time_suggested':
                                        $status_class = 'status-time-suggested';
                                        $status_text = 'Time Suggested';
                                        break;
                                }
                                ?>
                                <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                            </p>
                        </div>
                    </div>
                    <div class="request-body">
                        <div class="request-detail">
                            <div class="detail-icon">📅</div>
                            <div>
                                <strong>Requested Date:</strong> <?php echo date('F j, Y', strtotime($request['preferred_time'])); ?>
                            </div>
                        </div>
                        <div class="request-detail">
                            <div class="detail-icon">⏰</div>
                            <div>
                                <strong>Requested Time:</strong> <?php echo date('g:i A', strtotime($request['preferred_time'])); ?> - <?php echo date('g:i A', strtotime($request['end_time'])); ?>
                            </div>
                        </div>
                        <?php if($request['status'] === 'time_suggested'): ?>
                        <div class="request-detail">
                            <div class="detail-icon">🕒</div>
                            <div>
                                <strong>Suggested Date:</strong> <?php echo date('F j, Y', strtotime($request['suggested_time'])); ?>
                            </div>
                        </div>
                        <div class="request-detail">
                            <div class="detail-icon">🕒</div>
                            <div>
                                <strong>Suggested Time:</strong> <?php echo date('g:i A', strtotime($request['suggested_time'])); ?> - <?php echo date('g:i A', strtotime($request['suggested_end_time'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="request-detail">
                            <div class="detail-icon">📝</div>
                            <div>
                                <strong>Notes:</strong> <?php echo htmlspecialchars($request['notes'] ?: 'No additional notes'); ?>
                            </div>
                        </div>
                        <div class="request-detail">
                            <div class="detail-icon">📆</div>
                            <div>
                                <strong>Processed on:</strong> <?php echo date('M j, Y', strtotime($request['updated_at'])); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">📋</div>
                <h3>No Processed Requests</h3>
                <p>You haven't processed any appointment requests yet.</p>
            </div>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- Suggest Time Modal -->
    <div class="modal" id="suggest-time-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Suggest Alternative Time</h3>
                <button class="modal-close" onclick="closeModal('suggest-time-modal')">×</button>
            </div>
            <form method="post" action="tutor_requests.php">
                <input type="hidden" name="action" value="suggest">
                <input type="hidden" name="request_id" id="suggest-request-id">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="suggested_time">Suggested Date and Time</label>
                        <input type="datetime-local" id="suggested_time" name="suggested_time" class="form-control" required>
                    </div>
                </div>
                
                <div class="modal-footer">
                <button type="button" class="btn btn-reject" onclick="closeModal('suggest-time-modal')">Cancel</button>
                    <button type="submit" class="btn btn-suggest">Send Suggestion</button>
                </div>
            </form>
        </div>
    </div>
    
    <footer>
        <p>&copy; <?php echo date('Y'); ?> PeerLearn Platform. All rights reserved.</p>
    </footer>
    
    <script>
        // Tab switching functionality
        const tabs = document.querySelectorAll('.tab');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const tabId = tab.getAttribute('data-tab');
                
                // Remove active class from all tabs and contents
                tabs.forEach(t => t.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                
                // Add active class to current tab and content
                tab.classList.add('active');
                document.getElementById(`${tabId}-tab`).classList.add('active');
            });
        });
        
        // Modal functionality
        function openModal(id) {
            document.getElementById(id).style.display = 'flex';
        }
        
        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }
        
        function openSuggestModal(requestId, preferredTime) {
            document.getElementById('suggest-request-id').value = requestId;
            
            // Set min datetime to now
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            
            const minDatetime = `${year}-${month}-${day}T${hours}:${minutes}`;
            document.getElementById('suggested_time').min = minDatetime;
            
            // Set default value to preferred time if it's in the future
            const preferred = new Date(preferredTime);
            if (preferred > now) {
                const prefYear = preferred.getFullYear();
                const prefMonth = String(preferred.getMonth() + 1).padStart(2, '0');
                const prefDay = String(preferred.getDate()).padStart(2, '0');
                const prefHours = String(preferred.getHours()).padStart(2, '0');
                const prefMinutes = String(preferred.getMinutes()).padStart(2, '0');
                
                const defaultDatetime = `${prefYear}-${prefMonth}-${prefDay}T${prefHours}:${prefMinutes}`;
                document.getElementById('suggested_time').value = defaultDatetime;
            }
            
            openModal('suggest-time-modal');
        }
        
        // Close modals when clicking outside
        window.addEventListener('click', (event) => {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>

