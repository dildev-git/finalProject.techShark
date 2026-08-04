<?php
session_start();
include('includes/dbconnection.php');

$msg = '';
$msg_type = '';

$is_logged_in = isset($_SESSION['user_id']) && $_SESSION['role'] === 'Customer';
$customer_id = $is_logged_in ? $_SESSION['user_id'] : null;

// Handle New Inquiry
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_inquiry']) && $is_logged_in) {
    $message = trim($_POST['message']);
    $date = date('Y-m-d H:i:s');
    
    if(!empty($message)) {
        $sql = "INSERT INTO Inquiry (message, dateSubmitted, customerID) VALUES (?, ?, ?)";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssi", $message, $date, $customer_id);
            if(mysqli_stmt_execute($stmt)) {
                $msg = "Inquiry submitted successfully. Our team will respond shortly.";
                $msg_type = "success";
            } else {
                $msg = "Error submitting inquiry.";
                $msg_type = "danger";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Fetch Inquiries if logged in
$inquiries = [];
if ($is_logged_in) {
    $sql = "SELECT * FROM Inquiry WHERE customerID = ? ORDER BY dateSubmitted DESC";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $customer_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($res)) {
            $inquiries[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Tech Shark</title>
    <link rel="icon" type="image/png" href="assets/logo.png"/>
    <link rel="stylesheet" href="includes/css/customer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .contact-page-container {
            margin: 40px auto;
        }
        .contact-hero {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            color: white;
            padding: 60px 20px;
            text-align: center;
            border-radius: 8px;
            margin-bottom: 40px;
            box-shadow: var(--shadow-md);
        }
        .contact-hero h1 {
            font-size: 36px;
            margin-bottom: 15px;
        }
        .contact-hero p {
            font-size: 18px;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }
        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: start;
        }
        @media (max-width: 768px) {
            .contact-grid {
                grid-template-columns: 1fr;
            }
        }
        .contact-info-card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 20px;
        }
        .contact-info-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 25px;
        }
        .contact-info-icon {
            background: #eff6ff;
            color: var(--primary-color);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-right: 20px;
            flex-shrink: 0;
        }
        .contact-info-text h3 {
            margin: 0 0 5px 0;
            font-size: 18px;
            color: var(--text-color);
        }
        .contact-info-text p {
            margin: 0;
            color: var(--text-light);
            line-height: 1.6;
        }
        .map-container {
            width: 100%;
            height: 250px;
            border-radius: 8px;
            overflow: hidden;
            margin-top: 20px;
        }
        .inquiry-form-section {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 100px;
        }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #10b981; padding: 15px; border-radius: 4px; margin-bottom: 20px;}
        .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #ef4444; padding: 15px; border-radius: 4px; margin-bottom: 20px;}
        .alert-info { background: #eff6ff; color: #1e40af; border: 1px solid #3b82f6; padding: 15px; border-radius: 4px; margin-bottom: 20px;}
        .inq-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; border-left: 4px solid var(--primary-color); }
        .inq-resolved { border-left-color: var(--secondary-color); }
        .inq-header { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 13px; color: #64748b; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px; }
        .inq-msg { font-size: 14px; color: #334155; line-height: 1.6; margin-bottom: 0; }
        .inq-reply { font-size: 14px; color: #334155; line-height: 1.6; }
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-Pending { background: #fef3c7; color: #92400e; }
        .status-Resolved { background: #d1fae5; color: #065f46; }
        
        .history-scroll-container {
            max-height: 450px;
            overflow-y: auto;
            padding-right: 10px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        #support-history {
            scroll-margin-top: 150px; /* Change this amount (100px) according to the height of the header above. */
        }

        /* Layout for Left/Right split inside the card */
        .inq-body { display: flex; gap: 20px; align-items: stretch; }
        .inq-msg-col { flex: 1; max-height: 120px; overflow-y: auto; padding-right: 15px; }
        .inq-reply-col { flex: 1; max-height: 120px; overflow-y: auto; padding-left: 15px; border-left: 1px solid #e2e8f0; }

        /* Shared scrollbar styling */
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body>
    <?php include 'includes/customer_header.php'; ?>

    <div class="container contact-page-container">
        <div class="contact-hero">
            <h1>Get in Touch</h1>
            <p>We're here to help! Whether you have a question about our products, need repair support, or want to check on an order, our team is ready to assist you.</p>
        </div>
        
        <div class="contact-grid">
            <!-- Left Column: Contact Details -->
            <div class="contact-details-section">
                <div class="contact-info-card">
                    <div class="contact-info-item">
                        <div class="contact-info-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div class="contact-info-text">
                            <h3>Visit Our Store</h3>
                            <p>Tech Shark Super Center<br>12/3 Tech Avenue, Kandy Road, Kurunegala<br>Sri Lanka</p>
                        </div>
                    </div>
                    
                    <div class="contact-info-item">
                        <div class="contact-info-icon"><i class="fas fa-phone"></i></div>
                        <div class="contact-info-text">
                            <h3>Call Us</h3>
                            <p>Support: +94 37 234 2340<br>Sales: +94 37 234 2341</p>
                        </div>
                    </div>
                    
                    <div class="contact-info-item">
                        <div class="contact-info-icon"><i class="fas fa-envelope"></i></div>
                        <div class="contact-info-text">
                            <h3>Email Us</h3>
                            <p>support@techshark.com<br>sales@techshark.com</p>
                        </div>
                    </div>
                    
                    <div class="contact-info-item">
                        <div class="contact-info-icon"><i class="fas fa-clock"></i></div>
                        <div class="contact-info-text">
                            <h3>Business Hours</h3>
                            <p>Monday - Friday: 9:00 AM - 10:00 PM<br>Saturday: 8:00 AM - 6:00 PM<br>Sunday: Closed</p>
                        </div>
                    </div>

                    <div class="map-container">
                        <!-- Simple iframe map pointing to Colombo -->
                        <div class="map-container">
                            <iframe src="https://maps.google.com/maps?q=Kurunegala,%20Sri%20Lanka&t=&z=14&ie=UTF8&iwloc=&output=embed" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Inquiry Form -->
            <div class="inquiry-form-section">
                <h3>Send a Support Inquiry</h3>
                <p style="color:var(--text-light); margin-bottom:20px; font-size:14px;">Log in to send a secure message directly to our support team.</p>
                
                <?php if($msg): ?>
                    <div class="alert-<?php echo $msg_type; ?>"><?php echo $msg; ?></div>
                <?php endif; ?>

                <?php if ($is_logged_in): ?>
                    <form method="POST" action="contact.php">
                        <div style="margin-bottom: 20px;">
                            <label style="display:block; margin-bottom:8px; font-weight:500;">Your Message</label>
                            <textarea name="message" rows="6" required style="width:100%; padding:10px; border:1px solid var(--medium-gray); border-radius:4px; box-sizing:border-box;" placeholder="Type your question or issue here..."></textarea>
                        </div>
                        <button type="submit" name="submit_inquiry" class="btn" style="width:100%;">Submit Inquiry</button>
                    </form>
                <?php else: ?>
                    <div class="alert-info" style="text-align: center; padding: 30px 20px;">
                        <i class="fas fa-lock" style="font-size: 32px; color: #3b82f6; margin-bottom: 15px;"></i>
                        <h4 style="margin: 0 0 10px 0;">Login Required</h4>
                        <p style="margin-bottom: 20px;">You must be logged in to submit a support ticket so we can securely track your request.</p>
                        <a href="login.php" class="btn">Login to your account</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Past Inquiries Section (Logged in only) -->
        <?php if ($is_logged_in): ?>
        <div id="support-history" class="past-inquiries-section" style="margin-top: 60px;">
            <h2 class="section-title">My Support History</h2>
            
            <?php if(empty($inquiries)): ?>
                <div style="text-align:center; padding:50px; background:white; border-radius:8px; box-shadow: var(--shadow-sm);">
                    <i class="fas fa-envelope-open-text" style="font-size:48px; color:var(--medium-gray); margin-bottom:20px;"></i>
                    <h3>No past inquiries.</h3>
                    <p style="color: var(--text-light);">When you submit an inquiry, you can track the responses here.</p>
                </div>
            <?php else: ?>
                <div class="history-scroll-container custom-scrollbar">
                    <?php foreach($inquiries as $inq): ?>
                        <div class="inq-card <?php echo $inq['status'] == 'Resolved' ? 'inq-resolved' : ''; ?>" style="margin-bottom: 0;">
                            <div class="inq-header">
                                <span><i class="far fa-clock"></i> <?php echo date('M d, Y g:i A', strtotime($inq['dateSubmitted'])); ?></span>
                                <span class="status-badge status-<?php echo str_replace(' ', '-', $inq['status']); ?>"><?php echo htmlspecialchars($inq['status']); ?></span>
                            </div>
                            
                            <div class="inq-body">
                                <!-- Left side: Customer Inquiry -->
                                <div class="inq-msg-col custom-scrollbar">
                                    <strong style="display:block; margin-bottom:8px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #94a3b8;"><i class="fas fa-user"></i> Your Inquiry</strong>
                                    <div class="inq-msg">
                                        <?php echo nl2br(htmlspecialchars($inq['message'])); ?>
                                    </div>
                                </div>
                                
                                <!-- Right side: Support Reply -->
                                <div class="inq-reply-col custom-scrollbar">
                                    <?php if($inq['response']): ?>
                                        <div class="inq-reply" style="height: 100%; box-sizing: border-box; margin: 0;">
                                            <strong style="display:block; margin-bottom:8px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #10b981;"><i class="fas fa-headset"></i> Support Reply</strong>
                                            <?php echo nl2br(htmlspecialchars($inq['response'])); ?>
                                        </div>
                                    <?php else: ?>
                                        <div style="font-size:13px; color:#94a3b8; font-style:italic; height: 100%; display: flex; align-items: center; justify-content: center;">
                                            Awaiting response from our team...
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>

    <?php include 'includes/customer_footer.php'; ?>
</body>
</html>
