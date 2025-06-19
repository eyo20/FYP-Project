<?php
session_start();
require_once "db_connection.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get current user's role
$current_user_query = "SELECT role FROM user WHERE user_id = ?";
$stmt = $conn->prepare($current_user_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$current_user_result = $stmt->get_result();
$current_user = $current_user_result->fetch_assoc();
$stmt->close();

// Set default timezone
date_default_timezone_set('UTC');

// Initialize variables
$time_period = isset($_GET['period']) ? $_GET['period'] : 'week';
$report_type = isset($_GET['report']) ? $_GET['report'] : 'popular_courses';

// Calculate date ranges
$current_week_start = date('Y-m-d 00:00:00', strtotime('monday this week'));
$current_week_end = date('Y-m-d 23:59:59', strtotime('sunday this week'));
$current_month_start = date('Y-m-01 00:00:00');
$current_month_end = date('Y-m-t 23:59:59');

// Set start and end dates based on period
if ($time_period == 'month') {
    $start_date = $current_month_start;
    $end_date = $current_month_end;
} else {
    $start_date = $current_week_start;
    $end_date = $current_week_end;
}

// Check if export was requested
if (isset($_GET['action']) && $_GET['action'] == 'export') {
    require ('./fpd186/fpdf.php'); // Adjust path as needed
    
    // Create new PDF document
    $pdf = new FPDF('L','mm',"A4");
    
    // Set document information
    $pdf->SetCreator('PeerLearn Admin');
    $pdf->SetAuthor('PeerLearn');
    $pdf->SetTitle('Admin Report - ' . ucfirst($report_type));
    $pdf->SetSubject('Admin Report');
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'PeerLearn Admin Report', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'Report Type: ' . ucfirst(str_replace('_', ' ', $report_type)), 0, 1);
    $pdf->Cell(0, 10, 'Time Period: ' . ucfirst($time_period), 0, 1);
    $pdf->Cell(0, 10, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1);
    $pdf->Ln(10);
    
    // Generate the report content
    ob_start();
    switch($report_type) {
        case 'popular_courses':
            generatePopularCoursesPDF($conn, $pdf, $start_date, $end_date);
            break;
            
        case 'top_tutors':
            generateTopTutorsPDF($conn, $pdf);
            break;
            
        case 'reports_received':
            generateReportsReceivedPDF($conn, $pdf, $start_date, $end_date);
            break;
            
        case 'sessions_completed':
            generateSessionsCompletedPDF($conn, $pdf, $start_date, $end_date);
            break;
    }
    ob_end_clean();
    
    // Output the PDF
    $pdf->Output('peerlearn_report_' . $report_type . '_' . date('Ymd') . '.pdf', 'D');
    exit();
}

// PDF generation functions
function generatePopularCoursesPDF($conn, $pdf, $start_date, $end_date) {
    $sql = "SELECT 
                c.id as course_id,
                c.course_name,
                COUNT(s.session_id) as session_count,
                COUNT(DISTINCT s.student_id) as student_count,
                GROUP_CONCAT(DISTINCT CONCAT(u1.first_name, ' ', u1.last_name) SEPARATOR ', ') as tutors,
                GROUP_CONCAT(DISTINCT CONCAT(u2.first_name, ' ', u2.last_name) SEPARATOR ', ') as students
            FROM 
                course c
            JOIN 
                session s ON c.id = s.course_id
            JOIN 
                user u1 ON s.tutor_id = u1.user_id
            JOIN 
                user u2 ON s.student_id = u2.user_id
            WHERE 
                (s.status = 'completed' OR s.status = 'confirmed')
                AND s.end_datetime BETWEEN ? AND ?
            GROUP BY 
                c.id, c.course_name
            ORDER BY 
                session_count DESC
            LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Most Popular Courses', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    
    if ($result->num_rows > 0) {
        // Table header
        $header = array('Rank', 'Course Name', 'Sessions', 'Students', 'Tutors');
        $w = array(15, 60, 25, 25, 65);
        
        // Colors, line width and bold font
        $pdf->SetFillColor(115, 128, 236);
        $pdf->SetTextColor(255);
        $pdf->SetDrawColor(128, 128, 128);
        $pdf->SetLineWidth(0.3);
        $pdf->SetFont('', 'B');
        
        // Header
        for($i = 0; $i < count($header); $i++) {
            $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
        }
        $pdf->Ln();
        
        // Color and font restoration
        $pdf->SetFillColor(224, 235, 255);
        $pdf->SetTextColor(0);
        $pdf->SetFont('');
        
        // Data
        $fill = false;
        $rank = 1;
        while ($row = $result->fetch_assoc()) {
            $pdf->Cell($w[0], 6, $rank, 'LR', 0, 'C', $fill);
            $pdf->Cell($w[1], 6, $row['course_name'], 'LR', 0, 'L', $fill);
            $pdf->Cell($w[2], 6, $row['session_count'], 'LR', 0, 'C', $fill);
            $pdf->Cell($w[3], 6, $row['student_count'], 'LR', 0, 'C', $fill);
            $pdf->Cell($w[4], 6, $row['tutors'], 'LR', 0, 'L', $fill);
            $pdf->Ln();
            $fill = !$fill;
            $rank++;
        }
        // Closing line
        $pdf->Cell(array_sum($w), 0, '', 'T');
    } else {
        $pdf->Cell(0, 10, 'No completed sessions in the selected period to determine popular courses.', 0, 1);
    }
}

