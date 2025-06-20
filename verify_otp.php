<?php
session_start();

require_once 'db_connection.php';

// Initialize variables
$otp = '';
$otp_error = '';
$success_message = 'OTP has been sent to your email. Please check your inbox.';
$error_message = '';

// Debug: Log session and server time
error_log("Session reset_email: " . ($_SESSION['reset_email'] ?? 'Not set'));
error_log("Server time: " . date('Y-m-d H:i:s'));

// Check if reset_email session variable exists
if (!isset($_SESSION['reset_email'])) {
    $error_message = 'Session expired. Please request a new OTP.';
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Process form submission
if (isset($_POST['verify_btn'])) {
    $otp = trim($_POST['otp'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';
    $email = $_SESSION['reset_email'] ?? '';

    // Debug: Log submitted data
    error_log("Submitted OTP: $otp, Email: $email, CSRF: $csrf_token");

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

                // Debug: Log validation result
                error_log("OTP validation result: " . mysqli_stmt_num_rows($stmt));

                if (mysqli_stmt_num_rows($stmt) === 1) {
                    // OTP valid, proceed to reset
                    $_SESSION['reset_token'] = $otp; // 设置 reset_token
                    mysqli_stmt_close($stmt);
                    // Delete used OTP
                    $delete_sql = "DELETE FROM password_reset WHERE email = ?";
                    if ($delete_stmt = mysqli_prepare($conn, $delete_sql)) {
                        mysqli_stmt_bind_param($delete_stmt, 's', $email);
                        mysqli_stmt_execute($delete_stmt);
                        mysqli_stmt_close($delete_stmt);
                    } else {
                        error_log("Failed to prepare delete statement: " . mysqli_error($conn));
                    }

                    // Debug: Log redirect intention
                    error_log("Redirecting to reset_password_form.php for Email: $email");
                    error_log("Session after redirect: " . print_r($_SESSION, true));
                    header('Location: reset_password_form.php'); // Ensure redirect to reset_password_form.php
                    exit(); // Ensure script stops after redirect
                } else {
                    $error_message = 'Invalid or expired OTP. Please request a new one.';
                    error_log("OTP validation failed for Email: $email, OTP: $otp");
                }
            } else {
                $error_message = 'Database error. Please try again later.';
                error_log("Database execute error: " . mysqli_error($conn));
            }
            mysqli_stmt_close($stmt);
        } else {
            $error_message = 'Database error. Please try again later.';
            error_log("Database prepare error: " . mysqli_error($conn));
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
        #verify-form input[type=submit]:hover {
            background-color: #b5c500;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Enhanced shadow on hover */
        }
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
            <?php if (!empty($success_message)): ?>
                <div class="success-alert">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
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
            <p><a href="reset_password.php">Resend OTP</a></p>
        </div>
    </div>
</body>
</html>