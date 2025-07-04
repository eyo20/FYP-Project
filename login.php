<?php
// Start session
session_start();

// Include database connection
require_once "db_connection.php";

// Initialize error messages
$email_error    = '';
$password_error = '';
$login_error    = '';

//Check if password reset is successful
$password_reset_success = isset($_GET['password_reset']) && $_GET['password_reset'] === 'true';

if (isset($_POST['loginbtn'])) {
    $email    = trim($_POST['user_email']);
    $password = $_POST['user_password'];
    $error    = false;

    // Validate email
    if (empty($email)) {
        $email_error = 'Email is required';
        $error = true;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $email_error = 'Invalid email format';
        $error = true;
    }

    // Validate password
    if (empty($password)) {
        $password_error = 'Password is required';
        $error = true;
    }

    if (!$error) {
        // Fetch user and role information
        $sql = "SELECT user_id, email, password, role FROM user WHERE email = ?";

        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, 's', $email);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);

                if (mysqli_stmt_num_rows($stmt) === 1) {
                    mysqli_stmt_bind_result($stmt, $id, $db_email, $db_password, $role);
                    mysqli_stmt_fetch($stmt);

                    // Verify password (supports hashed or plain)
                    if (password_verify($password, $db_password)) {
                        // Login successful, set session variables
                        $_SESSION['loggedin'] = true;
                        $_SESSION['user_id'] = $id;
                        $_SESSION['email']    = $db_email;
                        $_SESSION['role']     = $role;


                        // Update last login time
                        $update_login_time = "UPDATE user SET last_login = NOW() WHERE user_id = ?";
                        $login_stmt = $conn->prepare($update_login_time);
                        $login_stmt->bind_param("i", $user["user_id"]);
                        $login_stmt->execute();
                        $login_stmt->close();

                        // Redirect based on role
                        if ($role === 'student') {
                            header('Location: student_main_page.php');
                            exit;
                        } elseif ($role === 'tutor') {
                            header('Location: tutor_main_page.php');
                            exit;
                        } elseif ($role === 'admin') {
                            header('Location: admin.html');
                            exit;
                        } else {
                            $login_error = 'User role not recognized';
                        }
                    } else {
                        $login_error = 'Invalid email or password';
                    }
                }
            } else {
                $login_error = 'Oops! Something went wrong. Please try again later.';
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Close database connection
mysqli_close($conn);
?>

<!DOCTYPE html>
<html>

<head>
    <title>PeerLearn - Login</title>
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

        .login-container {
            margin: 0 auto;
            border: 1px solid #ddd;
            border-radius: 10px;
            width: 400px;
            padding: 0;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            background-color: white;
        }

        #login-title {
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

        #login-title h2 {
            margin: 0;
            color: white;
            font-weight: 500;
        }

        #login-form {
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

        #login-form input[type=email],
        #login-form input[type=password] {
            width: 100%;
            border-radius: 5px;
            border: 1px solid #ddd;
            height: 45px;
            padding: 5px 15px;
            font-size: 16px;
            transition: border 0.3s;
        }

        #login-form input[type=email]:focus,
        #login-form input[type=password]:focus {
            border: 1px solid #00AEEF;
            outline: none;
        }

        .error-message {
            color: #e74c3c;
            font-size: 12px;
            margin-top: 5px;
            text-align: left;
        }

        #login-form input[type=submit] {
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

        #login-form input[type=submit]:hover {
            background-color: #b5c500;
        }

        #login-form p {
            margin-top: 20px;
            text-align: center;
        }

        #login-form p a {
            text-decoration: none;
            color: #00AEEF;
            font-size: 14px;
        }

        #login-form p a:hover {
            color: #2B3990;
            text-decoration: underline;
        }

        .login-error {
            background-color: #fce4e4;
            border: 1px solid #e74c3c;
            color: #e74c3c;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        .registration-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        .reset-password-success-message {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div id="login-title">
            <div class="logo-container">
                <img src="image/fyp_peerlearn_logo.png" alt="PeerLearn Logo">
                <h2>PeerLearn</h2>
            </div>
        </div>

        <div id="login-form">
            <?php
            // 检查是否有注册成功的消息
            $registrationSuccess = isset($_GET['registered']) && $_GET['registered'] === 'true';
            if ($registrationSuccess):
            ?>
                <div class="registration-success">
                    Registration successful! You can now log in with your email and password.
                </div>
            <?php endif; ?>

            <?php if ($password_reset_success): ?>
                <div class="reset-password-success-message">
                    Your password has been reset successfully. You can now log in with your new password.
                </div>
            <?php endif; ?>

            <?php if (!empty($login_error)): ?>
                <div class="login-error">
                    <?php echo $login_error; ?>
                </div>
            <?php endif; ?>

            <form name="loginfrm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="input-group">
                    <label for="user_email">Email Address</label>
                    <input type="email" name="user_email" id="user_email" value="<?php echo isset($_POST['user_email']) ? htmlspecialchars($_POST['user_email']) : ''; ?>">
                    <?php if (!empty($email_error)): ?>
                        <div class="error-message"><?php echo $email_error; ?></div>
                    <?php endif; ?>
                </div>

                <div class="input-group">
                    <label for="user_password">Password</label>
                    <input type="password" name="user_password" id="user_password">
                    <?php if (!empty($password_error)): ?>
                        <div class="error-message"><?php echo $password_error; ?></div>
                    <?php endif; ?>
                </div>

                <input type="submit" name="loginbtn" value="LOGIN">
            </form>

            <p><a href="reset_password.php">forgot your password?</a></p>
            <p><a href="register.php">New to PeerLearn? Create an account</a></p>
        </div>
    </div>
</body>

</html>