<?php
session_start();
require_once "db_connection.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get current user's info
$current_user_query = "SELECT user_id, first_name, last_name, profile_image, role FROM user WHERE user_id = ?";
$stmt = $conn->prepare($current_user_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$current_user_result = $stmt->get_result();
$current_user = $current_user_result->fetch_assoc();
$stmt->close();

// Check if user can post in community
$can_post = true; // Default to true, you can add restrictions if needed

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $content = trim($_POST['message']);
    
    if (!empty($content)) {
        // Check for duplicate messages within 5 seconds
        $check_query = "SELECT message_id FROM message 
                       WHERE sender_id = ? 
                       AND content = ? 
                       AND is_community = 1
                       AND sent_datetime > DATE_SUB(NOW(), INTERVAL 5 SECOND)
                       LIMIT 1";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("is", $_SESSION['user_id'], $content);
        $stmt->execute();
        $check_result = $stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            // Insert community message (receiver_id is NULL for community messages)
            $insert_query = "INSERT INTO message (sender_id, receiver_id, content, sent_datetime, is_read, is_community) 
                           VALUES (?, NULL, ?, NOW(), 0, 1)";
            $stmt = $conn->prepare($insert_query);
            
            if ($stmt->bind_param("is", $_SESSION['user_id'], $content) && $stmt->execute()) {
                // Success - redirect to prevent form resubmission
                header("Location: community_chat.php");
                exit();
            } else {
                error_log("Database error: " . $stmt->error);
                $_SESSION['error'] = "Failed to send message. Please try again.";
            }
        } else {
            $_SESSION['error'] = "You've recently sent the same message. Please wait a few seconds.";
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Message cannot be empty";
    }
}

// Get all community messages (most recent 50)
$messages_query = "SELECT 
                    m.message_id,
                    m.content,
                    m.sent_datetime,
                    u.user_id,
                    u.first_name,
                    u.last_name,
                    u.profile_image,
                    u.role
                  FROM message m
                  JOIN user u ON m.sender_id = u.user_id
                  WHERE m.is_community = 1
                  ORDER BY m.sent_datetime DESC
                  LIMIT 50";
$messages_result = $conn->query($messages_query);
$messages = $messages_result ? $messages_result->fetch_all(MYSQLI_ASSOC) : [];

// Determine back URL based on role
$back_url = match($current_user['role']) {
    'student' => 'messages_list.php',
    'tutor' => 'messages_list.php',
    default => 'admin_message.php'
};

