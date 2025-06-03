<?php
session_start();
require_once 'db_connection.php'; // 使用 MySQLi 连接

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo '<!DOCTYPE html><html><body><div class="error-message">Unauthorized access</div></body></html>';
    exit();
}

$tutor_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$tutor_id) {
    echo '<!DOCTYPE html><html><body><div class="error-message">Invalid tutor ID</div></body></html>';
    exit();
}

try {
    // Get tutor basic information
    $stmt = $conn->prepare("
        SELECT u.user_id, u.first_name, u.last_name, u.email, u.phone, u.profile_image,
               tp.major, tp.year, tp.bio, tp.qualifications, tp.is_verified, tp.rating, tp.total_sessions
        FROM user u
        LEFT JOIN tutorprofile tp ON u.user_id = tp.user_id
        WHERE u.user_id = ? AND u.role = 'tutor'
    ");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $tutor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $tutor = $result->fetch_assoc();
    $stmt->close();

    if (!$tutor) {
        echo '<!DOCTYPE html><html><body><div class="error-message">Tutor not found</div></body></html>';
        exit();
    }

    // Get tutor's course
    $stmt = $conn->prepare("
        SELECT c.course_name, ts.hourly_rate
        FROM tutorsubject ts
        LEFT JOIN course c ON ts.course_id = c.id
        WHERE ts.tutor_id = ?
        ORDER BY c.course_name
    ");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $tutor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $courses = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Get recent reviews
    $stmt = $conn->prepare("
        SELECT r.rating, r.comment, r.created_at,
               u.first_name, u.last_name
        FROM review r
        JOIN user u ON r.student_id = u.user_id
        WHERE r.tutor_id = ? AND r.is_approved = 1
        ORDER BY r.created_at DESC
        LIMIT 5
    ");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $tutor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $reviews = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Get session statistics for current user with this tutor
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_sessions,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_sessions,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_sessions,
            MAX(start_datetime) as last_session_date
        FROM session
        WHERE tutor_id = ? AND student_id = ?
    ");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("ii", $tutor_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $session_stats = $result->fetch_assoc();
    $stmt->close();

} catch (Exception $e) {
    error_log("Error in get_tutor_details.php: " . $e->getMessage());
    echo '<!DOCTYPE html><html><body><div class="error-message">Error loading tutor details: ' . htmlspecialchars($e->getMessage()) . '</div></body></html>';
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutor Details - Peer Tutoring Platform</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary: #2B3990; /* Deep blue */
            --secondary: #C4D600; /* Lime green */
            --light: #F5F5F5; /* Light gray */
            --dark-gray: #555;
            --danger: #dc3545;
            --success: #28a745;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: var(--light);
            color: var(--dark-gray);
            line-height: 1.6;
            padding: 2rem;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .tutor-profile {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .tutor-header {
            display: flex;
            gap: 1.5rem;
            align-items: flex-start;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--light);
        }

        .tutor-avatar-large {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: bold;
            overflow: hidden;
            flex-shrink: 0;
        }

        .tutor-avatar-large img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .tutor-info h2 {
            color: var(--primary);
            margin-bottom: 0.5rem;
            font-size: 1.8rem;
        }

        .tutor-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: var(--dark-gray);
        }

        .meta-item i {
            color: var(--secondary);
            width: 16px;
        }

        .verification-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.3rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
        }

        .verified {
            background: #e8f5e8;
            color: #2e7d32;
        }

        .unverified {
            background: #fff3e0;
            color: #f57c00;
        }

        .section {
            margin-bottom: 2rem;
        }

        .section h3 {
            color: var(--primary);
            margin-bottom: 1rem;
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .courses-grid {
            display: grid;
            gap: 1rem;
        }

        .course-item {
            background: var(--light);
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid var(--secondary);
        }

        .course-name {
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .course-tag {
            background: white;
            padding: 0.3rem 0.8rem;
            border-radius: 4px;
            font-size: 0.85rem;
            color: var(--dark-gray);
            border: 1px solid #ddd;
            display: inline-block;
        }

        .rate-display {
            color: var(--success);
            font-weight: bold;
            margin-top: 0.5rem;
        }

        .reviews-list {
            display: grid;
            gap: 1rem;
        }

        .review-item {
            background: var(--light);
            padding: 1rem;
            border-radius: 8px;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .reviewer-name {
            font-weight: bold;
            color: var(--primary);
        }

        .review-date {
            color: var(--dark-gray);
            font-size: 0.8rem;
        }

        .review-rating {
            display: flex;
            gap: 0.25rem;
            margin-bottom: 0.5rem;
        }

        .review-star {
            color: #ffd700;
        }

        .review-star.empty {
            color: #ddd;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1rem;
        }

        .stat-item {
            text-align: center;
            background: var(--light);
            padding: 1rem;
            border-radius: 8px;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary);
            display: block;
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--dark-gray);
            margin-top: 0.25rem;
        }

        .no-data {
            text-align: center;
            color: var(--dark-gray);
            font-style: italic;
            padding: 1rem;
        }

        .error-message {
            text-align: center;
            padding: 2rem;
            color: var(--danger);
            font-size: 1.2rem;
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }

            .container {
                padding: 1rem;
            }

            .tutor-header {
                flex-direction: column;
                text-align: center;
            }

            .tutor-meta {
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="tutor-profile">
            <!-- Tutor Header -->
            <div class="tutor-header">
                <div class="tutor-avatar-large">
                    <?php if ($tutor['profile_image']): ?>
                        <img src="<?php echo htmlspecialchars($tutor['profile_image']); ?>" 
                             alt="<?php echo htmlspecialchars($tutor['first_name']); ?>">
                    <?php else: ?>
                        <?php echo strtoupper(substr($tutor['first_name'], 0, 1)); ?>
                    <?php endif; ?>
                </div>
                
                <div class="tutor-info">
                    <h2><?php echo htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']); ?></h2>
                    
                    <div class="tutor-meta">
                        <?php if ($tutor['major']): ?>
                            <div class="meta-item">
                                <i class="fas fa-graduation-cap"></i>
                                <span><?php echo htmlspecialchars($tutor['major']); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($tutor['year']): ?>
                            <div class="meta-item">
                                <i class="fas fa-calendar"></i>
                                <span>Year <?php echo htmlspecialchars($tutor['year']); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($tutor['rating']): ?>
                            <div class="meta-item">
                                <i class="fas fa-star"></i>
                                <span><?php echo number_format($tutor['rating'], 1); ?>/5.0</span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($tutor['total_sessions']): ?>
                            <div class="meta-item">
                                <i class="fas fa-chalkboard-teacher"></i>
                                <span><?php echo $tutor['total_sessions']; ?> Sessions</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="verification-badge <?php echo $tutor['is_verified'] ? 'verified' : 'unverified'; ?>">
                        <i class="fas <?php echo $tutor['is_verified'] ? 'fa-check-circle' : 'fa-clock'; ?>"></i>
                        <?php echo $tutor['is_verified'] ? 'Verified Tutor' : 'Pending Verification'; ?>
                    </div>
                </div>
            </div>

            <!-- Bio Section -->
            <?php if ($tutor['bio']): ?>
                <div class="section">
                    <h3><i class="fas fa-user"></i> About</h3>
                    <p><?php echo nl2br(htmlspecialchars($tutor['bio'])); ?></p>
                </div>
            <?php endif; ?>

            <!-- Qualifications Section -->
            <?php if ($tutor['qualifications']): ?>
                <div class="section">
                    <h3><i class="fas fa-certificate"></i> Qualifications</h3>
                    <p><?php echo nl2br(htmlspecialchars($tutor['qualifications'])); ?></p>
                </div>
            <?php endif; ?>

            <!-- Courses Section -->
            <?php if (!empty($courses)): ?>
                <div class="section">
                    <h3><i class="fas fa-book"></i> Courses</h3>
                    <div class="courses-grid">
                        <?php foreach ($courses as $course): ?>
                            <div class="course-item">
                                <div class="course-name">
                                    <?php echo htmlspecialchars($course['course_name'] ?: 'Unnamed Course'); ?>
                                </div>
                                <div class="course-tag">
                                    <?php if ($course['hourly_rate']): ?>
                                        - RM<?php echo number_format($course['hourly_rate'], 2); ?>/hr
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="section">
                    <h3><i class="fas fa-book"></i> Courses</h3>
                    <div class="no-data">No courses available</div>
                </div>
            <?php endif; ?>

            <!-- Session History with This Tutor -->
            <?php if ($session_stats['total_sessions'] > 0): ?>
                <div class="section">
                    <h3><i class="fas fa-history"></i> Your Session History</h3>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $session_stats['total_sessions']; ?></span>
                            <div class="stat-label">Total Sessions</div>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $session_stats['completed_sessions']; ?></span>
                            <div class="stat-label">Completed</div>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $session_stats['cancelled_sessions']; ?></span>
                            <div class="stat-label">Cancelled</div>
                        </div>
                        <?php if ($session_stats['last_session_date']): ?>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo date('M j', strtotime($session_stats['last_session_date'])); ?></span>
                                <div class="stat-label">Last Session</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Recent Reviews -->
            <?php if (!empty($reviews)): ?>
                <div class="section">
                    <h3><i class="fas fa-comments"></i> Recent Reviews</h3>
                    <div class="reviews-list">
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-item">
                                <div class="review-header">
                                    <span class="reviewer-name">
                                        <?php echo htmlspecialchars($review['first_name'] . ' ' . substr($review['last_name'], 0, 1) . '.'); ?>
                                    </span>
                                    <span class="review-date">
                                        <?php echo date('M j, Y', strtotime($review['created_at'])); ?>
                                    </span>
                                </div>
                                
                                <div class="review-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'review-star' : 'review-star empty'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                
                                <?php if ($review['comment']): ?>
                                    <p><?php echo htmlspecialchars($review['comment']); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="section">
                    <h3><i class="fas fa-comments"></i> Reviews</h3>
                    <div class="no-data">No reviews yet</div>
                </div>
            <?php endif; ?>

            <!-- Contact Information -->
            <div class="section">
                <h3><i class="fas fa-envelope"></i> Contact</h3>
                <div class="tutor-meta">
                    <div class="meta-item">
                        <i class="fas fa-envelope"></i>
                        <span><?php echo htmlspecialchars($tutor['email']); ?></span>
                    </div>
                    <?php if ($tutor['phone']): ?>
                        <div class="meta-item">
                            <i class="fas fa-phone"></i>
                            <span><?php echo htmlspecialchars($tutor['phone']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>