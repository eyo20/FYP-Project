<?php
session_start();
require_once "db_connection.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 获取用户信息
$user_query = "SELECT username, email, role, first_name, last_name, phone, profile_image, last_login FROM user WHERE user_id = ?";
$stmt = $conn->prepare($user_query);
if (!$stmt) {
    die("准备查询失败: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();

if (!$user_data || $user_data['role'] != 'student') {
    // 如果用户不是学生，重定向到登录页面
    header("Location: login.php");
    exit();
}

$username = $user_data['username'];
$email = $user_data['email'];
$first_name = $user_data['first_name'] ?: '';
$last_name = $user_data['last_name'] ?: '';
$phone = $user_data['phone'] ?: '';
$profile_image = $user_data['profile_image'];
$last_login = $user_data['last_login'] ?? 'Never';
$stmt->close();

// 获取学生资料
$student_query = "SELECT major, year, school FROM studentprofile WHERE user_id = ?";
$stmt = $conn->prepare($student_query);
if (!$stmt) {
    die("准备查询失败: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student_result = $stmt->get_result();
if ($student_result->num_rows > 0) {
    $student_data = $student_result->fetch_assoc();
    $major = $student_data['major'] ?: '';
    $year = $student_data['year'] ?: '';
    $school = $student_data['school'] ?: '';
} else {
    // 如果没有学生资料，设置默认值
    $major = '';
    $year = '';
    $school = '';
}
$stmt->close();

// 获取未读消息数量
$unread_messages_query = "SELECT COUNT(*) as unread_count FROM message WHERE receiver_id = ? AND is_read = 0";
$stmt = $conn->prepare($unread_messages_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$messages_result = $stmt->get_result();
$messages_data = $messages_result->fetch_assoc();
$unread_messages = $messages_data['unread_count'];
$stmt->close();

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // 更新基本信息
        $first_name = $_POST['first_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $major = $_POST['major'] ?? '';
        $year = $_POST['year'] ?? '';
        $school = $_POST['school'] ?? '';
        
        // 更新用户基本信息
        $update_user = "UPDATE user SET first_name = ?, last_name = ?, phone = ? WHERE user_id = ?";
        $stmt = $conn->prepare($update_user);
        $stmt->bind_param("sssi", $first_name, $last_name, $phone, $user_id);
        $user_updated = $stmt->execute();
        $stmt->close();
        
        // 检查学生资料是否存在
        $check_profile = "SELECT user_id FROM studentprofile WHERE user_id = ?";
        $stmt = $conn->prepare($check_profile);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $profile_result = $stmt->get_result();
        $profile_exists = ($profile_result->num_rows > 0);
        $stmt->close();
        
        $profile_updated = false;
        
        if ($profile_exists) {
            // 更新学生资料
            $update_profile = "UPDATE studentprofile SET major = ?, year = ?, school = ? WHERE user_id = ?";
            $stmt = $conn->prepare($update_profile);
            $stmt->bind_param("sssi", $major, $year, $school, $user_id);
            $profile_updated = $stmt->execute();
            $stmt->close();
        } else {
            // 创建学生资料
            $create_profile = "INSERT INTO studentprofile (user_id, major, year, school) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($create_profile);
            
            if (!$stmt) {
                echo "<!-- 准备创建资料查询失败: " . $conn->error . " -->";
            } else {
                $stmt->bind_param("isss", $user_id, $major, $year, $school);
                $profile_updated = $stmt->execute();
                
                if (!$profile_updated) {
                    echo "<!-- 执行创建资料查询失败: " . $stmt->error . " -->";
                }
                
                $stmt->close();
            }
        }
        
        if ($user_updated && $profile_updated) {
            $success_message = "个人资料已成功更新！";
        } else {
            $error_message = "更新个人资料时出错，请重试！";
            if (!$user_updated) {
                $error_message .= " (用户信息更新失败)";
            }
            if (!$profile_updated) {
                $error_message .= " (学生资料" . ($profile_exists ? "更新" : "创建") . "失败)";
            }
        }
    }
    
    // 处理密码修改
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        $password_error = '';
        
        // 验证当前密码
        $password_query = "SELECT password FROM user WHERE user_id = ?";
        $stmt = $conn->prepare($password_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        if (!password_verify($current_password, $user['password'])) {
            $password_error = "当前密码不正确";
        } elseif (strlen($new_password) < 8) {
            $password_error = "新密码必须至少8个字符";
        } elseif (!preg_match('/[A-Z]/', $new_password)) {
            $password_error = "新密码必须包含至少一个大写字母";
        } elseif (!preg_match('/[0-9]/', $new_password)) {
            $password_error = "新密码必须包含至少一个数字";
        } elseif (!preg_match('/[^A-Za-z0-9]/', $new_password)) {
            $password_error = "新密码必须包含至少一个特殊字符";
        } elseif ($new_password !== $confirm_password) {
            $password_error = "两次输入的新密码不匹配";
        } else {
            // 更新密码
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_password = "UPDATE user SET password = ? WHERE user_id = ?";
            $stmt = $conn->prepare($update_password);
            $stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt->execute()) {
                $success_message = "密码已成功更新！";
            } else {
                $error_message = "更新密码失败，请重试。";
            }
            $stmt->close();
        }
        
        if (!empty($password_error)) {
            $error_message = $password_error;
        }
    }
}

// 处理头像上传
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (in_array($_FILES['profile_image']['type'], $allowed_types) && $_FILES['profile_image']['size'] <= $max_size) {
        $upload_dir = 'uploads/profile_images/';
        
        // 创建上传目录（如果不存在）
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $filename = uniqid() . '_' . $_FILES['profile_image']['name'];
        $target_file = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
            // 更新数据库中的头像路径
            $update_image = "UPDATE user SET profile_image = ? WHERE user_id = ?";
            $stmt = $conn->prepare($update_image);
            $stmt->bind_param("si", $target_file, $user_id);
            $image_updated = $stmt->execute();
            
            if ($image_updated) {
                $profile_image = $target_file;
                $success_message = "头像已成功更新！";
            } else {
                $error_message = "更新头像信息时出错，请重试！";
            }
        } else {
            $error_message = "上传头像时出错，请重试！";
        }
    } else {
        $error_message = "请上传有效的图片文件（JPG, PNG, GIF），大小不超过5MB！";
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>同伴辅导平台 - 学生个人资料</title>
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: var(--light-gray);
            color: #333;
        }
        
        .navbar {
            background-color: var(--primary);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .logo {
            font-weight: bold;
            font-size: 1.5rem;
        }
        
        .nav-links {
            display: flex;
            gap: 20px;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .nav-links a:hover {
            background-color: rgba(255,255,255,0.1);
        }
        
        .nav-links a.active {
            background-color: var(--accent);
            color: white;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-avatar {
            width: 35px;
            height: 35px;
            background-color: var(--secondary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            cursor: pointer;
            overflow: hidden;
        }
        
        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .notification-badge {
            background-color: var(--accent);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            margin-left: -10px;
            margin-top: -10px;
        }
        
        main {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .page-title {
            color: var(--primary);
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
        
        .profile-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
        }
        
        .profile-sidebar {
            background-color: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .profile-image-container {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary);
        }
        
        .profile-image-placeholder {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background-color: var(--secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            border: 3px solid var(--primary);
        }
        
        .edit-profile-image {
            position: absolute;
            bottom: 0;
            right: 0;
            background-color: var(--accent);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.2rem;
            border: 2px solid white;
        }
        
        .profile-name {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--primary);
            text-align: center;
        }
        
        .profile-role {
            color: var(--dark-gray);
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .profile-info {
            width: 100%;
        }
        
        .info-item {
            display: flex;
            margin-bottom: 1rem;
            align-items: center;
        }
        
        .info-icon {
            width: 30px;
            color: var(--primary);
            margin-right: 10px;
            text-align: center;
        }
        
        .info-text {
            flex: 1;
        }
        
        .profile-content {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        
        .profile-section {
            background-color: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .section-title {
            color: var(--primary);
            margin-bottom: 1.5rem;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
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
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        .btn {
            background-color: var(--accent);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #b3c300;
        }
        
        .btn-secondary {
            background-color: var(--secondary);
        }
        
        .btn-secondary:hover {
            background-color: #0098d0;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
        }
        
        footer {
            background-color: var(--primary);
            color: white;
            text-align: center;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        
        .form-text {
            font-size: 0.875rem;
            color: var(--dark-gray);
            margin-top: 0.25rem;
        }
        
        .danger-zone {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            padding: 1.5rem;
            margin-top: 1rem;
        }
        
        .danger-zone h4 {
            color: #721c24;
            margin-bottom: 0.5rem;
        }
        
        .btn-danger {
            background-color: #dc3545;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
            
            .nav-links {
                display: none;
                position: absolute;
                top: 60px;
                left: 0;
                right: 0;
                background-color: var(--primary);
                flex-direction: column;
                padding: 1rem;
                z-index: 100;
            }
            
            .nav-links.show {
                display: flex;
            }
            
            .menu-toggle {
                display: block;
                font-size: 1.5rem;
                cursor: pointer;
            }
        }
        
        @media (max-width: 576px) {
            .profile-image, .profile-image-placeholder {
                width: 120px;
                height: 120px;
            }
            
            .profile-name {
                font-size: 1.2rem;
            }
            
            .profile-section {
                padding: 1.5rem;
            }
            
            .btn {
                width: 100%;
            }
        }
        
        @media (min-width: 769px) {
            .menu-toggle {
                display: none;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">PeerLearn</div>
        <div class="nav-links">
            <a href="student_main_page.php">Find Tutors</a>
            <a href="student_sessions.php">My Sessions</a>
            <a href="student_profile.php" class="active">Profile</a>
            <a href="messages.php">Messages
                <?php if($unread_messages > 0): ?>
                <span class="notification-badge"><?php echo $unread_messages; ?></span>
                <?php endif; ?>
            </a>
        </div>
        <div class="user-menu">
            <div class="user-avatar">
                <?php if($profile_image): ?>
                <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile">
                <?php else: ?>
                <?php echo strtoupper(substr($first_name, 0, 1)); ?>
                <?php endif; ?>
            </div>
            <a href="logout.php" style="color: white; text-decoration: none;">Logout</a>
        </div>
        <div class="menu-toggle">☰</div>
    </nav>

    <main>
        <h1 class="page-title">Profile</h1>
        
        <?php if(isset($success_message)): ?>
        <div class="success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if(isset($error_message)): ?>
        <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="profile-container">
            <div class="profile-sidebar">
                <div class="profile-image-container">
                    <?php if($profile_image): ?>
                    <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile" class="profile-image" id="profile-image-preview">
                    <?php else: ?>
                    <div class="profile-image-placeholder" id="profile-image-placeholder"><?php echo strtoupper(substr($first_name, 0, 1)); ?></div>
                    <?php endif; ?>
                    <label for="profile_image_upload" class="edit-profile-image">
                        <i>📷</i>
                    </label>
                    <form id="image-upload-form" action="" method="post" enctype="multipart/form-data" style="display: none;">
                        <input type="file" id="profile_image_upload" name="profile_image" accept="image/*">
                    </form>
                </div>
                <h2 class="profile-name"><?php echo htmlspecialchars($first_name . ' ' . $last_name); ?></h2>
                <p class="profile-role">Student</p>
                
                <div class="profile-info">
                    <div class="info-item">
                        <div class="info-icon">📧</div>
                        <div class="info-text"><?php echo htmlspecialchars($email); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">📱</div>
                        <div class="info-text"><?php echo $phone ? htmlspecialchars($phone) : 'Not Set'; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">🎓</div>
                        <div class="info-text"><?php echo $major ? htmlspecialchars($major) : 'Not Set'; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">📅</div>
                        <div class="info-text"><?php echo $year ? htmlspecialchars($year) : 'Not Set'; ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">🕒</div>
                        <div class="info-text">Last login: <?php echo $last_login != 'Never' ? date('M d, Y H:i', strtotime($last_login)) : 'Never'; ?></div>
                    </div>
                </div>
            </div>
            
            <div class="profile-content">
                <div class="profile-section">
                    <h3 class="section-title">Personal Information</h3>
                    <form action="" method="post" name="profile_form" id="profile-form">
                        <input type="hidden" name="update_profile" value="1">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                        </div>
                        <div class="form-group">
                            <label for="major">Major</label>
                            <input type="text" class="form-control" id="major" name="major" value="<?php echo htmlspecialchars($major); ?>">
                        </div>
                        <div class="form-group">
                            <label for="year">Level</label>
                            <select class="form-control" id="year" name="year">
                            <option value="" <?php echo $year == '' ? 'selected' : ''; ?>>-- Select Level --</option>
                            <option value="Foundation" <?php echo $year == 'Foundation' ? 'selected' : ''; ?>>Foundation</option>
                            <option value="Diploma" <?php echo $year == 'Diploma' ? 'selected' : ''; ?>>Diploma</option>
                            <option value="Degree" <?php echo $year == 'Degree' ? 'selected' : ''; ?>>Degree</option>
                            
                            <option value="Master" <?php echo $year == 'Master' ? 'selected' : ''; ?>>Master</option>
                            <option value="PhD" <?php echo $year == 'PhD' ? 'selected' : ''; ?>>PhD</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn" id="save-profile-btn">Save Profile</button>
                    </form>
                </div>
                
                <div class="profile-section">
                    <h3 class="section-title">Security Settings</h3>
                    <form action="" method="post" name="password_form" id="password-form">
                        <input type="hidden" name="change_password" value="1">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <small class="form-text">Password must be at least 8 characters with uppercase, number, and special character.</small>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-secondary" id="change-password-btn">Change Password</button>
                    </form>
                    <p style="margin-top: 15px;">
                        <a href="forgot_password.php">Forgot your password?</a>
                    </p>
                </div>
                
                <div class="profile-section">
                    <h3 class="section-title">Account Settings</h3>
                    <div class="danger-zone">
                        <h4>Delete Account</h4>
                        <p style="margin-bottom: 1rem;">Warning: This action cannot be undone. All your data will be permanently deleted.</p>
                        <button type="button" id="delete-account
-btn" class="btn btn-danger">Delete My Account</button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2023 PeerLearn - Peer Tutoring Platform. All rights reserved.</p>
    </footer>

    <script>
        // Mobile menu toggle
        document.querySelector('.menu-toggle').addEventListener('click', function() {
            document.querySelector('.nav-links').classList.toggle('show');
        });
        
        // 头像上传预览和自动提交
        document.getElementById('profile_image_upload').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewElement = document.getElementById('profile-image-preview');
                    const placeholderElement = document.getElementById('profile-image-placeholder');
                    
                    if (previewElement) {
                        // 如果已经是图片，更新src
                        previewElement.src = e.target.result;
                    } else if (placeholderElement) {
                        // 如果是占位符，替换为图片元素
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.alt = "Profile";
                        img.className = "profile-image";
                        img.id = "profile-image-preview";
                        placeholderElement.parentNode.replaceChild(img, placeholderElement);
                    }
                    
                    // 自动提交表单
                    document.getElementById('image-upload-form').submit();
                }
                reader.readAsDataURL(file);
            }
        });
        
        // 个人资料表单验证
        document.getElementById('profile-form').addEventListener('submit', function(e) {
            const firstName = document.getElementById('first_name').value.trim();
            const lastName = document.getElementById('last_name').value.trim();
            const phone = document.getElementById('phone').value.trim();
            
            if (!firstName) {
                alert('请输入您的名字');
                e.preventDefault();
                return;
            }
            
            if (!lastName) {
                alert('请输入您的姓氏');
                e.preventDefault();
                return;
            }
            
            if (phone && !/^\d{10,15}$/.test(phone)) {
                alert('请输入有效的电话号码');
                e.preventDefault();
                return;
            }
        });
        
        // 密码表单验证
        document.getElementById('password-form').addEventListener('submit', function(e) {
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (!currentPassword) {
                alert('请输入当前密码');
                e.preventDefault();
                return;
            }
            
            if (newPassword.length < 8) {
                alert('新密码必须至少8个字符');
                e.preventDefault();
                return;
            }
            
            if (!/[A-Z]/.test(newPassword)) {
                alert('新密码必须包含至少一个大写字母');
                e.preventDefault();
                return;
            }
            
            if (!/[0-9]/.test(newPassword)) {
                alert('新密码必须包含至少一个数字');
                e.preventDefault();
                return;
            }
            
            if (!/[^A-Za-z0-9]/.test(newPassword)) {
                alert('新密码必须包含至少一个特殊字符');
                e.preventDefault();
                return;
            }
            
            if (newPassword !== confirmPassword) {
                alert('两次输入的新密码不匹配');
                e.preventDefault();
                return;
            }
        });
        
        // 保存个人资料确认
        document.getElementById('save-profile-btn').addEventListener('click', function(e) {
            if (!confirm('确定要保存个人资料更改吗？')) {
                e.preventDefault();
            }
        });
        
        // 修改密码确认
        document.getElementById('change-password-btn').addEventListener('click', function(e) {
            if (!confirm('确定要更改密码吗？')) {
                e.preventDefault();
            }
        });
        
        // 删除账户确认
        document.getElementById('delete-account-btn').addEventListener('click', function() {
            if (confirm('您确定要删除您的账户吗？此操作无法撤销，所有数据将被永久删除。')) {
                if (prompt('请输入"DELETE"以确认') === 'DELETE') {
                    window.location.href = 'delete_account.php';
                }
            }
        });
    </script>
</body>
</html>
