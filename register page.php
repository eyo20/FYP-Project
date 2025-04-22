<?php
require_once 'db_connection.php';
$errors = [];
$username = $email = $role = '';
$firstName = $lastName = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');

    if (empty($username)) $errors['username'] = 'Username is required.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Valid email is required.';
    if (empty($password) || strlen($password) < 6) $errors['password'] = 'Password must be at least 6 characters.';
    if ($password !== $confirmPassword) $errors['confirm_password'] = 'Passwords do not match.';
    if (empty($role)) $errors['role'] = 'Please select a role.';
    if (empty($firstName)) $errors['first_name'] = 'First name is required.';
    if (empty($lastName)) $errors['last_name'] = 'Last name is required.';

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT user_id FROM User WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $errors['email'] = 'Email is already registered.';
        } else {
            $stmt = $conn->prepare("INSERT INTO User (username, email, password, role, first_name, last_name) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $username, $email, $password, $role, $firstName, $lastName);
            
            if ($stmt->execute()) {
                $userId = $conn->insert_id;
                
                if ($role === 'student') {
                    $stmt = $conn->prepare("INSERT INTO StudentProfile (user_id) VALUES (?)");
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                } else if ($role === 'tutor') {
                    $stmt = $conn->prepare("INSERT INTO TutorProfile (user_id, hourly_rate) VALUES (?, 0)");
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                }
                
                header("Location: login.php");
                exit;
            } else {
                $errors['general'] = 'Registration failed. Please try again.';
            }
        }
        $stmt->close();
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PeerTutors - Register</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #5C6BC0;
            --background: #F4F6F8;
            --white: #fff;
            --danger: #e74c3c;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--background);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .register-container {
            background: var(--white);
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        .register-container h2 {
            margin-bottom: 24px;
            color: var(--primary);
            text-align: center;
        }
        .input-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
        }
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            outline: none;
            font-size: 14px;
        }
        .role-selection {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .role-option {
            flex: 1;
            margin: 0 5px;
            padding: 12px;
            border: 2px solid #ccc;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .role-option.selected {
            border-color: var(--primary);
            background: var(--primary);
            color: white;
        }
        .error {
            color: var(--danger);
            font-size: 13px;
            margin-top: 4px;
        }
        .submit-btn {
            width: 100%;
            background: var(--primary);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
        }
        .login-link {
            margin-top: 20px;
            text-align: center;
            font-size: 14px;
        }
        .login-link a {
            color: var(--primary);
            text-decoration: none;
        }
        .error-box {
            color: var(--danger);
            text-align: center;
            margin-bottom: 15px;
        }
        .name-row {
            display: flex;
            gap: 10px;
        }
        .name-row .input-group {
            flex: 1;
        }
    </style>
</head>
<body>
<div class="register-container">
    <h2>Create Account</h2>
    <?php if (!empty($errors['general'])): ?>
        <div class="error-box"><?= htmlspecialchars($errors['general']) ?></div>
    <?php endif; ?>
    <form id="registration-form" action="registration.php" method="POST" novalidate>
        <div class="input-group">
            <label for="username">Username</label>
            <input type="text" name="username" id="username" value="<?= htmlspecialchars($username) ?>">
            <?php if (!empty($errors['username'])): ?><div class="error"><?= $errors['username'] ?></div><?php endif; ?>
        </div>
        
        <div class="name-row">
            <div class="input-group">
                <label for="first_name">First Name</label>
                <input type="text" name="first_name" id="first_name" value="<?= htmlspecialchars($firstName) ?>">
                <?php if (!empty($errors['first_name'])): ?><div class="error"><?= $errors['first_name'] ?></div><?php endif; ?>
            </div>
            <div class="input-group">
                <label for="last_name">Last Name</label>
                <input type="text" name="last_name" id="last_name" value="<?= htmlspecialchars($lastName) ?>">
                <?php if (!empty($errors['last_name'])): ?><div class="error"><?= $errors['last_name'] ?></div><?php endif; ?>
            </div>
        </div>
        
        <div class="input-group">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" value="<?= htmlspecialchars($email) ?>">
            <?php if (!empty($errors['email'])): ?><div class="error"><?= $errors['email'] ?></div><?php endif; ?>
        </div>
        <div class="input-group">
            <label for="password">Password</label>
            <input type="password" name="password" id="password">
            <?php if (!empty($errors['password'])): ?><div class="error"><?= $errors['password'] ?></div><?php endif; ?>
        </div>
        <div class="input-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" name="confirm_password" id="confirm_password">
            <?php if (!empty($errors['confirm_password'])): ?><div class="error"><?= $errors['confirm_password'] ?></div><?php endif; ?>
        </div>
        <label>Select Role</label>
        <div class="role-selection">
            <div class="role-option" id="role-student" onclick="selectRole('student')">Student</div>
            <div class="role-option" id="role-tutor" onclick="selectRole('tutor')">Tutor</div>
        </div>
        <div class="error" id="role-error" style="display:none;">Please select a role.</div>
        <input type="hidden" name="role" id="role-input" value="<?= htmlspecialchars($role) ?>">
        <button type="submit" class="submit-btn">Register</button>
    </form>
    <div class="login-link">
        Already have an account? <a href="login.php">Login here</a>
    </div>
</div>
<script>
    let selectedRole = "<?= htmlspecialchars($role) ?>";
    if (selectedRole) {
        document.getElementById('role-' + selectedRole).classList.add('selected');
        document.getElementById('role-input').value = selectedRole;
    }
    function selectRole(role) {
        document.querySelectorAll('.role-option').forEach(option => {
            option.classList.remove('selected');
        });
        document.getElementById('role-' + role).classList.add('selected');
        selectedRole = role;
        document.getElementById('role-input').value = role;
        document.getElementById('role-error').style.display = 'none';
    }
    document.getElementById('registration-form').addEventListener('submit', function (e) {
        if (!selectedRole) {
            e.preventDefault();
            document.getElementById('role-error').style.display = 'block';
        }
    });
</script>
</body>
</html>