<?php
session_start();
require_once "db_connection.php";

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Initialize filter variables
$filter_user_id = '';
$filter_condition = '';

// Handle message filter if user is selected
if (isset($_GET['filter_user']) && !empty($_GET['filter_user'])) {
    $filter_user_id = $conn->real_escape_string($_GET['filter_user']);
    $filter_condition = " AND (m.sender_id = '$filter_user_id' OR m.receiver_id = '$filter_user_id')";
}

// Get all messages (both sent and received by admin) with optional filter
$query = "SELECT m.*, 
                 sender.username AS sender_name,
                 receiver.username AS receiver_name
          FROM message m
          JOIN user sender ON m.sender_id = sender.user_id
          JOIN user receiver ON m.receiver_id = receiver.user_id
          WHERE (m.sender_id = ? OR m.receiver_id = ?)
          $filter_condition
          ORDER BY m.sent_datetime DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $_SESSION['user_id'], $_SESSION['user_id']);
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Mark messages as read when viewed
if (!empty($messages)) {
    $unread_ids = array_column(array_filter($messages, function($msg) {
        return $msg['receiver_id'] == $_SESSION['user_id'] && $msg['is_read'] == 0;
    }), 'message_id');
    
    if (!empty($unread_ids)) {
        $placeholders = implode(',', array_fill(0, count($unread_ids), '?'));
        $types = str_repeat('i', count($unread_ids));
        $mark_read_query = "UPDATE message SET is_read = 1 WHERE message_id IN ($placeholders)";
        $stmt = $conn->prepare($mark_read_query);
        $stmt->bind_param($types, ...$unread_ids);
        $stmt->execute();
        $stmt->close();
    }
}

// Get all users for the filter dropdown and recipient dropdown
$users_query = "SELECT user_id, username, role FROM user WHERE user_id != ? ORDER BY role, username";
$stmt = $conn->prepare($users_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle message deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_message'])) {
    $message_id = $_POST['message_id'];
    $delete_query = "DELETE FROM message WHERE message_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $message_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_message.php");
    exit();
}

// Handle sending new message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $receiver_id = $_POST['receiver_id'];
    $content = trim($_POST['content']);
    
    if (!empty($content)) {
        $insert_query = "INSERT INTO message (sender_id, receiver_id, content, sent_datetime, is_read)
                         VALUES (?, ?, ?, NOW(), 0)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("iis", $_SESSION['user_id'], $receiver_id, $content);
        $stmt->execute();
        $stmt->close();
        header("Location: admin_message.php");
        exit();
    }
}

    $search_term = '';
$filtered_users = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search_recipient'])) {
    $search_term = trim($_POST['search_recipient']);
    
    // Get filtered users based on search
    $search_query = "SELECT user_id, username, role FROM user 
                    WHERE user_id != ? AND (username LIKE ? OR role LIKE ?)
                    ORDER BY role, username";
    $stmt = $conn->prepare($search_query);
    $search_param = "%$search_term%";
    $stmt->bind_param("iss", $_SESSION['user_id'], $search_param, $search_param);
    $stmt->execute();
    $filtered_users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Keep your original users query for when there's no search
