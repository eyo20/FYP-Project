<?php
// Start session
session_start();
require_once 'db_connection.php';

// Check database connection
if ($conn->connect_error) {
    $error_message = "Database connection lost: " . $conn->connect_error;
    error_log($error_message);
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log POST data for debugging
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST data received: " . print_r($_POST, true));
    error_log("FILES data received: " . print_r($_FILES, true));
}

// Check if user is logged in and has tutor role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutor') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get notification counts for navbar
$pending_requests = 0;
$unread_messages = 0;

// Query unread messages count
$message_query = "SELECT COUNT(*) as count FROM message WHERE receiver_id = ? AND is_read = 0";
$stmt = $conn->prepare($message_query);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $unread_messages = $row['count'];
    }
    $stmt->close();
} else {
    $error_message = "Database Error: Failed to prepare message count statement - " . $conn->error;
    error_log($error_message);
    $unread_messages = 0;
}

// Check for flash messages in session
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Fetch user information
$user_query = "SELECT u.*, tp.major, tp.year, tp.bio, tp.qualifications, tp.is_verified, cf.file_path as cgpa_file
               FROM user u 
               LEFT JOIN tutorprofile tp ON u.user_id = tp.user_id
               LEFT JOIN credential_file cf ON u.user_id = cf.user_id AND cf.file_type = 'cgpa'
               WHERE u.user_id = ?";

if ($stmt = $conn->prepare($user_query)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        header('Location: logout.php');
        exit;
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    $first_name = $user['first_name'];
    $last_name = $user['last_name'];
    $email = $user['email'];
    $phone = $user['phone'] ?? '';
    $major = $user['major'] ?? '';
    $year = $user['year'] ?? '';
    $bio = $user['bio'] ?? '';
    $qualifications = $user['qualifications'] ?? '';
    $profile_image = $user['profile_image'] ?? '';
    $is_verified = $user['is_verified'] ?? 0;
    $cgpa_file = $user['cgpa_file'] ?? '';
} else {
    $error_message = "Database error: " . $conn->error;
    error_log($error_message);
}

// Fetch all courses
$all_courses_query = "SELECT id,  course_name FROM course ORDER BY course_name";
$all_courses_result = $conn->query($all_courses_query);
$all_courses = [];
while ($course = $all_courses_result->fetch_assoc()) {
    $all_courses[] = $course;
}

// Fetch tutor's courses
$tutor_courses_query = "SELECT ts.*, c.course_name
                       FROM tutorsubject ts
                       JOIN course c ON ts.course_id = c.id
                       WHERE ts.tutor_id = ?";
$stmt = $conn->prepare($tutor_courses_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$courses_result = $stmt->get_result();
$tutor_courses = [];
while ($course = $courses_result->fetch_assoc()) {
    $tutor_courses[] = $course;
}

// Fetch user's current CGPA file
$user_cgpa_file = null;
$sql = "SELECT file_path, file_name FROM credential_file WHERE user_id = ? AND file_type = 'cgpa'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if (!empty($row['file_path'])) {
        $user_cgpa_file = $row['file_path'];
        $user_cgpa_filename = $row['file_name'];
    }
}

// Handle CGPA file deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_cgpa_file') {
    $sql = "SELECT file_path FROM credential_file WHERE user_id = ? AND file_type = 'cgpa'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $file_path = $row['file_path'];

        error_log("Attempting to delete file: $file_path for user ID: $user_id");

        if (!empty($file_path) && file_exists($file_path)) {
            if (unlink($file_path)) {
                error_log("File successfully deleted from filesystem: $file_path");
            } else {
                error_log("Failed to delete file from filesystem: $file_path");
            }
        } else {
            error_log("File does not exist or path is empty: $file_path");
        }

        $update_sql = "UPDATE credential_file SET file_path = NULL, file_name = NULL, file_type = NULL, status = 'pending' WHERE user_id = ? AND file_type = 'cgpa'";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("i", $user_id);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "CGPA credential file deleted successfully.";
            error_log("Database updated: CGPA credential file record cleared for user ID: $user_id");
            $user_cgpa_file = null; // Update local variable
        } else {
            $_SESSION['error_message'] = "Failed to update database after file deletion. Error: " . $conn->error;
            error_log("Database update failed after file deletion for user ID: $user_id. Error: " . $conn->error);
        }

        // No redirect to allow other form processing
    }
}

