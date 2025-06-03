<?php
// stud_head.php
// Common header for student pages
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Peer Tutoring Platform'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ---------------------------------- */
        /* Global Styles */
        /* ---------------------------------- */
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --success: #27ae60;
            --danger: #e74c3c;
            --warning: #f39c12;
            --light: #ecf0f1;
            --dark: #34495e;
            --gray: #bdc3c7;
            --light-gray: #f8f9fa;
            --dark-gray: #7f8c8d;
            --yellow-hover: #FFD700;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light);
            color: var(--dark);
            line-height: 1.6;
        }

        /* ---------------------------------- */
        /* Navigation Bar */
        /* ---------------------------------- */
        .header,
        .navbar {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 1.2rem 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .logo a {
            color: #FFFFFF;
            text-decoration: none;
            transition: color 0.3s ease, text-shadow 0.3s ease;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .logo a:hover {
            color: var(--yellow-hover);
            text-shadow: 0 2px 8px rgba(255, 215, 0, 0.5);
        }

        .nav-links {
            display: flex;
            gap: 5rem;
            align-items: center;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-size: 1rem;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: background 0.3s, opacity 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-links a:hover {
            opacity: 0.8;
            background: rgba(255, 255, 255, 0.1);
        }

        .user-menu {
            position: relative;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: white;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: background 0.3s;
        }

        .user-menu:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .dropdown {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 8px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            min-width: 220px;
            z-index: 1000;
        }

        .dropdown a {
            display: block;
            padding: 0.75rem 1.5rem;
            color: var(--dark);
            text-decoration: none;
            border-bottom: 1px solid var(--light);
            transition: background 0.3s;
        }

        .dropdown a:hover {
            background-color: var(--light-gray);
        }

        /* ---------------------------------- */
        /* Main Content */
        /* ---------------------------------- */
        .main {
            max-width: 1400px;
            margin: 3rem auto;
            padding: 0 2rem;
        }

        .page-header {
            text-align: center;
            margin-bottom: 3.5rem;
        }

        .page-header h1 {
            color: var(--primary);
            font-size: 2.2rem;
            margin-bottom: 0.75rem;
        }

        .page-header p {
            color: var(--dark-gray);
            font-size: 1.1rem;
        }

        /* ---------------------------------- */
        /* Alerts */
        /* ---------------------------------- */
        .alert {
            padding: 1.2rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: opacity 0.3s;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* ---------------------------------- */
        /* Tabs */
        /* ---------------------------------- */
        .tabs {
            display: flex;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2.5rem;
            overflow: hidden;
        }

        .tab {
            flex: 1;
            padding: 1.2rem 2rem;
            background: white;
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 500;
            color: var(--dark-gray);
            transition: all 0.3s;
            border-bottom: 4px solid transparent;
        }

        .tab:hover {
            background-color: var(--light-gray);
        }

        .tab.active {
            color: var(--primary);
            border-bottom-color: var(--secondary);
            background-color: var(--light-gray);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* ---------------------------------- */
        /* Session Cards */
        /* ---------------------------------- */
        .session-list {
            display: grid;
            gap: 2rem;
        }

        .session-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 2rem;
            align-items: start;
        }

        .session-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }

        .session-tutor {
            display: flex;
            align-items: center;
            gap: 1.2rem;
        }

        .session-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: var(--secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.4rem;
            overflow: hidden;
        }

        .session-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .session-details h3 {
            color: var(--primary);
            font-size: 1.4rem;
            margin-bottom: 0.75rem;
        }

        .session-time,
        .session-course,
        .session-location {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
            color: var(--dark-gray);
            font-size: 1rem;
        }

        .session-time i,
        .session-course i,
        .session-location i {
            width: 18px;
            color: var(--secondary);
        }

        .session-actions {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        /* ---------------------------------- */
        /* Buttons */
        /* ---------------------------------- */
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .btn-primary {
            background: var(--secondary);
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-warning:hover {
            background: #e67e22;
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #229954;
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--secondary);
            color: var(--secondary);
        }

        .btn-outline:hover {
            background: var(--secondary);
            color: white;
        }

        /* ---------------------------------- */
        /* Status Badges */
        /* ---------------------------------- */
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fff3e0;
            color: #ef6c00;
        }

        .status-scheduled {
            background: #e3f2fd;
            color: #1976d2;
        }

        .status-completed {
            background: #e8f5e8;
            color: #2e7d32;
        }

        .status-cancelled {
            background: #ffebee;
            color: #c62828;
        }

        /* ---------------------------------- */
        /* Empty State */
        /* ---------------------------------- */
        .empty-state {
            text-align: center;
            padding: 4rem;
            color: var(--dark-gray);
        }

        .empty-state i {
            font-size: 4.5rem;
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }

        /* ---------------------------------- */
        /* Modals */
        /* ---------------------------------- */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 2.5rem;
            max-width: 550px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--light);
        }

        .modal-header h3 {
            color: var(--primary);
            font-size: 1.5rem;
            margin: 0;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.8rem;
            cursor: pointer;
            color: var(--dark-gray);
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .close-btn:hover {
            color: var(--danger);
        }

        .form-group {
            margin-bottom: 2rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 600;
            color: var(--dark);
            font-size: 1rem;
        }

        .form-control {
            width: 100%;
            padding: 0.9rem;
            border: 2px solid var(--light);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }

        .modal-actions {
            display: flex;
            gap: 1.5rem;
            justify-content: flex-end;
            margin-top: 2.5rem;
        }

        /* ---------------------------------- */
        /* Rating */
        /* ---------------------------------- */
        .rating {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }

        .star {
            color: #ffd700;
            font-size: 1.2rem;
        }

        .star.empty {
            color: var(--gray);
        }

        .rating-input {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 0.5rem;
            margin: 0.75rem 0;
        }

        .rating-input input[type="radio"] {
            display: none;
        }

        .star-label {
            font-size: 2.2rem;
            color: var(--gray);
            cursor: pointer;
            transition: color 0.3s;
        }

        .rating-input input[type="radio"]:checked~.star-label,
        .rating-input .star-label:hover,
        .rating-input .star-label:hover~.star-label {
            color: #ffd700;
        }

        /* ---------------------------------- */
        /* Responsive Design */
        /* ---------------------------------- */
        @media (max-width: 768px) {

            .header,
            .navbar {
                padding: 1rem;
            }

            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-links {
                flex-direction: column;
                gap: 1.5rem;
                width: 100%;
                justify-content: center;
            }

            .nav-links a {
                padding: 0.75rem;
                width: 100%;
                justify-content: center;
            }

            .user-menu {
                width: 100%;
                justify-content: center;
            }

            .dropdown {
                width: 100%;
                right: 0;
            }

            .main {
                padding: 0 1rem;
                margin: 2rem auto;
            }

            .session-card {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .session-actions {
                flex-direction: row;
                justify-content: center;
                flex-wrap: wrap;
            }

            .tabs {
                flex-direction: column;
            }

            .tab {
                padding: 1rem;
            }

            .modal-content {
                margin: 1rem;
                width: calc(100% - 2rem);
                padding: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .logo {
                font-size: 1.5rem;
            }

            .page-header h1 {
                font-size: 1.8rem;
            }

            .btn {
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
            }

            .session-avatar {
                width: 60px;
                height: 60px;
            }
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo">

                PeerLearn
            </div>
            <nav class="nav-links">
                <a href="tutor_main_page.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="tutor_requests.php"><i class="fas fa-search"></i> Appoinment</a>
                <a href="tutor_students.php"><i class="fas fa-calendar"></i> My Student</a>
                <a href="messages.php"><i class="fas fa-envelope"></i> Messages</a>
                <div class="user-menu" onclick="toggleDropdown()">
                    <i class="fas fa-user-circle"></i>
                    <?php echo htmlspecialchars(isset($_SESSION['first_name']) ? $_SESSION['first_name'] : 'User'); ?>
                    <i class="fas fa-chevron-down"></i>
                    <div class="dropdown" id="userDropdown">
                        <a href="tutor_profile.php"><i class="fas fa-user"></i> Profile</a>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </nav>
        </div>
    </header>

    <script>
        // Dropdown functionality
        function toggleDropdown() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }
    </script>

</body>

</html>