$users_query = "SELECT user_id, username, role FROM user WHERE user_id != ? ORDER BY role, username";
$stmt = $conn->prepare($users_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get all users for the recipient dropdown
$users_query = "SELECT user_id, username, role FROM user WHERE user_id != ? ORDER BY role, username";
$stmt = $conn->prepare($users_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Messages</title>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp" rel="stylesheet" />
    <link rel="stylesheet" href="studentstyle.css">
    <style>
        /* Main layout styles */
        body {
            display: flex;
            margin: 0;
            font-family: poppins, sans-serif;
            background-color: #f5f7fa;
            min-height: 100vh;
        }
        
        aside {
            width: 210px;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            height: 100vh;
        }
        
        aside .top {
            margin-left: 0;
            padding-left: 1rem;
        }
        aside .logo {
            display: flex;
            gap: 0.8rem;
        }

        aside .logo img{
            width: 2rem;
            height: 2rem;
        }

        aside .close{
            display: none;
        }

        .main-content {
            flex: 1;
            padding: 30px;
            margin-left: 10px;
        }
        
        /* Message center specific styles - LARGER VERSION */
        .message-center-container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .message-center-container h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            color:rgb(0, 0, 0);
        }
        
        .message-container {
            display: flex;
            gap: 40px;
            margin-top: 20px;
        }
        
        .message-list, .message-compose {
            flex: 1;
            background-color: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            min-height: 600px;
        }
        
        .message-list h2, .message-compose h2 {
            font-size: 1.8rem;
            margin-bottom: 20px;
            color: #7380ec;
        }
        
        .no-messages {
            text-align: center;
            padding: 50px 0;
            font-size: 1.2rem;
            color: #666;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        select, textarea {
            width: 100%;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        textarea {
            min-height: 200px;
            resize: vertical;
        }
        
        /* Unified button styles */
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            text-align: center;
            display: inline-block;
            margin-top: 10px;
        }
        
        .btn-send {
            background-color: #7380ec;
            color: white;
            width: 100%;
        }
        
        .btn-send:hover {
            background-color: #5a6bd8;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .btn-delete {
            background-color: #ff4757;
            color: white;
        }
        
        .btn-delete:hover {
            background-color: #e8413a;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        /* Message styling */
        .message {
            padding: 20px;
            margin-bottom: 20px;
            background-color: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #ddd;
        }
        
        .message.unread {
            border-left-color:rgb(236, 115, 115);
            background-color: #f0f4ff;
        }
        
        .message-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .message-time {
            color: #666;
            font-size: 0.9rem;
        }
        
        .message-content {
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        .message-actions {
            text-align: right;
        }

        .btn-search {
            background-color: #6c757d;
            color: white;
            width: 100%;
            margin-bottom: 15px;
        }

        .btn-search:hover {
            background-color: #5a6268;
        }

        .message-filter {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .message-filter label {
            margin-bottom: 0;
            font-weight: 600;
        }
        
        .message-filter select {
            flex: 1;
            max-width: 300px;
        }
        
        .btn-filter {
            background-color: #6c757d;
            color: white;
            padding: 10px 15px;
        }
        
        .btn-filter:hover {
            background-color: #5a6268;
        }
        
        .btn-clear {
            background-color: #f8f9fa;
            color: #6c757d;
            border: 1px solid #ddd;
        }
        
        .btn-clear:hover {
            background-color: #e9ecef;
        }
    </style>
</head>
<body>
    <aside>
        <div class="top">
            <div class="logo">
                <img src="image/logo.png">
                <h2>PEER<span class="danger">LEARN</span></h2>
            </div>
            <div class="close" id="close-btn">
                <span class="material-symbols-sharp">close</span>
            </div>
        </div>

        <div class="sidebar">
            <a href="admin.html"><span class="material-symbols-sharp">grid_view</span><h3>Dashboard</h3></a>
            <a href="#"></a>
            <a href="admin_staff.php"><span class="material-symbols-sharp">badge</span><h3>Staff</h3></a>
            <a href="admin_student.php"><span class="material-symbols-sharp">person</span><h3>Students</h3></a>
            <a href="admin_tutors.php"><span class="material-symbols-sharp">eyeglasses</span><h3>Tutors</h3></a>
            <a href="admin_course.php"><span class="material-symbols-sharp">school</span><h3>Courses</h3></a>
            <a href="admin_message.php"class="active"><span class="material-symbols-sharp">chat</span><h3>Messages</h3></a>
            <a href="admin_report.php"><span class="material-symbols-sharp">description</span><h3>Reports</h3></a>
            <a href="home_page.html"><span class="material-symbols-sharp">logout</span><h3>Logout</h3></a>
        </div>
    </aside>
    
    <div class="main-content">
        <div class="message-center-container">
            <h1>Message Center</h1>


            
            <div class="message-container">
                <div class="message-list">
                    <h2>Your Messages</h2>
                    
                     <form method="GET" class="message-filter">
                            <select name="filter_user" id="filter_user">
                                <option value="">Filter by user...</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['user_id']; ?>" 
                                        <?php echo ($filter_user_id == $user['user_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['username']); ?>
                                        (<?php echo ucfirst($user['role']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-filter">Filter</button>
                            <?php if (!empty($filter_user_id)): ?>
                                <a href="admin_message.php" class="btn btn-clear">Clear</a>
                            <?php endif; ?>
                        </form>
                 
                    <?php if (empty($messages)): ?>
                        <div class="no-messages">
                            <p>No messages found.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $message): ?>
                            <div class="message <?php echo ($message['receiver_id'] == $_SESSION['user_id'] && $message['is_read'] == 0) ? 'unread' : ''; ?>">
                                <div class="message-header">
                                    <span>
                                        <?php if ($message['sender_id'] == $_SESSION['user_id']): ?>
                                            To: <span class="message-receiver"><?php echo htmlspecialchars($message['receiver_name']); ?></span>
                                        <?php else: ?>
                                            From: <span class="message-sender"><?php echo htmlspecialchars($message['sender_name']); ?></span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="message-time"><?php echo date('M j, Y g:i a', strtotime($message['sent_datetime'])); ?></span>
                                </div>
                                <div class="message-content">
                                    <?php echo nl2br(htmlspecialchars($message['content'])); ?>
                                </div>
                                <div class="message-actions">
                                    <?php if ($message['sender_id'] == $_SESSION['user_id'] || $message['receiver_id'] == $_SESSION['user_id']): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="message_id" value="<?php echo $message['message_id']; ?>">
                                            <button type="submit" name="delete_message" class="btn btn-delete">Delete Message</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                
       <div class="message-compose">
    <h2>Compose New Message</h2>
    
    <form method="POST">
        <div class="form-group">
            
            <label for="receiver_id" style="margin-top: 15px;">Select Recipient:</label>
            <select name="receiver_id" id="receiver_id" required>
                <option value="">Select a recipient</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?php echo $user['user_id']; ?>">
                        <?php echo htmlspecialchars($user['username']); ?>
                        <span class="role-badge <?php echo strtolower($user['role']); ?>-badge">
                            (<?php echo ucfirst($user['role']); ?>)
                        </span>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="content">Message:</label>
            <textarea name="content" id="content" required placeholder="Type your message here..."></textarea>
        </div>
        
        <button type="submit" name="send_message" class="btn btn-send">Send Message</button>
    </form>
</div>

            </div>
        </div>
    </div>
</body>
</html>