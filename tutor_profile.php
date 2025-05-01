<?php
session_start();
require_once "db_connection.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 获取用户信息
$user_query = "SELECT username, email, role, first_name, last_name, phone, profile_image FROM user WHERE user_id = ?";
$stmt = $conn->prepare($user_query);
if (!$stmt) {
    die("准备查询失败: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();

if (!$user_data || $user_data['role'] != 'tutor') {
    // 如果用户不是导师，重定向到学生页面或登录页面
    header("Location: login.php");
    exit();
}

$username = $user_data['username'];
$email = $user_data['email'];
$first_name = $user_data['first_name'] ?: '';
$last_name = $user_data['last_name'] ?: '';
$phone = $user_data['phone'] ?: '';
$profile_image = $user_data['profile_image'];
$stmt->close();

// 获取导师资料
$tutor_query = "SELECT major, year, bio, qualifications, is_verified FROM tutorprofile WHERE user_id = ?";
$stmt = $conn->prepare($tutor_query);
if (!$stmt) {
    die("准备查询失败: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tutor_result = $stmt->get_result();

if ($tutor_result->num_rows > 0) {
    $tutor_data = $tutor_result->fetch_assoc();
    $major = $tutor_data['major'] ?: '';
    $year = $tutor_data['year'] ?: '';
    $bio = $tutor_data['bio'] ?: '';
    $qualifications = $tutor_data['qualifications'] ?: '';
    $is_verified = $tutor_data['is_verified'];
} else {
    // 如果没有导师资料，设置默认值
    $major = '';
    $year = '';
    $bio = '';
    $qualifications = '';
    $is_verified = 0;
}
$stmt->close();


// 获取导师教授的科目
$subjects_query = "SELECT ts.subject_id, s.subject_name, ts.hourly_rate 
                  FROM tutorsubject ts 
                  JOIN subject s ON ts.subject_id = s.subject_id 
                  WHERE ts.tutor_id = ?";
$stmt = $conn->prepare($subjects_query);
if (!$stmt) {
    die("准备查询失败: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$subjects_result = $stmt->get_result();

$tutor_subjects = [];
while ($subject = $subjects_result->fetch_assoc()) {
    $tutor_subjects[] = $subject;
}
$stmt->close();


// 获取所有可选科目
$all_subjects_query = "SELECT subject_id, subject_name FROM subject ORDER BY subject_name";
$all_subjects_result = $conn->query($all_subjects_query);

$all_subjects = [];
while ($subject = $all_subjects_result->fetch_assoc()) {
    $all_subjects[] = $subject;
}

// 获取未读消息数量
$unread_messages_query = "SELECT COUNT(*) as unread_count FROM message WHERE receiver_id = ? AND is_read = 0";
$stmt = $conn->prepare($unread_messages_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$messages_result = $stmt->get_result();
$messages_data = $messages_result->fetch_assoc();
$unread_messages = $messages_data['unread_count'];
$stmt->close();

// 获取待处理的预约请求数量
$pending_requests_query = "SELECT COUNT(*) as pending_count
                          FROM session
                          WHERE tutor_id = ? AND status = 'pending'";
$stmt = $conn->prepare($pending_requests_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending_result = $stmt->get_result();
$pending_data = $pending_result->fetch_assoc();
$pending_requests = $pending_data['pending_count'];
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
        $bio = $_POST['bio'] ?? '';
        $qualifications = $_POST['qualifications'] ?? '';
        
        // 更新用户基本信息
        $update_user = "UPDATE user SET first_name = ?, last_name = ?, phone = ? WHERE user_id = ?";
        $stmt = $conn->prepare($update_user);
        $stmt->bind_param("sssi", $first_name, $last_name, $phone, $user_id);
        $user_updated = $stmt->execute();
        $stmt->close();
        
        // 检查导师资料是否存在
        $check_profile = "SELECT user_id FROM tutorprofile WHERE user_id = ?";
        $stmt = $conn->prepare($check_profile);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $profile_result = $stmt->get_result();
        $profile_exists = ($profile_result->num_rows > 0);
        $stmt->close();
        
        $profile_updated = false;
        
        if ($profile_exists) {
            // 更新导师资料
            $update_profile = "UPDATE tutorprofile SET major = ?, year = ?, bio = ?, qualifications = ? WHERE user_id = ?";
            $stmt = $conn->prepare($update_profile);
            $stmt->bind_param("ssssi", $major, $year, $bio, $qualifications, $user_id);
            $profile_updated = $stmt->execute();
            $stmt->close();
        } else {
            // 创建导师资料 - 使用直接调试输出
            echo "<!-- 尝试创建新的导师资料 -->";
            
            // 确保 is_verified 字段有默认值或明确设置
            $is_verified = 0;
            $create_profile = "INSERT INTO tutorprofile (user_id, major, year, bio, qualifications, is_verified) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($create_profile);
            
            if (!$stmt) {
                echo "<!-- Failed to prepare to create profile query: " . $conn->error . " -->";
            } else {
                $stmt->bind_param("issssi", $user_id, $major, $year, $bio, $qualifications, $is_verified);
                $profile_updated = $stmt->execute();
                
                if (!$profile_updated) {
                    echo "<!-- Failed to execute create profile query: " . $stmt->error . " -->";
                }
                
                $stmt->close();
            }
        }
        
        if ($user_updated && $profile_updated) {
            $success_message = "Profile updated successfully！";
        } else {
            $error_message = "An error occurred while updating your profile. Please try again.！";
            if (!$user_updated) {
                $error_message .= " (User information update failed)";
            }
            if (!$profile_updated) {
                $error_message .= " (tutor Information" . ($profile_exists ? "Update" : "Create") . "Fail)";
            }
        }
    }

    } elseif (isset($_POST['add_subject'])) {
        // 添加教授科目
        $subject_id = $_POST['subject_id'] ?? '';
        $hourly_rate = $_POST['hourly_rate'] ?? '';
        
        if (!empty($subject_id) && !empty($hourly_rate)) {
            // 检查是否已经添加过该科目
            $check_subject = "SELECT * FROM tutorsubject WHERE tutor_id = ? AND subject_id = ?";
            $stmt = $conn->prepare($check_subject);
            $stmt->bind_param("ii", $user_id, $subject_id);
            $stmt->execute();
            $subject_result = $stmt->get_result();
            
            if ($subject_result->num_rows > 0) {
                // 更新科目价格
                $update_subject = "UPDATE tutorsubject SET hourly_rate = ? WHERE tutor_id = ? AND subject_id = ?";
                $stmt = $conn->prepare($update_subject);
                $stmt->bind_param("dii", $hourly_rate, $user_id, $subject_id);
                $subject_updated = $stmt->execute();
                
                if ($subject_updated) {
                    $success_message = "Account price updated successfully！";
                } else {
                    $error_message = "An error occurred while updating the item price. Please try again.！";
                }
            } else {
                // 添加新科目
                $add_subject = "INSERT INTO tutorsubject (tutor_id, subject_id, hourly_rate) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($add_subject);
                $stmt->bind_param("iid", $user_id, $subject_id, $hourly_rate);
                $subject_added = $stmt->execute();
                
                if ($subject_added) {
                    $success_message = "Subject added successfully！";
                    
                    // 刷新科目列表
                    $stmt = $conn->prepare($subjects_query);
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $subjects_result = $stmt->get_result();
                    
                    $tutor_subjects = [];
                    while ($subject = $subjects_result->fetch_assoc()) {
                        $tutor_subjects[] = $subject;
                    }
                } else {
                    $error_message = "An error occurred while adding the subject. Please try again.！";
                }
            }
        } else {
            $error_message = "Please select the subject and set the price！";
        }
    } elseif (isset($_POST['remove_subject'])) {
        // 移除教授科目
        $subject_id = $_POST['subject_id'] ?? '';
        
        if (!empty($subject_id)) {
            $remove_subject = "DELETE FROM tutorsubject WHERE tutor_id = ? AND subject_id = ?";
            $stmt = $conn->prepare($remove_subject);
            $stmt->bind_param("ii", $user_id, $subject_id);
            $subject_removed = $stmt->execute();
            
            if ($subject_removed) {
                $success_message = "Subject removed successfully！";
                
                // 刷新科目列表
                $stmt = $conn->prepare($subjects_query);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $subjects_result = $stmt->get_result();
                
                $tutor_subjects = [];
                while ($subject = $subjects_result->fetch_assoc()) {
                    $tutor_subjects[] = $subject;
                }
            } else {
                $error_message = "An error occurred while removing the subject. Please try again！";
            }
        } else {
            $error_message = "Invalid subject ID！";
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
                $success_message = "Avatar updated successfully！";
            } else {
                $error_message = "An error occurred while updating the avatar information. Please try again.！";
            }
        } else {
            $error_message = "An error occurred while uploading your avatar. Please try again.！";
        }
    } else {
        $error_message = "Please upload a valid image file (JPG, PNG, GIF), no larger than 5MB！";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peer Tutoring Platform - Tutor Profile</title>
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
        
        .verified-badge {
            background-color: var(--accent);
            color: white;
            font-size: 0.8rem;
            padding: 0.3rem 0.7rem;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 1.5rem;
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
        
        .btn-danger {
            background-color: #dc3545;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .subject-list {
            margin-bottom: 1.5rem;
        }
        
        .subject-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border: 1px solid var(--gray);
            border-radius: 4px;
            margin-bottom: 0.5rem;
        }
        
        .subject-name {
            font-weight: 500;
        }
        
        .subject-rate {
            color: var(--dark-gray);
        }
        
        .subject-actions {
            display: flex;
            gap: 10px;
        }
        
        .subject-actions button {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--dark-gray);
            transition: color 0.3s;
        }
        
        .subject-actions button:hover {
            color: var(--primary);
        }
        
        .add-subject-form {
            display: flex;
            gap: 10px;
            margin-bottom: 1rem;
        }
        
        .add-subject-form select,
        .add-subject-form input {
            flex: 1;
            padding: 0.75rem;
            border: 1px solid var(--gray);
            border-radius: 4px;
            font-size: 1rem;
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
            <a href="tutor_main_page.php">Schedule management</a>
            <a href="tutor_profile.php" class="active">Profile</a>
            <a href="tutor_requests.php">Appointment Requests
                <?php if($pending_requests > 0): ?>
                <span class="notification-badge"><?php echo $pending_requests; ?></span>
                <?php endif; ?>
            </a>
            <a href="tutor_students.php">My Students</a>
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
                    <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile" class="profile-image">
                    <?php else: ?>
                    <div class="profile-image-placeholder"><?php echo strtoupper(substr($first_name, 0, 1)); ?></div>
                    <?php endif; ?>
                    <label for="profile_image_upload" class="edit-profile-image">
                        <i>📷</i>
                    </label>
                    <form id="image-upload-form" action="" method="post" enctype="multipart/form-data" style="display: none;">
                        <input type="file" id="profile_image_upload" name="profile_image" accept="image/*" onchange="document.getElementById('image-upload-form').submit();">
                    </form>
                </div>

                <h2 class="profile-name"><?php echo htmlspecialchars($first_name . ' ' . $last_name); ?></h2>
                <p class="profile-role">Tutor</p>

                <?php if($is_verified): ?>
                <div class="verified-badge">Verified Tutor</div>
                <?php endif; ?>

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
                </div>
            </div>

            <div class="profile-content">
                <div class="profile-section">
                    <h3 class="section-title">Personal Information</h3>
                    <form action="" method="post">
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
                             <label for="year">Year</label>
                             <select class="form-control" id="year" name="year">
                             <option value="" <?php echo $year == '' ? 'selected' : ''; ?>>-- Select Year --</option>
                             <option value="Year 1" <?php echo $year == 'Year 1' ? 'selected' : ''; ?>>Year 1</option>
                             <option value="Year 2" <?php echo $year == 'Year 2' ? 'selected' : ''; ?>>Year 2</option>
                             <option value="Year 3" <?php echo $year == 'Year 3' ? 'selected' : ''; ?>>Year 3</option>
                             <option value="Year 4" <?php echo $year == 'Year 4' ? 'selected' : ''; ?>>Year 4</option>
                             <option value="Foundation" <?php echo $year == 'Foundation' ? 'selected' : ''; ?>>Foundation</option>
                             <option value="Diploma" <?php echo $year == 'Diploma' ? 'selected' : ''; ?>>Diploma</option>
                             <option value="Master" <?php echo $year == 'Master' ? 'selected' : ''; ?>>Master</option>
                             <option value="PhD" <?php echo $year == 'PhD' ? 'selected' : ''; ?>>PhD</option>
                             </select>
                        </div>
                        <div class="form-group">
                            <label for="bio">About me</label>
                            <textarea class="form-control" id="bio" name="bio" placeholder="Introduce yourself, including your teaching style, experience, etc."><?php echo htmlspecialchars($bio); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="qualifications">Qualifications/Certificate</label>
                            <textarea class="form-control" id="qualifications" name="qualifications" placeholder="List your academic achievements, certifications, or relevant experience"><?php echo htmlspecialchars($qualifications); ?></textarea>
                        </div>
                        <button type="submit" class="btn">Save Profile</button>
                    </form>
                </div>

                <div class="profile-section">
                    <h3 class="section-title">Subjects Taught</h3>

                    <form action="" method="post" class="add-subject-form">
                        <input type="hidden" name="add_subject" value="1">
                        <select name="subject_id" class="form-control" required>
                            <option value="">-- Select Discipline --</option>
                            <?php foreach($all_subjects as $subject): ?>
                            <option value="<?php echo $subject['subject_id']; ?>"><?php echo htmlspecialchars($subject['subject_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" name="hourly_rate" class="form-control" placeholder="Hourly Rate (¥)" min="1" step="1" required>
                        <button type="submit" class="btn btn-secondary">Add Subject</button>
                    </form>

                    <?php if(empty($tutor_subjects)): ?>
                    <p>You haven't added any subjects yet. Please use the form above to add subjects you can teach.</p>
                    <?php else: ?>
                    <div class="subject-list">
                        <?php foreach($tutor_subjects as $subject): ?>
                        <div class="subject-item">
                            <div class="subject-name"><?php echo htmlspecialchars($subject['subject_name']); ?></div>
                            <div class="subject-rate">¥<?php echo htmlspecialchars($subject['hourly_rate']); ?>/hour</div>
                            <div class="subject-actions">
                                <form action="" method="post" style="display: inline;">
                                    <input type="hidden" name="remove_subject" value="1">
                                    <input type="hidden" name="subject_id" value="<?php echo $subject['subject_id']; ?>">
                                    <button type="submit" onclick="return confirm('Are you sure you want to remove this subject?')">
                                        <i>🗑️</i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
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
    </script>
</body>
</html>
