<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PeerLearn - Peer Tutoring Platform</title>
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
            --logo-gradient-blue: #00AEEF; /* Logo Gradient Start */
            --logo-gradient-green: #00A99D; /* Logo Gradient End */
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
        
        .btn-secondary {
            background-color: var(--secondary-color);
            color: var(--white);
        }
        
        .btn-secondary:hover {
            background-color: #009bd5;
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
            justify-content: space-between;/* Left-right distribution */
            align-items: center;/* Vertical center alignment */
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
        
        /* Hero Section */
        .hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 80px 0;
            background-color: var(--light-color);
            overflow: hidden;
            position: relative;
        }
        
        .hero-content {
            flex: 0 0 50%;
            z-index: 2;
        }
        
        .hero-title {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 20px;
            line-height: 1.2;
        }
        
        .hero-subtitle {
            font-size: 1.1rem;
            color: var(--dark-color);
            margin-bottom: 30px;
        }
        
        .hero-buttons {
            margin-top: 25px;
        }
        
        .hero-image {
            flex: 0 0 45%;
            position: relative;
            z-index: 1;
        }
        
        .hero-image img {
            width: 100%;
            height: auto;
            border-radius: 10px;
        }
        
        /* Geometric Decorations */
        .shape {
            position: absolute;
            z-index: 0;
        }
        
        .shape-circle {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: var(--secondary-color);
            opacity: 0.5;
            top: 20%;
            right: 10%;
        }
        
        .shape-triangle {
            width: 0;
            height: 0;
            border-left: 50px solid transparent;
            border-right: 50px solid transparent;
            border-bottom: 100px solid var(--accent-color);
            opacity: 0.3;
            bottom: 10%;
            left: 5%;
        }
        
        /* Features Section */
        .features {
            padding: 80px 0;
            background-color: var(--white);
        }
        
        .section-title {
            text-align: center;
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 50px;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }
        
        .feature-card {
            text-align: center;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
        }
        
        .feature-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--light-color);
            border-radius: 50%;
        }
        
        .feature-icon i {
            font-size: 30px;
            color: var(--primary-color);
        }
        
        .feature-title {
            font-size: 1.25rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .feature-desc {
            color: var(--dark-color);
        }
        
        /* Process Section */
        .process {
            padding: 80px 0;
            background-color: var(--light-color);
        }
        
        .process-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-top: 50px;
        }
        
        .process-step {
            flex: 0 0 18%;
            text-align: center;
            position: relative;
            z-index: 2;
        }
        
        .step-number {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 1.25rem;
            font-weight: bold;
        }
        
        .step-title {
            font-size: 1.1rem;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .step-desc {
            font-size: 0.9rem;
            color: var(--dark-color);
        }
        
        .process-line {
            position: absolute;
            top: 25px;
            left: 10%;
            width: 80%;
            height: 2px;
            background-color: var(--secondary-color);
            z-index: 1;
        }
        
        /* Community Section */
        .community {
            padding: 80px 0;
            background-color: var(--white);
        }
        
        .community-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .community-text {
            flex: 0 0 45%;
        }
        
        .community-title {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        
        .community-list {
            margin-bottom: 30px;
        }
        
        .community-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .community-item i {
            color: var(--accent-color);
            margin-right: 10px;
            font-size: 1.25rem;
        }
        
        .community-image {
            flex: 0 0 45%;
        }
        
        .community-image img {
            width: 100%;
            border-radius: 10px;
        }
        
        /* CTA Section */
        .cta {
            padding: 80px 0;
            background-color: var(--primary-color);
            text-align: center;
            color: var(--white);
        }
        
        .cta-title {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }
        
        .cta-text {
            font-size: 1.1rem;
            margin-bottom: 30px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .cta-stats {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }
        
        .cta-stat {
            margin: 0 30px;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--accent-color);
        }
        
        .stat-label {
            font-size: 0.9rem;
        }
        
        /* Footer */
        .footer {
            padding: 60px 0 30px;
            background-color: var(--dark-color);
            color: var(--white);
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .footer-logo {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .footer-logo img {
            height: 40px;
            margin-right: 10px;
        }
        
        .footer-contact p {
            margin-bottom: 10px;
        }
        
        .footer-links h4 {
            margin-bottom: 20px;
            color: var(--accent-color);
        }
        
        .footer-links ul {
            list-style: none;
        }
        
        .footer-links li {
            margin-bottom: 10px;
        }
        
        .footer-links a {
            color: var(--light-color);
            transition: color 0.3s ease;
        }
        
        .footer-links a:hover {
            color: var(--accent-color);
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        .social-links {
            display: flex;
            margin-top: 20px;
        }
        
        .social-links a {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--light-color);
            color: var(--dark-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            transition: background-color 0.3s ease;
        }
        
        .social-links a:hover {
            background-color: var(--accent-color);
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .hero {
                flex-direction: column;
                text-align: center;
                padding: 40px 0;
            }
            
            .hero-content, .hero-image {
                flex: 0 0 100%;
            }
            
            .hero-content {
                margin-bottom: 40px;
            }
            
            .process-steps {
                flex-wrap: wrap;
            }
            
            .process-step {
                flex: 0 0 45%;
                margin-bottom: 30px;
            }
            
            .process-line {
                display: none;
            }
            
            .community-content {
                flex-direction: column;
            }
            
            .community-text, .community-image {
                flex: 0 0 100%;
            }
            
            .community-text {
                margin-bottom: 40px;
            }
        }
        
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                padding: 15px 0;
            }
            
            .logo {
                margin-bottom: 15px;
            }
            
            .nav-container {
                margin-bottom: 15px;
            }
            
            .nav-menu {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .nav-item {
                margin: 5px 10px;
            }
            
            .hero-title {
                font-size: 2rem;
            }
            
            .section-title {
                font-size: 1.75rem;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
            
            .process-step {
                flex: 0 0 100%;
            }
            
            .cta-title {
                font-size: 2rem;
            }
            
            .cta-stats {
                flex-direction: column;
            }
            
            .cta-stat {
                margin:
                margin: 15px 0;
            }
        }
    </style>
    <!-- Import Font Icon Library (Font Awesome) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container">
            <div class="logo">
                <a href="#">
                    <img src="image/fyp_peerlearn_logo.png" alt="PeerLearn Logo">
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
                <a href="login.php" class="btn btn-primary">LOGIN/REGISTER</a>
            </div>
        </div>
    </nav>
</body>