function generateTopTutorsPDF($conn, $pdf) {
    $sql = "SELECT 
                u.user_id as user_id, 
                u.first_name, 
                u.last_name, 
                t.rating as avg_rating,
                t.total_sessions as session_count   
            FROM 
                user u
            JOIN 
                tutorprofile t ON u.user_id = t.user_id
            WHERE 
                t.rating > 0
            ORDER BY 
                t.rating DESC
            LIMIT 10";
    
    $result = $conn->query($sql);
    
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Top Rated Tutors', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    
    if ($result->num_rows > 0) {
        // Table header
        $header = array('Rank', 'Tutor Name', 'Rating', 'Sessions');
        $w = array(15, 80, 30, 30);
        
        // Header styling
        $pdf->SetFillColor(115, 128, 236);
        $pdf->SetTextColor(255);
        $pdf->SetDrawColor(128, 128, 128);
        $pdf->SetLineWidth(0.3);
        $pdf->SetFont('', 'B');
        
        // Header
        for($i = 0; $i < count($header); $i++) {
            $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
        }
        $pdf->Ln();
        
        // Data styling
        $pdf->SetFillColor(224, 235, 255);
        $pdf->SetTextColor(0);
        $pdf->SetFont('');
        
        // Data
        $fill = false;
        $rank = 1;
        while ($row = $result->fetch_assoc()) {
            $pdf->Cell($w[0], 6, $rank, 'LR', 0, 'C', $fill);
            $pdf->Cell($w[1], 6, $row['first_name'].' '.$row['last_name'], 'LR', 0, 'L', $fill);
            $pdf->Cell($w[2], 6, number_format($row['avg_rating'], 1).' ★', 'LR', 0, 'C', $fill);
            $pdf->Cell($w[3], 6, $row['session_count'], 'LR', 0, 'C', $fill);
            $pdf->Ln();
            $fill = !$fill;
            $rank++;
        }
        // Closing line
        $pdf->Cell(array_sum($w), 0, '', 'T');
    } else {
        $pdf->Cell(0, 10, 'No tutor ratings available.', 0, 1);
    }
}

