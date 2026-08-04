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
    <title>Privacy Policy - Tech Shark</title>
    <link rel="icon" type="image/png" href="assets/logo.png"/>
    <link rel="stylesheet" href="includes/css/customer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8fafc; }
        .legal-header { 
            background: #ffffff; 
            padding: 60px 20px; 
            text-align: center; 
            border-bottom: 1px solid #e2e8f0;
        }
        .legal-header h1 { 
            font-size: 2.5rem; 
            color: #0f172a; 
            margin-bottom: 15px; 
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        .last-updated { 
            display: inline-block;
            background: #f1f5f9;
            padding: 5px 15px;
            border-radius: 20px;
            color: #64748b; 
            font-size: 0.9rem;
            font-weight: 500;
        }
        .legal-container { 
            max-width: 1100px; 
            margin: 40px auto; 
            display: flex; 
            gap: 40px; 
            padding: 0 20px; 
        }
        .legal-sidebar { 
            width: 250px; 
            flex-shrink: 0; 
        }
        .legal-sidebar-nav {
            position: sticky;
            top: 20px;
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .legal-sidebar-nav h3 {
            font-size: 1.1rem;
            color: #0f172a;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        .legal-sidebar-nav ul { 
            list-style: none; 
            padding: 0; 
            margin: 0; 
        }
        .legal-sidebar-nav li { 
            margin-bottom: 12px; 
        }
        .legal-sidebar-nav a { 
            color: #64748b; 
            text-decoration: none; 
            font-size: 0.95rem;
            transition: color 0.2s;
            display: block;
        }
        .legal-sidebar-nav a:hover, .legal-sidebar-nav a.active { 
            color: var(--primary-color); 
            font-weight: 600;
        }
        .legal-content { 
            flex: 1; 
            background: #ffffff;
            padding: 40px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            color: #334155; 
            line-height: 1.8; 
            font-size: 1.05rem; 
        }
        .legal-content h2 { 
            color: #0f172a; 
            margin-top: 40px; 
            margin-bottom: 20px; 
            font-size: 1.5rem; 
            padding-bottom: 10px;
            border-bottom: 1px solid #f1f5f9;
        }
        .legal-content h2:first-child {
            margin-top: 0;
        }
        .legal-content p { margin-bottom: 20px; }
        .legal-content ul { 
            padding-left: 20px; 
            margin-bottom: 20px; 
        }
        .legal-content li { 
            margin-bottom: 10px; 
        }
        .highlight-box {
            background: #f8fafc;
            border-left: 4px solid var(--primary-color);
            padding: 20px;
            margin: 30px 0;
            border-radius: 0 8px 8px 0;
        }
        @media (max-width: 768px) {
            .legal-container { flex-direction: column; }
            .legal-sidebar { width: 100%; }
            .legal-sidebar-nav { position: static; }
            .legal-content { padding: 20px; }
        }
    </style>
</head>
<body>
    <?php include 'includes/customer_header.php'; ?>

    <div class="legal-header">
        <div class="container">
            <h1>Privacy Policy</h1>
            <div class="last-updated"><i class="far fa-clock"></i> Effective Date: May 8, 2026</div>
        </div>
    </div>

    <div class="legal-container">
        <!-- Sidebar Navigation -->
        <aside class="legal-sidebar">
            <nav class="legal-sidebar-nav">
                <h3>Table of Contents</h3>
                <ul>
                    <li><a href="#introduction">1. Introduction</a></li>
                    <li><a href="#information-we-collect">2. Information We Collect</a></li>
                    <li><a href="#how-we-use">3. How We Use Information</a></li>
                    <li><a href="#data-protection">4. Data Protection</a></li>
                    <li><a href="#third-party">5. Third-Party Sharing</a></li>
                    <li><a href="#your-rights">6. Your Rights</a></li>
                    <li><a href="#contact">7. Contact Us</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="legal-content">
            <section id="introduction">
                <h2>1. Introduction</h2>
                <p>At Tech Shark, we are deeply committed to protecting your privacy and ensuring the security of your personal information. This Privacy Policy outlines our practices regarding data collection, usage, and protection when you interact with our website, retail stores, and repair services.</p>
                <div class="highlight-box">
                    <strong>Summary:</strong> We only collect data necessary to provide you with excellent service, process your orders, and improve your experience. We do not sell your personal data.
                </div>
            </section>

            <section id="information-we-collect">
                <h2>2. Information We Collect</h2>
                <p>When you use our services, we may collect the following types of information to provide and improve our offerings:</p>
                <ul>
                    <li><strong>Personal Identification Information:</strong> Name, email address, phone number, and physical address when you register for an account, place an order, or submit a repair ticket.</li>
                    <li><strong>Payment Information:</strong> Processed securely via our payment gateway partners (such as PayHere). Tech Shark does not store or process raw credit card numbers on our local servers.</li>
                    <li><strong>Device & Usage Data:</strong> Information about how you interact with our website, including IP addresses, browser types, and pages visited, collected securely via cookies to enhance functionality.</li>
                </ul>
            </section>

            <section id="how-we-use">
                <h2>3. How We Use Your Information</h2>
                <p>The information we collect is strictly utilized for the following core business purposes:</p>
                <ul>
                    <li>To process, fulfill, and ship your hardware orders.</li>
                    <li>To manage and track your repair requests efficiently.</li>
                    <li>To communicate with you regarding order statuses, critical updates, and customer support inquiries.</li>
                    <li>To send promotional emails and newsletters (only if you have explicitly opted in).</li>
                    <li>To analyze website traffic and improve our user interface and customer experience.</li>
                </ul>
            </section>

            <section id="data-protection">
                <h2>4. Data Protection and Security</h2>
                <p>We implement robust, industry-standard security measures, including HTTPS data encryption and secure server infrastructure, to protect your personal information against unauthorized access, alteration, or destruction. We regularly audit our security protocols to ensure your data remains completely safe.</p>
            </section>

            <section id="third-party">
                <h2>5. Third-Party Sharing</h2>
                <p>We <strong>do not</strong> sell, trade, or rent your personal identification information to external marketing agencies or third parties. We may share generic aggregated demographic information not linked to any personal identification information with our business partners. We also utilize trusted third-party service providers (such as local courier services or payment processors) strictly to operate our business, provided that those parties agree to keep this information strictly confidential.</p>
            </section>

            <section id="your-rights">
                <h2>6. Your Rights</h2>
                <p>You maintain full control over your data. You have the right to access, correct, or permanently delete your personal data stored on our platform at any time. You can manage your information directly via your Tech Shark user profile or by contacting our support team.</p>
            </section>

            <section id="contact">
                <h2>7. Contact Us</h2>
                <p>If you have any questions, concerns, or requests regarding this Privacy Policy or how we handle your data, please reach out to our dedicated privacy team:</p>
                <p>
                    <strong>Email:</strong> privacy@techshark.com<br>
                    <strong>Phone:</strong> +94 11 234 5678<br>
                    <strong>Address:</strong> Tech Shark Headquarters, Colombo, Sri Lanka
                </p>
                <p>Or alternatively, submit a ticket through our <a href="contact.php" style="color:var(--primary-color); font-weight:600;">Contact Page</a>.</p>
            </section>
        </main>
    </div>

    <?php include 'includes/customer_footer.php'; ?>

    <script>
        // Smooth scrolling for sidebar links
        document.querySelectorAll('.legal-sidebar-nav a').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const targetId = this.getAttribute('href').substring(1);
                const targetElement = document.getElementById(targetId);
                
                if(targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 20,
                        behavior: 'smooth'
                    });
                    
                    // Update active state
                    document.querySelectorAll('.legal-sidebar-nav a').forEach(a => a.classList.remove('active'));
                    this.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>