// Handle CGPA file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['cgpa_file']) && $_FILES['cgpa_file']['error'] == 0) {
    $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png'];
    $max_size = 10 * 1024 * 1024;

    if (in_array($_FILES['cgpa_file']['type'], $allowed_types) && $_FILES['cgpa_file']['size'] <= $max_size) {
        $upload_dir = 'Uploads/credentials/';

        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $filename = uniqid() . '_' . $_FILES['cgpa_file']['name'];
        $target_file = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['cgpa_file']['tmp_name'], $target_file)) {
            $check_credential = "SELECT * FROM credential_file WHERE user_id = ? AND file_type = 'cgpa'";
            $stmt = $conn->prepare($check_credential);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $old_file = $row['file_path'];

                if (!empty($old_file) && file_exists($old_file) && $old_file != $target_file) {
                    unlink($old_file);
                    error_log("Old file deleted: $old_file");
                }

                $update_credential = "UPDATE credential_file SET 
                    file_name = ?, 
                    file_path = ?, 
                    file_type = ?,
                    upload_date = NOW(),
                    status = 'pending' 
                    WHERE user_id = ? AND file_type = 'cgpa'";
                $stmt = $conn->prepare($update_credential);
                $file_name = $_FILES['cgpa_file']['name'];
                $file_type = $_FILES['cgpa_file']['type'];
                $stmt->bind_param("sssi", $file_name, $target_file, $file_type, $user_id);
                $credential_updated = $stmt->execute();
            } else {
                $insert_credential = "INSERT INTO credential_file 
                    (user_id, file_name, file_path, file_type, status) 
                    VALUES (?, ?, ?, ?, 'pending')";
                $stmt = $conn->prepare($insert_credential);
                $file_name = $_FILES['cgpa_file']['name'];
                $file_type = $_FILES['cgpa_file']['type'];
                $stmt->bind_param("isss", $user_id, $file_name, $target_file, $file_type);
                $credential_updated = $stmt->execute();
            }

            if (isset($credential_updated) && $credential_updated) {
                $user_cgpa_file = $target_file;
                $user_cgpa_filename = $file_name;
                $_SESSION['success_message'] = "CGPA credential file uploaded successfully. It will be reviewed by an administrator.";
                error_log("CGPA credential file updated for user ID: $user_id, file: $target_file");
            } else {
                $_SESSION['error_message'] = "Failed to update CGPA credential file information in the database. Error: " . $conn->error;
                error_log("Failed to update CGPA credential file for user ID: $user_id. Error: " . $conn->error);
                unlink($target_file); // Remove uploaded file
            }
        } else {
            $_SESSION['error_message'] = "Failed to upload CGPA credential file. Please try again.";
            error_log("Failed to move uploaded file for user ID: $user_id");
        }
    } else {
        $_SESSION['error_message'] = "Please upload a valid file (PDF, Word, or image) no larger than 10MB.";
        error_log("Invalid file type or size for user ID: $user_id");
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle profile update
    if (isset($_POST['update_profile'])) {
        error_log("Processing profile update");
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $phone = trim($_POST['phone']);
        $major = trim($_POST['major']);
        $year = trim($_POST['year']);
        $bio = trim($_POST['bio']);
        $qualifications = trim($_POST['qualifications']);

        $phone_valid = false;
        if (!empty($phone)) {
            $phone = preg_replace('/[^0-9]/', '', $phone);
            if (substr($phone, 0, 3) === '011') {
                $phone_valid = (strlen($phone) === 11);
            } else if (substr($phone, 0, 2) === '01') {
                $phone_valid = (strlen($phone) === 10);
            }

            if (!$phone_valid) {
                $_SESSION['error_message'] = "Invalid Malaysian phone number format. Numbers starting with 011 should be 11 digits, others should be 10 digits.";
                error_log($_SESSION['error_message']);
            }
        } else {
            $phone_valid = true;
        }

        if ($phone_valid) {
            // Check phone uniqueness
            $check_phone = "SELECT user_id FROM user WHERE phone = ? AND user_id != ?";
            $stmt = $conn->prepare($check_phone);
            $stmt->bind_param("si", $phone, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $_SESSION['error_message'] = "Phone number already in use!";
                error_log("Duplicate phone for user_id: $user_id: $phone");
            } else {
                $update_user_query = "UPDATE user SET
                                     first_name = ?,
                                     last_name = ?,
                                     phone = ?
                                     WHERE user_id = ?";

                $stmt = $conn->prepare($update_user_query);
                if (!$stmt) {
                    $_SESSION['error_message'] = "Database Error: Failed to prepare user update statement - " . $conn->error;
                    error_log($_SESSION['error_message']);
                } else {
                    $stmt->bind_param("sssi", $first_name, $last_name, $phone, $user_id);
                    $user_updated = $stmt->execute();
                    if (!$user_updated) {
                        $_SESSION['error_message'] = "Database Error: Failed to update user information - " . $stmt->error;
                        error_log($_SESSION['error_message']);
                    }
                    $stmt->close();
                }

                $check_tutor = "SELECT user_id FROM tutorprofile WHERE user_id = ?";
                $stmt = $conn->prepare($check_tutor);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $update_tutor_query = "UPDATE tutorprofile SET
                                           major = ?,
                                           year = ?,
                                           bio = ?,
                                           qualifications = ?
                                           WHERE user_id = ?";
                    $stmt = $conn->prepare($update_tutor_query);
                } else {
                    $update_tutor_query = "INSERT INTO tutorprofile (user_id, major, year, bio, qualifications)
                                           VALUES (?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($update_tutor_query);
                }

                if (!$stmt) {
                    $_SESSION['error_message'] = "Database Error: Failed to prepare tutor update statement - " . $conn->error;
                    error_log($_SESSION['error_message']);
                } else {
                    $stmt->bind_param("ssssi", $major, $year, $bio, $qualifications, $user_id);
                    $tutor_updated = $stmt->execute();
                    if (!$tutor_updated) {
                        $_SESSION['error_message'] = "Database Error: Failed to update tutor information - " . $stmt->error;
                        error_log($_SESSION['error_message']);
                    }
                    $stmt->close();
                }

                if (isset($user_updated) && $user_updated && isset($tutor_updated) && $tutor_updated) {
                    $_SESSION['success_message'] = "Profile updated successfully!";
                }
            }
        }
    }
    // Handle course addition
    else if (isset($_POST['add_subject'])) {
        error_log("Processing add course");
        $course_id = intval($_POST['course_id']);
        $hourly_rate = floatval($_POST['hourly_rate']);

        error_log("User ID: $user_id, Course ID: $course_id, Hourly Rate: $hourly_rate");

        if (empty($course_id) || empty($hourly_rate)) {
            $_SESSION['error_message'] = "请选择课程并输入每小时收费！";
            error_log("验证失败：检测到空值");
        } else {
            // 检查重复课程
            $check_query = "SELECT id FROM tutorsubject WHERE tutor_id = ? AND course_id = ?";
            $stmt = $conn->prepare($check_query);
            if (!$stmt) {
                $_SESSION['error_message'] = "数据库错误：无法准备查询！";
                error_log("准备查询失败： " . $conn->error);
            } else {
                $stmt->bind_param("ii", $user_id, $course_id);
                $stmt->execute();
                $result = $stmt->get_result();

                error_log("查询重复课程结果： num_rows = " . $result->num_rows);

                if ($result->num_rows > 0) {
                    $_SESSION['error_message'] = "此课程已添加，请选择其他课程！";
                    error_log("检测到重复课程：tutor_id=$user_id, course_id=$course_id");
                    $stmt->close();
                } else {
                    // 验证 course_id
                    $check_course = "SELECT id FROM course WHERE id = ?";
                    $stmt = $conn->prepare($check_course);
                    if (!$stmt) {
                        $_SESSION['error_message'] = "数据库错误：无法验证课程！";
                        error_log("验证课程查询失败： " . $conn->error);
                    } else {
                        $stmt->bind_param("i", $course_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $valid_course = ($result->num_rows > 0);

                        if (!$valid_course) {
                            $_SESSION['error_message'] = "选择的课程无效！";
                            error_log("外键验证失败：course_id=$course_id 不存在");
                        } else {
                            $insert_query = "INSERT INTO tutorsubject (tutor_id, course_id, hourly_rate) VALUES (?, ?, ?)";
                            $stmt = $conn->prepare($insert_query);
                            if (!$stmt) {
                                $_SESSION['error_message'] = "数据库错误：无法准备插入查询！";
                                error_log("准备插入查询失败： " . $conn->error);
                            } else {
                                $stmt->bind_param("iid", $user_id, $course_id, $hourly_rate);
                                if ($stmt->execute()) {
                                    $_SESSION['success_message'] = "课程添加成功！";
                                    error_log("课程添加成功，用户ID: $user_id, 课程ID: $course_id");
                                } else {
                                    $_SESSION['error_message'] = "添加课程失败。错误: " . $stmt->error;
                                    error_log("添加课程失败，用户ID: $user_id, 错误: " . $stmt->error);
                                }
                            }
                        }
                    }
                    $stmt->close();
                }
            }
        }
    }
    // Refresh page to display notification
    header("Location: tutor_profile.php");
    exit();

    // Handle course deletion
    if (isset($_POST['remove_subject'])) {
        error_log("Processing remove course");
        $course_id = intval($_POST['course_id']);

        $delete_query = "DELETE FROM tutorsubject WHERE tutor_id = ? AND course_id = ?";
        $stmt = $conn->prepare($delete_query);
        if (!$stmt) {
            $_SESSION['error_message'] = "Database Error: Failed to prepare delete statement - " . $conn->error;
            error_log($_SESSION['error_message']);
        } else {
            $stmt->bind_param("ii", $user_id, $course_id);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Course removed successfully!";
                error_log("Course removed successfully for user ID: $user_id");
            } else {
                $_SESSION['error_message'] = "Failed to remove course. Error: " . $stmt->error;
                error_log("Failed to remove course for user ID: $user_id. Error: " . $stmt->error);
            }
            $stmt->close();
        }
    }
    // Handle profile image upload
    else if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        error_log("Processing profile image upload");
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024;

        if (in_array($_FILES['profile_image']['type'], $allowed_types) && $_FILES['profile_image']['size'] <= $max_size) {
            $upload_dir = 'Uploads/profile_images/';

            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $filename = $user_id . '_' . time() . '_' . $_FILES['profile_image']['name'];
            $target_file = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                // Delete old profile image
                if (!empty($profile_image) && file_exists($profile_image)) {
                    unlink($profile_image);
                    error_log("Old profile image deleted: $profile_image");
                }

                $update_query = "UPDATE user SET profile_image = ? WHERE user_id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("si", $target_file, $user_id);

                if ($stmt->execute()) {
                    $profile_image = $target_file;
                    $_SESSION['success_message'] = "Profile image updated successfully!";
                    error_log("Profile image updated for user ID: $user_id");
                } else {
                    $_SESSION['error_message'] = "Failed to update profile image in database. Error: " . $stmt->error;
                    error_log("Failed to update profile image for user ID: $user_id. Error: " . $stmt->error);
                    unlink($target_file);
                }
            } else {
                $_SESSION['error_message'] = "Failed to upload profile image. Please try again.";
                error_log("Failed to move profile image for user ID: $user_id");
            }
        } else {
            $_SESSION['error_message'] = "Please upload a valid image file (JPEG, PNG, or GIF) no larger than 5MB.";
            error_log("Invalid profile image type or size for user ID: $user_id");
        }
    }
    // Handle password change
    else if (isset($_POST['change_password'])) {
        error_log("Processing password change");
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        $check_password_query = "SELECT password FROM user WHERE user_id = ?";
        $stmt = $conn->prepare($check_password_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();

        if (password_verify($current_password, $user_data['password'])) {
            if ($new_password === $confirm_password) {
                if (strlen($new_password) >= 8) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_password_query = "UPDATE user SET password = ? WHERE user_id = ?";
                    $stmt = $conn->prepare($update_password_query);
                    $stmt->bind_param("si", $hashed_password, $user_id);

                    if ($stmt->execute()) {
                        $_SESSION['success_message'] = "Password updated successfully!";
                        error_log("Password updated for user ID: $user_id");
                    } else {
                        $_SESSION['error_message'] = "Failed to update password. Error: " . $stmt->error;
                        error_log("Failed to update password for user ID: $user_id. Error: " . $stmt->error);
                    }
                } else {
                    $_SESSION['error_message'] = "New password must be at least 8 characters long.";
                    error_log("Password too short for user ID: $user_id");
                }
            } else {
                $_SESSION['error_message'] = "New passwords do not match.";
                error_log("Password mismatch for user ID: $user_id");
            }
        } else {
            $_SESSION['error_message'] = "Current password is incorrect.";
            error_log("Incorrect current password for user ID: $user_id");
        }
    }
}

