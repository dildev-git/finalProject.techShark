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
    <title>FAQs - Tech Shark</title>
    <link rel="icon" type="image/png" href="assets/logo.png"/>
    <link rel="stylesheet" href="includes/css/customer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .page-header { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: white; padding: 60px 20px; text-align: center; }
        .page-header h1 { font-size: 2.5rem; margin-bottom: 10px; }
        .content-section { max-width: 800px; margin: 60px auto; padding: 0 20px; }
        .faq-item { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 20px; overflow: hidden; }
        .faq-question { padding: 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; font-weight: 600; color: #1e293b; font-size: 1.1rem; transition: background 0.2s; }
        .faq-question:hover { background: #f1f5f9; }
        .faq-answer { padding: 0 20px; max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out, padding 0.3s ease; color: #475569; line-height: 1.6; }
        .faq-item.active .faq-answer { padding: 20px; max-height: 500px; border-top: 1px solid #e2e8f0; }
        .faq-item.active .faq-question i { transform: rotate(180deg); }
        .faq-question i { transition: transform 0.3s; color: var(--primary-color); }
    </style>
</head>
<body>
    <?php include 'includes/customer_header.php'; ?>

    <div class="page-header">
        <div class="container">
            <h1>Frequently Asked Questions</h1>
            <p style="color:#94a3b8;">Find answers to our most common questions.</p>
        </div>
    </div>

    <div class="container">
        <div class="content-section">
            <div class="faq-item">
                <div class="faq-question">How long does shipping take? <i class="fas fa-chevron-down"></i></div>
                <div class="faq-answer">Standard shipping typically takes 3-5 business days within the country. Express shipping options are available at checkout which can reduce delivery time to 1-2 business days.</div>
            </div>
            <div class="faq-item">
                <div class="faq-question">What is your return policy? <i class="fas fa-chevron-down"></i></div>
                <div class="faq-answer">We offer a 30-day money-back guarantee on all unopened and unused products. For defective items, we provide a full refund or exchange within 14 days of purchase. Please refer to our Terms of Service for complete details.</div>
            </div>
            <div class="faq-item">
                <div class="faq-question">Do you offer repair services for laptops not bought from Tech Shark? <i class="fas fa-chevron-down"></i></div>
                <div class="faq-answer">Yes! Our expert repair technicians service a wide variety of brands and devices, regardless of where they were originally purchased. You can easily submit a repair request via the Repairs page.</div>
            </div>
            <div class="faq-item">
                <div class="faq-question">Is my payment information secure? <i class="fas fa-chevron-down"></i></div>
                <div class="faq-answer">Absolutely. We use industry-standard encryption and partner with trusted payment gateways (like PayHere) to ensure your credit card and personal data are handled securely and are never stored on our servers.</div>
            </div>
            <div class="faq-item">
                <div class="faq-question">How do I track my order? <i class="fas fa-chevron-down"></i></div>
                <div class="faq-answer">Once your order is processed, you can view the live status in your Profile under the "Orders" tab. You will also receive real-time notifications via our website whenever your order status changes.</div>
            </div>
        </div>
    </div>

    <?php include 'includes/customer_footer.php'; ?>

    <script>
        document.querySelectorAll('.faq-question').forEach(item => {
            item.addEventListener('click', () => {
                const parent = item.parentElement;
                const isActive = parent.classList.contains('active');
                
                // Close all
                document.querySelectorAll('.faq-item').forEach(faq => faq.classList.remove('active'));
                
                // Open clicked
                if (!isActive) {
                    parent.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>
