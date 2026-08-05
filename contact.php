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
        /* ── Support History ── */
        #support-history { scroll-margin-top: 150px; }

        .history-wrap {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .inq-thread {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            overflow: hidden;
            border: 1px solid #e8edf3;
            transition: box-shadow 0.2s ease;
        }
        .inq-thread:hover { box-shadow: 0 6px 24px rgba(0,0,0,0.10); }

        /* Top meta bar */
        .inq-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 20px;
            background: #f8fafc;
            border-bottom: 1px solid #e8edf3;
            font-size: 13px;
            color: #64748b;
        }
        .inq-meta-left { display: flex; align-items: center; gap: 10px; }
        .inq-id-badge {
            background: var(--primary-color);
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 20px;
            letter-spacing: 0.5px;
        }
        .inq-date { display: flex; align-items: center; gap: 5px; }

        /* Status pill */
        .inq-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .inq-status.pending  { background: #fef3c7; color: #92400e; }
        .inq-status.resolved { background: #d1fae5; color: #065f46; }

        /* Message + Reply columns */
        .inq-cols {
            display: grid;
            grid-template-columns: 1fr 1fr;
        }
        @media (max-width: 640px) { .inq-cols { grid-template-columns: 1fr; } }

        .inq-col {
            padding: 18px 22px;
        }
        .inq-col + .inq-col {
            border-left: 1px solid #e8edf3;
        }
        .inq-col-label {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            margin-bottom: 10px;
        }
        .inq-col-label.customer { color: var(--primary-color); }
        .inq-col-label.support  { color: #10b981; }

        .inq-bubble {
            font-size: 14px;
            line-height: 1.7;
            color: #374151;
            max-height: 110px;
            overflow-y: auto;
            padding-right: 4px;
        }
        .inq-bubble::-webkit-scrollbar { width: 4px; }
        .inq-bubble::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 4px; }
        .inq-bubble::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

        .inq-awaiting {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #94a3b8;
            font-style: italic;
            padding: 8px 0;
        }
        .inq-awaiting::before {
            content: '';
            width: 8px; height: 8px;
            background: #f59e0b;
            border-radius: 50%;
            flex-shrink: 0;
            animation: pulse-dot 1.5s ease-in-out infinite;
        }
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50%  { opacity: 0.4; transform: scale(0.7); }
        }

        /* Empty state */
        .inq-empty {
            text-align: center;
            padding: 60px 30px;
            background: #fff;
            border-radius: 14px;
            border: 2px dashed #e2e8f0;
            color: #94a3b8;
        }
        .inq-empty i { font-size: 48px; margin-bottom: 16px; display: block; opacity: 0.5; }
        .inq-empty h3 { margin: 0 0 8px; color: #64748b; font-size: 18px; }
        .inq-empty p { margin: 0; font-size: 14px; }
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
        <div id="support-history" style="margin-top: 60px;">

            <!-- Section Header -->
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px;">
                <div>
                    <h2 class="section-title" style="margin:0 0 4px;">My Support History</h2>
                    <p style="margin:0; font-size:14px; color:var(--text-light);">Track all your submitted inquiries and our replies below.</p>
                </div>
                <?php if(!empty($inquiries)): ?>
                <span style="background:#eff6ff; color:var(--primary-color); font-size:13px; font-weight:600; padding:6px 16px; border-radius:20px; border:1px solid #bfdbfe;">
                    <?php echo count($inquiries); ?> <?php echo count($inquiries) == 1 ? 'Inquiry' : 'Inquiries'; ?>
                </span>
                <?php endif; ?>
            </div>

            <?php if(empty($inquiries)): ?>
                <div class="inq-empty">
                    <i class="fas fa-envelope-open-text"></i>
                    <h3>No inquiries yet</h3>
                    <p>When you submit an inquiry, you can track the replies here.</p>
                </div>
            <?php else: ?>
                <div class="history-wrap">
                    <?php foreach($inquiries as $idx => $inq):
                        $is_resolved = $inq['status'] === 'Resolved';
                        $inq_num = count($inquiries) - $idx;
                    ?>
                    <div class="inq-thread">

                        <!-- Meta bar -->
                        <div class="inq-meta">
                            <div class="inq-meta-left">
                                <span class="inq-date">
                                    <i class="far fa-calendar-alt"></i>
                                    <?php echo date('M d, Y', strtotime($inq['dateSubmitted'])); ?>
                                    &nbsp;&middot;&nbsp;
                                    <i class="far fa-clock"></i>
                                    <?php echo date('g:i A', strtotime($inq['dateSubmitted'])); ?>
                                </span>
                            </div>
                            <span class="inq-status <?php echo $is_resolved ? 'resolved' : 'pending'; ?>">
                                <i class="fas <?php echo $is_resolved ? 'fa-check-circle' : 'fa-hourglass-half'; ?>"></i>
                                <?php echo htmlspecialchars($inq['status']); ?>
                            </span>
                        </div>

                        <!-- Two-column body -->
                        <div class="inq-cols">

                            <!-- Customer Message -->
                            <div class="inq-col">
                                <div class="inq-col-label customer">
                                    <i class="fas fa-user-circle"></i> Your Inquiry
                                </div>
                                <div class="inq-bubble">
                                    <?php echo nl2br(htmlspecialchars($inq['message'])); ?>
                                </div>
                            </div>

                            <!-- Support Reply -->
                            <div class="inq-col">
                                <div class="inq-col-label support">
                                    <i class="fas fa-headset"></i> Support Reply
                                </div>
                                <?php if($inq['response']): ?>
                                    <div class="inq-bubble">
                                        <?php echo nl2br(htmlspecialchars($inq['response'])); ?>
                                    </div>
                                <?php else: ?>
                                    <div class="inq-awaiting">Awaiting response from our team…</div>
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
