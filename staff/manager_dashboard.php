<?php
session_start();
include(__DIR__ . '/../includes/dbconnection.php');

// Security Check: Redirect to login if not a Manager
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Manager') {
    header("Location: ../login.php");
    exit;
}

$staff_id = $_SESSION['user_id'];
$staff_role = $_SESSION['role'];
$staff_name = $_SESSION['name'];
$view = isset($_GET['view']) ? $_GET['view'] : 'dashboard';

include('includes/header.php');
include('includes/sidebar.php');
?>

<main class="main-content">
    <header class="topbar">
        <div>
            <button class="sidebar-toggle-btn" id="sidebarToggle" title="Toggle sidebar">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        <div><h3 style="margin:0; font-weight:normal;">Welcome back, <b><?php echo htmlspecialchars($staff_name); ?></b></h3></div>
        <div></div>
    </header>

    <div class="content-area">
        <?php 
        // ==========================================
        // 1. DASHBOARD OVERVIEW
        // ==========================================
        if ($view == 'dashboard') {
            echo "<h2>Dashboard Overview</h2>";
            $total_orders    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM `Order`"))['c'];
            $total_revenue   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(totalAmount),0) as t FROM `Order` WHERE status != 'Rejected'"))['t'];
            $total_customers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM Customer"))['c'];
            
            echo "<div class='dashboard-cards' style='margin-bottom: 25px;'>";
            echo "<div class='card'><div class='info'><p>Total Orders</p><h3>" . number_format($total_orders) . "</h3></div><i class='fas fa-shopping-cart' style='color:#000000;opacity:1;'></i></div>";
            echo "<div class='card'><div class='info'><p>Total Revenue</p><h3>LKR " . number_format($total_revenue, 0) . "</h3></div><i class='fas fa-coins' style='color:#000000;opacity:1;'></i></div>";
            echo "<div class='card'><div class='info'><p>Total Customers</p><h3>" . number_format($total_customers) . "</h3></div><i class='fas fa-users' style='color:#000000;opacity:1;'></i></div>";
            echo "</div>";
            
            echo "<div class='panel'><h3>Quick Message</h3><p>Ensure sensitive data is handled according to policy. Role functionality is restricted server-side.</p></div>";
        }
        
        // ==========================================
        // 2. ANALYTICS VIEW
        // ==========================================
        elseif ($view == 'analytics') {
            $range = isset($_GET['range']) ? $_GET['range'] : '12_months';
            $date_condition = ""; $group_format_label = ""; $group_format_key = "";

            if ($range == '7_days') {
                $date_condition = ">= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                $group_format_label = "'%b %d'"; $group_format_key = "'%Y-%m-%d'";
            } elseif ($range == '30_days') {
                $date_condition = ">= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                $group_format_label = "'%b %d'"; $group_format_key = "'%Y-%m-%d'";
            } elseif ($range == '3_months') {
                $date_condition = ">= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
                $group_format_label = "'%b %Y'"; $group_format_key = "'%Y-%m'";
            } elseif ($range == '6_months') {
                $date_condition = ">= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
                $group_format_label = "'%b %Y'"; $group_format_key = "'%Y-%m'";
            } else { 
                $date_condition = ">= DATE_SUB(NOW(), INTERVAL 12 MONTH)";
                $group_format_label = "'%b %Y'"; $group_format_key = "'%Y-%m'";
            }

            // Stat Totals
            $total_orders  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM `Order` WHERE orderDate $date_condition"))['c'];
            $total_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(totalAmount),0) as t FROM `Order` WHERE status != 'Rejected' AND orderDate $date_condition"))['t'];
            $total_customers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM Customer"))['c']; 

            // Line Chart Query
            $monthly_res = mysqli_query($conn,
                "SELECT DATE_FORMAT(orderDate, $group_format_label) AS time_label,
                        DATE_FORMAT(orderDate, $group_format_key) AS time_key,
                        SUM(totalAmount) AS revenue, COUNT(*) AS orders
                 FROM `Order` WHERE orderDate $date_condition AND status != 'Rejected'
                 GROUP BY time_key, time_label ORDER BY time_key ASC");

            $line_labels = []; $line_revenue = []; $line_orders = [];
            while ($mr = mysqli_fetch_assoc($monthly_res)) {
                $line_labels[]  = $mr['time_label'];
                $line_revenue[] = (float)$mr['revenue'];
                $line_orders[]  = (int)$mr['orders'];
            }

            // Pie Chart Query
            $cat_res = mysqli_query($conn,
                "SELECT c.categoryName, COALESCE(SUM(od.unitPrice * od.quantity), 0) AS cat_revenue
                 FROM Category c
                 LEFT JOIN Product p ON p.categoryID = c.categoryID
                 LEFT JOIN Order_Details od ON od.productID = p.productID
                 LEFT JOIN `Order` o ON o.orderID = od.orderID AND o.status != 'Rejected'
                 WHERE o.orderDate $date_condition
                 GROUP BY c.categoryID, c.categoryName HAVING cat_revenue > 0 ORDER BY cat_revenue DESC");

            $pie_labels = []; $pie_data = [];
            while ($cr = mysqli_fetch_assoc($cat_res)) {
                $pie_labels[] = $cr['categoryName'];
                $pie_data[]   = (float)$cr['cat_revenue'];
            }

            // Top Products Query
            $top_products_res = mysqli_query($conn,
                "SELECT p.productName, p.productID, SUM(od.quantity) as soldQty, SUM(od.quantity * od.unitPrice) as totalRev
                 FROM Order_Details od
                 JOIN Product p ON od.productID = p.productID JOIN `Order` o ON o.orderID = od.orderID
                 WHERE o.status != 'Rejected' AND o.orderDate $date_condition
                 GROUP BY p.productID ORDER BY soldQty DESC LIMIT 10");

            $pie_colors = ['#6366f1','#f59e0b','#10b981','#3b82f6','#ec4899','#8b5cf6'];
            
            $range_titles = [
                '7_days' => 'Last 7 Days', '30_days' => 'Last 30 Days',
                '3_months' => 'Last 3 Months', '6_months' => 'Last 6 Months', '12_months' => 'Last 12 Months'
            ];
            $current_title = $range_titles[$range];

            // Sending data to JavaScript
            echo "<script>
                window.chartData = {
                    lineLabels: " . json_encode($line_labels) . ",
                    lineRevenue: " . json_encode($line_revenue) . ",
                    lineOrders: " . json_encode($line_orders) . ",
                    pieLabels: " . json_encode($pie_labels) . ",
                    pieData: " . json_encode($pie_data) . ",
                    pieColors: " . json_encode(array_slice($pie_colors, 0, count($pie_labels))) . "
                };
            </script>";
            ?>

            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h2 style="margin:0;">Business Analytics</h2>
                <div style="background:#fff; padding:5px 15px; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,0.05); border:1px solid #e2e8f0;">
                    <i class="fas fa-calendar-alt" style="color:#94a3b8; margin-right:8px;"></i>
                    <select style="border:none; outline:none; font-weight:bold; color:#334155; cursor:pointer; font-size:14px;" 
                            onchange="window.location.href='?view=analytics&range=' + this.value">
                        <option value="7_days" <?php if($range == '7_days') echo 'selected'; ?>>Last 7 Days</option>
                        <option value="30_days" <?php if($range == '30_days') echo 'selected'; ?>>Last 30 Days</option>
                        <option value="3_months" <?php if($range == '3_months') echo 'selected'; ?>>Last 3 Months</option>
                        <option value="6_months" <?php if($range == '6_months') echo 'selected'; ?>>Last 6 Months</option>
                        <option value="12_months" <?php if($range == '12_months') echo 'selected'; ?>>Last 12 Months</option>
                    </select>
                </div>
            </div>

            <div class="dashboard-cards" style="margin-bottom:25px;">
                <div class="card"><div class="info"><p>Orders (<?php echo $current_title; ?>)</p><h3><?php echo number_format($total_orders); ?></h3></div><i class="fas fa-shopping-cart" style='color:#000000;opacity:1;'></i></div>
                <div class="card"><div class="info"><p>Revenue (<?php echo $current_title; ?>)</p><h3>LKR <?php echo number_format($total_revenue, 0); ?></h3></div><i class="fas fa-coins" style='color:#000000;opacity:1;'></i></div>
                <div class="card"><div class="info"><p>Total Customers</p><h3><?php echo number_format($total_customers); ?></h3></div><i class="fas fa-users" style='color:#000000;opacity:1;'></i></div>
            </div>

            <div style="margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; display:flex;">
                <button class="tab-btn active" onclick="openManagerTab('salesTab', this)"><i class="fas fa-chart-line"></i> Sales Trend</button>
                <button class="tab-btn" onclick="openManagerTab('categoryTab', this)"><i class="fas fa-chart-pie"></i> Revenue by Category</button>
                <button class="tab-btn" onclick="openManagerTab('topProductsTab', this)"><i class="fas fa-star"></i> Top Products</button>
            </div>

            <div id="salesTab" class="tab-content" style="display:block;">
                <div class="panel">
                    <h2 style="margin-top:0;">Sales Overview <span style="font-size:13px;font-weight:400;color:#888;">(<?php echo $current_title; ?>)</span>
                        <a href="../api/generate_pdf.php?type=sales" target="_blank" class="btn btn-primary" style="font-size:12px; float:right;"><i class="fas fa-file-pdf" style="color:white;"></i> Export Report</a>
                    </h2>
                    <canvas id="salesLineChart" style="max-height:320px;"></canvas>
                </div>
            </div>

            <div id="categoryTab" class="tab-content">
                <div class="panel">
                    <h2 style="margin-top:0;">Revenue by Category <span style="font-size:13px;font-weight:400;color:#888;">(<?php echo $current_title; ?>)</span></h2>
                    <div style="display:flex;gap:30px;align-items:center;flex-wrap:wrap;">
                        <div style="flex:0 0 320px;max-width:320px;">
                            <canvas id="categoryPieChart"></canvas>
                        </div>
                        <div style="flex:1;">
                            <?php foreach($pie_labels as $i => $lbl): ?>
                            <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px; background:#f8fafc; padding:10px; border-radius:8px;">
                                <span style="width:16px;height:16px;border-radius:4px;background:<?php echo $pie_colors[$i % count($pie_colors)]; ?>;flex-shrink:0;"></span>
                                <span style="flex:1; font-weight:600; color:#334155;"><?php echo htmlspecialchars($lbl); ?></span>
                                <strong style="color:var(--secondary);">LKR <?php echo number_format($pie_data[$i], 0); ?></strong>
                            </div>
                            <?php endforeach; ?>
                            <?php if(empty($pie_labels)): ?>
                                <div style="text-align:center; color:#94a3b8; padding:30px;"><i class="fas fa-chart-pie" style="font-size:40px; margin-bottom:10px; opacity:0.5;"></i><br>No revenue data in the selected period.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div id="topProductsTab" class="tab-content">
                <div class="panel">
                    <h2 style="margin-top:0;">Top 10 Selling Products <span style="font-size:13px;font-weight:400;color:#888;">(<?php echo $current_title; ?>)</span></h2>
                    <table class="top-prod-table" style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr>
                                <th style="padding:12px; text-align:left;">Product ID</th>
                                <th style="padding:12px; text-align:left;">Product Name</th>
                                <th style="padding:12px; text-align:center;">Units Sold</th>
                                <th style="padding:12px; text-align:right;">Total Revenue Generated</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if(mysqli_num_rows($top_products_res) > 0) {
                                while($tp = mysqli_fetch_assoc($top_products_res)): ?>
                                <tr style="border-bottom:1px solid #e2e8f0;">
                                    <td style="padding:12px; font-weight:600; color:#64748b;">#PROD-<?php echo str_pad($tp['productID'], 4, '0', STR_PAD_LEFT); ?></td>
                                    <td style="padding:12px; font-weight:600; color:#0f172a;"><?php echo htmlspecialchars($tp['productName']); ?></td>
                                    <td style="padding:12px; text-align:center;"><span style="background:#e0e7ff; color:#3730a3; padding:4px 10px; border-radius:20px; font-weight:bold; font-size:13px;"><?php echo $tp['soldQty']; ?></span></td>
                                    <td style="padding:12px; text-align:right; font-weight:bold; color:#10b981;">LKR <?php echo number_format($tp['totalRev'], 2); ?></td>
                                </tr>
                                <?php endwhile; 
                            } else {
                                echo "<tr><td colspan='4' style='text-align:center; padding:30px; color:#94a3b8;'>No products sold in this period.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php 
        // ==========================================
        // 3. REPORTS VIEW
        // ==========================================
        } elseif ($view == 'reports') {
            $total_orders    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM `Order`"))['c'];
            $total_revenue   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(totalAmount),0) as t FROM `Order` WHERE status != 'Rejected'"))['t'];
            $total_customers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM Customer"))['c'];
            ?>
            <h2 style="margin:0 0 20px;">PDF Reports</h2>
            <div class="dashboard-cards" style="margin-bottom:25px;">
                <div class="card"><div class="info"><p>Total Orders</p><h3><?php echo number_format($total_orders); ?></h3></div><i class="fas fa-shopping-cart" style='color:#000000;opacity:1;'></i></div>
                <div class="card"><div class="info"><p>Total Revenue</p><h3>LKR <?php echo number_format($total_revenue, 0); ?></h3></div><i class="fas fa-coins" style='color:#000000;opacity:1;'></i></div>
                <div class="card"><div class="info"><p>Customers</p><h3><?php echo number_format($total_customers); ?></h3></div><i class="fas fa-users" style='color:#000000;opacity:1;'></i></div>
            </div>
            <div class="panel">
                <h2 style="margin-top:0;">Generate Analytical Reports</h2>
                <p style="color:#666;margin-bottom:25px;">Click a report below to open a print-ready PDF containing full analytics data.</p>
                <div style="display:flex;gap:15px;flex-wrap:wrap;">
                    <a href="../api/generate_pdf.php?type=sales" target="_blank" class="btn btn-primary" style="padding:12px 24px;font-size:14px;"><i class="fas fa-chart-line"></i> &nbsp;Sales Analytics Report</a>
                    <a href="../api/generate_pdf.php?type=inventory" target="_blank" class="btn btn-success" style="padding:12px 24px;font-size:14px;"><i class="fas fa-boxes"></i> &nbsp;Inventory Report</a>
                    <a href="../api/generate_pdf.php?type=customers" target="_blank" class="btn" style="background:#8b5cf6;padding:12px 24px;font-size:14px;"><i class="fas fa-users"></i> &nbsp;Customer Report</a>
                </div>
            </div>
            <?php
        }
        ?>
    </div>
</main>

<script src="assets/js/manager_dashboard.js"></script>
<script src="assets/js/notes_board.js"></script>
</body>
</html>