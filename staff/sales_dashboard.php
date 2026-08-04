<?php
session_start();
include(__DIR__ . '/../includes/dbconnection.php');

// Security Check: Redirect to login if not a Sales Representative
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Sales Representative') {
    header("Location: ../login.php");
    exit;
}

$staff_id = $_SESSION['user_id'];
$staff_role = $_SESSION['role'];
$staff_name = $_SESSION['name'];
$view = isset($_GET['view']) ? $_GET['view'] : 'dashboard';

$msg = '';
$msg_type = '';

// ==========================================
// 1. SALES LOGIC (Order Updates)
// ==========================================

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_order'])) {
    $oid = (int)$_POST['order_id'];
    $status = $_POST['status'];
    
    // Update order status
    mysqli_query($conn, "UPDATE `Order` SET status = '$status' WHERE orderID = $oid");
    
    // Send Notification to Customer
    $res = mysqli_query($conn, "SELECT customerID FROM `Order` WHERE orderID = $oid");
    if($row = mysqli_fetch_assoc($res)) {
        $cid = $row['customerID'];
        $notif_msg = "Your order #ORD-" . str_pad($oid, 4, '0', STR_PAD_LEFT) . " status has been updated to '$status'.";
        $safe_msg = mysqli_real_escape_string($conn, $notif_msg);
        mysqli_query($conn, "INSERT INTO Notification (message, type, date, customerID, is_read) VALUES ('$safe_msg', 'Order Update', NOW(), $cid, 0)");
    }
    
    $msg = "Order status updated successfully."; $msg_type = "success";
}

