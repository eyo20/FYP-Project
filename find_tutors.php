<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get all subjects for the filter dropdown
$subjectQuery = "SELECT * FROM subject ORDER BY subject_name";
$subjectResult = mysqli_query($conn, $subjectQuery);

// Get all courses for the filter dropdown
$courseQuery = "SELECT c.*, s.subject_name FROM course c 
                JOIN subject s ON c.subject_id = s.subject_id 
                ORDER BY s.subject_name, c.course_name";
$courseResult = mysqli_query($conn, $courseQuery);

// Initialize variables for search filters
$searchName = isset($_GET['search_name']) ? $_GET['search_name'] : '';
$subjectFilter = isset($_GET['subject']) ? (int)$_GET['subject'] : 0;
$courseFilter = isset($_GET['course']) ? (int)$_GET['course'] : 0;
$ratingFilter = isset($_GET['rating']) ? (int)$_GET['rating'] : 0;

// Build the tutor search query with filters
$tutorQuery = "SELECT DISTINCT u.user_id, u.first_name, u.last_name, u.profile_image, 
               tp.rating, tp.bio, tp.qualifications, tp.is_verified
               FROM user u
               JOIN tutorprofile tp ON u.user_id = tp.user_id
               LEFT JOIN tutorsubject ts ON u.user_id = ts.tutor_id
               LEFT JOIN subject s ON ts.subject_id = s.subject_id
               LEFT JOIN course c ON s.subject_id = c.subject_id
               WHERE u.role = 'tutor' AND u.is_active = 1";

// Apply filters if set
if (!empty($searchName)) {
    $searchName = mysqli_real_escape_string($conn, $searchName);
    $tutorQuery .= " AND (u.first_name LIKE '%$searchName%' OR u.last_name LIKE '%$searchName%')";
}

if ($subjectFilter > 0) {
    $tutorQuery .= " AND ts.subject_id = $subjectFilter";
}

if ($courseFilter > 0) {
    $tutorQuery .= " AND c.course_id = $courseFilter";
}

if ($ratingFilter > 0) {
    $tutorQuery .= " AND tp.rating >= $ratingFilter";
}

$tutorQuery .= " ORDER BY tp.rating DESC, u.first_name, u.last_name";
$tutorResult = mysqli_query($conn, $tutorQuery);

