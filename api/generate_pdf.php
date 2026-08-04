<?php
/**
 * Generates a print-ready analytics PDF report for Managers.
 *
 * Supported types: sales | inventory | customers
 *
 * NOTE ON DOMPDF:
 * When Composer is available, replace the window.print() approach with:
 *   require_once '../vendor/autoload.php';
 *   use Dompdf\Dompdf;
 *   $dompdf = new Dompdf();
 *   $dompdf->loadHtml($html);
 *   $dompdf->setPaper('A4', 'landscape');
 *   $dompdf->render();
 *   $dompdf->stream("report.pdf", ["Attachment" => false]);
 */

session_start();
include('../includes/dbconnection.php');

/** @var mysqli $conn Provided by dbconnection.php */
if (!isset($conn) || !$conn) {
    echo json_encode(['count' => 0]);
    exit;
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Manager') {
    die("<p style='color:red;font-family:sans-serif;padding:20px;'>Access Denied. Manager role required.</p>");
}

$type      = isset($_GET['type']) ? $_GET['type'] : 'sales';
$generated = date('F d, Y \a\t H:i');
$manager   = htmlspecialchars($_SESSION['name']);

// ── Shared CSS ────────────────────────────────────────────────────────────────
$css = "
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: Arial, Helvetica, sans-serif; color:#1e1e2f; background:#fff; padding:30px; }
    .header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:30px; padding-bottom:15px; border-bottom:3px solid #6366f1; }
    .logo-area h1 { font-size:22px; color:#6366f1; }
    .logo-area p { font-size:12px; color:#888; margin-top:3px; }
    .meta { text-align:right; font-size:12px; color:#666; line-height:1.6; }
    .stats { display:flex; gap:15px; margin-bottom:25px; }
    .stat-box { flex:1; background:#f5f6fa; border-radius:8px; padding:16px; text-align:center; border-top:4px solid #6366f1; }
    .stat-box .val { font-size:22px; font-weight:700; color:#1e1e2f; }
    .stat-box .lbl { font-size:11px; color:#888; margin-top:4px; text-transform:uppercase; letter-spacing:.5px; }
    table { width:100%; border-collapse:collapse; margin-top:10px; font-size:13px; }
    thead tr { background:#6366f1; color:#fff; }
    th, td { padding:10px 12px; text-align:left; border-bottom:1px solid #eee; }
    tbody tr:nth-child(even) { background:#f9f9ff; }
    .badge { padding:3px 8px; border-radius:12px; font-size:11px; font-weight:600; }
    .badge-green  { background:#d1fae5; color:#065f46; }
    .badge-yellow { background:#fef3c7; color:#92400e; }
    .badge-blue   { background:#dbeafe; color:#1e40af; }
    .badge-red    { background:#fee2e2; color:#991b1b; }
    .section-title { font-size:16px; font-weight:700; margin:25px 0 12px; color:#1e1e2f; padding-bottom:6px; border-bottom:1px solid #e0e0e0; }
    .footer { margin-top:35px; padding-top:12px; border-top:1px solid #eee; font-size:11px; color:#999; text-align:center; }
    @media print {
        body { padding:15px; }
        .no-print { display:none !important; }
        a { text-decoration:none; color:inherit; }
    }
";

// ── Report HTML Builder ───────────────────────────────────────────────────────
$body = '';
$title = 'Tech Shark Report';

if ($type === 'sales') {
    $title = 'Sales Analytics Report';

    // Totals
    $t_orders   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM `Order`"))['c'];
    $t_revenue  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(totalAmount),0) t FROM `Order` WHERE status != 'Rejected'"))['t'];
    $t_customers= mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM Customer"))['c'];

    // Monthly breakdown
    $monthly_res = mysqli_query($conn,
        "SELECT DATE_FORMAT(orderDate,'%b %Y') AS month_label,
                DATE_FORMAT(orderDate,'%Y-%m') AS month_key,
                COUNT(*) AS orders,
                SUM(totalAmount) AS revenue
         FROM `Order`
         WHERE status != 'Rejected'
         GROUP BY month_key, month_label
         ORDER BY month_key DESC
         LIMIT 12");

    // Order list
    $order_res = mysqli_query($conn,
        "SELECT o.orderID, o.orderDate, o.totalAmount, o.status, c.fullName
         FROM `Order` o
         JOIN Customer c ON c.customerID = o.customerID
         ORDER BY o.orderDate DESC");

    // Category revenue
    $cat_res = mysqli_query($conn,
        "SELECT cat.categoryName, COALESCE(SUM(od.unitPrice * od.quantity),0) AS rev
         FROM Category cat
         LEFT JOIN Product p ON p.categoryID = cat.categoryID
         LEFT JOIN Order_Details od ON od.productID = p.productID
         LEFT JOIN `Order` o ON o.orderID = od.orderID AND o.status != 'Rejected'
         GROUP BY cat.categoryID, cat.categoryName
         ORDER BY rev DESC");

    $body .= "<div class='stats'>
        <div class='stat-box'><div class='val'>{$t_orders}</div><div class='lbl'>Total Orders</div></div>
        <div class='stat-box'><div class='val'>LKR " . number_format($t_revenue, 0) . "</div><div class='lbl'>Total Revenue</div></div>
        <div class='stat-box'><div class='val'>{$t_customers}</div><div class='lbl'>Customers</div></div>
    </div>";

    // Monthly table
    $body .= "<div class='section-title'>Monthly Sales Breakdown</div>";
    $body .= "<table><thead><tr><th>Month</th><th>Orders</th><th>Revenue (LKR)</th></tr></thead><tbody>";
    while ($mr = mysqli_fetch_assoc($monthly_res)) {
        $body .= "<tr><td>{$mr['month_label']}</td><td>{$mr['orders']}</td><td>" . number_format($mr['revenue'], 2) . "</td></tr>";
    }
    $body .= "</tbody></table>";

    // Category revenue
    $body .= "<div class='section-title'>Revenue by Category</div>";
    $body .= "<table><thead><tr><th>Category</th><th>Revenue (LKR)</th></tr></thead><tbody>";
    while ($cr = mysqli_fetch_assoc($cat_res)) {
        $body .= "<tr><td>{$cr['categoryName']}</td><td>" . number_format($cr['rev'], 2) . "</td></tr>";
    }
    $body .= "</tbody></table>";

    // Order list
    $body .= "<div class='section-title'>All Orders</div>";
    $body .= "<table><thead><tr><th>Order #</th><th>Customer</th><th>Date</th><th>Amount (LKR)</th><th>Status</th></tr></thead><tbody>";
    $grand = 0;
    while ($or = mysqli_fetch_assoc($order_res)) {
        $badge = match($or['status']) {
            'Delivered'  => 'badge-green',
            'Processing', 'Shipped' => 'badge-blue',
            'Pending'    => 'badge-yellow',
            'Rejected'   => 'badge-red',
            default      => 'badge-yellow'
        };
        $body .= "<tr>
            <td>#ORD-" . str_pad($or['orderID'], 4, '0', STR_PAD_LEFT) . "</td>
            <td>" . htmlspecialchars($or['fullName']) . "</td>
            <td>" . date('M d, Y', strtotime($or['orderDate'])) . "</td>
            <td>" . number_format($or['totalAmount'], 2) . "</td>
            <td><span class='badge $badge'>{$or['status']}</span></td>
        </tr>";
        if ($or['status'] !== 'Rejected') $grand += $or['totalAmount'];
    }
    $body .= "<tr style='font-weight:700;background:#eef2ff;'><td colspan='3' style='text-align:right;'>Grand Total</td><td>LKR " . number_format($grand, 2) . "</td><td></td></tr>";
    $body .= "</tbody></table>";

} elseif ($type === 'inventory') {
    $title = 'Inventory Report';

    $t_products  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM Product"))['c'];
    $t_low       = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM Product WHERE quantity_in_stock < 3"))['c'];
    $t_value     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(price * quantity_in_stock),0) t FROM Product"))['t'];

    $body .= "<div class='stats'>
        <div class='stat-box'><div class='val'>{$t_products}</div><div class='lbl'>Total Products</div></div>
        <div class='stat-box' style='border-top-color:#ef4444;'><div class='val' style='color:#ef4444;'>{$t_low}</div><div class='lbl'>Low Stock (&lt;3)</div></div>
        <div class='stat-box'><div class='val'>LKR " . number_format($t_value, 0) . "</div><div class='lbl'>Stock Value</div></div>
    </div>";

    $res = mysqli_query($conn,
        "SELECT p.productID, p.productName, p.brand, cat.categoryName, p.price, p.quantity_in_stock, p.status
         FROM Product p
         LEFT JOIN Category cat ON cat.categoryID = p.categoryID
         ORDER BY p.quantity_in_stock ASC");

    $body .= "<div class='section-title'>Product Inventory</div>";
    $body .= "<table><thead><tr><th>#</th><th>Product</th><th>Brand</th><th>Category</th><th>Price (LKR)</th><th>Stock</th><th>Status</th></tr></thead><tbody>";
    while ($pr = mysqli_fetch_assoc($res)) {
        $stock_style = $pr['quantity_in_stock'] < 3 ? "color:#ef4444;font-weight:700;" : "";
        $badge = $pr['status'] === 'Active' ? 'badge-green' : 'badge-red';
        $body .= "<tr>
            <td>{$pr['productID']}</td>
            <td>" . htmlspecialchars($pr['productName']) . "</td>
            <td>" . htmlspecialchars($pr['brand']) . "</td>
            <td>" . htmlspecialchars($pr['categoryName']) . "</td>
            <td>" . number_format($pr['price'], 2) . "</td>
            <td style='{$stock_style}'>{$pr['quantity_in_stock']}</td>
            <td><span class='badge $badge'>{$pr['status']}</span></td>
        </tr>";
    }
    $body .= "</tbody></table>";

} elseif ($type === 'customers') {
    $title = 'Customer Report';

    $t_customers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM Customer"))['c'];
    $t_orders    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT customerID) c FROM `Order`"))['c'];

    $body .= "<div class='stats'>
        <div class='stat-box'><div class='val'>{$t_customers}</div><div class='lbl'>Total Customers</div></div>
        <div class='stat-box'><div class='val'>{$t_orders}</div><div class='lbl'>Customers with Orders</div></div>
    </div>";

    $res = mysqli_query($conn,
        "SELECT c.customerID, c.fullName, c.email, c.contactNo, c.city,
                COUNT(o.orderID) AS order_count,
                COALESCE(SUM(o.totalAmount),0) AS total_spent
         FROM Customer c
         LEFT JOIN `Order` o ON o.customerID = c.customerID AND o.status != 'Rejected'
         GROUP BY c.customerID
         ORDER BY total_spent DESC");

    $body .= "<div class='section-title'>Customer Directory</div>";
    $body .= "<table><thead><tr><th>#</th><th>Full Name</th><th>Email</th><th>Contact</th><th>City</th><th>Orders</th><th>Total Spent (LKR)</th></tr></thead><tbody>";
    while ($cr = mysqli_fetch_assoc($res)) {
        $body .= "<tr>
            <td>{$cr['customerID']}</td>
            <td>" . htmlspecialchars($cr['fullName']) . "</td>
            <td>" . htmlspecialchars($cr['email']) . "</td>
            <td>" . htmlspecialchars($cr['contactNo']) . "</td>
            <td>" . htmlspecialchars($cr['city']) . "</td>
            <td>{$cr['order_count']}</td>
            <td>" . number_format($cr['total_spent'], 2) . "</td>
        </tr>";
    }
    $body .= "</tbody></table>";
}

// ── Render Full Page ──────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $title; ?> - Tech Shark</title>
    <style><?php echo $css; ?></style>
</head>
<body>
    <!-- Print button (hidden on actual print) -->
    <div class="no-print" style="margin-bottom:20px;display:flex;gap:10px;align-items:center;">
        <button onclick="window.print()" style="padding:10px 22px;background:#6366f1;color:#fff;border:none;border-radius:6px;font-size:14px;cursor:pointer;font-family:inherit;">
            🖨 Print / Save as PDF
        </button>
        <a href="../staff/manager_dashboard.php?view=reports" style="padding:10px 22px;background:#f3f4f6;color:#333;border:1px solid #ddd;border-radius:6px;font-size:14px;text-decoration:none;">
            ← Back to Reports
        </a>
    </div>

    <!-- Report Header -->
    <div class="header">
        <div class="logo-area">
            <h1>⚡ Tech Shark</h1>
            <p><?php echo $title; ?></p>
        </div>
        <div class="meta">
            Generated by: <strong><?php echo $manager; ?></strong><br>
            Date: <?php echo $generated; ?><br>
            Report Type: <strong><?php echo ucfirst($type); ?></strong>
        </div>
    </div>

    <!-- Report Body -->
    <?php echo $body; ?>

    <!-- Footer -->
    <div class="footer">
        This is an official Tech Shark system-generated report. Confidential — for internal use only.<br>
        &copy; <?php echo date('Y'); ?> Tech Shark Operations &nbsp;|&nbsp; Generated: <?php echo $generated; ?>
    </div>

    <script>
    // Auto-open print dialog on load if ?print=1
    const params = new URLSearchParams(window.location.search);
    if (params.get('print') === '1') { window.print(); }
    </script>
</body>
</html>