// Refresh user information
$refresh_user_query = "SELECT u.*, tp.major, tp.year, tp.bio, tp.qualifications, tp.is_verified, cf.file_path as cgpa_file
                      FROM user u 
                      LEFT JOIN tutorprofile tp ON u.user_id = tp.user_id
                      LEFT JOIN credential_file cf ON u.user_id = cf.user_id AND cf.file_type = 'cgpa'
                      WHERE u.user_id = ?";
$stmt = $conn->prepare($refresh_user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Re-fetch tutor's courses
$stmt = $conn->prepare($tutor_courses_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$courses_result = $stmt->get_result();
$tutor_courses = [];
while ($course = $courses_result->fetch_assoc()) {
    $tutor_courses[] = $course;
}

$first_name = $user['first_name'];
$last_name = $user['last_name'];
$email = $user['email'];
$phone = $user['phone'] ?? '';
$major = $user['major'] ?? '';
$year = $user['year'] ?? '';
$bio = $user['bio'] ?? '';
$qualifications = $user['qualifications'] ?? '';
$profile_image = $user['profile_image'] ?? '';
$is_verified = $user['is_verified'] ?? 0;
$cgpa_file = $user['cgpa_file'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peer Tutoring Platform - Tutor Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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
            font-weight: bold;
            border: 3px solid var(--primary);
        }

        .edit-profile-image {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 40px;
            height: 40px;
            background-color: var(--accent);
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
            text-align: right;
            /* 右对齐内容 */
            width: 100%;
            /* 确保 div 填满 td */
            display: inline-block;
            /* 使 div 表现如内联块元素 */

        }

        .hourly-rate-cell {
            width: 120px;
            /* 固定列宽，确保一致性 */
            text-align: right;
            /* 确保 td 内容右对齐 */
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
            flex-wrap: wrap;
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
            min-width: 150px;
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

        .custom-alert {
            display: flex;
            align-items: center;
            border-radius: 8px;
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            padding: 12px 15px;
        }

        .alert-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #2e7d32;
        }

        .alert-danger {
            background-color: #fdecea;
            color: #d32f2f;
            border-left: 4px solid #d32f2f;
        }

        .alert-icon {
            margin-right: 15px;
            font-size: 20px;
        }

        .alert-content {
            flex-grow: 1;
        }

        .custom-alert .close {
            padding: 0;
            background-color: transparent;
            border: 0;
            font-size: 1.5rem;
            opacity: 0.5;
            cursor: pointer;
        }

        .custom-alert .close:hover {
            opacity: 1;
        }
    </style>
</head>

<body>
    <?php include 'header/tut_head.php'; ?>

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
                    <?php if ($profile_image): ?>
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
                <?php if ($is_verified): ?>
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
                    <form action="" method="post" enctype="multipart/form-data">
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
                            <small class="form-text text-muted">Malaysian format: 011-xxxxxxxx (11 digits) or 01x-xxxxxxx (10 digits)</small>
                        </div>
                        <div class="form-group">
                            <label for="major">Major</label>
                            <input type="text" class="form-control" id="major" name="major" value="<?php echo htmlspecialchars($major); ?>">
                        </div>
                        <div class="form-group">
                            <label for="year">Level</label>
                            <select class="form-control" id="year" name="year">
                                <option value="" <?php echo $year == '' ? 'selected' : ''; ?>>-- Select level --</option>

                                <option value="Degree first year" <?php echo $year == 'Degree first year' ? 'selected' : ''; ?>>Degree first year</option>
                                <option value="Degree second year" <?php echo $year == 'Degree second year' ? 'selected' : ''; ?>>Degree second year</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="cgpa_file">Cgpa Credentials</label>
                            <input type="file" class="form-control" id="cgpa_file" name="cgpa_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <small class="form-text text-muted">Upload your Cgpa transcripts (PDF, Word, or image files)</small>

                            <?php if (isset($user_cgpa_file) && !empty($user_cgpa_file)): ?>
                                <div class="mt-2 border p-2 bg-light">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-file"></i> Current file: <?php echo htmlspecialchars($user_cgpa_filename); ?>
                                        </div>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="delete_cgpa_file">
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this file?');">
                                                <i class="fas fa-trash"></i> Delete File
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endif; ?>
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
                    <h3 class="section-title">Courses Taught</h3>

                    <?php if (isset($_GET['success']) && $_GET['success'] == 'course_added'): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            Course added successfully!
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">×</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <form action="" method="post" class="add-subject-form">
                        <input type="hidden" name="add_subject" value="1">
                        <div class="form-group">
                            <select name="course_id" id="course_select" class="form-control" required>
                                <option value="">-- Select Course --</option>
                                <?php foreach ($all_courses as $course): ?>
                                    <option value="<?php echo $course['id']; ?>">
                                        <?php echo htmlspecialchars($course['course_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <input type="number" name="hourly_rate" class="form-control" placeholder="Hourly Rate (RM)" min="1" step="1" required>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-secondary">Add Course</button>
                        </div>
                    </form>

                    <?php if (empty($tutor_courses)): ?>
                        <p>You haven't added any courses yet. Please use the form above to add courses you can teach.</p>
                    <?php else: ?>
                        <div class="subject-list">
                            <?php foreach ($tutor_courses as $course): ?>
                                <div class="subject-item">
                                    <div class="subject-info">
                                        <div class="course-name"><?php echo htmlspecialchars($course['course_name']); ?> </div>
                                    </div>
                                    <td class="px-6 py-4 whitespace-nowrap hourly-rate-cell">
                                        <div class="subject-rate">RM<?php echo number_format($course['hourly_rate'], 2); ?>/hour</div>
                                    </td>
                                    <div class="subject-actions">
                                        <form action="" method="post" style="display: inline;">
                                            <input type="hidden" name="remove_subject" value="1">
                                            <input type="hidden" name="course_id" value="<?php echo $course['course_id']; ?>">
                                            <button type="submit" onclick="return confirm('Are you sure you want to remove this course?')">
                                                <i>🗑️</i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="profile-section">
                    <h3 class="section-title">Change Password</h3>
                    <form action="" method="post">
                        <input type="hidden" name="change_password" value="1">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                            <small class="form-text text-muted">Password must be at least 8 characters long</small>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                        </div>
                        <button type="submit" class="btn">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>© 2025 PeerLearn - Peer Tutoring Platform. All rights reserved.</p>
    </footer>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Mobile menu toggle
        document.querySelector('.menu-toggle').addEventListener('click', function() {
            document.querySelector('.nav-links').classList.toggle('show');
        });

        // Initialize Select2
        $(document).ready(function() {
            $('#course_select').select2({
                placeholder: "-- Select Course --",
                allowClear: true,
                width: '100%'
            });
        });
    </script>
</body>

</html>