// Display any errors
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Community Chat</title>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="studentstyle.css">
    <style>
        :root {
            --primary: #2B3990;
            --secondary: #00AEEF;
            --accent: #C4D600;
            --light-gray: #f5f7fa;
            --gray: #e9ecef;
            --dark-gray: #6c757d;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }
        
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: var(--light-gray);
            padding-top: 0; 
        }
        
        .main-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            height: 70px;
        }
        
        .main-content {
            flex: 1;
            padding: 30px;
            margin-top: 70px;
        }
        
        .chat-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        
        .chat-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .chat-title {
            font-size: 1.8rem;
            color: var(--primary);
        }
        
        .chat-messages {
            max-height: 500px;
            overflow-y: auto;
            margin-bottom: 20px;
            padding: 15px;
            background: var(--light-gray);
            border-radius: 10px;
        }
        
        .message {
            margin-bottom: 15px;
            padding: 15px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .message-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
        }
        
        .message-default-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
            margin-right: 10px;
        }
        
        .message-sender-info {
            flex-grow: 1;
        }
        
        .message-sender {
            font-weight: 600;
            color: var(--primary);
        }
        
        .message-time {
            color: var(--dark-gray);
            font-size: 0.8rem;
            margin-left: 10px;
        }
        
        .message-content {
            line-height: 1.5;
            margin-left: 50px;
        }
        
        .chat-form {
            display: flex;
            margin-top: 20px;
        }
        
        .chat-form textarea {
            flex: 1;
            padding: 15px;
            border: 1px solid var(--gray);
            border-radius: 8px;
            resize: none;
            min-height: 80px;
        }
        
        .chat-form button {
            margin-left: 10px;
            padding: 0 20px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .chat-form button:hover {
            background: var(--accent);
        }
        
        .role-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 15px;
            font-size: 0.7rem;
            font-weight: 600;
            margin-left: 8px;
        }
        
        .admin-badge {
            background-color: #f3e5f5;
            color: #8e24aa;
        }
        
        .tutor-badge {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .student-badge {
            background-color: #e8f5e9;
            color: #388e3c;
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            margin-bottom: 20px;
            padding: 8px 15px;
            background-color: var(--primary);
            color: white;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .back-button:hover {
            background-color: var(--accent);
            transform: translateY(-2px);
        }
        
        .back-button .material-symbols-sharp {
            margin-right: 5px;
            font-size: 20px;
        }
     .error-message {
            color: #dc3545;
            background-color: #f8d7da;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .refresh-button {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .refresh-button:hover {
            background-color: var(--accent);
        }

        aside {
            width: 210px;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            height: 100vh;
            margin-left: 0;
            padding-left: 0;
            left: 0;
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

        /* ======================== Side Bar ================================ */
       aside .sidebar {
            margin-left: 0;
            padding-left: 0;
        }


        aside h3 {
            font-weight: 500;
        }

        aside .sidebar a{
            display: flex;
            color:  #7d8da1;
            margin-left: 2rem;
            gap: 1rem;
            align-items: center;
            position: relative;
            height: 3.7rem;
            transition: all 300ms ease;
        }

        aside .sidebar a span{
            font-size: 1.6rem;
            transition: all 300ms ease;
        }

        aside .sidebar  a:last-child{
            position: absolute;
            bottom: 2rem;
            width: 100%;

        }

        aside .sidebar a.active {
            background: rgba(132, 139, 200, 0.18);
            color: #7380ec;
            margin-left: 0;
        }

        aside .sidebar a.active:before{
            content: "";
            width: 6px;
            height: 100%;
            background: #7380ec;

        }

        aside .sidebar a.active span{
            color: #7380ec;
            margin-left: calc(1rem -3 px);
        }

        aside .sidebar a:hover {
            color: #7380ec;
        }

        aside .sidebar a:hover span{
            margin-left: 1rem;
        }

        aside .sidebar .message-count {
            background: #ff7782;
            color: #fff;
            padding: 2px 10px;
            font-size: 11px;
            border-radius: 0.4rem;
        }
        .container {
            width: 100%;
            margin: 0;
            gap: 1.8rem;
            grid-template-columns: 14rem auto 23rem;
        }
    </style>
</head>
<body>

    <script>
        // Dropdown functionality
        function toggleDropdown() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }

        // Mobile menu toggle
        document.querySelector('.menu-toggle').addEventListener('click', function() {
            document.querySelector('.nav-links').classList.toggle('show');
        });

        // Avatar upload preview and automatic submission
        document.getElementById('profile_image_upload').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewElement = document.getElementById('profile-image-preview');
                    const placeholderElement = document.getElementById('profile-image-placeholder');

                    if (previewElement) {
                        // If it is already an image, update src
                        previewElement.src = e.target.result;
                    } else if (placeholderElement) {
                        // If it is a placeholder, replace it with an image element
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.alt = "Profile";
                        img.className = "profile-image";
                        img.id = "profile-image-preview";
                        placeholderElement.parentNode.replaceChild(img, placeholderElement);
                    }

                    // Automatically submit forms
                    document.getElementById('image-upload-form').submit();
                }
                reader.readAsDataURL(file);
            }
        });

    </script>
    <?php 
    
    // Include the appropriate header based on user role
    if ($current_user['role'] === 'student') {
        include 'header/stud_head.php';
    } elseif ($current_user['role'] === 'tutor') {
        include 'header/tut_head.php';
    } else 
    ?>
        <?php if ($current_user['role'] === 'admin'): ?>
    <div class="container">
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
                <a href="admin_message.php"class="active" ><span class="material-symbols-sharp">chat</span><h3>Messages</h3></a>
                <a href="admin_report.php"><span class="material-symbols-sharp">description</span><h3>Reports</h3></a>
                <a href="home_page.html"><span class="material-symbols-sharp">logout</span><h3>Logout</h3></a>
            </div>
        </aside>
        
    <?php endif; ?>

    <div class="main-content">
        <div class="chat-container">
            <a href="<?= $back_url ?>" class="back-button">
                <span class="material-symbols-sharp">arrow_back</span>
                Back
            </a>
            
            <?php if (!empty($error)): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <button class="refresh-button" onclick="location.reload()">
                <span class="material-symbols-sharp">refresh</span>
                Refresh Messages
            </button>
            
            <div class="chat-header">
                <h1 class="chat-title">Community Chat</h1>
            </div>
            
            <div class="chat-messages" id="messages-container">
                <?php if (!empty($messages)): ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="message">
                            <div class="message-header">
                                <?php if (!empty($message['profile_image'])): ?>
                                    <img src="<?= htmlspecialchars($message['profile_image']) ?>" class="message-avatar" alt="Profile">
                                <?php else: ?>
                                    <div class="message-default-avatar">
                                        <?= substr($message['first_name'], 0, 1) . substr($message['last_name'], 0, 1) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="message-sender-info">
                                    <span class="message-sender">
                                        <?= htmlspecialchars($message['first_name'] . ' ' . $message['last_name']) ?>
                                        <span class="role-badge <?= $message['role'] ?>-badge">
                                            <?= ucfirst($message['role']) ?>
                                        </span>
                                    </span>
                                    <span class="message-time">
                                        <?= date('M j, g:i a', strtotime($message['sent_datetime'])) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="message-content">
                                <?= nl2br(htmlspecialchars($message['content'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No messages yet. Be the first to post!</p>
                <?php endif; ?>
            </div>
            
            <?php if ($can_post): ?>
                <form class="chat-form" method="POST" action="community_chat.php">
                    <textarea name="message" placeholder="Type your message to the community..." required></textarea>
                    <button type="submit">
                        <span class="material-symbols-sharp">send</span>
                    </button>
                </form>
            <?php else: ?>
                <div class="error-message">You are currently restricted from posting in the community.</div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-scroll to bottom of messages
        const messagesContainer = document.getElementById('messages-container');
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
        
        // Check for new messages every 10 seconds
        setInterval(() => {
            fetch('check_new_community_messages.php')
                .then(response => response.json())
                .then(data => {
                    if (data.new_messages) {
                        location.reload();
                    }
                })
                .catch(error => console.error('Error checking messages:', error));
        }, 10000);
        
        // Also check when page becomes visible again
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                fetch('check_new_community_messages.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.new_messages) {
                            location.reload();
                        }
                    });
            }
        });
    </script>
</body>
</html>