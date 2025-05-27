<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $session_id = isset($_POST['session_id']) ? (int)$_POST['session_id'] : 0;
    $tutor_id = isset($_POST['tutor_id']) ? (int)$_POST['tutor_id'] : 0;
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

    // Validate input
    if (!$session_id || !$tutor_id || !$rating || $rating < 1 || $rating > 5) {
        $error_message = 'Invalid rating data provided.';
    } else {
        try {
            $pdo->beginTransaction();

            // Verify that this session belongs to the current student and is completed
            $stmt = $pdo->prepare("
                SELECT session_id, status 
                FROM session 
                WHERE session_id = ? AND student_id = ? AND tutor_id = ? AND status = 'completed'
            ");
            $stmt->execute([$session_id, $_SESSION['user_id'], $tutor_id]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$session) {
                throw new Exception('Session not found or not eligible for review.');
            }

            // Check if review already exists
            $stmt = $pdo->prepare("
                SELECT review_id 
                FROM review 
                WHERE session_id = ? AND student_id = ? AND tutor_id = ?
            ");
            $stmt->execute([$session_id, $_SESSION['user_id'], $tutor_id]);
            $existing_review = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing_review) {
                throw new Exception('You have already reviewed this session.');
            }

            // Insert the review
            $stmt = $pdo->prepare("
                INSERT INTO review (session_id, student_id, tutor_id, rating, comment, created_at, is_approved)
                VALUES (?, ?, ?, ?, ?, NOW(), 1)
            ");
            $stmt->execute([$session_id, $_SESSION['user_id'], $tutor_id, $rating, $comment]);

            // Update tutor's average rating
            $stmt = $pdo->prepare("
                UPDATE tutorprofile 
                SET rating = (
                    SELECT AVG(rating) 
                    FROM review 
                    WHERE tutor_id = ? AND is_approved = 1
                ),
                total_sessions = (
                    SELECT COUNT(*) 
                    FROM session 
                    WHERE tutor_id = ? AND status = 'completed'
                )
                WHERE user_id = ?
            ");
                        $stmt->execute([$tutor_id, $tutor_id, $tutor_id]);

            $pdo->commit();
            $success_message = 'Thank you for your review! Your feedback has been submitted successfully.';

        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = $e->getMessage();
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Database error in submit_review.php: " . $e->getMessage());
            $error_message = 'An error occurred while submitting your review. Please try again.';
        }
    }
}

// Redirect back to sessions page with message
if ($success_message) {
    $_SESSION['success_message'] = $success_message;
} elseif ($error_message) {
    $_SESSION['error_message'] = $error_message;
}

header('Location: student_sessions.php');
exit();
?>

