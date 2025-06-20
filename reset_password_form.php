<?php
session_start();
require_once 'db_connection.php';

// Enable strict error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Initialize session variables
$email = $_SESSION['reset_email'] ?? '';
$token = $_SESSION['reset_token'] ?? '';
$password_error = '';
$success_message = '';
$error_message = '';

// Check session variables
if (!isset($_POST['reset_btn']) && (empty($email) || empty($token))) {
    $error_message = 'Session expired. Please request a new OTP.';
    header("Location: reset_password.php");
    exit();
}

// Process form submission
if (isset($_POST['reset_btn'])) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate password
    try {
        if (empty($new_password) || empty($confirm_password)) {
            throw new Exception('Both fields are required.');
        }
        if ($new_password !== $confirm_password) {
            throw new Exception('Passwords do not match.');
        }
        if (strlen($new_password) < 8) {
            throw new Exception('Password must be at least 8 characters.');
        }
        if (!preg_match('/[A-Z]/', $new_password)) {
            throw new Exception('Password must contain at least one uppercase letter.');
        }
        if (!preg_match('/[0-9]/', $new_password)) {
            throw new Exception('Password must contain at least one number.');
        }
        if (!preg_match('/[^A-Za-z0-9]/', $new_password)) {
            throw new Exception('Password must contain at least one special character.');
        }

        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $sql = "UPDATE user SET password = ? WHERE email = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, 'ss', $hashed_password, $email);
            if (mysqli_stmt_execute($stmt)) {
                // Verify update
                $check_sql = "SELECT password FROM user WHERE email = ?";
                if ($check_stmt = mysqli_prepare($conn, $check_sql)) {
                    mysqli_stmt_bind_param($check_stmt, 's', $email);
                    mysqli_stmt_execute($check_stmt);
                    mysqli_stmt_bind_result($check_stmt, $db_password);
                    mysqli_stmt_fetch($check_stmt);
                    mysqli_stmt_close($check_stmt);
                    if ($db_password === $hashed_password) {
                        // Delete used OTP
                        $delete_sql = "DELETE FROM password_reset WHERE email = ?";
                        if ($delete_stmt = mysqli_prepare($conn, $delete_sql)) {
                            mysqli_stmt_bind_param($delete_stmt, 's', $email);
                            mysqli_stmt_execute($delete_stmt);
                            mysqli_stmt_close($delete_stmt);
                        } else {
                            throw new Exception('Failed to prepare OTP deletion statement: ' . mysqli_error($conn));
                        }
                        $success_message = 'Password reset successfully. <a href="login.php">Click here to login</a>.';
                        unset($_SESSION['reset_email']);
                        unset($_SESSION['reset_token']);
                    } else {
                        throw new Exception('Password update failed in database.');
                    }
                } else {
                    throw new Exception('Database verification failed: ' . mysqli_error($conn));
                }
            } else {
                throw new Exception('Failed to reset password: ' . mysqli_error($conn));
            }
            mysqli_stmt_close($stmt);
        } else {
            throw new Exception('Database preparation failed: ' . mysqli_error($conn));
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        error_log("Reset Password Error: " . $e->getMessage());
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html>
<head>
    <title>PeerLearn - Reset Password</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style type="text/css">
        * {
            box-sizing: border-box;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        body {
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .reset-container {
            margin: 0 auto;
            border: 1px solid #ddd;
            border-radius: 10px;
            width: 400px;
            padding: 0;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            background-color: white;
            text-align: center;
        }
        #reset-title {
            background-color: #2B3990;
            border-radius: 8px 8px 0 0;
            height: 70px;
            display: flex;
            align-items: center;
            padding: 0 20px;
        }
        .logo-container {
            display: flex;
            align-items: center;
            margin: 0 auto;
        }
        .logo-container img {
            height: 40px;
            margin-right: 10px;
        }
        #reset-title h2 {
            margin: 0;
            color: white;
            font-weight: 500;
        }
        #reset-form {
            padding: 25px;
        }
        .input-group {
            position: relative;
            margin-bottom: 20px;
            text-align: left;
        }
        .input-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-size: 14px;
        }
        #reset-form input[type=password] {
            width: 100%;
            border-radius: 5px;
            border: 1px solid #ddd;
            height: 45px;
            padding: 5px 15px;
            font-size: 16px;
            transition: border 0.3s;
        }
        #reset-form input[type=password]:focus {
            border: 1px solid #00AEEF;
            outline: none;
        }
        .error-message {
            color: #e74c3c;
            font-size: 12px;
            margin-top: 5px;
            text-align: left;
        }
        #reset-form input[type=submit] {
            background-color: #C4D600;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 5px;
            color: #2B3990;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 10px;
        }
        #reset-form input[type=submit]:hover {
            background-color: #b5c500;
        }
        .error-alert, .success-alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            width: 100%;
        }
        .error-alert {
            background-color: #fce4e4;
            border: 1px solid #e74c3c;
            color: #e74c3c;
        }
        .success-alert {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        a {
            color: #00AEEF;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div id="reset-title">
            <div class="logo-container">
                <img src="image/fyp_peerlearn_logo.png" alt="PeerLearn Logo">
                <h2>PeerLearn</h2>
            </div>
        </div>
        <div id="reset-form">
            <h3 style="text-align: center; color: #2B3990; margin-top: 0;">Reset Password</h3>
            <?php if (!empty($error_message)): ?>
                <div class="error-alert">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($success_message)): ?>
                <div class="success-alert">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            <?php if (empty($success_message) && empty($error_message)): ?>
                <form method="POST" action="">
                    <div class="input-group">
                        <label for="new_password">New Password</label>
                        <input type="password" name="new_password" id="new_password" placeholder="Enter new password" required>
                        <?php if (!empty($password_error)): ?>
                            <div class="error-message"><?php echo htmlspecialchars($password_error); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="input-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm new password" required>
                        <?php if (!empty($password_error)): ?>
                            <div class="error-message"><?php echo htmlspecialchars($password_error); ?></div>
                        <?php endif; ?>
                    </div>
                    <input type="submit" name="reset_btn" value="Reset Password">
                </form>
            <?php endif; ?>
            <p><a href="login.php">Back to Login</a></p>
        </div>
    </div>
</body>
</html>
<!-- Ensure no trailing PHP code or hidden characters -->