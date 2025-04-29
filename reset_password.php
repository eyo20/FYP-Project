<?php
// Start session
session_start();

// Include database connection
require_once "db_connection.php";

// Initialize variables
$token = '';
$email = '';
$token_valid = false;
$password = '';
$confirm_password = '';
$password_error = '';
$confirm_password_error = '';
$general_error = '';
$success_message = '';

// Check if token is provided in URL
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    
    // Validate token
    $sql = "SELECT email, expires_at FROM password_reset WHERE token = ? AND expires_at > NOW()";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, 's', $token);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_store_result($stmt);
            
            if (mysqli_stmt_num_rows($stmt) === 1) {
                mysqli_stmt_bind_result($stmt, $email, $expires_at);
                mysqli_stmt_fetch($stmt);
                $token_valid = true;
            } else {
                $general_error = 'Invalid or expired password reset link. Please request a new one.';
            }
        } else {
            $general_error = 'Something went wrong. Please try again later.';
        }
        
        mysqli_stmt_close($stmt);
    } else {
        $general_error = 'Database error. Please try again later.';
    }
} else {
    $general_error = 'Invalid request. No token provided.';
}

// Process form submission
if (isset($_POST['reset_password_btn']) && $token_valid) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $email = $_POST['email'];
    $token = $_POST['token'];
    
        // Validate password
        if (empty($password)) {
            $password_error = 'Password is required';
        } elseif (strlen($password) < 8) {
            $password_error = 'Password must be at least 8 characters long';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $password_error = 'Password must contain at least one uppercase letter';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $password_error = 'Password must contain at least one number';
        } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $password_error = 'Password must contain at least one special character';
        }
        
        // Validate confirm password
        if (empty($confirm_password)) {
            $confirm_password_error = 'Please confirm your password';
        } elseif ($password !== $confirm_password) {
            $confirm_password_error = 'Passwords do not match';
        }
        
        // If no errors, update password
        if (empty($password_error) && empty($confirm_password_error)) {
            // Hash the new password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Update user password in database
            $update_sql = "UPDATE user SET password = ? WHERE email = ?";
            if ($update_stmt = mysqli_prepare($conn, $update_sql)) {
                mysqli_stmt_bind_param($update_stmt, 'ss', $hashed_password, $email);
                
                if (mysqli_stmt_execute($update_stmt)) {
                    // Password updated successfully
                    
                    // Delete the used token
                    $delete_sql = "DELETE FROM password_reset WHERE token = ?";
                    if ($delete_stmt = mysqli_prepare($conn, $delete_sql)) {
                        mysqli_stmt_bind_param($delete_stmt, 's', $token);
                        mysqli_stmt_execute($delete_stmt);
                        mysqli_stmt_close($delete_stmt);
                    }
                    
                    // Send notification email
                    $to = $email;
                    $subject = 'PeerLearn - Password Changed Successfully';
                    
                    // Create HTML message
                    $message = '
                    <html>
                    <head>
                        <title>Your PeerLearn Password Has Been Changed</title>
                    </head>
                    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
                        <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                            <div style="text-align: center; margin-bottom: 20px;">
                                <h2 style="color: #2B3990;">PeerLearn Password Changed</h2>
                            </div>
                            <p>Hello,</p>
                            <p>Your password for PeerLearn has been successfully changed.</p>
                            <p>If you did not make this change, please contact support immediately.</p>
                            <p>Thank you,<br>The PeerLearn Team</p>
                            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #777; text-align: center;">
                                <p>This is an automated email, please do not reply.</p>
                            </div>
                        </div>
                    </body>
                    </html>';
                    
                    // Set email headers
                    $headers = "MIME-Version: 1.0" . "\r\n";
                    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                    $headers .= 'From: PeerLearn <noreply@peerlearn.com>' . "\r\n";
                    
                    // Send email
                    mail($to, $subject, $message, $headers);
                    
                    // Set success message and redirect after a delay
                    $success_message = 'Your password has been reset successfully. You will be redirected to the login page in a few seconds.';
                    header("refresh:5;url=login.php?password_reset=true");
                } else {
                    $general_error = 'Failed to update password. Please try again.';
                }
                
                mysqli_stmt_close($update_stmt);
            } else {
                $general_error = 'Database error. Please try again later.';
            }
        }
    }
    
    // Close database connection
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
            }
            
            .input-group label {
                display: block;
                text-align: left;
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
            
            .password-requirements {
                margin-bottom: 20px;
                color: #555;
                font-size: 12px;
                line-height: 1.5;
                background-color: #f8f9fa;
                padding: 10px;
                border-radius: 5px;
            }
            
            .password-requirements ul {
                margin: 5px 0 0 20px;
                padding: 0;
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
                
                <?php if(!empty($general_error)): ?>
                    <div class="error-alert">
                        <?php echo $general_error; ?>
                    </div>
                    <?php if(!$token_valid): ?>
                        <p style="text-align: center;"><a href="forgot_password.php">Request a new password reset</a></p>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php if(!empty($success_message)): ?>
                    <div class="success-alert">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if($token_valid && empty($success_message)): ?>
                    <div class="password-requirements">
                        <strong>Password must:</strong>
                        <ul>
                            <li>Be at least 8 characters long</li>
                            <li>Include at least one uppercase letter (A-Z)</li>
                            <li>Include at least one number (0-9)</li>
                            <li>Include at least one special character (!@#$%^&*)</li>
                        </ul>
                    </div>
                    
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . '?token=' . $token); ?>">
                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        
                        <div class="input-group">
                            <label for="password">New Password</label>
                            <input type="password" name="password" id="password" placeholder="Enter new password">
                            <?php if(!empty($password_error)): ?>
                                <div class="error-message"><?php echo $password_error; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="input-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm new password">
                            <?php if(!empty($confirm_password_error)): ?>
                                <div class="error-message"><?php echo $confirm_password_error; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <input type="submit" name="reset_password_btn" value="RESET PASSWORD">
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </body>
    </html>
    
