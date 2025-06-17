<?php
session_start();
require_once 'db_connection.php';

// Verify admin access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Unauthorized access!";
    header("Location: admin_staff.php");
    exit();
}

// Check if action parameter exists
if (!isset($_GET['action'])) {
    $_SESSION['error'] = "Invalid request";
    header("Location: admin_staff.php");
    exit();
}

$action = $_GET['action'];

// Handle different actions
switch ($action) {
    case 'add':
        // Handle add new staff
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = "Invalid request method";
            header("Location: add_staff.php");
            exit();
        }

        // Get form data
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = 'admin'; // Force role to be 'admin'

        // Validate inputs
        if (empty($username) || empty($email) || empty($_POST['password'])) {
            $_SESSION['error'] = "All fields are required";
            $_SESSION['form_data'] = $_POST;
            header("Location: add_staff.php");
            exit();
        }

        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT * FROM user WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['error'] = "Username or email already exists";
            $_SESSION['form_data'] = $_POST;
            header("Location: add_staff.php");
            exit();
        }

        // Insert new staff with forced admin role
        $stmt = $conn->prepare("INSERT INTO user (username, email, password, role, is_active) VALUES (?, ?, ?, 'admin', 1)");
        $stmt->bind_param("sss", $username, $email, $password);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Staff member added successfully as admin";
            header("Location: admin_staff.php");
            exit();
        } else {
            $_SESSION['error'] = "Error adding staff member: " . $conn->error;
            $_SESSION['form_data'] = $_POST;
            header("Location: add_staff.php");
            exit();
        }
        break;

    case 'delete':
    case 'activate':
    case 'deactivate':
        // These actions require an ID parameter
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            $_SESSION['error'] = "Invalid request";
            header("Location: admin_staff.php");
            exit();
        }

        $user_id = intval($_GET['id']);

        if ($action === 'delete') {
            // Check if this is the last admin
            $stmt = $conn->prepare("SELECT COUNT(*) as admin_count FROM user WHERE role = 'admin' AND user_id != ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $admin_count = $result->fetch_assoc()['admin_count'];
            
            $stmt = $conn->prepare("SELECT role FROM user WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user_role = $result->fetch_assoc()['role'];
            
            if ($user_role === 'admin' && $admin_count === 0) {
                $_SESSION['error'] = "Cannot delete the last admin";
                header("Location: admin_staff.php");
                exit();
            }

            // Delete user
            $stmt = $conn->prepare("DELETE FROM user WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = "Staff member deleted successfully";
            } else {
                $_SESSION['error'] = "Error deleting staff member: " . $conn->error;
            }
        } else {
            // Handle activate/deactivate
            $is_active = $action === 'activate' ? 1 : 0;
            
            $stmt = $conn->prepare("UPDATE user SET is_active = ? WHERE user_id = ?");
            $stmt->bind_param("ii", $is_active, $user_id);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = "Staff member status updated successfully";
            } else {
                $_SESSION['error'] = "Error updating staff member status: " . $conn->error;
            }
        }
        
        header("Location: admin_staff.php");
        exit();
        break;

    default:
        $_SESSION['error'] = "Invalid action";
        header("Location: admin_staff.php");
        exit();
}

?>