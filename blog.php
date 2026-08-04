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
    <title>Blog - Tech Shark</title>
    <link rel="icon" type="image/png" href="assets/logo.png"/>
    <link rel="stylesheet" href="includes/css/customer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .page-header { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: white; padding: 60px 20px; text-align: center; }
        .page-header h1 { font-size: 2.5rem; margin-bottom: 10px; }
        .content-section { max-width: 1000px; margin: 60px auto; padding: 0 20px; }
        .blog-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; }
        .blog-card { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; transition: transform 0.3s; }
        .blog-card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px rgba(0,0,0,0.1); }
        .blog-img { height: 200px; background: #cbd5e1; display: flex; align-items: center; justify-content: center; color: #64748b; font-size: 3rem; }
        .blog-content { padding: 25px; }
        .blog-meta { font-size: 0.9rem; color: #94a3b8; margin-bottom: 10px; }
        .blog-title { font-size: 1.25rem; font-weight: 600; color: #1e293b; margin-bottom: 15px; }
        .blog-excerpt { color: #475569; line-height: 1.6; margin-bottom: 20px; font-size: 0.95rem; }
        .read-more { color: var(--primary-color); text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; }
        .read-more:hover { color: var(--secondary-color); }
    </style>
</head>
<body>
    <?php include 'includes/customer_header.php'; ?>

    <div class="page-header">
        <div class="container">
            <h1>Tech Insights & News</h1>
            <p style="color:#94a3b8;">Stay updated with the latest in tech, reviews, and Tech Shark announcements.</p>
        </div>
    </div>

    <div class="container">
        <div class="content-section">
            <div class="blog-grid">
                <div class="blog-card">
                    <div class="blog-img"><i class="fas fa-laptop-code"></i></div>
                    <div class="blog-content">
                        <div class="blog-meta"><i class="far fa-calendar-alt"></i> May 8, 2026 &nbsp;&bull;&nbsp; Tech Tips</div>
                        <h3 class="blog-title">How to Choose the Right Laptop for Programming in 2026</h3>
                        <p class="blog-excerpt">Discover the essential specs and features developers need to look for when buying a new laptop this year, from CPU cores to RAM capacity.</p>
                        <a href="#" class="read-more">Read Article <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
                <div class="blog-card">
                    <div class="blog-img"><i class="fas fa-gamepad"></i></div>
                    <div class="blog-content">
                        <div class="blog-meta"><i class="far fa-calendar-alt"></i> April 22, 2026 &nbsp;&bull;&nbsp; Gaming</div>
                        <h3 class="blog-title">Top 5 Custom PC Builds for 4K Gaming</h3>
                        <p class="blog-excerpt">We break down the ultimate component combinations to achieve buttery-smooth 4K gameplay on the latest AAA titles without bottlenecking.</p>
                        <a href="#" class="read-more">Read Article <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
                <div class="blog-card">
                    <div class="blog-img"><i class="fas fa-shield-alt"></i></div>
                    <div class="blog-content">
                        <div class="blog-meta"><i class="far fa-calendar-alt"></i> March 15, 2026 &nbsp;&bull;&nbsp; Security</div>
                        <h3 class="blog-title">Data Storage: HDD vs SSD vs NVMe Explained</h3>
                        <p class="blog-excerpt">Still confused about the difference between storage types? Learn which storage drive is right for your workflow and budget.</p>
                        <a href="#" class="read-more">Read Article <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/customer_footer.php'; ?>
</body>
</html>
