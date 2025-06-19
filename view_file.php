<?php
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "peer_tutoring_platform");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if(isset($_GET['file_id']) && !empty($_GET['file_id'])) {
    $file_id = $conn->real_escape_string($_GET['file_id']);
    
    $sql = "SELECT file_name, file_path, file_type FROM credential_file WHERE file_id = '$file_id'";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $file = $result->fetch_assoc();
        
        // Define your base directory (adjust according to your server)
        $base_dir = $_SERVER['DOCUMENT_ROOT'] . '/FYP-Project-main/';
        
        // Construct full path
        $filepath = $base_dir . $file['file_path'];
        
        // Debug output (remove in production)
        error_log("Attempting to access file at: " . $filepath);
        
        // Verify file exists and is readable
        if (file_exists($filepath) && is_readable($filepath)) {
            header('Content-Type: ' . $file['file_type']);
            header('Content-Disposition: inline; filename="' . $file['file_name'] . '"');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            exit;
        } else {
            // More detailed error reporting
            $error = "File not found or not readable. ";
            $error .= "Server path: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
            $error .= "Attempted path: " . $filepath . "<br>";
            $error .= "File exists: " . (file_exists($filepath) ? 'Yes' : 'No') . "<br>";
            $error .= "Readable: " . (is_readable($filepath) ? 'Yes' : 'No');
            die($error);
        }
    }
}
?>