<?php
session_start();
require_once "db_connection.php";

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get current user ID from session
$current_user_id = (int)$_SESSION['user_id'];

// Get current user's role
$current_user_role_query = "SELECT role FROM user WHERE user_id = ?";
$stmt = $conn->prepare($current_user_role_query);
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$current_user_role_result = $stmt->get_result();
$current_user_role = $current_user_role_result->fetch_assoc()['role'];
$stmt->close();

// Get other user ID from URL with proper validation
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    header("Location: messages_list.php");
    exit();
}

$other_user_id = (int)$_GET['user_id'];

// Validate other user ID
if ($other_user_id <= 0) {
    die("<div style='padding:20px; font-family:Arial;'>
            <h2>Invalid User</h2>
            <p>The user you're trying to message doesn't exist.</p>
            <p><a href='messages_list.php'>Back to messages</a></p>
         </div>");
}

// Get other user data
$other_user_query = "SELECT user_id, username, email, first_name, last_name, phone, profile_image, role 
                    FROM user WHERE user_id = ?";
$stmt = $conn->prepare($other_user_query);
$stmt->bind_param("i", $other_user_id);
$stmt->execute();
$other_user_result = $stmt->get_result();
$other_user_data = $other_user_result->fetch_assoc();
$stmt->close();

if (!$other_user_data) {
    die("Error: The user you're trying to message doesn't exist");
}

// Get messages between these users
$message_query = "SELECT message_id, sender_id, receiver_id, content, sent_datetime, is_read 
                  FROM message 
                  WHERE (sender_id = ? AND receiver_id = ?)
                  OR (sender_id = ? AND receiver_id = ?)
                  ORDER BY sent_datetime ASC";
$stmt = $conn->prepare($message_query);
$stmt->bind_param("iiii", $current_user_id, $other_user_id, $other_user_id, $current_user_id);
$stmt->execute();
$messages_result = $stmt->get_result();
$stmt->close();

// Mark messages as read
$mark_read_query = "UPDATE message SET is_read = 1 
                   WHERE receiver_id = ? AND sender_id = ? AND is_read = 0";
$stmt = $conn->prepare($mark_read_query);
$stmt->bind_param("ii", $current_user_id, $other_user_id);
$stmt->execute();
$stmt->close();

// Function to determine profile page based on role
function getProfilePage($role) {
    return $role === 'tutor' ? 'tutor_main_page.php' : 'student_main_page.php';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat with <?php echo htmlspecialchars($other_user_data['first_name'] . ' ' . $other_user_data['last_name']); ?></title>
    <link rel="stylesheet" href="msgstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
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
            margin-bottom: 50px;
            height: 70px;
        }
        
        .chat-area {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }
        .chat-box {
            max-height: 500px;
            overflow-y: auto;
            padding: 10px;
            background: #f9f9f9;
        }
    </style>
</head>
<body>
        <?php 
    // Include the appropriate header based on user role
    if ($current_user_role === 'student') {
        include 'header/stud_head.php';
    } elseif ($current_user_role === 'tutor') {
        include 'header/tut_head.php';
    } else {
        // Default header for admin or other roles if needed
        include 'header/header.php';
    }
    ?>

    
    <div class="wrapper">
        <section class="chat-area">
            <header>
                <a href="<?php echo getProfilePage($current_user_role); ?>" class="back-icon">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <img src="<?php echo htmlspecialchars($other_user_data['profile_image'] ?: 'images/default_profile.jpg'); ?>" alt="Profile" class="profile-img">
                <div class="details">
                    <span><?php echo htmlspecialchars($other_user_data['first_name'] . ' ' . $other_user_data['last_name']); ?></span>
                    <p><?php echo htmlspecialchars(ucfirst($other_user_data['role'])); ?></p>
                </div>
            </header>
            
            <div class="chat-box">
                <?php while ($row = $messages_result->fetch_assoc()): ?>
                    <div class="chat <?php echo $row['sender_id'] == $current_user_id ? 'outgoing' : 'incoming'; ?>">
                        <?php if ($row['sender_id'] != $current_user_id): ?>
                            <img src="<?php echo htmlspecialchars($other_user_data['profile_image'] ?: 'images/default_profile.jpg'); ?>" alt="">
                        <?php endif; ?>
                        <div class="details">
                            <p><?php echo htmlspecialchars($row['content']); ?></p>
                            <span class="time"><?php echo date('h:i A', strtotime($row['sent_datetime'])); ?></span>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <form action="send_message.php" method="POST" class="typing-area" autocomplete="off">
                <input type="text" name="message" placeholder="Type a message here..." required>
                <input type="hidden" name="receiver_id" value="<?php echo $other_user_id; ?>">
                <button type="submit"><i class="fab fa-telegram-plane"></i></button>
            </form>
        </section>
    </div>

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


        // Auto-scroll to bottom of chat
        const chatBox = document.querySelector('.chat-box');
        chatBox.scrollTop = chatBox.scrollHeight;
        
        // Function to check for new messages
        function checkNewMessages() {
            fetch('check_new_messages.php?receiver_id=<?php echo $other_user_id; ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.new_messages) {
                        location.reload();
                    }
                })
                .catch(error => console.error('Error checking messages:', error));
        }
        
        // Check for new messages every 3 seconds
        setInterval(checkNewMessages, 3000);
        
        // Also check when the page becomes visible again
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                checkNewMessages();
            }
        });
    </script>
</body>
</html>