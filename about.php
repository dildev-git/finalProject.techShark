<?php
session_start();
include('includes/dbconnection.php');
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Tech Shark</title>
    <link rel="icon" type="image/png" href="assets/logo.png"/>
    <link rel="stylesheet" href="includes/css/customer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .page-header { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: white; padding: 80px 20px; text-align: center; }
        .page-header h1 { font-size: 3rem; margin-bottom: 15px; }
        .page-header p { font-size: 1.2rem; color: #94a3b8; max-width: 600px; margin: 0 auto; }
        .content-section { max-width: 900px; margin: 60px auto; padding: 0 20px; line-height: 1.8; color: #334155; }
        .content-section h2 { font-size: 2rem; color: #1e293b; margin-top: 40px; margin-bottom: 20px; }
        .content-section p { margin-bottom: 20px; font-size: 1.1rem; }
        .feature-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; margin-top: 50px; }
        .feature-card { text-align: center; padding: 40px 20px; background: #fff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; transition: transform 0.3s; }
        .feature-card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px rgba(0,0,0,0.1); }
        .feature-card i { font-size: 3rem; color: var(--primary-color); margin-bottom: 20px; }
        .feature-card h3 { font-size: 1.3rem; margin-bottom: 15px; color: #1e293b; }
    </style>
</head>
<body>
    <?php include 'includes/customer_header.php'; ?>

    <div class="page-header">
        <div class="container">
            <h1>About Tech Shark</h1>
            <p>Your ultimate destination for premium tech, expert support, and unparalleled customer experience.</p>
        </div>
    </div>

    <div class="container">
        <div class="content-section">
            <h2>Our Story</h2>
            <p>Founded in 2025, Tech Shark was born out of a passion for high-performance computing and cutting-edge technology. We recognized a gap in the market for a tech retailer that doesn't just sell boxes, but provides comprehensive guidance, expert repairs, and a genuinely curated selection of components and devices.</p>
            <p>From custom-built rigs for hardcore gamers to sleek, professional laptops for enterprise users, we've carefully selected every item in our inventory to meet our rigorous standards for quality and performance.</p>

            <div class="feature-grid">
                <div class="feature-card">
                    <i class="fas fa-microchip"></i>
                    <h3>Premium Selection</h3>
                    <p>We source only the best products from trusted, world-class manufacturers.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-tools"></i>
                    <h3>Expert Repairs</h3>
                    <p>Our certified technicians can diagnose and fix almost any hardware issue.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-headset"></i>
                    <h3>24/7 Support</h3>
                    <p>Our dedicated support team is always ready to assist you with your tech needs.</p>
                </div>
            </div>
            
            <h2>Our Mission</h2>
            <p>Our mission is simple: to empower creators, gamers, and professionals with the technology they need to push boundaries. We believe that buying tech should be an exciting, seamless, and transparent experience. Welcome to the Tech Shark family.</p>
        </div>
    </div>

    <?php include 'includes/customer_footer.php'; ?>
</body>
</html>
