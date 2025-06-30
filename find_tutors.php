<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get all courses for the filter dropdown
$courseQuery = "SELECT id, course_name FROM course ORDER BY course_name";
$courseResult = mysqli_query($conn, $courseQuery);
if (!$courseResult) {
    die("Course query failed: " . mysqli_error($conn));
}

// Initialize variables for search filters
$searchName = isset($_GET['search_name']) ? trim($_GET['search_name']) : '';
$courseFilter = isset($_GET['course']) ? (int)$_GET['course'] : 0;
$ratingFilter = isset($_GET['rating']) ? (int)$_GET['rating'] : 0;

// Build the tutor search query with filters
$tutorQuery = "SELECT DISTINCT u.user_id, u.first_name, u.last_name, u.profile_image, 
               tp.rating, tp.bio, tp.qualifications, tp.is_verified
               FROM user u
               JOIN tutorprofile tp ON u.user_id = tp.user_id
               LEFT JOIN tutorsubject ts ON u.user_id = ts.tutor_id
               LEFT JOIN course c ON ts.course_id = c.id
               WHERE u.role = 'tutor' AND u.is_active = 1";

// Apply filters if set
if (!empty($searchName)) {
    $searchName = mysqli_real_escape_string($conn, $searchName);
    $tutorQuery .= " AND (u.first_name LIKE '%$searchName%' OR u.last_name LIKE '%$searchName%')";
}

if ($courseFilter > 0) {
    $tutorQuery .= " AND ts.course_id = $courseFilter";
}

if ($ratingFilter > 0) {
    $tutorQuery .= " AND tp.rating >= $ratingFilter";
}

$tutorQuery .= " ORDER BY tp.rating DESC, u.first_name, u.last_name";
$tutorResult = mysqli_query($conn, $tutorQuery);

// Check for query execution errors
if (!$tutorResult) {
    die("Tutor query failed: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find a Tutor - Peer Tutoring Platform</title>
    <link rel="stylesheet" href="css/find_tutor_style2.css">
    <style>
        :root {
            --primary: #2B3990;
            --secondary: #00AEEF;
            --accent: #C4D600;
            --light-gray: #f5f7fa;
            --gray: #e9ecef;
            --dark-gray: #6c757d;
        }

        .tutor-card {
            transition: transform 0.3s;
            margin-bottom: 20px;
        }

        .tutor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
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
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar>.container {
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

        .btn {
            background-color: var(--accent);
            color: white;
            border: none;
            padding: 0.75rem 1rem;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
            text-align: center;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            background-color: #b3c300;
        }

        /* 确保占位符显示 */
        .form-control::placeholder {
            color: #6c757d !important;
            opacity: 1 !important;
            font-size: 0.9rem;
        }
    </style>
</head>

<body>
    <?php include 'header/stud_head.php'; ?>



    <div class="container mt-4">
        <h1 class="mb-4">Find a Tutor</h1>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" action="find_tutors.php" class="row align-items-end">
                <div class="col-md-3 mb-3">
                    <label for="search_name" class="form-label">Tutor Name</label>
                    <input type="text" class="form-control" id="search_name" name="search_name "
                        value="<?php echo htmlspecialchars($searchName); ?>" placeholder="Enter tutor name" style="width: 100%;height:55px ;">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="course" class="form-label">Course</label>
                    <select class="form-control" id="course" name="course" style="width: 100%;height:55px ;">
                        <option value="0">All Courses</option>
                        <?php mysqli_data_seek($courseResult, 0); ?>
                        <?php while ($course = mysqli_fetch_assoc($courseResult)): ?>
                            <option value="<?php echo $course['id']; ?>"
                                <?php echo ($courseFilter == $course['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['course_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label for="rating" class="form-label">Minimum Rating</label>
                    <select class="form-control" id="rating" name="rating" style="width: 100%;height:55px ;">
                        <option value="0" <?php echo ($ratingFilter == 0) ? 'selected' : ''; ?>>Any Rating</option>
                        <option value="5" <?php echo ($ratingFilter == 5) ? 'selected' : ''; ?>>5 Stars</option>
                        <option value="4" <?php echo ($ratingFilter == 4) ? 'selected' : ''; ?>>4+ Stars</option>
                        <option value="3" <?php echo ($ratingFilter == 3) ? 'selected' : ''; ?>>3+ Stars</option>
                        <option value="2" <?php echo ($ratingFilter == 2) ? 'selected' : ''; ?>>2+ Stars</option>
                    </select>
                </div>
                <div class="col-md-2 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-block" style="width: 100%;">Search</button>
                </div>
            </form>
        </div>

        <!-- Results Section -->
        <div class="row">
            <?php if (mysqli_num_rows($tutorResult) > 0): ?>
                <?php while ($tutor = mysqli_fetch_assoc($tutorResult)): ?>
                    <div class="col-md-6">
                        <div class="card tutor-card">
                            <div class="card-body p-4">
                                <div class="row align-items-center">
                                    <div class="col-md-4 text-center">
                                        <?php if (!empty($tutor['profile_image']) && file_exists($tutor['profile_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($tutor['profile_image']); ?>" alt="Profile" class="profile-img shadow-sm">
                                        <?php else: ?>
                                            <img src="Uploads/profile_images/default.jpg" alt="Default Profile" class="profile-img shadow-sm">
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-8">
                                        <h5 class="card-title mb-2">
                                            <?php echo htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']); ?>
                                            <?php if ($tutor['is_verified']): ?>
                                                <span class="verified-badge ms-2" title="Verified Tutor"><i class="fas fa-check-circle"></i></span>
                                            <?php endif; ?>
                                        </h5>

                                        <div class="rating-stars mb-3">
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
                                        $tutorId = $tutor['user_id'];
                                        $coursesQuery = "SELECT c.course_name, ts.hourly_rate 
                                                        FROM tutorsubject ts 
                                                        JOIN course c ON ts.course_id = c.id 
                                                        WHERE ts.tutor_id = ?";
                                        $stmt = $conn->prepare($coursesQuery);
                                        if ($stmt) {
                                            $stmt->bind_param("i", $tutorId);
                                            $stmt->execute();
                                            $coursesResult = $stmt->get_result();

                                            if ($coursesResult->num_rows > 0):
                                        ?>
                                                <p class="card-text mb-2"><strong>Courses:</strong>
                                                    <?php
                                                    $courses = [];
                                                    while ($course = $coursesResult->fetch_assoc()) {
                                                        $courses[] = $course['course_name'] . ' (RM' . number_format($course['hourly_rate'], 2) . '/hr)';
                                                    }
                                                    echo implode(', ', $courses);
                                                    ?>
                                                </p>
                                        <?php
                                            endif;
                                            $stmt->close();
                                        } else {
                                            echo '<p class="card-text text-danger">Error fetching courses: ' . htmlspecialchars($conn->error) . '</p>';
                                        }
                                        ?>

                

                                        <div class="d-flex gap-2">
                                            <a href="appointments.php?tutor_id=<?php echo $tutor['user_id']; ?>" class="btn btn-primary btn-sm">View Profile</a>
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

    <footer class="text-center mt-4">
        <p>&copy; 2025 PeerLearn - Peer Tutoring Platform. All rights reserved.</p>
    </footer>

        <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
        <script>
            function toggleDropdown() {
                const dropdown = document.getElementById('userDropdown');
                dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
            }
        </script>
</body>

</html>