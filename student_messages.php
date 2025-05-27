<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit();
}

$tutor_id = isset($_GET['tutor_id']) ? (int)$_GET['tutor_id'] : 0;
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Get conversations
try {
    $conversations_query = "
        SELECT DISTINCT 
            CASE 
                WHEN m.sender_id = ? THEN m.receiver_id 
                ELSE m.sender_id 
            END as other_user_id,
            u.first_name, u.last_name, u.profile_image,
            MAX(m.created_at) as last_message_time,
            (SELECT content FROM message m2 
             WHERE (m2.sender_id = ? AND m2.receiver_id = other_user_id) 
                OR (m2.sender_id = other_user_id AND m2.receiver_id = ?)
             ORDER BY m2.created_at DESC LIMIT 1) as last_message,
            COUNT(CASE WHEN m.receiver_id = ? AND m.is_read = 0 THEN 1 END) as unread_count
        FROM message m
        JOIN user u ON u.user_id = CASE 
            WHEN m.sender_id = ? THEN m.receiver_id 
            ELSE m.sender_id 
        END
        WHERE (m.sender_id = ? OR m.receiver_id = ?)
        GROUP BY other_user_id, u.first_name, u.last_name, u.profile_image
        ORDER BY last_message_time DESC
    ";
    
    $stmt = $pdo->prepare($conversations_query);
    $stmt->execute([
        $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], 
        $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']
    ]);
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If tutor_id is specified and no existing conversation, add tutor info
    if ($tutor_id && !array_filter($conversations, function($conv) use ($tutor_id) {
        return $conv['other_user_id'] == $tutor_id;
    })) {
        $stmt = $pdo->prepare("
            SELECT user_id as other_user_id, first_name, last_name, profile_image
            FROM user 
            WHERE user_id = ? AND role = 'tutor'
        ");
        $stmt->execute([$tutor_id]);
        $tutor_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($tutor_info) {
            $tutor_info['last_message_time'] = null;
            $tutor_info['last_message'] = null;
            $tutor_info['unread_count'] = 0;
            array_unshift($conversations, $tutor_info);
        }
    }

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $conversations = [];
}

// Get messages for selected conversation
$messages = [];
$selected_user = null;
$current_conversation_id = $tutor_id ?: (isset($conversations[0]) ? $conversations[0]['other_user_id'] : 0);