function generateReportsReceivedPDF($conn, $pdf, $start_date, $end_date) {
    $sql = "SELECT 
                r.rating,
                r.comment,
                r.is_approved,
                r.approved_by,
                r.created_at,
                r.student_id,
                u.username
            FROM 
                review r
            JOIN 
                user u ON r.student_id = u.user_id
            WHERE 
                r.created_at BETWEEN ? AND ?
            ORDER BY 
                r.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Set up PDF document
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Reviews Received', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    
    // Check if there are results
    if ($result->num_rows > 0) {
        // Define column widths
        $w = array(25, 20, 40, 50); // Adjust these values as needed
         $totalWidth = array_sum($w);

        $pageWidth = $pdf->GetPageWidth();
        $leftMargin = ($pageWidth - $totalWidth) / 2;
        $pdf->SetLeftMargin($leftMargin);

        // Header
        $header = array('Student', 'Rating', 'Comment', 'Date');
        $pdf->SetFillColor(200, 220, 255);
        $pdf->SetTextColor(0);
        $pdf->SetFont('helvetica', 'B');
        
        for($i = 0; $i < count($header); $i++) {
            $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
        }
        $pdf->Ln();
        
        // Data styling
        $pdf->SetFillColor(224, 235, 255);
        $pdf->SetTextColor(0);
        $pdf->SetFont('helvetica', '');
        
        // Data rows
        $fill = false;
        while ($row = $result->fetch_assoc()) {
            // Format the data for display
            $status = $row['is_approved'] ? 'Approved' : 'Pending';
            $date = date('Y-m-d H:i', strtotime($row['created_at']));
            
            $pdf->Cell($w[0], 6, $row['username'], 'LR', 0, 'L', $fill);
            $pdf->Cell($w[1], 6, $row['rating'], 'LR', 0, 'C', $fill);
            $pdf->Cell($w[2], 6, substr($row['comment'], 0, 50), 'LR', 0, 'L', $fill); // Limit comment length
            $pdf->Cell($w[3], 6, $date, 'LR', 0, 'C', $fill);
            $pdf->Ln();
            
            $fill = !$fill;
        }
        
        // Closing line
        $pdf->Cell(array_sum($w), 0, '', 'T');
    } else {
        $pdf->Cell(0, 10, 'No reviews received between '.$start_date.' and '.$end_date, 0, 1, 'C');
    }
}

