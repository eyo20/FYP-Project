<?php
session_start();
require_once 'db_connection.php';
require 'vendor/autoload.php'; // PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($conn) {
    mysqli_query($conn, "SET time_zone = '+08:00'");
    // Enable MySQL error reporting
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
}

// Initialize variables
$email = '';
$email_error = '';
$success_message = '';
$error_message = '';

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Process form submission
if (isset($_POST['reset_btn'])) {
    $email = trim($_POST['user_email'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Verify CSRF token
    if (!isset($_SESSION['csrf_token']) || $csrf_token !== $_SESSION['csrf_token']) {
        $error_message = 'Invalid request. Please try again.';
    } elseif (empty($email)) {
        $email_error = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $email_error = 'Invalid email format';
    } elseif (!preg_match('/@.*\.mmu\.edu\.my$/', $email)) {
        $email_error = 'Not an MMU email address';
    } else {
        try {
            // Check if email exists
            $sql = "SELECT user_id FROM user WHERE email = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, 's', $email);
                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_store_result($stmt);

                    if (mysqli_stmt_num_rows($stmt) === 1) {
                        // Generate OTP (6 digits)
                        $otp = sprintf("%06d", rand(0, 999999));

                        // Delete existing tokens
                        $delete_sql = "DELETE FROM password_reset WHERE email = ?";
                        if ($delete_stmt = mysqli_prepare($conn, $delete_sql)) {
                            mysqli_stmt_bind_param($delete_stmt, 's', $email);
                            mysqli_stmt_execute($delete_stmt);
                            mysqli_stmt_close($delete_stmt);
                        } else {
                            throw new Exception('Failed to prepare delete statement: ' . mysqli_error($conn));
                        }

                        // Store OTP with expires_at calculated by MySQL
                        $insert_sql = "INSERT INTO password_reset (email, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))";
                        if ($insert_stmt = mysqli_prepare($conn, $insert_sql)) {
                            mysqli_stmt_bind_param($insert_stmt, 'ss', $email, $otp);
                            if (mysqli_stmt_execute($insert_stmt)) {
                                // Check if insertion was successful
                                if (mysqli_stmt_affected_rows($insert_stmt) === 1) {
                                    // Fetch created_at and expires_at for logging
                                    $check_sql = "SELECT created_at, expires_at FROM password_reset WHERE email = ? AND token = ?";
                                    if ($check_stmt = mysqli_prepare($conn, $check_sql)) {
                                        mysqli_stmt_bind_param($check_stmt, 'ss', $email, $otp);
                                        mysqli_stmt_execute($check_stmt);
                                        mysqli_stmt_bind_result($check_stmt, $created_at, $expires_at);
                                        if (mysqli_stmt_fetch($check_stmt)) {
                                            error_log("OTP: $otp, Email: $email, Created: $created_at, Expires: $expires_at");
                                        } else {
                                            error_log("Failed to fetch OTP record for Email: $email, OTP: $otp");
                                        }
                                        mysqli_stmt_close($check_stmt);
                                    }

                                    // Send OTP via Gmail SMTP
                                    $mail = new PHPMailer(true);
                                    try {
                                        // Server settings
                                        $mail->isSMTP();
                                        $mail->Host = 'smtp.gmail.com';
                                        $mail->SMTPAuth = true;
                                        $mail->Username = 'peerlearn.not.reply@gmail.com';
                                        $mail->Password = 'epqb obac nhed uflg';
                                        $mail->SMTPSecure = 'tls';
                                        $mail->Port = 587;

                                        // Recipients
                                        $mail->setFrom('peerlearn.not.reply@gmail.com', 'PeerLearn');
                                        $mail->addAddress($email);
                                        $mail->addReplyTo('no-reply@yourdomain.com', 'No Reply');

                                        // Content
                                        $mail->isHTML(true);
                                        $mail->Subject = 'PeerLearn - OTP for Password Reset';
                                        $mail->Body = '<html><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;"><div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;"><h2 style="color: #2B3990; text-align: center;">PeerLearn Password Reset</h2><p>Hello,</p><p>Your OTP for password reset is: <strong>' . $otp . '</strong></p><p>This OTP expires in 15 minutes.</p><p>If you didn’t request this, ignore this email.</p><p>Thank you,<br>The PeerLearn Team</p></div></body></html>';
                                        $mail->AltBody = "Your OTP for password reset is: $otp\nExpires in 15 minutes.\nIf you didn't request this, ignore this.";

                                        $mail->send();
                                        $_SESSION['reset_email'] = $email;
                                        header('Location: verify_otp.php');
                                        exit();
                                    } catch (Exception $e) {
                                        error_log("Mailer Error: " . $mail->ErrorInfo);
                                        $error_message = 'Failed to send OTP: ' . htmlspecialchars($e->getMessage());
                                    }
                                } else {
                                    throw new Exception('OTP insertion failed: No rows affected.');
                                }
                            } else {
                                throw new Exception('OTP insertion failed: ' . mysqli_error($conn));
                            }
                            mysqli_stmt_close($insert_stmt);
                        } else {
                            throw new Exception('Failed to prepare insert statement: ' . mysqli_error($conn));
                        }
                    } else {
                        $success_message = 'If your email exists in our system, you will receive an OTP.';
                    }
                } else {
                    throw new Exception('Failed to execute email check: ' . mysqli_error($conn));
                }
                mysqli_stmt_close($stmt);
            } else {
                throw new Exception('Failed to prepare email check statement: ' . mysqli_error($conn));
            }
        } catch (Exception $e) {
            error_log("Database Error: " . $e->getMessage());
            $error_message = 'Database error: ' . htmlspecialchars($e->getMessage());
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
    <title>PeerLearn - Forgot Password</title>
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
        .forgot-container {
            margin: 0 auto;
            border: 1px solid #ddd;
            border-radius: 10px;
            width: 400px;
            padding: 0;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            background-color: white;
        }
        #forgot-title {
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
        #forgot-title h2 {
            margin: 0;
            color: white;
            font-weight: 500;
        }
        #forgot-form {
            padding: 25px;
            text-align: center; /* Center align form content */
        }
        .input-group {
            position: relative;
            margin-bottom: 20px;
            text-align: left;
        }
        .input-group label {
            display: block;
            text-align: left;
            margin-bottom: 5px;
            color: #555;
            font-size: 14px;
        }
        #forgot-form input[type=email] {
            width: 100%;
            border-radius: 5px;
            border: 1px solid #ddd;
            height: 45px;
            padding: 5px 15px;
            font-size: 16px;
            transition: border 0.3s;
        }
        #forgot-form input[type=email]:focus {
            border: 1px solid #00AEEF;
            outline: none;
        }
        .error-message {
            color: #e74c3c;
            font-size: 12px;
            margin-top: 5px;
            text-align: left;
        }
        #forgot-form input[type=submit] {
            background-color: #C4D600;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px; /* Increased radius for modern look */
            color: #2B3990;
            font-weight: 600; /* Slightly softer bold */
            font-size: 18px; /* Larger font size */
            cursor: pointer;
            transition: background-color 0.3s, box-shadow 0.3s; /* Smooth transition */
            margin: 10px auto 0; /* Center the button */
            display: block; /* Ensure block-level for centering */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* Add subtle shadow */
        }
        #forgot-form input[type=submit]:hover {
            background-color: #b5c500;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Enhanced shadow on hover */
        }
        #forgot-form p {
            margin-top: 20px;
            text-align: center;
        }
        #forgot-form p a {
            text-decoration: none;
            color: #00AEEF;
            font-size: 14px;
        }
        #forgot-form p a:hover {
            color: #2B3990;
            text-decoration: underline;
        }
        .error-alert {
            background-color: #fce4e4;
            border: 1px solid #e74c3c;
            color: #e74c3c;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .success-alert {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
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
    <div class="forgot-container">
        <div id="forgot-title">
            <div class="logo-container">
                <img src="image/fyp_peerlearn_logo.png" alt="PeerLearn Logo">
                <h2>PeerLearn</h2>
            </div>
        </div>
        <div id="forgot-form">
            <h3 style="text-align: center; color: #2B3990; margin-top: 0;">Forgot Password</h3>
            <div class="instructions">
                Enter your MMU email address below, and we'll send you instructions to reset your password.
            </div>
            <?php if (!empty($error_message)): ?>
                <div class="error-alert">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($success_message)): ?>
                <div class="success-alert">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="input-group">
                    <label for="user_email">Email Address</label>
                    <input type="email" name="user_email" id="user_email" value="<?php echo htmlspecialchars($email); ?>" placeholder="Enter your MMU email">
                    <?php if (!empty($email_error)): ?>
                        <div class="error-message"><?php echo htmlspecialchars($email_error); ?></div>
                    <?php endif; ?>
                </div>
                <input type="submit" name="reset_btn" value="SEND OTP">
            </form>
            <p><a href="login.php">Back to Login</a></p>
        </div>
    </div>
</body>
</html>