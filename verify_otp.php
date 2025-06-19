<?php
session_start();
<<<<<<< HEAD

require_once 'db_connection.php';
=======
require_once 'db_connect.php';
>>>>>>> bcc8a4adc3f23035369f82c5393af8e628d8ac81

// Initialize variables
$otp = '';
$otp_error = '';
$success_message = '';
$error_message = '';

<<<<<<< HEAD
// Check if OTP was just sent
if (isset($_SESSION['otp_sent']) && $_SESSION['otp_sent']) {
    $success_message = 'OTP has been sent to your email. Please check your inbox.';
    unset($_SESSION['otp_sent']); // Clear the flag after displaying
}

=======
>>>>>>> bcc8a4adc3f23035369f82c5393af8e628d8ac81
// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Process form submission
if (isset($_POST['verify_btn'])) {
    $otp = trim($_POST['otp'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';
    $email = $_SESSION['reset_email'] ?? '';

    // Verify CSRF token
    if (!isset($_SESSION['csrf_token']) || $csrf_token !== $_SESSION['csrf_token']) {
        $error_message = 'Invalid request. Please try again.';
    } elseif (empty($otp)) {
        $otp_error = 'OTP is required';
    } elseif (!preg_match('/^\d{6}$/', $otp)) {
        $otp_error = 'Invalid OTP format (must be 6 digits)';
    } elseif (empty($email)) {
        $error_message = 'Session expired. Please request a new OTP.';
    } else {
        // Check OTP in database
        $sql = "SELECT * FROM password_reset WHERE email = ? AND token = ? AND expires_at > NOW()";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, 'ss', $email, $otp);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);

                if (mysqli_stmt_num_rows($stmt) === 1) {
                    // OTP valid, proceed to reset
                    mysqli_stmt_close($stmt);
                    // Delete used OTP
                    $delete_sql = "DELETE FROM password_reset WHERE email = ?";
                    if ($delete_stmt = mysqli_prepare($conn, $delete_sql)) {
                        mysqli_stmt_bind_param($delete_stmt, 's', $email);
                        mysqli_stmt_execute($delete_stmt);
                        mysqli_stmt_close($delete_stmt);
                    }
<<<<<<< HEAD
                    $_SESSION['reset_token'] = $otp; // Ensure token is available
                    header('Location: reset_password_form.php');
=======
                    unset($_SESSION['reset_email']); // Clear session
                    header('Location: reset_password_form.php'); // 跳转到重置密码页面
>>>>>>> bcc8a4adc3f23035369f82c5393af8e628d8ac81
                    exit();
                } else {
                    $error_message = 'Invalid or expired OTP. Please request a new one.';
                }
            } else {
                $error_message = 'Database error. Please try again later.';
            }
            mysqli_stmt_close($stmt);
        } else {
            $error_message = 'Database error. Please try again later.';
        }
    }
}

// Generate new CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Close database connection
mysqli_close($conn);
?>

<!DOCTYPE html>
<html>
<head>
    <title>PeerLearn - Verify OTP</title>
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
        .verify-container {
            margin: 0 auto;
            border: 1px solid #ddd;
            border-radius: 10px;
            width: 400px;
            padding: 0;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            background-color: white;
        }
        #verify-title {
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
        }
        .logo-container img {
            height: 40px;
            margin-right: 10px;
        }
        #verify-title h2 {
            margin: 0;
            color: white;
            font-weight: 500;
        }
        #verify-form {
            padding: 25px;
        }
        .input-group {
            position: relative;
            margin-bottom: 20px;
<<<<<<< HEAD
            text-align: left;
        }
        .input-group label {
            display: block;
=======
        }
        .input-group label {
            display: block;
            text-align: left;
>>>>>>> bcc8a4adc3f23035369f82c5393af8e628d8ac81
            margin-bottom: 5px;
            color: #555;
            font-size: 14px;
        }
        #verify-form input[type=text] {
            width: 100%;
            border-radius: 5px;
            border: 1px solid #ddd;
            height: 45px;
            padding: 5px 15px;
            font-size: 16px;
            transition: border 0.3s;
        }
        #verify-form input[type=text]:focus {
            border: 1px solid #00AEEF;
            outline: none;
        }
        .error-message {
            color: #e74c3c;
            font-size: 12px;
            margin-top: 5px;
            text-align: left;
        }
        #verify-form input[type=submit] {
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
        #verify-form input[type=submit]:hover {
            background-color: #b5c500;
        }
<<<<<<< HEAD
        .error-alert, .success-alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            width: 100%;
=======
        #verify-form p {
            margin-top: 20px;
            text-align: center;
        }
        #verify-form p a {
            text-decoration: none;
            color: #00AEEF;
            font-size: 14px;
        }
        #verify-form p a:hover {
            color: #2B3990;
            text-decoration: underline;
>>>>>>> bcc8a4adc3f23035369f82c5393af8e628d8ac81
        }
        .error-alert {
            background-color: #fce4e4;
            border: 1px solid #e74c3c;
            color: #e74c3c;
<<<<<<< HEAD
        }
        .success-alert {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
=======
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
>>>>>>> bcc8a4adc3f23035369f82c5393af8e628d8ac81
        }
        .instructions {
            margin-bottom: 20px;
            color: #555;
            font-size: 14px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="verify-container">
        <div id="verify-title">
            <div class="logo-container">
                <img src="image/fyp_peerlearn_logo.png" alt="PeerLearn Logo">
                <h2>PeerLearn</h2>
            </div>
        </div>
        <div id="verify-form">
            <h3 style="text-align: center; color: #2B3990; margin-top: 0;">Verify OTP</h3>
            <div class="instructions">
                Enter the OTP sent to your MMU email address below to reset your password.
            </div>
<<<<<<< HEAD
            <?php if (!empty($success_message)): ?>
                <div class="success-alert">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
=======
>>>>>>> bcc8a4adc3f23035369f82c5393af8e628d8ac81
            <?php if (!empty($error_message)): ?>
                <div class="error-alert">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="input-group">
                    <label for="otp">OTP</label>
                    <input type="text" name="otp" id="otp" value="<?php echo htmlspecialchars($otp); ?>" placeholder="Enter 6-digit OTP" maxlength="6">
                    <?php if (!empty($otp_error)): ?>
                        <div class="error-message"><?php echo htmlspecialchars($otp_error); ?></div>
                    <?php endif; ?>
                </div>
                <input type="submit" name="verify_btn" value="Verify">
            </form>
<<<<<<< HEAD
            <p><a href="reset_password.php" style="text-decoration: none; color: #00AEEF; font-size: 14px;">Resend OTP</a></p>
=======
            <p><a href="reset_password.php">Resend OTP</a></p>
>>>>>>> bcc8a4adc3f23035369f82c5393af8e628d8ac81
        </div>
    </div>
</body>
</html>