<style>
    /* Color Variables (if not defined elsewhere) */
    :root {
        --primary-color: #2B3990;     /* Deep Blue */
        --secondary-color: #00AEEF;   /* Light Blue */
        --accent-color: #C4D600;      /* Yellow-Green */
        --light-color: #F8F9FA;       /* Light Grey */
        --dark-color: #333333;        /* Dark Grey */
        --white: #FFFFFF;             /* White */
    }

    /* Container Styles (if not defined elsewhere) */
    .container {
        width: 90%;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 15px;
    }

    /* Footer Styles */
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
        padding: 0;
    }
    
    .footer-links li {
        margin-bottom: 10px;
    }
    
    .footer-links a {
        color: var(--light-color);
        transition: color 0.3s ease;
        text-decoration: none;
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

    /* Responsive Footer */
    @media (max-width: 768px) {
        .footer-content {
            grid-template-columns: 1fr;
        }
        
        .social-links {
            justify-content: center;
        }
    }
</style>

<!-- Font Awesome (if not included elsewhere) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-about">
                <div class="footer-logo">
                    <img src="peerlearn-logo.png" alt="PeerLearn Logo">
                </div>
                <p>A platform connecting students with tutors to create effective learning experiences.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            
            <div class="footer-links">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="#">Home</a></li>
                    <li><a href="#">Find a Tutor</a></li>
                    <li><a href="#">Become a Tutor</a></li>
                    <li><a href="#">Learning Community</a></li>
                    <li><a href="#">About Us</a></li>
                </ul>
            </div>
            
            <div class="footer-links">
                <h4>Legal Information</h4>
                <ul>
                    <li><a href="#">Terms of Use</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Cookie Policy</a></li>
                </ul>
            </div>
            
            <div class="footer-contact">
                <h4>Contact Us</h4>
                <p><i class="fas fa-envelope"></i> info@peerlearn.com</p>
                <p><i class="fas fa-phone"></i> +123 456 7890</p>
                <p><i class="fas fa-map-marker-alt"></i> Address Information</p>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2025 PeerLearn. All rights reserved.</p>
        </div>
    </div>
</footer>
    