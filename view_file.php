<?php
// Start output buffering at the very beginning
ob_start();
session_start();

// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
        $base_dir = $_SERVER['DOCUMENT_ROOT'] . '/FYP-Project/';
        
        // Construct full path
        $filepath = $base_dir . $file['file_path'];
        
        // Get file extension
        $file_extension = strtolower(pathinfo($file['file_name'], PATHINFO_EXTENSION));
        
        // Debug information
        error_log("File download requested - ID: $file_id");
        error_log("Full path: $filepath");
        error_log("File type from DB: " . $file['file_type']);
        error_log("File extension: $file_extension");
        
        // Verify file exists and is readable
        if (file_exists($filepath) && is_readable($filepath)) {
            // Clear any previous output
            ob_clean();
            
            // Special handling for PDF files to ensure they display inline
            if ($file_extension === 'pdf') {
                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="' . basename($file['file_name']) . '"');
                header('Content-Length: ' . filesize($filepath));
                readfile($filepath);
                exit;
            }
            
            // Define allowed file types for viewing
            $allowed_viewable_types = ['jpg', 'jpeg', 'png', 'gif', 'txt'];
            $force_download_types = ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip'];
            
            // Set appropriate headers based on file type
            if (in_array($file_extension, $allowed_viewable_types)) {
                // For viewable files, display inline
                header('Content-Type: ' . $file['file_type']);
                header('Content-Disposition: inline; filename="' . basename($file['file_name']) . '"');
            } elseif (in_array($file_extension, $force_download_types)) {
                // For non-viewable files, force download
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($file['file_name']) . '"');
            } else {
                // Default handling
                header('Content-Type: ' . $file['file_type']);
                header('Content-Disposition: inline; filename="' . basename($file['file_name']) . '"');
            }
            
            header('Content-Length: ' . filesize($filepath));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            
            // Clear output buffer again and send file
            ob_clean();
            flush();
            readfile($filepath);
            exit;
        } else {
            // Detailed error reporting
            $error = "File not found or not readable.<br><br>";
            $error .= "<strong>Debug Information:</strong><br>";
            $error .= "Server root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
            $error .= "Base directory: $base_dir<br>";
            $error .= "Attempted path: $filepath<br>";
            $error .= "File exists: " . (file_exists($filepath) ? 'Yes' : 'No') . "<br>";
            $error .= "Readable: " . (is_readable($filepath) ? 'Yes' : 'No') . "<br>";
            $error .= "File size: " . (file_exists($filepath) ? filesize($filepath) : 'N/A') . " bytes";
            
            die($error);
        }
    } else {
        die("Error: File not found in database.");
    }
} else {
    die("Error: No file ID specified.");
}

// Close database connection
$conn->close();
?>