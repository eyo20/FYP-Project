<?php
// Start session
session_start();

// Include database connection and PHPMailer
require_once 'db_connection.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Initialize variables
$email = '';
$email_error = '';
$success_message = '';
$error_message = '';

// Generate CSRF token if not set
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
        // Check if email exists
        $sql = "SELECT user_id FROM user WHERE email = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, 's', $email);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);

                if (mysqli_stmt_num_rows($stmt) === 1) {
                    // Email exists, generate token
                    $token = bin2hex(random_bytes(32));
                    $expires_at = date('Y-m-d H:i:s', time() + 15 * 60);

                    // Delete existing tokens
                    $delete_sql = "DELETE FROM password_reset WHERE email = ?";
                    if ($delete_stmt = mysqli_prepare($conn, $delete_sql)) {
                        mysqli_stmt_bind_param($delete_stmt, 's', $email);
                        mysqli_stmt_execute($delete_stmt);
                        mysqli_stmt_close($delete_stmt);
                    }

                    // Store new token
                    $insert_sql = "INSERT INTO password_reset (email, token, expires_at) VALUES (?, ?, ?)";
                    if ($insert_stmt = mysqli_prepare($conn, $insert_sql)) {
                        mysqli_stmt_bind_param($insert_stmt, 'sss', $email, $token, $expires_at);

                        if (mysqli_stmt_execute($insert_stmt)) {
                            // Send email with PHPMailer
                            $mail = new PHPMailer(true);
                            try {
                                // SMTP settings
                                $mail->isSMTP();
                                $mail->Host = 'smtp.gmail.com';
                                $mail->SMTPAuth = true;
                                $mail->Username = 'your-email@gmail.com'; // Replace with your Gmail
                                $mail->Password = 'your-app-password'; // Replace with App Password
                                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                                $mail->Port = 587;

                                // Recipients
                                $mail->setFrom('noreply@peerlearn.com', 'PeerLearn');
                                $mail->addAddress($email);

                                // Content
                                $reset_link = 'http://localhost:8080/reset_password.php?token=' . $token; // Adjust domain/port
                                $mail->isHTML(true);
                                $mail->Subject = 'PeerLearn - Password Reset Request';
                                $mail->Body = '
                                    <html>
                                    <head>
                                        <title>Reset Your PeerLearn Password</title>
                                    </head>
                                    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
                                        <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                                            <div style="text-align: center; margin-bottom: 20px;">
                                                <h2 style="color: #2B3990;">PeerLearn Password Reset</h2>
                                            </div>
                                            <p>Hello,</p>
                                            <p>We received a request to reset your password for your PeerLearn account. Please click the button below to reset your password:</p>
                                            <p style="text-align: center;">
                                                <a href="' . $reset_link . '" style="display: inline-block; background-color: #C4D600; color: #2B3990; text-decoration: none; padding: 10px 20px; border-radius: 5px; font-weight: bold;">Reset Password</a>
                                            </p>
                                            <p>This link will expire in 15 minutes.</p>
                                            <p>If you not request a password reset, please ignore this email or contact support if you have concerns.</p>
                                            <p>Thank you,<br>The PeerLearn Team</p>
                                            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #777; text-align: center;">
                                                <p>This is an automated email, please do not reply.</p>
                                            </div>
                                        </div>
                                    </body>
                                    </html>';
                                $mail->AltBody = "Reset your PeerLearn password: $reset_link\nThis link expires in 15 minutes.\nIf you not request this, ignore this email.";

                                $mail->send();
                                $success_message = 'Password reset instructions have been sent to your email. Please check your inbox.';
                                $email = '';
                            } catch (Exception $e) {
                                $error_message = 'Failed to send password reset email. Please try again later.';
                                error_log("PHPMailer error: " . $mail->ErrorInfo);
                            }
                        } else {
                            $error_message = 'Something went wrong. Please try again later.';
                        }
                        mysqli_stmt_close($insert_stmt);
                    } else {
                        $error_message = 'Database error. Please try again later.';
                    }
                } else {
                    // Email doesn't exist, show vague message for security
                    $success_message = 'If your email exists in our system, you will receive password reset instructions.';
                }
            } else {
                $error_message = 'Something went wrong. Please try again later.';
            }
            mysqli_stmt_close($stmt);
        } else {
            $error_message = 'Database error. Please try again later.';
        }
    }
}

// Generate new CSRF token for next request
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
        }
        
        .input-group {
            position: relative;
            margin-bottom: 20px;
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
            border-radius: 5px;
            color: #2B3990;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 10px;
        }
        
        #forgot-form input[type=submit]:hover {
            background-color: #b5c500;
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
                
                <input type="submit" name="reset_btn" value="SEND RESET LINK">
            </form>
            
            <p><a href="login.php">Back to Login</a></p>
        </div>
    </div>
</body>
</html>