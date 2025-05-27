<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo '<div style="text-align: center; padding: 2rem; color: var(--danger);">Unauthorized access</div>';
    exit();
}

$tutor_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$tutor_id) {
    echo '<div style="text-align: center; padding: 2rem; color: var(--danger);">Invalid tutor ID</div>';
    exit();
}

try {
    // Get tutor basic information
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.first_name, u.last_name, u.email, u.phone, u.profile_image,
               tp.major, tp.year, tp.bio, tp.qualifications, tp.is_verified, tp.rating, tp.total_sessions
        FROM user u
        LEFT JOIN tutorprofile tp ON u.user_id = tp.user_id
        WHERE u.user_id = ? AND u.role = 'tutor'
    ");
    $stmt->execute([$tutor_id]);
    $tutor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tutor) {
        echo '<div style="text-align: center; padding: 2rem; color: var(--danger);">Tutor not found</div>';
        exit();
    }

    // Get tutor's subjects and courses
    $stmt = $pdo->prepare("
        SELECT s.subject_name, c.course_code, c.course_name, ts.hourly_rate, p.programme_name
        FROM tutorsubject ts
        JOIN subject s ON ts.subject_id = s.subject_id
        LEFT JOIN course c ON ts.course_id = c.course_id
        LEFT JOIN programme p ON ts.programme_id = p.programme_id
        WHERE ts.tutor_id = ?
        ORDER BY s.subject_name, c.course_code
    ");
    $stmt->execute([$tutor_id]);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent reviews
    $stmt = $pdo->prepare("
        SELECT r.rating, r.comment, r.created_at,
               u.first_name, u.last_name
        FROM review r
        JOIN user u ON r.student_id = u.user_id
        WHERE r.tutor_id = ? AND r.is_approved = 1
        ORDER BY r.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$tutor_id]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get session statistics for current user with this tutor
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_sessions,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_sessions,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_sessions,
            MAX(start_datetime) as last_session_date
        FROM session
        WHERE tutor_id = ? AND student_id = ?
    ");
    $stmt->execute([$tutor_id, $_SESSION['user_id']]);
    $session_stats = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo '<div style="text-align: center; padding: 2rem; color: var(--danger);">Error loading tutor details</div>';
    exit();
}
?>

<style>
    .tutor-profile {
        padding: 1rem 0;
    }

    .tutor-header {
        display: flex;
        gap: 1.5rem;
        margin-bottom: 2rem;
        align-items: start;
    }

    .tutor-avatar-large {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: var(--secondary);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 2rem;
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
        color: var(--dark-gray);
        font-size: 0.9rem;
    }

    .meta-item i {
        color: var(--secondary);
        width: 16px;
    }

    .verification-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
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
        display: flex;
                align-items: center;
        gap: 0.5rem;
    }

    .subjects-grid {
        display: grid;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .subject-item {
        background: var(--light);
        padding: 1rem;
        border-radius: 8px;
        border-left: 4px solid var(--secondary);
    }

    .subject-name {
        font-weight: bold;
        color: var(--primary);
        margin-bottom: 0.5rem;
    }

    .course-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .course-tag {
        background: white;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.8rem;
        color: var(--dark-gray);
        border: 1px solid var(--gray);
    }

    .rate-display {
        color: var(--success);
        font-weight: bold;
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
        color: var(--gray);
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
        color: var(--secondary);
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

    @media (max-width: 768px) {
        .tutor-header {
            flex-direction: column;
            text-align: center;
        }

        .tutor-meta {
            justify-content: center;
        }

        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

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
                        <span><?php echo $tutor['total_sessions']; ?> sessions</span>
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

    <!-- Subjects and Courses -->
    <?php if (!empty($subjects)): ?>
        <div class="section">
            <h3><i class="fas fa-book"></i> Subjects & Courses</h3>
            <div class="subjects-grid">
                <?php 
                $grouped_subjects = [];
                foreach ($subjects as $subject) {
                    $subject_name = $subject['subject_name'];
                    if (!isset($grouped_subjects[$subject_name])) {
                        $grouped_subjects[$subject_name] = [];
                    }
                    $grouped_subjects[$subject_name][] = $subject;
                }
                
                foreach ($grouped_subjects as $subject_name => $subject_courses): ?>
                    <div class="subject-item">
                        <div class="subject-name"><?php echo htmlspecialchars($subject_name); ?></div>
                        
                        <?php if (!empty($subject_courses[0]['course_code'])): ?>
                            <div class="course-list">
                                <?php foreach ($subject_courses as $course): ?>
                                    <span class="course-tag">
                                        <?php echo htmlspecialchars($course['course_code']); ?>
                                        <?php if ($course['hourly_rate']): ?>
                                            - $<?php echo number_format($course['hourly_rate'], 2); ?>/hr
                                        <?php endif; ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <?php if ($subject_courses[0]['hourly_rate']): ?>
                                <div class="rate-display">
                                    $<?php echo number_format($subject_courses[0]['hourly_rate'], 2); ?>/hour
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
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
