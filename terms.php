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
    <title>Terms of Service - Tech Shark</title>
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
            <h1>Terms of Service</h1>
            <div class="last-updated"><i class="far fa-clock"></i> Effective Date: May 8, 2026</div>
        </div>
    </div>

    <div class="legal-container">
        <!-- Sidebar Navigation -->
        <aside class="legal-sidebar">
            <nav class="legal-sidebar-nav">
                <h3>Table of Contents</h3>
                <ul>
                    <li><a href="#acceptance">1. Acceptance of Terms</a></li>
                    <li><a href="#accounts">2. User Accounts</a></li>
                    <li><a href="#sales">3. Sales and Pricing</a></li>
                    <li><a href="#repairs">4. Repair Services</a></li>
                    <li><a href="#returns">5. Return & Refund Policy</a></li>
                    <li><a href="#liability">6. Limitation of Liability</a></li>
                    <li><a href="#changes">7. Changes to Terms</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="legal-content">
            <section id="acceptance">
                <h2>1. Acceptance of Terms</h2>
                <p>Welcome to Tech Shark. By accessing our website, purchasing our products, or utilizing our repair services, you acknowledge that you have read, understood, and agree to be legally bound by these Terms of Service. If you do not agree to these terms, you are explicitly prohibited from using our services and must discontinue use immediately.</p>
                <div class="highlight-box">
                    <strong>Note:</strong> These terms constitute a binding legal agreement between you and Tech Shark.
                </div>
            </section>

            <section id="accounts">
                <h2>2. User Accounts</h2>
                <p>To access certain features, such as placing hardware orders or submitting repair requests, you are required to register an account. You are solely responsible for maintaining the strict confidentiality of your account credentials (username and password) and for all activities that occur under your account.</p>
                <p>Tech Shark reserves the absolute right to suspend or terminate accounts that exhibit fraudulent activity, abuse our systems, or otherwise violate our community guidelines and policies.</p>
            </section>

            <section id="sales">
                <h2>3. Product Sales and Pricing</h2>
                <p>All hardware, components, and accessories listed on our website are subject to market availability. While we strive for complete accuracy, Tech Shark does not warrant that product descriptions or other content is entirely error-free.</p>
                <p>We reserve the right to modify prices, discontinue specific products, or cancel orders at our sole discretion in the event of pricing errors, suspected fraud, or inventory shortages. In the event of an order cancellation initiated by Tech Shark, a full refund will be immediately issued to the original payment method.</p>
            </section>

            <section id="repairs">
                <h2>4. Repair Services</h2>
                <p>Repair estimates provided online are strictly preliminary. Final repair costs and timelines will be conclusively determined only after a physical diagnostic is performed by our certified technicians.</p>
                <p>By submitting a device for repair, you authorize Tech Shark to perform the necessary diagnostics and repairs. <strong>Important:</strong> We are not responsible for any pre-existing data loss. Customers are strongly advised to back up all sensitive and important data before submitting devices to our technicians.</p>
            </section>

            <section id="returns">
                <h2>5. Return and Refund Policy</h2>
                <p>We accept returns for unused, unopened, and sealed hardware within 30 days of the delivery date. Defective items can be exchanged or refunded within 14 days following a technical verification by our staff.</p>
                <p>Please note that diagnostic fees, repair service labor fees, and outbound shipping costs are entirely non-refundable unless a repair failed due to the direct and proven negligence of our technicians.</p>
            </section>

            <section id="liability">
                <h2>6. Limitation of Liability</h2>
                <p>To the maximum extent permitted by applicable law, Tech Shark, its directors, employees, and affiliates shall not be liable for any indirect, incidental, special, consequential, or punitive damages resulting from your access to or use of, or inability to access or use, our services, products, or any content provided by us.</p>
            </section>

            <section id="changes">
                <h2>7. Changes to Terms</h2>
                <p>We reserve the right to review, modify, or completely update these Terms of Service at any time without prior individual notice. We will notify users of significant, material changes by posting a prominent notice on our website. Your continued use of our services after such modifications constitutes your formal acceptance of the new terms.</p>
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
