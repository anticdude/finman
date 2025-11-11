<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinMan - Personal Finance Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #6b63ff;
            --light: #f8f9fa;
            --dark: #212529;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --gray: #6c757d;
            --light-gray: #e9ecef;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7fb;
            color: var(--dark);
            line-height: 1.6;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Styles */
        header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 1rem 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
        }

        .logo i {
            margin-right: 10px;
        }

        .nav-links {
            display: flex;
            list-style: none;
        }

        .nav-links li {
            margin-left: 1.5rem;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 0.5rem 0;
            position: relative;
        }

        .nav-links a:hover {
            color: rgba(255, 255, 255, 0.8);
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background-color: white;
            transition: width 0.3s ease;
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .auth-buttons {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.6rem 1.2rem;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            font-size: 0.9rem;
        }

        .btn-outline {
            background: transparent;
            border: 2px solid white;
            color: white;
        }

        .btn-outline:hover {
            background: white;
            color: var(--primary);
        }

        .btn-primary {
            background: white;
            color: var(--primary);
        }

        .btn-primary:hover {
            background: rgba(255, 255, 255, 0.9);
        }

        /* Hero Section */
        .hero {
            padding: 5rem 0;
            background: linear-gradient(135deg, #f5f7fb 0%, #e4e8f5 100%);
            text-align: center;
        }

        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--dark);
        }

        .hero p {
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto 2rem;
            color: var(--gray);
        }

        .hero-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn-large {
            padding: 0.8rem 2rem;
            font-size: 1rem;
        }

        /* Features Section */
        .features {
            padding: 5rem 0;
            background-color: white;
        }

        .section-title {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-title h2 {
            font-size: 2.2rem;
            color: var(--dark);
            margin-bottom: 1rem;
        }

        .section-title p {
            color: var(--gray);
            max-width: 600px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-align: center;
            border-top: 4px solid var(--primary);
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .feature-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: white;
            font-size: 1.8rem;
        }

        .feature-card h3 {
            font-size: 1.4rem;
            margin-bottom: 1rem;
            color: var(--dark);
        }

        .feature-card p {
            color: var(--gray);
        }

        /* Dashboard Preview */
        .dashboard-preview {
            padding: 5rem 0;
            background: linear-gradient(135deg, #f5f7fb 0%, #e4e8f5 100%);
        }

        .dashboard-container {
            display: flex;
            align-items: center;
            gap: 3rem;
        }

        .dashboard-content {
            flex: 1;
        }

        .dashboard-content h2 {
            font-size: 2.2rem;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .dashboard-content p {
            color: var(--gray);
            margin-bottom: 1.5rem;
        }

        .dashboard-image {
            flex: 1;
            background: white;
            border-radius: 10px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .dashboard-image img {
            width: 100%;
            height: auto;
            display: block;
        }

        /* Testimonials */
        .testimonials {
            padding: 5rem 0;
            background-color: white;
        }

        .testimonial-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .testimonial-card {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border-left: 4px solid var(--primary);
        }

        .testimonial-text {
            font-style: italic;
            margin-bottom: 1.5rem;
            color: var(--gray);
        }

        .testimonial-author {
            display: flex;
            align-items: center;
        }

        .author-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--light-gray);
            margin-right: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-weight: bold;
        }

        .author-info h4 {
            margin-bottom: 0.2rem;
            color: var(--dark);
        }

        .author-info p {
            color: var(--gray);
            font-size: 0.9rem;
        }

        /* CTA Section */
        .cta {
            padding: 5rem 0;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            text-align: center;
        }

        .cta h2 {
            font-size: 2.2rem;
            margin-bottom: 1rem;
        }

        .cta p {
            max-width: 600px;
            margin: 0 auto 2rem;
            font-size: 1.1rem;
        }

        /* Footer */
        footer {
            background: var(--dark);
            color: white;
            padding: 4rem 0 2rem;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .footer-column h3 {
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 0.5rem;
        }

        .footer-column h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 2px;
            background: var(--primary);
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 0.8rem;
        }

        .footer-links a {
            color: #adb5bd;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: white;
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            color: white;
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            background: var(--primary);
            transform: translateY(-3px);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #adb5bd;
            font-size: 0.9rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-links {
                margin-top: 1rem;
            }

            .hero h1 {
                font-size: 2.2rem;
            }

            .dashboard-container {
                flex-direction: column;
            }

            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <nav class="navbar">
                <div class="logo">
                    <i class="fas fa-chart-line"></i>
                    FinMan
                </div>
                <ul class="nav-links">
                    <li><a href="#">Home</a></li>
                    <li><a href="#">Features</a></li>
                    <li><a href="#">Pricing</a></li>
                    <li><a href="#">About</a></li>
                    <li><a href="#">Contact</a></li>
                </ul>
                <div class="auth-buttons">
                    <a href="finboard/index.php" class="btn btn-outline">Login</a>
                    <!-- <button class="btn btn-primary">Sign Up</button> -->
                </div>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>Take Control of Your Financial Future</h1>
            <p>FinMan helps you manage expenses, track income, monitor investments, and achieve your financial goals with ease.</p>
            <div class="hero-buttons">
                <button class="btn btn-primary btn-large">Get Started Free</button>
                <!-- <button class="btn btn-outline btn-large">Watch Demo</button> -->
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <div class="section-title">
                <h2>Powerful Financial Management Features</h2>
                <p>Everything you need to manage your finances effectively in one place</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <h3>Monthly Expense Tracking</h3>
                    <p>Monitor your spending habits, categorize expenses, and identify areas where you can save money.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                    <h3>Monthly Income Tracking</h3>
                    <p>Keep track of all your income sources and visualize your earnings with detailed reports.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3>EMI & Loan Tracking</h3>
                    <p>Manage all your loans and EMIs in one place with payment reminders and payoff projections.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Multi-User Family Tracking</h3>
                    <p>Collaborate with family members to manage shared expenses and financial goals together.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <h3>Credit Card Management</h3>
                    <p>Track credit card spending, due dates, rewards, and manage multiple cards efficiently.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <h3>Financial Reports</h3>
                    <p>Generate comprehensive financial reports with visual charts to understand your financial health.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Dashboard Preview -->
    <section class="dashboard-preview">
        <div class="container">
            <div class="dashboard-container">
                <div class="dashboard-content">
                    <h2>Intuitive Dashboard for Complete Financial Overview</h2>
                    <p>Our clean and user-friendly dashboard gives you a complete picture of your financial health at a glance. Track your net worth, monthly budget, upcoming bills, and investment performance all in one place.</p>
                    <p>Customizable widgets allow you to prioritize the information that matters most to you.</p>
                    <!-- <button class="btn btn-primary btn-large">Explore Dashboard</button> -->
                </div>
                <div class="dashboard-image">
                    <!-- Placeholder for dashboard image -->
                    <div style="background: linear-gradient(135deg, #4361ee, #6b63ff); height: 300px; display: flex; align-items: center; justify-content: center; color: white;">
                        <i class="fas fa-chart-bar" style="font-size: 4rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <!-- <section class="testimonials">
        <div class="container">
            <div class="section-title">
                <h2>What Our Users Say</h2>
                <p>Join thousands of satisfied users who have transformed their financial lives with FinMan</p>
            </div>
            <div class="testimonial-grid">
                <div class="testimonial-card">
                    <div class="testimonial-text">
                        "FinMan helped me save over $5,000 in the first year by identifying unnecessary subscriptions and spending patterns I wasn't aware of."
                    </div>
                    <div class="testimonial-author">
                        <div class="author-avatar">JS</div>
                        <div class="author-info">
                            <h4>Jennifer Smith</h4>
                            <p>Marketing Manager</p>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <div class="testimonial-text">
                        "The family tracking feature has been a game-changer for my wife and me. We can now coordinate our spending and saving goals seamlessly."
                    </div>
                    <div class="testimonial-author">
                        <div class="author-avatar">MR</div>
                        <div class="author-info">
                            <h4>Michael Rodriguez</h4>
                            <p>Software Engineer</p>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <div class="testimonial-text">
                        "As someone with multiple credit cards, the credit card tracking feature has saved me from late fees and helped me maximize my rewards."
                    </div>
                    <div class="testimonial-author">
                        <div class="author-avatar">SP</div>
                        <div class="author-info">
                            <h4>Sarah Patel</h4>
                            <p>Financial Analyst</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section> -->

    <!-- CTA Section
    <section class="cta">
        <div class="container">
            <h2>Start Your Financial Journey Today</h2>
            <p>Join over 100,000 users who are taking control of their finances with FinMan. It's free to get started!</p>
            <button class="btn btn-primary btn-large">Create Your Free Account</button>
        </div>
    </section> -->

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h3>FinMan</h3>
                    <p>Your comprehensive personal finance management solution for a secure financial future.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="footer-column">
                    <h3>Features</h3>
                    <ul class="footer-links">
                        <li><a href="#">Expense Tracking</a></li>
                        <li><a href="#">Income Management</a></li>
                        <li><a href="#">EMI Calculator</a></li>
                        <li><a href="#">Family Finance</a></li>
                        <li><a href="#">Credit Card Tracking</a></li>
                    </ul>
                </div>
                <!-- <div class="footer-column">
                    <h3>Company</h3>
                    <ul class="footer-links">
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Careers</a></li>
                        <li><a href="#">Blog</a></li>
                        <li><a href="#">Press</a></li>
                        <li><a href="#">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Support</h3>
                    <ul class="footer-links">
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">FAQs</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                        <li><a href="#">Security</a></li>
                    </ul>
                </div> -->
            </div>
            <div class="footer-bottom">
                <p>&copy; 2023 FinMan. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Simple script for button interactions
        document.addEventListener('DOMContentLoaded', function() {
            const buttons = document.querySelectorAll('.btn');
            
            buttons.forEach(button => {
                button.addEventListener('click', function() {
                    // Add a simple ripple effect
                    const ripple = document.createElement('span');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = event.clientX - rect.left - size / 2;
                    const y = event.clientY - rect.top - size / 2;
                    
                    ripple.style.width = ripple.style.height = size + 'px';
                    ripple.style.left = x + 'px';
                    ripple.style.top = y + 'px';
                    ripple.classList.add('ripple');
                    
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });
        });
    </script>
</body>
</html>