// Check for query execution errors
if (!$tutorResult) {
    die("Query failed: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find a Tutor - Peer Tutoring Platform</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .tutor-card {
            transition: transform 0.3s;
            margin-bottom: 20px;
        }
        .tutor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .profile-img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
        }
        .verified-badge {
            color: #28a745;
        }
        .rating-stars {
            color: #ffc107;
        }
        .filter-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            background-color: var(--white);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .navbar > .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-grow: 1;
        }
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                padding: 15px 0;
            }
            
            .logo {
                margin-bottom: 15px;
            }
            
            .nav-menu {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .nav-item {
                margin: 5px 10px;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container mt-4">
        <h1 class="mb-4">Find a Tutor</h1>
        
        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" action="find_tutors.php" class="row">
                <div class="col-md-3 mb-3">
                    <label for="search_name">Tutor Name</label>
                    <input type="text" class="form-control" id="search_name" name="search_name" 
                           value="<?php echo htmlspecialchars($searchName); ?>" placeholder="Search by name">
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="subject">Subject</label>
                    <select class="form-control" id="subject" name="subject">
                        <option value="0">All Subjects</option>
                        <?php while ($subject = mysqli_fetch_assoc($subjectResult)): ?>
                            <option value="<?php echo $subject['subject_id']; ?>" 
                                <?php echo ($subjectFilter == $subject['subject_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($subject['subject_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="course">Course</label>
                    <select class="form-control" id="course" name="course">
                        <option value="0">All Courses</option>
                        <?php mysqli_data_seek($courseResult, 0); ?>
                        <?php while ($course = mysqli_fetch_assoc($courseResult)): ?>
                            <option value="<?php echo $course['course_id']; ?>"
                                <?php echo ($courseFilter == $course['course_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="col-md-2 mb-3">
                    <label for="rating">Minimum Rating</label>
                    <select class="form-control" id="rating" name="rating">
                        <option value="0" <?php echo ($ratingFilter == 0) ? 'selected' : ''; ?>>Any Rating</option>
                        <option value="5" <?php echo ($ratingFilter == 5) ? 'selected' : ''; ?>>5 Stars</option>
                        <option value="4" <?php echo ($ratingFilter == 4) ? 'selected' : ''; ?>>4+ Stars</option>
                        <option value="3" <?php echo ($ratingFilter == 3) ? 'selected' : ''; ?>>3+ Stars</option>
                        <option value="2" <?php echo ($ratingFilter == 2) ? 'selected' : ''; ?>>2+ Stars</option>
                    </select>
                </div>
                
                <div class="col-md-1 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-block">Filter</button>
                </div>
            </form>
        </div>
        
        <!-- Results Section -->
        <div class="row">
            <?php if (mysqli_num_rows($tutorResult) > 0): ?>
                <?php while ($tutor = mysqli_fetch_assoc($tutorResult)): ?>
                    <div class="col-md-6">
                        <div class="card tutor-card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 text-center">
                                        <?php if (!empty($tutor['profile_image']) && file_exists($tutor['profile_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($tutor['profile_image']); ?>" alt="Profile" class="profile-img">
                                        <?php else: ?>
                                            <img src="uploads/profile_images/default.jpg" alt="Default Profile" class="profile-img">
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-8">
                                        <h5 class="card-title">
                                            <?php echo htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']); ?>
                                            <?php if ($tutor['is_verified']): ?>
                                                <span class="verified-badge" title="Verified Tutor"><i class="fas fa-check-circle"></i></span>
                                            <?php endif; ?>
                                        </h5>
                                        
                                        <div class="rating-stars mb-2">
                                            <?php
                                            $rating = (float)$tutor['rating'];
                                            for ($i = 1; $i <= 5; $i++) {
                                                if ($i <= $rating) {
                                                    echo '<i class="fas fa-star"></i>';
                                                } elseif ($i - 0.5 <= $rating) {
                                                    echo '<i class="fas fa-star-half-alt"></i>';
                                                } else {
                                                    echo '<i class="far fa-star"></i>';
                                                }
                                            }
                                            echo ' <span class="text-muted">(' . number_format($rating, 1) . ')</span>';
                                            ?>
                                        </div>
                                        
                                        <?php
                                        // Get tutor's subjects and courses
                                        $tutorId = $tutor['user_id'];
                                        $subjectsQuery = "SELECT s.subject_name, ts.hourly_rate 
                                                         FROM tutorsubject ts 
                                                         JOIN subject s ON ts.subject_id = s.subject_id 
                                                         WHERE ts.tutor_id = $tutorId";
                                        $subjectsResult = mysqli_query($conn, $subjectsQuery);
                                        
                                        if (mysqli_num_rows($subjectsResult) > 0):
                                        ?>
                                            <p class="card-text"><strong>Subjects:</strong>
                                                <?php
                                                $subjects = [];
                                                while ($subject = mysqli_fetch_assoc($subjectsResult)) {
                                                    $subjects[] = $subject['subject_name'] . ' ($' . number_format($subject['hourly_rate'], 2) . '/hr)';
                                                }
                                                echo implode(', ', $subjects);
                                                ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($tutor['bio'])): ?>
                                            <p class="card-text text-truncate"><?php echo htmlspecialchars($tutor['bio']); ?></p>
                                        <?php endif; ?>
                                        
                                    <div class="mt-3">
                                        <a href="booking.php?tutor_id=<?php echo $tutor['user_id']; ?>" class="btn btn-outline-primary btn-sm">View Profile</a>
                                        <a href="appointments.php?tutor_id=<?php echo $tutor['user_id']; ?>" class="btn btn-primary btn-sm">Book Session</a>
                                        <a href="add_to_cart.php?tutor_id=<?php echo $tutor['user_id']; ?>" class="btn btn-success btn-sm"><i class="fas fa-shopping-cart"></i> Add to Cart</a>
                                    </div>
 
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        No tutors found matching your criteria. Please try different search filters.
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Dynamic course filtering based on subject selection
        $(document).ready(function() {
            $('#subject').change(function() {
                const subjectId = $(this).val();
                if (subjectId > 0) {
                    // Filter courses by subject
                    $('#course option').each(function() {
                        const courseOption = $(this);
                        if (courseOption.val() == 0) return; // Skip "All Courses" option
                        
                        // You would need to add a data attribute to course options with their subject_id
                        // For now, we'll reload the page with the subject filter
                    });
                } else {
                    // Show all courses
                    $('#course option').show();
                }
            });
        });
    </script>
</body>
</html>
