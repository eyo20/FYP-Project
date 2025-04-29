<?php
// Display all errors (use in development environment, remove in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include database connection
require_once "db_connection.php";

// Initialize variables
$username = $email = $role = '';
$firstName = $lastName = '';
$errors = [];

// If form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    
    // Validate if data is empty
    if (empty($username) || empty($email) || empty($password) || empty($role) || empty($firstName) || empty($lastName)) {
        $errors['general'] = "All fields are required!";
    } 
    // Validate MMU email
    elseif (!preg_match('/@.*\.mmu\.edu\.my$/', $email)) {
        $errors['email'] = "Not an MMU email address";
    }
    // Verify password complexity
    elseif (strlen($password) < 8) {
        $errors['password'] = "Password must be at least 8 characters long";
    }
    elseif (!preg_match('/[A-Z]/', $password)) {
        $errors['password'] = "Password must contain at least one uppercase letter";
    }
    elseif (!preg_match('/[0-9]/', $password)) {
        $errors['password'] = "Password must contain at least one number";
    }
    elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors['password'] = "Password must contain at least one special character";
    }
    // Verify Password Confirmation
    elseif ($password !== $confirmPassword) {
        $errors['confirm_password'] = "Passwords do not match";
    }
    else {
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Check if email already exists (changed from username to email)
        $stmt = mysqli_prepare($conn, "SELECT user_id FROM user WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors['email'] = "Email already exists!";
        } else {
            // Insert new user
            mysqli_stmt_close($stmt);
            $stmt = mysqli_prepare($conn, "INSERT INTO user (username, email, password, role, first_name, last_name) VALUES (?, ?, ?, ?, ?, ?)");
            
            if (!$stmt) {
                $errors['general'] = "Database error: " . mysqli_error($conn);
            } else {
                mysqli_stmt_bind_param($stmt, "ssssss", $username, $email, $hashedPassword, $role, $firstName, $lastName);
                
                if (mysqli_stmt_execute($stmt)) {
                    // Registration successful, redirect to login page
                    header("Location: login.php?registered=true");
                    exit;
                } else {
                    $errors['general'] = "Registration failed: " . mysqli_stmt_error($stmt);
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peer Tutoring Platform - Registration</title>
    <style>
        /* Your existing CSS styles remain unchanged */
        :root {
            --primary-color: #2B3990;
            --secondary-color: #00AEEF;
            --accent-color: #C4D600;
            --light-gray: #f5f5f5;
            --error-color: #dc3545;
        }
        
        body, html {
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
            background-color: var(--light-gray);
            height: 100%;
        }
        
        .container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
            box-sizing: border-box;
        }
        
        .logo {
            margin-bottom: 30px;
            text-align: center;
            padding: 0 10px;
        }
        
        .logo h1 {
            color: var(--primary-color);
            margin: 0;
            font-size: 28px;
        }
        
        .logo p {
            color: var(--secondary-color);
            margin: 5px 0 0;
            font-size: 16px;
        }
        
        .registration-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
            width: 100%;
            max-width: 400px;
            box-sizing: border-box;
        }
        
        .registration-card h2 {
            color: var(--primary-color);
            text-align: center;
            margin-top: 0;
            margin-bottom: 25px;
            font-size: 24px;
        }
        
        .input-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .input-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }
        
        .input-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        .input-group input:focus {
            outline: none;
            border-color: var(--secondary-color);
        }
        
        .input-group input.error {
            border-color: var(--error-color);
        }
        
        .error-message {
            color: var(--error-color);
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }
        
        .error-message.visible {
            display: block;
        }
        
        /* PHP error messages are always visible */
        .php-error {
            color: var(--error-color);
            font-size: 12px;
            margin-top: 5px;
            display: block;
        }
        
        .role-selection {
            margin-bottom: 20px;
        }
        
        .role-selection h3 {
            margin-top: 0;
            margin-bottom: 10px;
            color: #333;
            font-size: 16px;
            font-weight: 500;
        }
        
        .role-options {
            display: flex;
            gap: 15px;
        }
        
        /* Make role options stack on very small screens */
        @media (max-width: 360px) {
            .role-options {
                flex-direction: column;
            }
        }
        
        .role-option {
            flex: 1;
            border: 2px solid #ddd;
            border-radius: 5px;
            padding: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .role-option.selected {
            border-color: var(--primary-color);
            background-color: rgba(43, 57, 144, 0.05);
        }
        
        .role-option:hover {
            border-color: var(--secondary-color);
        }
        
        .role-option span {
            display: block;
            margin-top: 5px;
            font-weight: 500;
        }
        
        .signup-btn {
            width: 100%;
            padding: 14px;
            background-color: var(--accent-color);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        
        .signup-btn:hover {
            background-color: #b1c000;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        
        .login-link a {
            color: var(--secondary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .role-error {
            color: var(--error-color);
            font-size: 12px;
            text-align: center;
            margin-top: 5px;
            display: none;
        }
        
        .role-error.visible {
            display: block;
        }
        
        /* Name fields row */
        .name-row {
            display: flex;
            gap: 15px;
        }
        
        .name-row .input-group {
            flex: 1;
        }
        
        /* Enhanced responsive styles */
        @media (max-width: 480px) {
            .registration-card {
                padding: 20px;
            }
            
            .logo h1 {
                font-size: 24px;
            }
            
            .registration-card h2 {
                font-size: 20px;
            }
            
            .input-group input {
                padding: 10px;
            }
            
            .signup-btn {
                padding: 12px;
            }
            
            .name-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>Peer Tutoring Platform</h1>
            <p>Learn Together, Grow Together</p>
        </div>
        
        <div class="registration-card">
            <h2>Create an Account</h2>
            
            <?php if (!empty($errors['general'])): ?>
                <div class="php-error" style="text-align: center; margin-bottom: 15px;"><?= htmlspecialchars($errors['general']) ?></div>
            <?php endif; ?>
            
            <form id="registration-form" method="POST" action="register.php" novalidate>
                <div class="input-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter your username" value="<?= htmlspecialchars($username) ?>" required>
                    <div class="error-message" id="username-error">Username is required</div>
                    <?php if (!empty($errors['username'])): ?>
                        <div class="php-error"><?= htmlspecialchars($errors['username']) ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="name-row">
                    <div class="input-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" placeholder="First name" value="<?= htmlspecialchars($firstName) ?>" required>
                        <div class="error-message" id="first-name-error">First name is required</div>
                        <?php if (!empty($errors['first_name'])): ?>
                            <div class="php-error"><?= htmlspecialchars($errors['first_name']) ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="input-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" placeholder="Last name" value="<?= htmlspecialchars($lastName) ?>" required>
                        <div class="error-message" id="last-name-error">Last name is required</div>
                        <?php if (!empty($errors['last_name'])): ?>
                            <div class="php-error"><?= htmlspecialchars($errors['last_name']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="input-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter your MMU email address" value="<?= htmlspecialchars($email) ?>" required>
                    <div class="error-message" id="email-error">Please enter a valid MMU email address</div>
                    <?php if (!empty($errors['email'])): ?>
                        <div class="php-error"><?= htmlspecialchars($errors['email']) ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="input-group">
                     <label for="password">Password</label>
                     <input type="password" id="password" name="password" placeholder="8+ chars with A-Z, 0-9 & symbol" required>
                     <div class="error-message" id="password-error">Password must be at least 8 characters long</div>
                     <?php if (!empty($errors['password'])): ?>
                         <div class="php-error"><?= htmlspecialchars($errors['password']) ?></div>
                     <?php endif; ?>
                </div>

                
                <div class="input-group">
                    <label for="confirm-password">Confirm Password</label>
                    <input type="password" id="confirm-password" name="confirm_password" placeholder="Re-enter your password" required>
                    <div class="error-message" id="confirm-password-error">Passwords do not match</div>
                    <?php if (!empty($errors['confirm_password'])): ?>
                        <div class="php-error"><?= htmlspecialchars($errors['confirm_password']) ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="role-selection">
                    <h3>Which role would you like to join as?</h3>
                    <div class="role-options">
                        <div class="role-option <?= $role === 'student' ? 'selected' : '' ?>" onclick="selectRole('student')" id="role-student">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 15C15.3137 15 18 12.3137 18 9C18 5.68629 15.3137 3 12 3C8.68629 3 6 5.68629 6 9C6 12.3137 8.68629 15 12 15Z" stroke="#2B3990" stroke-width="2"/>
                                <path d="M3 20.4V20.4C3 20.4 6 18 12 18C18 18 21 20.4 21 20.4V20.4" stroke="#2B3990" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <span>Student</span>
                        </div>
                        <div class="role-option <?= $role === 'tutor' ? 'selected' : '' ?>" onclick="selectRole('tutor')" id="role-tutor">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 3L22 8L12 13L2 8L12 3Z" stroke="#2B3990" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M22 8V16" stroke="#2B3990" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M6 10.6V16C6 16S8.5 18 12 18C15.5 18 18 16 18 16V10.6" stroke="#2B3990" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span>Tutor</span>
                        </div>
                    </div>
                    <div class="role-error" id="role-error">Please select a role</div>
                    <?php if (!empty($errors['role'])): ?>
                        <div class="php-error" style="text-align: center;"><?= htmlspecialchars($errors['role']) ?></div>
                    <?php endif; ?>
                </div>
                
                <input type="hidden" name="role" id="role-input" value="<?= htmlspecialchars($role) ?>">
                <button type="submit" class="signup-btn">Sign Up</button>
            </form>
            
            <div class="login-link">
                Already have an account? <a href="login.php">Log in</a>
            </div>
        </div>
    </div>
    
    <script>
        // Track selected role
        let selectedRole = "<?= htmlspecialchars($role) ?>";
        
        function selectRole(role) {
            // Remove selected state from all options
            document.querySelectorAll('.role-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Add selected state to chosen role
            document.getElementById('role-' + role).classList.add('selected');
            
            // Update selected role value
            selectedRole = role;
            document.getElementById('role-input').value = role;
            
            // Hide role error if showing
            document.getElementById('role-error').classList.remove('visible');
        }
        
        // Real-time form validation
        document.getElementById('username').addEventListener('input', validateUsername);
        document.getElementById('email').addEventListener('input', validateEmail);
        document.getElementById('password').addEventListener('input', validatePassword);
        document.getElementById('confirm-password').addEventListener('input', validateConfirmPassword);
        document.getElementById('first_name').addEventListener('input', validateFirstName);
        document.getElementById('last_name').addEventListener('input', validateLastName);
        
        function validateUsername() {
            const username = document.getElementById('username');
            const errorElement = document.getElementById('username-error');
            
            if (!username.value.trim()) {
                showError(username, errorElement, 'Username is required');
                return false;
            } else {
                hideError(username, errorElement);
                return true;
            }
        }
        
        function validateFirstName() {
            const firstName = document.getElementById('first_name');
            const errorElement = document.getElementById('first-name-error');
            
            if (!firstName.value.trim()) {
                showError(firstName, errorElement, 'First name is required');
                return false;
            } else {
                hideError(firstName, errorElement);
                return true;
            }
        }
        
        function validateLastName() {
            const lastName = document.getElementById('last_name');
            const errorElement = document.getElementById('last-name-error');
            
            if (!lastName.value.trim()) {
                showError(lastName, errorElement, 'Last name is required');
                return false;
            } else {
                hideError(lastName, errorElement);
                return true;
            }
        }
        
        function validateEmail() {
            const email = document.getElementById('email');
            const errorElement = document.getElementById('email-error');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const mmuEmailRegex = /@.*\.mmu\.edu\.my$/;
            
            if (!email.value.trim()) {
                showError(email, errorElement, 'Email is required');
                return false;
            } else if (!emailRegex.test(email.value)) {
                showError(email, errorElement, 'Please enter a valid email address');
                return false;
            } else if (!mmuEmailRegex.test(email.value)) {
                showError(email, errorElement, 'Not an MMU email address');
                return false;
            } else {
                hideError(email, errorElement);
                return true;
            }
        }
        
        function validatePassword() {
            const password = document.getElementById('password');
            const errorElement = document.getElementById('password-error');
    
            if (!password.value) {
                showError(password, errorElement, 'Password is required');
                return false;
            } else if (password.value.length < 8) {
                showError(password, errorElement, 'Password must be at least 8 characters long');
                return false;
            } else if (!/[A-Z]/.test(password.value)) {
                showError(password, errorElement, 'Password must contain at least one uppercase letter');
                return false;
            } else if (!/[0-9]/.test(password.value)) {
                showError(password, errorElement, 'Password must contain at least one number');
                return false;
            } else if (!/[^A-Za-z0-9]/.test(password.value)) {
                showError(password, errorElement, 'Password must contain at least one special character');
                return false;
            } else {
               hideError(password, errorElement);
               return true;
        }   

        // If the confirm password field has a value, validate it as well
        if (document.getElementById('confirm-password').value) {
            validateConfirmPassword();
        }
    }
        
        function validateConfirmPassword() {
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm-password');
            const errorElement = document.getElementById('confirm-password-error');
            
            if (!confirmPassword.value) {
                showError(confirmPassword, errorElement, 'Please confirm your password');
                return false;
            } else if (confirmPassword.value !== password.value) {
                showError(confirmPassword, errorElement, 'Passwords do not match');
                return false;
            } else {
                hideError(confirmPassword, errorElement);
                return true;
            }
        }
        
        function validateRole() {
            const errorElement = document.getElementById('role-error');
            
            if (!selectedRole) {
                errorElement.classList.add('visible');
                return false;
            } else {
                errorElement.classList.remove('visible');
                return true;
            }
        }
        
        function showError(inputElement, errorElement, message) {
            inputElement.classList.add('error');
            errorElement.textContent = message;
            errorElement.classList.add('visible');
        }
        
        function hideError(inputElement, errorElement) {
            inputElement.classList.remove('error');
            errorElement.classList.remove('visible');
        }
        
        // Form submission handling
        document.getElementById('registration-form').addEventListener('submit', function(e) {
            // Client-side validation before form submission
            const isUsernameValid = validateUsername();
            const isEmailValid = validateEmail();
            const isPasswordValid = validatePassword();
            const isConfirmPasswordValid = validateConfirmPassword();
            const isFirstNameValid = validateFirstName();
            const isLastNameValid = validateLastName();
            const isRoleSelected = validateRole();
            
            // Prevent form submission if client-side validation fails
            if (!(isUsernameValid && isEmailValid && isPasswordValid && isConfirmPasswordValid && 
                  isFirstNameValid && isLastNameValid && isRoleSelected)) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
