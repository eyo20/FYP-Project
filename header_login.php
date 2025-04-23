<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - PeerLearn' : 'PeerLearn - Peer Tutoring Platform'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
        }
        
        a {
            text-decoration: none;
            color: inherit;
        }
        
        /* Color Variables */
        :root {
            --primary-color: #2B3990;     /* Deep Blue */
            --secondary-color: #00AEEF;   /* Light Blue */
            --accent-color: #C4D600;      /* Yellow-Green */
            --light-color: #F8F9FA;       /* Light Grey */
            --dark-color: #333333;        /* Dark Grey */
            --white: #FFFFFF;             /* White */
        }
        
        /* Container Styles */
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        /* Button Styles */
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 30px;
            font-weight: bold;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background-color: var(--accent-color);
            color: var(--dark-color);
        }
        
        .btn-primary:hover {
            background-color: #b1c100;
        }
        
        /* Navigation Bar */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            background-color: var(--white);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .logo {
            display: flex;
            align-items: center;
        }
        
        .logo img {
            height: 70px;
        }
        
        .navbar > .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-grow: 1;
        }
        
        .nav-menu {
            display: flex;
            justify-content: center;
            list-style: none;
        }
        
        .nav-item {
            margin: 0 15px;
        }
        
        .nav-link {
            color: var(--dark-color);
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .nav-link:hover {
            color: var(--primary-color);
        }
        
        .nav-buttons {
            display: flex;
            align-items: center;
        }
        
        .nav-buttons .btn {
            margin-left: 15px;
        }
        
        /* Page Header */
        .page-header {
            background-color: var(--light-color);
            padding: 40px 0;
            text-align: center;
        }
        
        .page-title {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                padding: 15px 0;
            }
            
            .logo {
                margin-bottom: 15px;
            }
            
            .nav-menu {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .nav-item {
                margin: 5px 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container">
            <div class="logo">
                <a href="index.php">
                    <img src="fyp_peerlearn_logo.png" alt="PeerLearn Logo">
                </a>
            </div>
            
            <div class="nav-container">
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="#" class="nav-link">FOR STUDENTS</a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">FOR TUTORS</a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">LEARNING COMMUNITY</a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">ABOUT US</a>
                    </li>
                </ul>
            </div>
            
            <div class="nav-buttons">
                <a href="#" class="btn btn-primary">LOGIN/REGISTER</a>
            </div>
        </div>
    </nav>
    
    <?php if (isset($pageTitle)): ?>
    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <h1 class="page-title"><?php echo $pageTitle; ?></h1>
        </div>
    </div>
    <?php endif; ?>