if ($current_conversation_id) {
    try {
        // Get selected user info
        $stmt = $pdo->prepare("
            SELECT user_id, first_name, last_name, profile_image, role
            FROM user 
            WHERE user_id = ?
        ");
        $stmt->execute([$current_conversation_id]);
        $selected_user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get messages
        $stmt = $pdo->prepare("
            SELECT m.*, u.first_name, u.last_name
            FROM message m
            JOIN user u ON m.sender_id = u.user_id
            WHERE (m.sender_id = ? AND m.receiver_id = ?) 
               OR (m.sender_id = ? AND m.receiver_id = ?)
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$_SESSION['user_id'], $current_conversation_id, $current_conversation_id, $_SESSION['user_id']]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Mark messages as read
        $stmt = $pdo->prepare("
            UPDATE message 
            SET is_read = 1 
            WHERE sender_id = ? AND receiver_id = ? AND is_read = 0
        ");
        $stmt->execute([$current_conversation_id, $_SESSION['user_id']]);

    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
    }
}

// Handle sending new message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $receiver_id = (int)$_POST['receiver_id'];
    $content = trim($_POST['content']);

    if ($receiver_id && $content) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO message (sender_id, receiver_id, content, created_at, is_read)
                VALUES (?, ?, ?, NOW(), 0)
            ");
            $stmt->execute([$_SESSION['user_id'], $receiver_id, $content]);
            
            // Redirect to refresh the page
            header("Location: student_messages.php?tutor_id=" . $receiver_id);
            exit();
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            $error_message = 'Failed to send message. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Peer Tutoring Platform</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --success: #27ae60;
            --danger: #e74c3c;
            --warning: #f39c12;
            --light: #ecf0f1;
            --dark: #2c3e50;
            --gray: #bdc3c7;
            --dark-gray: #7f8c8d;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 0;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--dark);
            font-weight: 500;
            transition: color 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-links a:hover,
        .nav-links a.active {
            color: var(--secondary);
        }

        .user-menu {
            position: relative;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            border-radius: 8px;
            transition: background 0.3s;
        }

        .user-menu:hover {
            background: var(--light);
        }

        .dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 8px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 0.5rem 0;
            min-width: 150px;
            display: none;
        }

        .dropdown a {
            display: block;
            padding: 0.75rem 1rem;
            color: var(--dark);
            text-decoration: none;
            transition: background 0.3s;
        }

        .dropdown a:hover {
            background: var(--light);
        }

        .main {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
            height: calc(100vh - 120px);
        }

        .conversations-panel {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow-y: auto;
        }

        .panel-header {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--light);
        }

        .panel-header h2 {
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .conversation-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 0.5rem;
            text-decoration: none;
            color: inherit;
        }

        .conversation-item:hover {
            background: var(--light);
            transform: translateX(5px);
        }

        .conversation-item.active {
            background: var(--secondary);
            color: white;
        }

        .conversation-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            overflow: hidden;
            flex-shrink: 0;
        }

        .conversation-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .conversation-info {
            flex: 1;
            min-width: 0;
        }

        .conversation-name {
            font-weight: bold;
            margin-bottom: 0.25rem;
        }

        .conversation-preview {
            font-size: 0.9rem;
            opacity: 0.7;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .conversation-meta {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.25rem;
        }

        .conversation-time {
            font-size: 0.8rem;
            opacity: 0.7;
        }

        .unread-badge {
            background: var(--danger);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: bold;
        }

        .chat-panel {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .chat-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--light);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .chat-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            overflow: hidden;
        }

        .chat-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .chat-user-info h3 {
            color: var(--primary);
            margin-bottom: 0.25rem;
        }

        .chat-user-role {
            color: var(--dark-gray);
            font-size: 0.9rem;
        }
                .messages-container {
            flex: 1;
            padding: 1.5rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .message {
            display: flex;
            gap: 0.75rem;
            max-width: 70%;
        }

        .message.sent {
            align-self: flex-end;
            flex-direction: row-reverse;
        }

        .message.received {
            align-self: flex-start;
        }

        .message-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: var(--secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 0.8rem;
            overflow: hidden;
            flex-shrink: 0;
        }

        .message-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .message-content {
            background: var(--light);
            padding: 0.75rem 1rem;
            border-radius: 15px;
            position: relative;
        }

        .message.sent .message-content {
            background: var(--secondary);
            color: white;
        }

        .message-text {
            margin-bottom: 0.25rem;
            line-height: 1.4;
        }

        .message-time {
            font-size: 0.7rem;
            opacity: 0.7;
        }

        .message-form {
            padding: 1.5rem;
            border-top: 1px solid var(--light);
            display: flex;
            gap: 1rem;
            align-items: flex-end;
        }

        .message-input {
            flex: 1;
            padding: 0.75rem;
            border: 2px solid var(--light);
            border-radius: 25px;
            resize: none;
            min-height: 45px;
            max-height: 120px;
            font-family: inherit;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .message-input:focus {
            outline: none;
            border-color: var(--secondary);
        }

        .send-btn {
            background: var(--secondary);
            color: white;
            border: none;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            flex-shrink: 0;
        }

        .send-btn:hover {
            background: #2980b9;
            transform: scale(1.05);
        }

        .send-btn:disabled {
            background: var(--gray);
            cursor: not-allowed;
            transform: none;
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--dark-gray);
            text-align: center;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .no-conversations {
            text-align: center;
            color: var(--dark-gray);
            padding: 2rem;
        }

        .no-conversations i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .header-content {
                padding: 0 1rem;
            }

            .nav-links {
                gap: 1rem;
            }

            .nav-links a span {
                display: none;
            }

            .main {
                grid-template-columns: 1fr;
                grid-template-rows: 200px 1fr;
                padding: 0 1rem;
                height: calc(100vh - 100px);
            }

            .conversations-panel {
                overflow-x: auto;
            }

            .conversation-item {
                min-width: 250px;
                display: inline-flex;
                margin-right: 0.5rem;
            }

            .message {
                max-width: 85%;
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
                <a href="student_dashboard.php"><i class="fas fa-home"></i> <span>Dashboard</span></a>
                <a href="find_tutors.php"><i class="fas fa-search"></i> <span>Find Tutors</span></a>
                <a href="student_sessions.php"><i class="fas fa-calendar"></i> <span>My Sessions</span></a>
                <a href="student_messages.php" class="active"><i class="fas fa-envelope"></i> <span>Messages</span></a>
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
        <!-- Conversations Panel -->
        <div class="conversations-panel">
            <div class="panel-header">
                <h2><i class="fas fa-comments"></i> Conversations</h2>
            </div>

            <?php if (empty($conversations)): ?>
                <div class="no-conversations">
                    <i class="fas fa-inbox"></i>
                    <h3>No conversations yet</h3>
                    <p>Start a conversation with a tutor from your sessions.</p>
                </div>
            <?php else: ?>
                <?php foreach ($conversations as $conversation): ?>
                    <a href="student_messages.php?tutor_id=<?php echo $conversation['other_user_id']; ?>" 
                       class="conversation-item <?php echo $current_conversation_id == $conversation['other_user_id'] ? 'active' : ''; ?>">
                        <div class="conversation-avatar">
                            <?php if ($conversation['profile_image']): ?>
                                <img src="<?php echo htmlspecialchars($conversation['profile_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($conversation['first_name']); ?>">
                            <?php else: ?>
                                <?php echo strtoupper(substr($conversation['first_name'], 0, 1)); ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="conversation-info">
                            <div class="conversation-name">
                                <?php echo htmlspecialchars($conversation['first_name'] . ' ' . $conversation['last_name']); ?>
                            </div>
                            <?php if ($conversation['last_message']): ?>
                                <div class="conversation-preview">
                                    <?php echo htmlspecialchars(substr($conversation['last_message'], 0, 50)) . (strlen($conversation['last_message']) > 50 ? '...' : ''); ?>
                                </div>
                            <?php else: ?>
                                <div class="conversation-preview">Start a conversation...</div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="conversation-meta">
                            <?php if ($conversation['last_message_time']): ?>
                                <div class="conversation-time">
                                    <?php echo date('M j', strtotime($conversation['last_message_time'])); ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($conversation['unread_count'] > 0): ?>
                                <div class="unread-badge"><?php echo $conversation['unread_count']; ?></div>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Chat Panel -->
        <div class="chat-panel">
            <?php if ($selected_user): ?>
                <!-- Chat Header -->
                <div class="chat-header">
                    <div class="chat-avatar">
                        <?php if ($selected_user['profile_image']): ?>
                            <img src="<?php echo htmlspecialchars($selected_user['profile_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($selected_user['first_name']); ?>">
                        <?php else: ?>
                            <?php echo strtoupper(substr($selected_user['first_name'], 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <div class="chat-user-info">
                        <h3><?php echo htmlspecialchars($selected_user['first_name'] . ' ' . $selected_user['last_name']); ?></h3>
                        <div class="chat-user-role"><?php echo ucfirst($selected_user['role']); ?></div>
                    </div>
                </div>

                <!-- Messages Container -->
                <div class="messages-container" id="messagesContainer">
                    <?php if (empty($messages)): ?>
                        <div class="empty-state">
                            <i class="fas fa-comment-dots"></i>
                            <h3>No messages yet</h3>
                            <p>Start the conversation by sending a message below.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $message): ?>
                            <div class="message <?php echo $message['sender_id'] == $_SESSION['user_id'] ? 'sent' : 'received'; ?>">
                                <div class="message-avatar">
                                    <?php if ($message['sender_id'] == $_SESSION['user_id']): ?>
                                        <?php echo strtoupper(substr($_SESSION['first_name'], 0, 1)); ?>
                                    <?php else: ?>
                                        <?php if ($selected_user['profile_image']): ?>
                                            <img src="<?php echo htmlspecialchars($selected_user['profile_image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($selected_user['first_name']); ?>">
                                        <?php else: ?>
                                            <?php echo strtoupper(substr($selected_user['first_name'], 0, 1)); ?>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="message-content">
                                    <div class="message-text"><?php echo nl2br(htmlspecialchars($message['content'])); ?></div>
                                    <div class="message-time"><?php echo date('M j, g:i A', strtotime($message['created_at'])); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Message Form -->
                <form class="message-form" method="POST" action="" onsubmit="return validateMessage()">
                    <input type="hidden" name="receiver_id" value="<?php echo $selected_user['user_id']; ?>">
                    <input type="hidden" name="send_message" value="1">
                    <textarea name="content" class="message-input" placeholder="Type your message..." 
                              rows="1" id="messageInput" required></textarea>
                    <button type="submit" class="send-btn" id="sendBtn">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-comments"></i>
                    <h3>Select a conversation</h3>
                    <p>Choose a conversation from the left panel to start messaging.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Alerts -->
    <?php if ($success_message): ?>
        <div class="alert alert-success" style="position: fixed; top: 80px; right: 20px; z-index: 1000;">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-error" style="position: fixed; top: 80px; right: 20px; z-index: 1000;">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <script>
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

        // Auto-resize textarea
        const messageInput = document.getElementById('messageInput');
        if (messageInput) {
            messageInput.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 120) + 'px';
            });

            // Send message on Enter (but allow Shift+Enter for new lines)
            messageInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    if (this.value.trim()) {
                        this.closest('form').submit();
                    }
                }
            });
        }

        // Validate message before sending
        function validateMessage() {
            const input = document.getElementById('messageInput');
            const sendBtn = document.getElementById('sendBtn');
            
                        if (!input.value.trim()) {
                return false;
            }
            
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            return true;
        }

        // Scroll to bottom of messages
        function scrollToBottom() {
            const container = document.getElementById('messagesContainer');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        }

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

            // Scroll to bottom on page load
            scrollToBottom();
        });

        // Auto-refresh messages every 30 seconds
        setInterval(function() {
            const currentUrl = window.location.href;
            if (currentUrl.includes('tutor_id=')) {
                // Only refresh if we're in an active conversation
                fetch(currentUrl)
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newMessages = doc.getElementById('messagesContainer');
                        const currentMessages = document.getElementById('messagesContainer');
                        
                        if (newMessages && currentMessages && 
                            newMessages.innerHTML !== currentMessages.innerHTML) {
                            currentMessages.innerHTML = newMessages.innerHTML;
                            scrollToBottom();
                        }
                    })
                    .catch(error => {
                        console.log('Auto-refresh failed:', error);
                    });
            }
        }, 30000);
    </script>
</body>
</html>