function generateSessionsCompletedPDF($conn, $pdf, $start_date, $end_date) {
    $sql = "SELECT 
                s.session_id,
                c.course_name,
                u1.first_name as tutor_first,
                u1.last_name as tutor_last,
                u2.first_name as student_first,
                u2.last_name as student_last,
                s.start_datetime,
                s.end_datetime,
                l.location_name
            FROM 
                session s
            JOIN 
                course c ON s.course_id = c.id
            JOIN 
                user u1 ON s.tutor_id = u1.user_id
            JOIN 
                user u2 ON s.student_id = u2.user_id
            LEFT JOIN
                location l ON s.location_id = l.location_id
            WHERE 
                (s.status = 'completed' OR s.status = 'confirmed')
                AND s.end_datetime BETWEEN ? AND ?
            ORDER BY 
                s.end_datetime DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Sessions Completed', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    
    if ($result->num_rows > 0) {
        // Table header (Edit the Table Column here)
        $header = array('ID', 'Course', 'Tutor', 'Student', 'Location', 'Start', 'End');
        $w = array(15,60, 40, 40, 50, 35, 35);
        
        // Header styling
        $pdf->SetFillColor(115, 128, 236);
        $pdf->SetTextColor(255);
        $pdf->SetDrawColor(128, 128, 128);
        $pdf->SetLineWidth(0.3);
        $pdf->SetFont('', 'B');
        
        // Header
        for($i = 0; $i < count($header); $i++) {
            $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
        }
        $pdf->Ln();
        
        // Data styling
        $pdf->SetFillColor(224, 235, 255);
        $pdf->SetTextColor(0);
        $pdf->SetFont('');
        
        // Data
        $fill = false;
        while ($row = $result->fetch_assoc()) {
            $tutor_name = $row['tutor_first'].' '.$row['tutor_last'];
            $student_name = $row['student_first'].' '.$row['student_last'];
            $start_time = date('M j, g:i A', strtotime($row['start_datetime']));
            $end_time = date('M j, g:i A', strtotime($row['end_datetime']));
            $location = $row['location_name'] ? $row['location_name'] : 'Online';
            
            $pdf->Cell($w[0], 6, $row['session_id'], 'LR', 0, 'C', $fill);
            $pdf->Cell($w[1], 6, $row['course_name'], 'LR', 0, 'L', $fill);
            $pdf->Cell($w[2], 6, $tutor_name, 'LR', 0, 'L', $fill);
            $pdf->Cell($w[3], 6, $student_name, 'LR', 0, 'L', $fill);
            $pdf->Cell($w[4], 6, $location, 'LR', 0, 'L', $fill);
            $pdf->Cell($w[5], 6, $start_time, 'LR', 0, 'C', $fill);
            $pdf->Cell($w[6], 6, $end_time, 'LR', 0, 'C', $fill);
            $pdf->Ln();
            $fill = !$fill;
        }
        // Closing line
        $pdf->Cell(array_sum($w), 0, '', 'T');
    } else {
        $pdf->Cell(0, 10, 'No completed sessions in the selected period.', 0, 1);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE-edge">
    <meta name="viewport" content="width-device-width, initial-scale=1.0">
    <title>Admin Reports</title>
     <!--Material Cdn-->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp" rel="stylesheet" />

    <!--Style Sheet-->
    <link rel="stylesheet" href="studentstyle.css">
</head>

<body>
    <style>
        :root {
            --primary: #7380ec;
            --danger: #ff7782;
            --success: #41f1b6;
            --warning: #ffbb55;
            --white: #fff;
            --info-dark: #7d8da1;
            --info-light: #dce1eb;
            --dark: #363949;
            --light: rgba(132, 139, 200, 0.18);
            --primary-variant: #111e88;
            --dark-variant: #677483;
            --color-background: #f6f6f9;
            
            --card-border-radius: 2rem;
            --border-radius-1: 0.4rem;
            --border-radius-2: 0.8rem;
            --border-radius-3: 1.2rem;
            
            --card-padding: 1.8rem;
            --padding-1: 1.2rem;
            
            --box-shadow: 0 2rem 3rem var(--light);
        }
        
        * {
            margin: 0;
            padding: 0;
            outline: 0;
            appearance: none;
            border: 0;
            text-decoration: none;
            list-style: none;
            box-sizing: border-box;
        }
        
        html {
            font-size: 14px;
        }
        
        body {
            margin: 0;
            padding: 0;
        }
        
        
       .container {
            width: 100%;
            margin: 0;
            gap: 1.8rem;
            grid-template-columns: 14rem auto 23rem;
        }
        

        a{
            color: #363949;
        }

        img {
            display: block;
            width: 100%;
        }

        h1{
            font-weight: 800;
            font-size: 1.8rem;
        }

        h2{
            font-size: 1.4rem;
        }

        h3{
            font-size: 0.87rem;
        }

        h4{
            font-size: 0.8rem;
        }

        h5{
            font-size: 0.77rem;
        }

        small {
            font-size: 0.75rem;
        }

        .profile-photo{
            width: 2.8rem;
            height: 2.8rem;
            border-radius: 50%;
            overflow: hidden;
        }

        .text-muted {
            color: #dce1eb;
        }

        p{
            color:#677483;
        }

        b{
            color: #363949;
        }
        .primary{
            color: #7380ec;
        }
        .danger{
            color: #ff7782;
        }
        .success{
            color: #41f1b6;
        }
        .warning{
            color: #ffbb55;
        }


        aside {
            width: 210px;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            height: 100vh;
            margin-left: 0;
            padding-left: 0;
            left: 0;
        }


        aside .top {
            margin-left: 0;
            padding-left: 1rem;
        }

        aside .logo {
            display: flex;
            gap: 0.8rem;
        }

        aside .logo img{
            width: 2rem;
            height: 2rem;
        }

        aside .close{
            display: none;
        }

        /* ======================== Side Bar ================================ */
       aside .sidebar {
            margin-left: 0;
            padding-left: 0;
        }


        aside h3 {
            font-weight: 500;
        }

        aside .sidebar a{
            display: flex;
            color:  #7d8da1;
            margin-left: 2rem;
            gap: 1rem;
            align-items: center;
            position: relative;
            height: 3.7rem;
            transition: all 300ms ease;
        }

        aside .sidebar a span{
            font-size: 1.6rem;
            transition: all 300ms ease;
        }

        aside .sidebar  a:last-child{
            position: absolute;
            bottom: 2rem;
            width: 100%;

        }

        aside .sidebar a.active {
            background: rgba(132, 139, 200, 0.18);
            color: #7380ec;
            margin-left: 0;
        }

        aside .sidebar a.active:before{
            content: "";
            width: 6px;
            height: 100%;
            background: #7380ec;

        }

        aside .sidebar a.active span{
            color: #7380ec;
            margin-left: calc(1rem -3 px);
        }

        aside .sidebar a:hover {
            color: #7380ec;
        }

        aside .sidebar a:hover span{
            margin-left: 1rem;
        }

        aside .sidebar .message-count {
            background: #ff7782;
            color: #fff;
            padding: 2px 10px;
            font-size: 11px;
            border-radius: 0.4rem;
        }
                
        /* ========== MAIN CONTENT ========== */
        main {
            margin-top: 1.4rem;
            margin-left: 6rem;
        }
        
        .report-controls {
            background: white;
            padding: var(--card-padding);
            border-radius: var(--card-border-radius);
            margin-top: 1rem;
            box-shadow: var(--box-shadow);
            transition: all 300ms ease;
        }
        
        .report-controls:hover {
            box-shadow: none;
        }
        
        .report-controls select, .report-controls button {
            padding: 0.8rem 1.2rem;
            margin-right: 1rem;
            border-radius: var(--border-radius-1);
            border: 1px solid var(--info-light);
        }
        
        .report-controls button {
            background: var(--primary);
            color: white;
            border: none;
            cursor: pointer;
            transition: all 300ms ease;
        }
        
        .report-controls button:hover {
            background: var(--primary-variant);
        }
        
        .export-btn {
            background: var(--danger);
            color: white;
            border: none;
            cursor: pointer;
            transition: all 300ms ease;
        }

        .export-btn:hover {
            background: #e66772;
        }
        
        .report-results {
            margin-top: 2rem;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2rem;
            background: white;
            border-radius: var(--card-border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
        }
        
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--info-light);
        }
        
        th {
            background-color: var(--light);
            color: var(--dark);
            font-weight: 600;
        }
        
        tr:hover {
            background-color: var(--info-light);
        }
        
        .no-data {
            text-align: center;
            padding: 2rem;
            color: var(--info-dark);
            background: white;
            border-radius: var(--card-border-radius);
            box-shadow: var(--box-shadow);
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            margin-top: 2rem;
            padding: 0.8rem 1.6rem;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: var(--border-radius-1);
            font-weight: 500;
            transition: all 300ms ease;
        }
        
        .back-btn:hover {
            background: var(--primary-variant);
        }
        
        .back-btn .material-symbols-sharp {
            margin-right: 0.8rem;
            font-size: 1.4rem;
        }
        
        .profile-actions {
            display: flex;
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
    </style>
</head>
<body>
    <div class="container">
        <aside>
            <div class="top">
                <div class="logo">
                    <img src="image/logo.png">
                    <h2>PEER<span class="danger">LEARN</span></h2>
                </div>
                <div class="close" id="close-btn">
                    <span class="material-symbols-sharp">close</span>
                </div>
            </div>

            <div class="sidebar">
                <a href="admin.html"><span class="material-symbols-sharp">grid_view</span><h3>Dashboard</h3></a>
                <a href="#"></a>
                <a href="admin_staff.php"><span class="material-symbols-sharp">badge</span><h3>Staff</h3></a>
                <a href="admin_student.php"><span class="material-symbols-sharp">person</span><h3>Students</h3></a>
                <a href="admin_tutors.php"><span class="material-symbols-sharp">eyeglasses</span><h3>Tutors</h3></a>
                <a href="admin_course.php"><span class="material-symbols-sharp">school</span><h3>Courses</h3></a>
                <a href="admin_message.php"><span class="material-symbols-sharp">chat</span><h3>Messages</h3></a>
                <a href="admin_report.php" class="active"><span class="material-symbols-sharp">description</span><h3>Reports</h3></a>
                <a href="home_page.html"><span class="material-symbols-sharp">logout</span><h3>Logout</h3></a>
            </div>
        </aside>
        
        <main>
            <h1>Admin Reports Dashboard</h1>
            
            <div class="report-controls">
                <form method="get" action="admin_report.php">
                    <label for="report">Report Type:</label>
                    <select name="report" id="report">
                        <option value="popular_courses" <?php echo ($report_type == 'popular_courses') ? 'selected' : ''; ?>>Popular Courses</option>
                        <option value="top_tutors" <?php echo ($report_type == 'top_tutors') ? 'selected' : ''; ?>>Top Rated Tutors</option>
                        <option value="reports_received" <?php echo ($report_type == 'reports_received') ? 'selected' : ''; ?>>Reports Received</option>
                        <option value="sessions_completed" <?php echo ($report_type == 'sessions_completed') ? 'selected' : ''; ?>>Sessions Completed</option>
                    </select>
                    
                    <label for="period">Time Period:</label>
                    <select name="period" id="period">
                        <option value="week" <?php echo ($time_period == 'week') ? 'selected' : ''; ?>>This Week</option>
                        <option value="month" <?php echo ($time_period == 'month') ? 'selected' : ''; ?>>This Month</option>
                    </select>
                    
                    <button type="submit" name="action" value="generate">Generate Report</button>
                    <button type="submit" name="action" value="export" class="export-btn">Export as PDF</button>
                </form>
            </div>
            
            <div class="report-results">
                <?php
                switch($report_type) {
                    case 'popular_courses':
                        echo "<h2>Most Popular Courses - This " . ucfirst($time_period) . "</h2>";
                        displayPopularCourses($conn, $start_date, $end_date);
                        break;
                        
                    case 'top_tutors':
                        echo "<h2>Top Rated Tutors</h2>";
                        displayTopTutors($conn);
                        break;
                        
                    case 'reports_received':
                        echo "<h2>Reports Received - This " . ucfirst($time_period) . "</h2>";
                        displayReportsReceived($conn, $start_date, $end_date);
                        break;
                        
                    case 'sessions_completed':
                        echo "<h2>Sessions Completed - This " . ucfirst($time_period) . "</h2>";
                        displaySessionsCompleted($conn, $start_date, $end_date);
                        break;
                        
                    default:
                        echo "<h2>Most Popular Courses - This " . ucfirst($time_period) . "</h2>";
                        displayPopularCourses($conn, $start_date, $end_date);
                }
                ?>
            </div>
        </main>
    </div>
</body>
</html>

<?php
// Function to display popular courses based on completed sessions
function displayPopularCourses($conn, $start_date, $end_date) {
    $sql = "SELECT 
                c.id as course_id,
                c.course_name,
                COUNT(s.session_id) as session_count,
                COUNT(DISTINCT s.student_id) as student_count,
                GROUP_CONCAT(DISTINCT CONCAT(u1.first_name, ' ', u1.last_name) SEPARATOR ', ') as tutors,
                GROUP_CONCAT(DISTINCT CONCAT(u2.first_name, ' ', u2.last_name) SEPARATOR ', ') as students
            FROM 
                course c
            JOIN 
                session s ON c.id = s.course_id
            JOIN 
                user u1 ON s.tutor_id = u1.user_id
            JOIN 
                user u2 ON s.student_id = u2.user_id
            WHERE 
                (s.status = 'completed' OR s.status = 'confirmed')
                AND s.end_datetime BETWEEN ? AND ?
            GROUP BY 
                c.id, c.course_name
            ORDER BY 
                session_count DESC
            LIMIT 10"; // Show top 10 most popular courses
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Get total unique courses count for the header
    $totalCoursesSql = "SELECT COUNT(DISTINCT c.id) as total_courses
                        FROM course c
                        JOIN session s ON c.id = s.course_id
                        WHERE (s.status = 'completed' OR s.status = 'confirmed')
                        AND s.end_datetime BETWEEN ? AND ?";
    $totalStmt = $conn->prepare($totalCoursesSql);
    $totalStmt->bind_param("ss", $start_date, $end_date);
    $totalStmt->execute();
    $totalResult = $totalStmt->get_result();
    $totalCourses = $totalResult->fetch_assoc()['total_courses'];
    
    if ($result->num_rows > 0) {
        echo "<div class='summary-header'>Total Courses Taken: " . $totalCourses . "</div>";
        echo "<table>
                <tr>
                    <th>Rank</th>
                    <th>Course Name</th>
                    <th>Number of Sessions</th>
                    <th>Unique Students</th>
                    <th>Tutors Involved</th>
                    <th>Students Enrolled</th>
                </tr>";
        
        $rank = 1;
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>".$rank."</td>
                    <td>".htmlspecialchars($row['course_name'])."</td>
                    <td>".$row['session_count']."</td>
                    <td>".$row['student_count']."</td>
                    <td>".htmlspecialchars($row['tutors'])."</td>
                    <td>".htmlspecialchars($row['students'])."</td>
                  </tr>";
            $rank++;
        }
        echo "</table>";
    } else {
        echo "<div class='no-data'>No completed sessions in the selected period to determine popular courses.</div>";
    }
}

// Function to display top rated tutors
function displayTopTutors($conn) {
    $sql = "SELECT 
                u.user_id as user_id, 
                u.first_name, 
                u.last_name, 
                t.rating as avg_rating,
                t.total_sessions as session_count   
            FROM 
                user u
            JOIN 
                tutorprofile t ON u.user_id = t.user_id
            WHERE 
                t.rating > 0
            ORDER BY 
                t.rating DESC
            LIMIT 10";
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        echo "<table>
                <tr>
                    <th>Rank</th>
                    <th>Tutor Name</th>
                    <th>Average Rating</th>
                    <th>Total Sessions</th>
                </tr>";
        
        $rank = 1;
        while ($row = $result->fetch_assoc()) {
            $avg_rating = number_format($row['avg_rating'], 1);
            echo "<tr>
                    <td>".$rank."</td>
                    <td>".htmlspecialchars($row['first_name'])." ".htmlspecialchars($row['last_name'])."</td>
                    <td>".$avg_rating." ★</td>
                    <td>".$row['session_count']."</td>
                  </tr>";
            $rank++;
        }
        echo "</table>";
    } else {
        echo "<div class='no-data'>No tutor ratings available.</div>";
    }
}

