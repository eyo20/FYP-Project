<?php
session_start();
require_once 'db_connection.php';
error_log("Starting submit_review.php");

// 检查学生登录
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    error_log("Session check failed: user_id=" . ($_SESSION['user_id'] ?? 'unset') . ", role=" . ($_SESSION['role'] ?? 'unset'));
    header("Location: login.php");
    exit;
}

// 检查 POST 请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    header("Location: student_sessions.php");
    exit;
}

// 获取表单数据
$session_id = $_POST['session_id'] ?? 0;
$rating = $_POST['rating'] ?? 0;
$comment = $_POST['comment'] ?? '';
error_log("Received: session_id=$session_id, rating=$rating, comment=$comment");

// 验证输入
if (!is_numeric($session_id) || !is_numeric($rating) || $rating < 1 || $rating > 5) {
    error_log("Validation failed: session_id=$session_id, rating=$rating");
    $_SESSION['error'] = "Invalid session or rating.";
    header("Location: student_sessions.php");
    exit;
}

$conn->begin_transaction();
try {
    // 检查会话是否存在且已完成
    $stmt = $conn->prepare("SELECT session_id, tutor_id, status FROM session WHERE session_id = ? AND student_id = ? AND status = 'completed'");
    if (!$stmt) {
        error_log("Session query prepare failed: " . $conn->error);
        throw new Exception("Database error: Unable to check session.");
    }
    $stmt->bind_param("ii", $session_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        error_log("Session not found or not completed: session_id=$session_id, student_id=" . $_SESSION['user_id']);
        throw new Exception("Session not found or not completed.");
    }
    $session = $result->fetch_assoc();
    $user_id = $session['tutor_id'];
    $stmt->close();

    // 检查是否已评论
    $stmt = $conn->prepare("SELECT review_id FROM review WHERE session_id = ? AND student_id = ?");
    if (!$stmt) {
        error_log("Review check prepare failed: " . $conn->error);
        throw new Exception("Database error: Unable to check review.");
    }
    $stmt->bind_param("ii", $session_id, $_SESSION['user_id']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        error_log("Review already exists: session_id=$session_id, student_id=" . $_SESSION['user_id']);
        throw new Exception("You have already reviewed this session.");
    }
    $stmt->close();

    // 插入评论
    $stmt = $conn->prepare("INSERT INTO review (session_id, student_id, tutor_id, rating, comment, created_at, is_approved) VALUES (?, ?, ?, ?, ?, NOW(), 1)");
    if (!$stmt) {
        error_log("Insert review prepare failed: " . $conn->error);
        throw new Exception("Database error: Unable to insert review.");
    }
    $stmt->bind_param("iiiss", $session_id, $_SESSION['user_id'], $user_id, $rating, $comment);
    if (!$stmt->execute()) {
        error_log("Insert review failed: " . $conn->error);
        throw new Exception("Failed to insert review.");
    }
    $stmt->close();

    // 更新导师评分和会话总数
    $stmt = $conn->prepare("UPDATE tutorprofile SET rating = (SELECT AVG(rating) FROM review WHERE tutor_id = ? AND is_approved = 1), total_sessions = (SELECT COUNT(*) FROM session WHERE tutor_id = ? AND status = 'completed') WHERE user_id = ?");
    if (!$stmt) {
        error_log("Update tutorprofile prepare failed: " . $conn->error);
        throw new Exception("Database error: Unable to update tutor profile.");
    }
    $stmt->bind_param("iii", $user_id, $user_id, $user_id);
    if (!$stmt->execute()) {
        error_log("Update tutorprofile failed: " . $conn->error);
        throw new Exception("Failed to update tutor profile.");
    }
    $stmt->close();

    // 提交事务
    $conn->commit();
    error_log("Review submitted successfully: session_id=$session_id");
    $_SESSION['success'] = "Thank you for your review!";
    header("Location: student_sessions.php");
    exit;
} catch (Exception $e) {
    $conn->rollback();
    error_log("Error: " . $e->getMessage());
    $_SESSION['error'] = "Failed to submit review: " . $e->getMessage();
    header("Location: student_sessions.php");
    exit;
}

$conn->close();
?>