// ==========================================
// 2. UI & VIEWS
// ==========================================
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
        <?php if($msg): ?>
            <div class="alert alert-<?php echo $msg_type; ?>"><?php echo $msg; ?></div>
        <?php endif; ?>

        <?php 
        // --- 1. DASHBOARD OVERVIEW ---
        if ($view == 'dashboard') {
            echo "<h2>Sales Dashboard Overview</h2>";
            echo "<div class='dashboard-cards'>";
            
            $ro      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM `Order`"));
            $rp_pend = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM `Order` WHERE status='Pending'"));
            $rp_proc = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM `Order` WHERE status='Processing'"));
            $rp_ship = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM `Order` WHERE status='Shipped'"));
            
            echo "<div class='card'><div class='info'><p>Total Orders</p><h3 style='color:#6F55FF;'>{$ro['c']}</h3></div><i class='fas fa-shopping-cart' style='color:#6F55FF;opacity:0.7;'></i></div>";
            echo "<div class='card'><div class='info'><p>Pending Orders</p><h3 style='color:#f59e0b;'>{$rp_pend['c']}</h3></div><i class='fas fa-clock' style='color:#f59e0b;opacity:0.7;'></i></div>";
            echo "<div class='card'><div class='info'><p>Processing</p><h3 style='color:#3b82f6;'>{$rp_proc['c']}</h3></div><i class='fas fa-box-open' style='color:#3b82f6;opacity:0.7;'></i></div>";
            echo "<div class='card'><div class='info'><p>Shipped Orders</p><h3 style='color:#10b981;'>{$rp_ship['c']}</h3></div><i class='fas fa-truck' style='color:#10b981;opacity:0.7;'></i></div>";
            
            echo "</div>";
            echo "<div class='panel'><h3>Quick Message</h3><p>Ensure to process pending orders quickly. Keep customers updated on their deliveries.</p></div>";
        }
        
        // --- 2. ORDERS MANAGEMENT VIEW ---
        elseif ($view == 'orders') {
            echo "<h2>Manage Orders</h2>";
            echo "<div style='position:relative; margin-bottom:18px; width:100%; max-width:420px;'>
                    <input type='text' id='orderSearch' placeholder='Search by Order ID or Customer Name...'
                        style='width:100%; box-sizing:border-box; padding:9px 35px 9px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; outline:none;'>
                    <i class='fas fa-search' style='position:absolute; right:14px; top:50%; transform:translateY(-50%); color:#9ca3af; pointer-events:none;'></i>
                </div>";

            $ordersData = [];
            $ordersByStatus = [
                'Pending' => [],
                'Processing' => [],
                'Shipped' => [],
                'Delivered' => [],
                'Rejected' => []
            ];

            // Get Order, Customer, and Payment data
            $res = mysqli_query($conn, "
                SELECT o.*, 
                       c.fullName as customerName, c.email as customerEmail, c.contactNo as customerPhone, c.address as customerAddress, c.city as customerCity,
                       p.method as paymentMethod, p.status as paymentStatus, p.paymentDate
                FROM `Order` o
                JOIN Customer c ON o.customerID = c.customerID
                LEFT JOIN Payment p ON o.orderID = p.orderID
                ORDER BY o.orderDate DESC
            ");
            
            while($r = mysqli_fetch_assoc($res)){
                $oid = $r['orderID'];
                // Getting items related to the order
                $items_res = mysqli_query($conn, "
                    SELECT od.quantity, od.unitPrice, pr.productName 
                    FROM Order_Details od
                    JOIN Product pr ON od.productID = pr.productID
                    WHERE od.orderID = $oid
                ");
                $items = [];
                while($it = mysqli_fetch_assoc($items_res)) {
                    $items[] = $it;
                }
                $r['items'] = $items;
                $ordersData[$oid] = $r;

                // Separation by status
                $status = $r['status'];
                if (!isset($ordersByStatus[$status])) { $ordersByStatus[$status] = []; }
                $ordersByStatus[$status][] = $r;
            }

            // Sending data to the JavaScript Modal
            echo "<script>window.ordersData = " . json_encode($ordersData) . ";</script>";

            $statusColors = [
                'Pending' => '#f59e0b',
                'Processing' => '#3b82f6',
                'Shipped' => '#10b981',
                'Delivered' => '#64748b',
                'Rejected' => '#ef4444'
            ];

            // Displaying Tables One by One Status
            foreach ($ordersByStatus as $status => $ordersList) {
                if (empty($ordersList)) continue; 

                $color = isset($statusColors[$status]) ? $statusColors[$status] : '#333';
                $count = count($ordersList);
                $panel_id = 'order-panel-' . strtolower(str_replace(' ', '-', $status));
                
                echo "<div class='panel order-panel' style='border-left:4px solid {$color}; padding-bottom:0;'>";
                // Collapsible header
                echo "<div class='collapsible-panel-header' onclick='togglePanel(\"body-{$panel_id}\", this.querySelector(\".panel-toggle-btn\"))'>
                        <div style='display:flex;align-items:center;gap:10px;'>
                            <i class='fas fa-shopping-cart' style='color:{$color};'></i>
                            <span style='font-size:15px;font-weight:700;color:var(--text-primary);'>{$status} Orders</span>
                            <span style='background:{$color};color:#fff;font-size:11px;padding:2px 10px;border-radius:20px;font-weight:600;'>{$count} orders</span>
                        </div>
                        <button class='panel-toggle-btn' type='button'>
                            <i class='fas fa-chevron-down toggle-icon'></i>
                            <span class='toggle-text'>Expand</span>
                        </button>
                      </div>";
                // Collapsible body (collapsed by default)
                echo "<div class='collapsible-body' id='body-{$panel_id}' style='padding-top:0;'>";
                echo "<div style='overflow-x:auto; padding: 14px 0 6px;'><table><tr><th>Order ID</th><th>Date</th><th>Amount</th><th>Status</th><th colspan='2'>Action</th></tr>";
                echo "<tbody>";

                foreach ($ordersList as $r) {
                    $custSearch = strtolower(htmlspecialchars($r['customerName']));
                    $ordIdFormatted = 'ord-' . str_pad($r['orderID'], 4, '0', STR_PAD_LEFT);
                    
                    echo "<tr class='order-row' data-search='{$custSearch} {$ordIdFormatted}'>
                    <td>#ORD-" . str_pad($r['orderID'], 4, '0', STR_PAD_LEFT) . "</td>
                    <td>{$r['orderDate']}</td>
                    <td>LKR ".number_format($r['totalAmount'], 2)."</td>
                    <td><span style='padding:4px 8px;border-radius:4px;font-size:12px;font-weight:bold;background:#e2e8f0;color:#333;'>{$r['status']}</span></td>
                    <td>
                        <form method='POST' style='display:flex; gap:5px;'>
                            <input type='hidden' name='order_id' value='{$r['orderID']}'>
                            <select name='status' class='form-control' style='margin:0; width:auto; padding:4px;'>
                                <option ".($r['status']=='Pending'?'selected':'').">Pending</option>
                                <option ".($r['status']=='Processing'?'selected':'').">Processing</option>
                                <option ".($r['status']=='Shipped'?'selected':'').">Shipped</option>
                                <option ".($r['status']=='Delivered'?'selected':'').">Delivered</option>
                                <option ".($r['status']=='Rejected'?'selected':'').">Rejected</option>
                            </select>
                            <button type='submit' name='update_order' class='btn btn-success' style='padding:6px 12px;font-size:12px;'><i class='fas fa-save'></i> Save</button>
                        </form>
                    </td>
                    <td>
                        <button class='btn btn-primary btn-view-order' style='padding:6px 12px;font-size:12px;' data-id='{$r['orderID']}'>
                            <i class='fas fa-eye'></i> View
                        </button>
                    </td></tr>";
                }
                echo "</tbody></table></div>";
                echo "</div>"; // collapsible-body
                echo "</div>"; // panel
            }
            ?>
            
            <div id="orderModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:9999;align-items:center;justify-content:center;padding:20px;">
                <div style="background:#fff;border-radius:12px;padding:30px;width:700px;max-width:100%;max-height:90vh;overflow-y:auto;box-shadow:0 25px 50px -12px rgba(0,0,0,0.5);">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;border-bottom:1px solid #eee;padding-bottom:15px;">
                        <h2 style="margin:0;color:var(--dark);">Order Details <span id="mod_oid" style="color:var(--accent);"></span></h2>
                        <button id="closeOrderModal" style="background:none;border:none;font-size:24px;cursor:pointer;color:#888;">&times;</button>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:25px;">
                        <div style="background:#f8fafc;padding:15px;border-radius:8px;border:1px solid #e2e8f0;">
                            <h4 style="margin-top:0;color:var(--secondary);border-bottom:1px solid #cbd5e1;padding-bottom:5px;">Customer Info</h4>
                            <p style="margin:5px 0;"><strong>Name:</strong> <span id="mod_cname"></span></p>
                            <p style="margin:5px 0;"><strong>Email:</strong> <span id="mod_cemail"></span></p>
                            <p style="margin:5px 0;"><strong>Phone:</strong> <span id="mod_cphone"></span></p>
                            <p style="margin:5px 0;"><strong>Address:</strong> <span id="mod_caddr"></span>, <span id="mod_ccity"></span></p>
                        </div>
                        <div style="background:#f8fafc;padding:15px;border-radius:8px;border:1px solid #e2e8f0;">
                            <h4 style="margin-top:0;color:var(--secondary);border-bottom:1px solid #cbd5e1;padding-bottom:5px;">Payment Info</h4>
                            <p style="margin:5px 0;"><strong>Method:</strong> <span id="mod_pmethod"></span></p>
                            <p style="margin:5px 0;"><strong>Status:</strong> <span id="mod_pstatus"></span></p>
                            <p style="margin:5px 0;"><strong>Date:</strong> <span id="mod_pdate"></span></p>
                            <p style="margin:5px 0;"><strong>Order Status:</strong> <span id="mod_ostatus" style="font-weight:bold;"></span></p>
                        </div>
                    </div>

                    <h4 style="margin:0 0 10px 0;color:var(--dark);">Ordered Items</h4>
                    <table style="width:100%;border-collapse:collapse;margin-bottom:20px;">
                        <thead style="background:#f1f5f9;">
                            <tr>
                                <th style="padding:10px;text-align:left;border-bottom:2px solid #cbd5e1;">Product</th>
                                <th style="padding:10px;text-align:center;border-bottom:2px solid #cbd5e1;">Qty</th>
                                <th style="padding:10px;text-align:right;border-bottom:2px solid #cbd5e1;">Unit Price (LKR)</th>
                                <th style="padding:10px;text-align:right;border-bottom:2px solid #cbd5e1;">Subtotal (LKR)</th>
                            </tr>
                        </thead>
                        <tbody id="mod_items_body">
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" style="padding:10px;text-align:right;font-weight:bold;font-size:16px;">Total Amount:</td>
                                <td style="padding:10px;text-align:right;font-weight:bold;font-size:18px;color:var(--secondary);">LKR <span id="mod_total"></span></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            
        <?php } ?>
    </div>
</main>

<script src="assets/js/sales_dashboard.js"></script>
<script src="assets/js/notes_board.js"></script>
<script>
function togglePanel(bodyId, btn) {
    const body = document.getElementById(bodyId);
    if (!body) return;
    const isOpen = body.classList.contains('open');
    body.classList.toggle('open', !isOpen);
    if (btn) {
        btn.classList.toggle('expanded', !isOpen);
        const textEl = btn.querySelector('.toggle-text');
        if (textEl) textEl.textContent = isOpen ? 'Expand' : 'Collapse';
    }
}
</script>
</body>
</html>