function displayReportsReceived($conn, $start_date, $end_date) {
    $sql = "SELECT 
                r.rating,
                r.comment,
                r.is_approved,
                r.approved_by,
                r.created_at,
                r.student_id,
                u.username
            FROM 
                review r
            JOIN 
                user u ON r.student_id = u.user_id
            WHERE 
                r.created_at BETWEEN ? AND ?
            ORDER BY 
                r.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<table>
                <tr>
                    <th>Rating</th>
                    <th>Comment</th>
                    <th>Sent by</th>
                    <th>Created At</th>
                </tr>";
        
        while ($row = $result->fetch_assoc()) {
            $created_at = date('M j, Y g:i A', strtotime($row['created_at']));
            
            echo "<tr>
                    <td>".htmlspecialchars($row['rating'])."</td>
                    <td>".htmlspecialchars($row['comment'])."</td>
                    <td>".htmlspecialchars($row['username'])."</td>
                    <td>".$created_at."</td>
                  </tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='no-data'>No reviews found between ".htmlspecialchars($start_date)." and ".htmlspecialchars($end_date)."</div>";
    }
}

// Function to display completed sessions
function displaySessionsCompleted($conn, $start_date, $end_date) {
    $sql = "SELECT 
                s.session_id,
                c.course_name,
                u1.first_name as tutor_first,
                u1.last_name as tutor_last,
                u2.first_name as student_first,
                u2.last_name as student_last,
                s.start_datetime,
                s.end_datetime,
                l.location_name  -- Changed from l.name
            FROM 
                session s
            JOIN 
                course c ON s.course_id = c.id
            JOIN 
                user u1 ON s.tutor_id = u1.user_id
            JOIN 
                user u2 ON s.student_id = u2.user_id
            LEFT JOIN
                location l ON s.location_id = l.location_id
            WHERE 
                (s.status = 'completed' OR s.status = 'confirmed')
                AND s.end_datetime BETWEEN ? AND ?
            ORDER BY 
                s.end_datetime DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<table>
                <tr>
                    <th>Session ID</th>
                    <th>Course</th>
                    <th>Tutor</th>
                    <th>Student</th>
                    <th>Location</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                </tr>";
        
        while ($row = $result->fetch_assoc()) {
            $tutor_name = htmlspecialchars($row['tutor_first']).' '.htmlspecialchars($row['tutor_last']);
            $student_name = htmlspecialchars($row['student_first']).' '.htmlspecialchars($row['student_last']);
            $start_time = date('M j, Y g:i A', strtotime($row['start_datetime']));
            $end_time = date('M j, Y g:i A', strtotime($row['end_datetime']));
            
            echo "<tr>
                    <td>".$row['session_id']."</td>
                    <td>".htmlspecialchars($row['course_name'])."</td>
                    <td>".$tutor_name."</td>
                    <td>".$student_name."</td>
                    <td>".($row['location_name'] ? htmlspecialchars($row['location_name']) : 'Online')."</td>
                    <td>".$start_time."</td>
                    <td>".$end_time."</td>
                  </tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='no-data'>No completed sessions in the selected period.</div>";
    }
}

$conn->close();
?>