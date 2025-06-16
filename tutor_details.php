<?php
// tutor_details.php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = ""; 
$dbname = "peer_tutoring_platform";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$tutor = null;
$credentials = array();

// Check if ID parameter exists
if(isset($_GET['id']) && !empty($_GET['id'])) {
    $user_id = $conn->real_escape_string($_GET['id']);
    
    // Get tutor information and credentials in one query
    $sql = "SELECT sp.*, u.username, u.email, u.phone, 
                   cf.file_id, cf.file_name, cf.file_path, cf.file_type, 
                   cf.upload_date, cf.status, cf.is_verified, cf.rejection_reason
            FROM tutorprofile sp
            JOIN user u ON sp.user_id = u.user_id
            LEFT JOIN credential_file cf ON sp.user_id = cf.user_id
            WHERE sp.user_id = '$user_id'
            ORDER BY 
                CASE WHEN cf.status = 'approved' THEN 0 
                     WHEN cf.status = 'pending' THEN 1
                     ELSE 2 END,
                cf.upload_date DESC";   
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            if ($tutor === null) {
                // First row has the tutor data
                $tutor = array(
                    'username' => $row['username'],
                    'email' => $row['email'],
                    'phone' => $row['phone'],
                    'year' => $row['year'],
                    'program' => $row['program'],
                    'major' => $row['major'],
                    'rating' => $row['rating']
                );
            }
            if (!empty($row['file_id'])) {
                $credentials[] = array(
                    'file_id' => $row['file_id'],
                    'file_name' => $row['file_name'],
                    'file_path' => $row['file_path'],
                    'file_type' => $row['file_type'],
                    'upload_date' => $row['upload_date'],
                    'status' => $row['status'],
                    'is_verified' => $row['is_verified'],
                    'rejection_reason' => $row['rejection_reason']
                );
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutor Profile - <?php echo isset($tutor['username']) ? htmlspecialchars($tutor['username']) : 'Tutor'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp" rel="stylesheet">
    <link rel="stylesheet" href="studentstyle.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            padding: 0;
            color: #333;
        }
        
        .container {
            display: flex;
            min-height: 100vh;
        }
        
        aside {
            width: 250px;
            background: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px;
        }
        
        .logo {
            display: flex;
            align-items: center;
        }
        
        .logo img {
            width: 40px;
            height: 40px;
            margin-right: 10px;
        }
        
        .logo h2 {
            font-size: 18px;
        }
        
        .danger {
            color: #ff7782;
        }
        
        .sidebar {
            padding: 20px;
        }
        
        .sidebar a {
            display: flex;
            align-items: center;
            color: #7d8da1;
            padding: 12px 10px;
            margin-bottom: 5px;
            text-decoration: none;
            transition: all 0.3s;
            border-radius: 6px;
        }
        
        .sidebar a.active {
            background: rgba(115, 128, 236, 0.1);
            color: #7380ec;
        }
        
        .sidebar a:hover:not(.active) {
            background: #f6f6f9;
        }
        
        .sidebar .material-symbols-sharp {
            margin-right: 10px;
            font-size: 22px;
        }
        
        .sidebar h3 {
            font-size: 15px;
            font-weight: 500;
            margin: 0;
        }
        
        .message-count {
            background: #ff7782;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
            margin-left: auto;
        }
        
        .profile-content {
            flex: 1;
            padding: 30px;
            max-width: 900px;
            margin: 0 auto;
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .profile-icon {
            font-size: 60px;
            margin-right: 25px;
            color: #7380ec;
        }
        
        .profile-title h1 {
            margin: 0;
            font-size: 28px;
            color: #363949;
        }
        
        .profile-title p {
            margin: 5px 0 0;
            color: #7d8da1;
            font-size: 16px;
        }
        
        .profile-sections {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }
        
        .profile-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .profile-section h2 {
            margin-top: 0;
            color: #7380ec;
            font-size: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .detail-item {
            margin-bottom: 15px;
        }
        
        .detail-item strong {
            display: block;
            color: #7d8da1;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .detail-item p {
            margin: 0;
            font-size: 16px;
        }
        
        .full-width {
            grid-column: span 2;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            margin-top: 30px;
            padding: 10px 20px;
            background: #7380ec;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: background 0.3s;
        }
        
        .back-btn:hover {
            background: #6572ce;
        }
        
        .back-btn .material-symbols-sharp {
            margin-right: 8px;
            font-size: 20px;
        }
        
        .profile-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .edit-btn {
            display: inline-flex;
            align-items: center;
            padding: 10px 20px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: background 0.3s;
        }

        .edit-btn:hover {
            background: #3e8e41;
        }

        .edit-btn .material-symbols-sharp {
            margin-right: 8px;
            font-size: 20px;
        }
        
        /* Credentials specific styles */
        .credentials-list {
            margin-top: 10px;
        }

        .credential-item {
            margin-bottom: 12px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid #ddd;
        }

        .credential-item.approved {
            border-left-color: #28a745;
        }

        .credential-item.pending {
            border-left-color: #ffc107;
        }

        .credential-item.rejected {
            border-left-color: #dc3545;
        }

        .credential-info {
            display: flex;
            align-items: center;
            margin-bottom: 6px;
        }

        .view-credential {
            color: #7380ec;
            text-decoration: none;
            font-weight: 500;
            margin-right: 8px;
        }

        .view-credential:hover {
            text-decoration: underline;
        }

        .file-type {
            font-size: 12px;
            color: #6c757d;
        }

        .credential-meta {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #6c757d;
        }

        .verification-status {
            padding: 2px 6px;
            border-radius: 4px;
        }

        .verification-status.pending {
            color: #ffc107;
            background-color: #fff3cd;
        }

        .verification-status.approved {
            color: #28a745;
            background-color: #d4edda;
        }

        .verification-status.rejected {
            color: #dc3545;
            background-color: #f8d7da;
        }
        
        .rejection-reason {
            margin-top: 8px;
            padding: 8px;
            background-color: #f8f9fa;
            border-radius: 4px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <aside>
            <div class="top">
                <div class="logo">
                    <img src="image/logo.png" alt="PeerLearn Logo">
                    <h2>PEER<span class="danger">LEARN</span></h2>
                </div>
                <div class="close" id="close-btn">
                    <span class="material-symbols-sharp">close</span>
                </div>
            </div>

            <div class="sidebar">
                <a href="admin.html"><span class="material-symbols-sharp">grid_view</span><h3>Dashboard</h3></a>
                <a href="admin_student.php"><span class="material-symbols-sharp">person</span><h3>Students</h3></a>
                <a href="admin_tutors.php" class="active"><span class="material-symbols-sharp">eyeglasses</span><h3>Tutors</h3></a>
                <a href="admin_course.php"><span class="material-symbols-sharp">school</span><h3>Courses</h3></a>
                <a href="admin_message.php"><span class="material-symbols-sharp">chat</span><h3>Messages</h3></a>
                <a href="admin_report.php"><span class="material-symbols-sharp">description</span><h3>Reports</h3></a>
                <a href="home_page.html"><span class="material-symbols-sharp">logout</span><h3>Logout</h3></a>
            </div>
        </aside>

        <div class="profile-content">
            <?php if(isset($tutor)): ?>
                <div class="profile-header">
                    <span class="material-symbols-sharp profile-icon">account_circle</span>
                    <div class="profile-title">
                        <h1><?php echo htmlspecialchars($tutor['username']); ?></h1>
                        <p>Tutor Profile</p>
                    </div>
                </div>
                
                <div class="profile-sections">
                    <div class="profile-section">
                        <h2>Academic Information</h2>
                        <div class="detail-item">
                            <strong>Level</strong>
                            <p><?php echo !empty($tutor['year']) ? htmlspecialchars($tutor['year']) : 'N/A'; ?></p>
                        </div>
                        <div class="detail-item">
                            <strong>Program</strong>
                            <p><?php echo !empty($tutor['program']) ? htmlspecialchars($tutor['program']) : 'N/A'; ?></p>
                        </div>
                        <div class="detail-item">
                            <strong>Major</strong>
                            <p><?php echo !empty($tutor['major']) ? htmlspecialchars($tutor['major']) : 'N/A'; ?></p>
                        </div>
                    </div>
                    
                    <div class="profile-section">
                        <h2>Contact Information</h2>
                        <div class="detail-item">
                            <strong>Email</strong>
                            <p><?php echo !empty($tutor['email']) ? htmlspecialchars($tutor['email']) : 'N/A'; ?></p>
                        </div>
                        <div class="detail-item">
                            <strong>Phone</strong>
                            <p><?php echo !empty($tutor['phone']) ? htmlspecialchars($tutor['phone']) : 'N/A'; ?></p>
                        </div>
                        <div class="detail-item">
                            <strong>Rating</strong>
                            <p><?php echo !empty($tutor['rating']) ? htmlspecialchars($tutor['rating']) : 'N/A'; ?></p>
                        </div>
                    </div>
                    
                    <div class="profile-section full-width">
                    <?php if (!empty($credentials) && is_array($credentials)): ?>
                        <?php foreach ($credentials as $cred): ?>
                            <?php if (isset($cred['file_id']) && $cred['file_id'] !== null): ?>
                                <div class="credential-item <?php echo htmlspecialchars($cred['status']); ?>">
                                    <div class="credential-info">
                                        <strong>
                                            <?php if ($cred['status'] == 'approved'): ?>
                                                <a href="download.php?file_id=<?php echo htmlspecialchars($cred['file_id']); ?>" class="view-credential">
                                                    <?php echo htmlspecialchars($cred['file_name']); ?>
                                                </a>
                                            <?php else: ?>
                                                <?php echo htmlspecialchars($cred['file_name']); ?>
                                            <?php endif; ?>
                                        </strong>
                                        <span class="verification-status <?php echo htmlspecialchars($cred['status']); ?>">
                                            <?php echo ucfirst($cred['status']); ?>
                                            <?php if ($cred['is_verified'] && $cred['status'] == 'approved'): ?>
                                                (Verified)
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="file-type">Type: <?php echo htmlspecialchars($cred['file_type']); ?></div>
                                    <div class="credential-meta">
                                        <span>Uploaded: <?php echo htmlspecialchars($cred['upload_date']); ?></span>
                                    </div>
                                    <?php if ($cred['status'] == 'rejected' && !empty($cred['rejection_reason'])): ?>
                                        <div class="rejection-reason">
                                            <strong>Reason: </strong><?php echo htmlspecialchars($cred['rejection_reason']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No credentials found for this tutor.</p>
                    <?php endif; ?>
                    </div>
                </div>
                
                <a href="admin_tutors.php" class="back-btn">
                    <span class="material-symbols-sharp">arrow_back</span>
                    Back to Tutors List
                </a>
            <?php else: ?>
                <div class="profile-section full-width">
                    <h2>Error</h2>
                    <p>No tutor information found for the specified ID.</p>
                    <a href="admin_tutors.php" class="back-btn">
                        <span class="material-symbols-sharp">arrow_back</span>
                        Back to Tutors List
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>