<?php
session_start();
require_once "db_connection.php";

// Enable error display for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user information
$user_query = "SELECT username, email, role, first_name, last_name, phone, profile_image, last_login FROM user WHERE user_id = ?";
$stmt = $conn->prepare($user_query);
if (!$stmt) {
    die("Prepare query failed: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();

if (!$user_data || $user_data['role'] != 'student') {
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

// Get student information
$student_query = "SELECT major, year, school FROM studentprofile WHERE user_id = ?";
$stmt = $conn->prepare($student_query);
if (!$stmt) {
    die("Prepare query failed: " . $conn->error);
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
    $major = '';
    $year = '';
    $school = '';
}
$stmt->close();

// Get the number of unread messages
$unread_messages_query = "SELECT COUNT(*) as unread_count FROM message WHERE receiver_id = ? AND is_read = 0";
$stmt = $conn->prepare($unread_messages_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$messages_result = $stmt->get_result();
$messages_data = $messages_result->fetch_assoc();
$unread_messages = $messages_data['unread_count'];
$stmt->close();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Update basic information
        $first_name = $_POST['first_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $major = $_POST['major'] ?? '';
        $year = $_POST['year'] ?? '';
        $school = $_POST['school'] ?? '';

        // Verify Malaysia phone number
        $phone_valid = false;
        if (!empty($phone)) {
            $phone = preg_replace('/[^0-9]/', '', $phone);
            if (substr($phone, 0, 3) === '011') {
                $phone_valid = (strlen($phone) === 11);
            } else if (substr($phone, 0, 2) === '01') {
                $phone_valid = (strlen($phone) === 10);
            }
            if (!$phone_valid) {
                $error_message = "Invalid Malaysian phone number format. Numbers starting with 011 should be 11 digits, others should be 10 digits.";
                error_log($error_message);
            }
        } else {
            $phone_valid = true;
        }

        if ($phone_valid) {
            // Start transaction for user and profile updates
            $conn->begin_transaction();
            try {
                // Update user information
                $update_user = "UPDATE user SET first_name = ?, last_name = ?, phone = ? WHERE user_id = ?";
                $stmt = $conn->prepare($update_user);
                if (!$stmt) {
                    throw new Exception("Failed to prepare user update statement: " . $conn->error);
                }
                $stmt->bind_param("sssi", $first_name, $last_name, $phone, $user_id);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update user information: " . $stmt->error);
                }
                $stmt->close();

                // Check if student profile exists
                $check_profile = "SELECT user_id FROM studentprofile WHERE user_id = ?";
                $stmt = $conn->prepare($check_profile);
                if (!$stmt) {
                    throw new Exception("Failed to prepare profile check statement: " . $conn->error);
                }
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $profile_result = $stmt->get_result();
                $profile_exists = ($profile_result->num_rows > 0);
                $stmt->close();

                if ($profile_exists) {
                    // Update student profile
                    $update_profile = "UPDATE studentprofile SET major = ?, year = ?, school = ? WHERE user_id = ?";
                    $stmt = $conn->prepare($update_profile);
                    if (!$stmt) {
                        throw new Exception("Failed to prepare profile update statement: " . $conn->error);
                    }
                    $stmt->bind_param("sssi", $major, $year, $school, $user_id);
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to update student profile: " . $stmt->error);
                    }
                    $stmt->close();
                } else {
                    // Create student profile
                    $create_profile = "INSERT INTO studentprofile (user_id, major, year, school) VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($create_profile);
                    if (!$stmt) {
                        throw new Exception("Failed to prepare profile create statement: " . $conn->error);
                    }
                    $stmt->bind_param("isss", $user_id, $major, $year, $school);
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to create student profile: " . $stmt->error);
                    }
                    $stmt->close();
                }

                $conn->commit();
                $success_message = "Profile updated successfully!";
            } catch (Exception $e) {
                $conn->rollback();
                $error_message = "Error updating profile: " . $e->getMessage();
                error_log($error_message);
            }
        }
    }

    // Handle profile image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        $upload_dir = __DIR__ . '/Uploads/profile_images/';

        // Create upload directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0775, true)) {
                $error_message = "Failed to create upload directory.";
                error_log($error_message);
            }
        }

        // Check for upload errors
        switch ($_FILES['profile_image']['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
                $error_message = "The uploaded file exceeds the maximum allowed size.";
                error_log($error_message);
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $error_message = "The uploaded file exceeds the form's maximum size.";
                error_log($error_message);
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_message = "The file was only partially uploaded.";
                error_log($error_message);
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $error_message = "Missing a temporary folder.";
                error_log($error_message);
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $error_message = "Failed to write file to disk.";
                error_log($error_message);
                break;
            case UPLOAD_ERR_EXTENSION:
                $error_message = "A PHP extension stopped the file upload.";
                error_log($error_message);
                break;
            default:
                $error_message = "Unknown upload error.";
                error_log($error_message);
                break;
        }

        if (!isset($error_message)) {
            // Validate file type and size
            if (!in_array($_FILES['profile_image']['type'], $allowed_types)) {
                $error_message = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
                error_log("Invalid file type: " . $_FILES['profile_image']['type']);
            } elseif ($_FILES['profile_image']['size'] > $max_size) {
                $error_message = "File is too large. Maximum size is 5MB.";
                error_log("File size exceeded: " . $_FILES['profile_image']['size']);
            } else {
                // Validate image integrity
                if (!getimagesize($_FILES['profile_image']['tmp_name'])) {
                    $error_message = "Uploaded file is not a valid image.";
                    error_log($error_message);
                } else {
                    $filename = uniqid() . '_' . basename($_FILES['profile_image']['name']);
                    $target_file = $upload_dir . $filename;

                    // Start transaction for file move and database update
                    $conn->begin_transaction();
                    try {
                        if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                            throw new Exception("Failed to move uploaded file to $target_file.");
                        }

                        // Delete old profile image if it exists
                        if ($user_data['profile_image'] && file_exists($user_data['profile_image'])) {
                            unlink($user_data['profile_image']);
                        }

                        // Update database with new image path
                        $relative_path = 'Uploads/profile_images/' . $filename;
                        $update_image = "UPDATE user SET profile_image = ? WHERE user_id = ?";
                        $stmt = $conn->prepare($update_image);
                        if (!$stmt) {
                            throw new Exception("Failed to prepare image update statement: " . $conn->error);
                        }
                        $stmt->bind_param("si", $relative_path, $user_id);
                        if (!$stmt->execute()) {
                            throw new Exception("Failed to update profile image: " . $stmt->error);
                        }
                        $stmt->close();

                        $conn->commit();
                        $profile_image = $relative_path;
                        $success_message = "Profile image uploaded successfully!";
                    } catch (Exception $e) {
                        $conn->rollback();
                        if (file_exists($target_file)) {
                            unlink($target_file);
                        }
                        $error_message = "Failed to upload profile image: " . $e->getMessage();
                        error_log($error_message);
                    }
                }
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peer Tutoring Platform - Student Profile</title>
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
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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
            background-color: rgba(255, 255, 255, 0.1);
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
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
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
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
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
            .profile-image,
            .profile-image-placeholder {
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
    <?php include 'header/stud_head.php'; ?>

    <main>
        <h1 class="page-title">Profile</h1>

        <?php if (isset($success_message)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="profile-container">
            <div class="profile-sidebar">
                <div class="profile-image-container">
                    <?php if ($profile_image && file_exists($profile_image)): ?>
                        <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile" class="profile-image" id="profile-image-preview">
                    <?php else: ?>
                        <div class="profile-image-placeholder" id="profile-image-placeholder"><?php echo strtoupper(substr($first_name, 0, 1)); ?></div>
                    <?php endif; ?>
                    <label for="profile_image_upload" class="edit-profile-image">
                        <i>ðŸ“·</i>
                    </label>
                    <form id="image-upload-form" action="" method="post" enctype="multipart/form-data" style="display: none;">
                        <input type="file" id="profile_image_upload" name="profile_image" accept="image/jpeg,image/jpg,image/png,image/gif">
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
                        <button type="submit" class="btn" id="save-profile-btn">Save Profile</button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2025 PeerLearn - Peer Tutoring Platform. All rights reserved.</p>
    </footer>

    <script>
        // Dropdown functionality
        function toggleDropdown() {
            const dropdown = document.getElementById('userDropdown');
            if (dropdown) {
                dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
            }
        }

        // Mobile menu toggle
        document.querySelector('.menu-toggle')?.addEventListener('click', function() {
            document.querySelector('.nav-links')?.classList.toggle('show');
        });

        // Profile image upload preview and auto-submit
        document.getElementById('profile_image_upload').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file type and size on client side
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                const maxSize = 5 * 1024 * 1024; // 5MB
                if (!allowedTypes.includes(file.type)) {
                    alert('Invalid file type. Only JPG, PNG, and GIF are allowed.');
                    return;
                }
                if (file.size > maxSize) {
                    alert('File is too large. Maximum size is 5MB.');
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewElement = document.getElementById('profile-image-preview');
                    const placeholderElement = document.getElementById('profile-image-placeholder');

                    if (previewElement) {
                        previewElement.src = e.target.result;
                    } else if (placeholderElement) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.alt = "Profile";
                        img.className = "profile-image";
                        img.id = "profile-image-preview";
                        placeholderElement.parentNode.replaceChild(img, placeholderElement);
                    }

                    // Auto-submit the form
                    const form = document.getElementById('image-upload-form');
                    if (form) {
                        form.submit();
                    } else {
                        console.error('Image upload form not found.');
                    }
                };
                reader.onerror = function() {
                    alert('Error reading the file. Please try again.');
                };
                reader.readAsDataURL(file);
            }
        });

        // Profile form validation
        document.getElementById('profile-form').addEventListener('submit', function(e) {
            const firstName = document.getElementById('first_name').value.trim();
            const lastName = document.getElementById('last_name').value.trim();
            const phone = document.getElementById('phone').value.trim();

            if (!firstName) {
                alert('Please enter your first name');
                e.preventDefault();
                return;
            }

            if (!lastName) {
                alert('Please enter your last name');
                e.preventDefault();
                return;
            }

            if (phone && !/^\d{10,15}$/.test(phone)) {
                alert('Please enter a valid phone number (10-15 digits)');
                e.preventDefault();
                return;
            }
        });

        // Save profile confirmation
        document.getElementById('save-profile-btn').addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to save your profile changes?')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>