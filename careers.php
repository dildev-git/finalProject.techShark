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
    <title>Careers - Tech Shark</title>
    <link rel="icon" type="image/png" href="assets/logo.png"/>
    <link rel="stylesheet" href="includes/css/customer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .page-header { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: white; padding: 60px 20px; text-align: center; }
        .page-header h1 { font-size: 2.5rem; margin-bottom: 10px; }
        .content-section { max-width: 800px; margin: 60px auto; padding: 0 20px; }
        .job-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 25px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; transition: box-shadow 0.3s; }
        .job-card:hover { box-shadow: 0 10px 15px rgba(0,0,0,0.05); }
        .job-info h3 { font-size: 1.3rem; color: #1e293b; margin-bottom: 10px; }
        .job-meta { font-size: 0.9rem; color: #64748b; display: flex; gap: 15px; }
        .job-meta i { color: var(--primary-color); }
        .apply-btn { padding: 10px 20px; background: var(--primary-color); color: white; border-radius: 6px; text-decoration: none; font-weight: 500; transition: background 0.3s; }
        .apply-btn:hover { background: var(--secondary-color); }
        .perks-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .perk-item { text-align: center; padding: 20px; background: #f8fafc; border-radius: 8px; }
        .perk-item i { font-size: 2rem; color: var(--primary-color); margin-bottom: 10px; }
        .perk-item h4 { color: #334155; margin-bottom: 5px; }
    </style>
</head>
<body>
    <?php include 'includes/customer_header.php'; ?>

    <div class="page-header">
        <div class="container">
            <h1>Join the Tech Shark Team</h1>
            <p style="color:#94a3b8;">Help us build the ultimate technology retail and repair experience.</p>
        </div>
    </div>

    <div class="container">
        <div class="content-section">
            
            <h2 style="margin-bottom: 20px; color:#1e293b;">Why Work With Us?</h2>
            <div class="perks-grid">
                <div class="perk-item">
                    <i class="fas fa-heartbeat"></i>
                    <h4>Health Benefits</h4>
                    <p style="font-size:0.9rem; color:#64748b;">Comprehensive health and wellness coverage.</p>
                </div>
                <div class="perk-item">
                    <i class="fas fa-laptop"></i>
                    <h4>Tech Allowance</h4>
                    <p style="font-size:0.9rem; color:#64748b;">Annual budget for your personal tech setup.</p>
                </div>
                <div class="perk-item">
                    <i class="fas fa-graduation-cap"></i>
                    <h4>Continuous Learning</h4>
                    <p style="font-size:0.9rem; color:#64748b;">Paid certifications and training programs.</p>
                </div>
            </div>

            <h2 style="margin-bottom: 20px; color:#1e293b;">Open Positions</h2>
            
            <div class="job-card">
                <div class="job-info">
                    <h3>Senior Repair Technician</h3>
                    <div class="job-meta">
                        <span><i class="fas fa-map-marker-alt"></i> Colombo Branch</span>
                        <span><i class="fas fa-clock"></i> Full-time</span>
                    </div>
                </div>
                <a href="contact.php" class="apply-btn">Apply Now</a>
            </div>

            <div class="job-card">
                <div class="job-info">
                    <h3>Sales Representative</h3>
                    <div class="job-meta">
                        <span><i class="fas fa-map-marker-alt"></i> Kurunegala Branch</span>
                        <span><i class="fas fa-clock"></i> Full-time</span>
                    </div>
                </div>
                <a href="contact.php" class="apply-btn">Apply Now</a>
            </div>

            <div class="job-card">
                <div class="job-info">
                    <h3>Inventory Specialist</h3>
                    <div class="job-meta">
                        <span><i class="fas fa-map-marker-alt"></i> Remote / Warehouse</span>
                        <span><i class="fas fa-clock"></i> Part-time</span>
                    </div>
                </div>
                <a href="contact.php" class="apply-btn">Apply Now</a>
            </div>

            <p style="margin-top:40px; color:#64748b; text-align:center;">Don't see a role that fits? Send your resume to <a href="mailto:careers@techshark.com" style="color:var(--primary-color);">careers@techshark.com</a>.</p>

        </div>
    </div>

    <?php include 'includes/customer_footer.php'; ?>
</body>
</html>
