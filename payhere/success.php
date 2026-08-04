<?php
/**
 * After the payment is successful through PayHere, the customer comes here.
 * Only here the Order is saved in the DB as 'Pending' and the Payment as 'Completed'.
 */

session_start();
require_once '../includes/dbconnection.php';
require_once 'config.php';

/** @var mysqli $conn Provided by dbconnection.php */

// ── Auth guard ─────────────────────────────────────────────────────────────────
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Customer') {
    header('Location: ../login.php');
    exit;
}

$customer_id = (int) $_SESSION['user_id'];
$is_paid = false;
$order_id = 0;

// ── The moment you successfully made a payment through PayHere ──────────────────────────
if (isset($_SESSION['temp_checkout'])) {
    
    // Getting cart details to save
    $cart_items = [];
    $total = 0.00;
    $stmt = mysqli_prepare($conn, 'SELECT c.quantity, p.productID, p.price, p.productName FROM Cart c JOIN Product p ON c.productID = p.productID WHERE c.customerID = ?');
    mysqli_stmt_bind_param($stmt, 'i', $customer_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $cart_items[] = $row;
        $total += ($row['price'] * $row['quantity']);
    }
    mysqli_stmt_close($stmt);

    if (!empty($cart_items)) {
        mysqli_begin_transaction($conn);
        try {
            $now = date('Y-m-d H:i:s');
            
            // 1. Saving the order -> Status: 'Pending' (Until the Sales Rep changes it)
            $stmt = mysqli_prepare($conn, "INSERT INTO `Order` (orderDate, totalAmount, status, customerID) VALUES (?, ?, 'Pending', ?)");
            mysqli_stmt_bind_param($stmt, 'sdi', $now, $total, $customer_id);
            mysqli_stmt_execute($stmt);
            $order_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            // 2. Saving Order Details and Reducing Product Stock
            $stmt_od = mysqli_prepare($conn, "INSERT INTO Order_Details (quantity, unitPrice, orderID, productID) VALUES (?, ?, ?, ?)");
            $stmt_stock = mysqli_prepare($conn, "UPDATE Product SET quantity_in_stock = GREATEST(0, quantity_in_stock - ?) WHERE productID = ?");
            
            foreach ($cart_items as $item) {
                mysqli_stmt_bind_param($stmt_od, 'idii', $item['quantity'], $item['price'], $order_id, $item['productID']);
                mysqli_stmt_execute($stmt_od);
                
                mysqli_stmt_bind_param($stmt_stock, 'ii', $item['quantity'], $item['productID']);
                mysqli_stmt_execute($stmt_stock);
            }
            mysqli_stmt_close($stmt_od);
            mysqli_stmt_close($stmt_stock);

            // 3. Saving payment details -> Status: 'Paid'
            $stmt = mysqli_prepare($conn, "INSERT INTO Payment (orderID, paymentDate, amount, method, status) VALUES (?, ?, ?, 'PayHere', 'Paid')");
            mysqli_stmt_bind_param($stmt, 'isd', $order_id, $now, $total);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // 4. Creating an in-system notification for the customer
            $notif_msg = "Payment for Order #ORD-" . str_pad($order_id, 4, '0', STR_PAD_LEFT) . " was successful. LKR " . number_format($total, 2);
            $notif_type = 'Payment Confirmation';
            $stmt = mysqli_prepare($conn, "INSERT INTO Notification (message, type, date, customerID, is_read) VALUES (?, ?, ?, ?, 0)");
            mysqli_stmt_bind_param($stmt, 'sssi', $notif_msg, $notif_type, $now, $customer_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // 5. Deleting the cart
            $stmt = mysqli_prepare($conn, "DELETE FROM Cart WHERE customerID = ?");
            mysqli_stmt_bind_param($stmt, 'i', $customer_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            mysqli_commit($conn);
            unset($_SESSION['temp_checkout']); // To prevent saving the same order twice
            
            // Fix: Implement PRG (Post/Redirect/Get) pattern
            // By redirecting here, we ensure the URL gets the ?order_id parameter.
            // This prevents the page from silently redirecting to the cart if the user reloads
            // or if the browser loses the temporary POST state.
            header("Location: success.php?order_id=" . $order_id);
            exit;
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            die('Error saving order: ' . htmlspecialchars($e->getMessage()));
        }
    }
} else {
    // ── If you came to view a successfully saved payment ─────────────────────
    $order_id = (int) ($_GET['order_id'] ?? 0);
    if ($order_id > 0) {
        $is_paid = true;
    }
}

// If no Order ID, go back to Cart
if ($order_id <= 0) {
    header('Location: ../cart.php');
    exit;
}

// ── Fetching the relevant Order Data from the Database to display in the UI ─────────────────────
$stmt = mysqli_prepare($conn, 'SELECT o.orderID, o.orderDate, o.totalAmount, o.status, c.fullName, c.email FROM `Order` o JOIN Customer c ON o.customerID = c.customerID WHERE o.orderID = ? AND o.customerID = ?');
mysqli_stmt_bind_param($stmt, 'ii', $order_id, $customer_id);
mysqli_stmt_execute($stmt);
$order = mysqli_stmt_get_result($stmt)->fetch_assoc();
mysqli_stmt_close($stmt);

if (!$order) {
    header('Location: ../cart.php');
    exit;
}

$stmt = mysqli_prepare($conn, 'SELECT od.quantity, od.unitPrice, p.productName, p.productImage FROM Order_Details od JOIN Product p ON od.productID = p.productID WHERE od.orderID = ?');
mysqli_stmt_bind_param($stmt, 'i', $order_id);
mysqli_stmt_execute($stmt);
$items = mysqli_stmt_get_result($stmt)->fetch_all(MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$order_ref    = '#ORD-' . str_pad($order_id, 4, '0', STR_PAD_LEFT);
$status_label = $order['status']; // Initially this will be 'Pending'
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful – Tech Shark</title>
    <link rel="icon" type="image/png" href="../assets/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 16px;
        }
        .card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 25px 60px rgba(0,0,0,.35);
            width: 100%;
            max-width: 680px;
            overflow: hidden;
        }
        .banner {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            padding: 48px 40px 40px;
            text-align: center;
            color: #fff;
        }
        .check-circle {
            width: 80px; height: 80px;
            background: rgba(255,255,255,.2);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
            font-size: 36px;
        }
        .banner h1 { font-size: 28px; font-weight: 800; margin-bottom: 6px; }
        .banner p  { font-size: 15px; opacity: .9; }

        .body { padding: 36px 40px; }

        .order-meta {
            display: flex; gap: 16px; flex-wrap: wrap;
            background: #f8fafc;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 28px;
        }
        .meta-item { flex: 1; min-width: 140px; }
        .meta-label { font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; }
        .meta-value { font-size: 15px; font-weight: 700; color: #1e293b; }

        .items-title { font-size: 14px; font-weight: 700; color: #475569; margin-bottom: 12px; text-transform: uppercase; letter-spacing: .5px; }
        .item-row {
            display: flex; align-items: center; gap: 14px;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .item-row:last-child { border-bottom: none; }
        .item-img {
            width: 52px; height: 52px;
            object-fit: cover; border-radius: 8px;
            background: #e2e8f0;
        }
        .item-name { font-weight: 600; font-size: 14px; color: #1e293b; }
        .item-sub  { font-size: 12px; color: #64748b; margin-top: 2px; }
        .item-price{ margin-left: auto; font-weight: 700; font-size: 14px; color: #059669; white-space: nowrap; }

        .total-row {
            display: flex; justify-content: space-between;
            padding: 16px 0 0;
            border-top: 2px solid #e2e8f0;
            margin-top: 12px;
            font-weight: 800; font-size: 18px; color: #0f172a;
        }

        .actions { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 30px; }
        .btn {
            flex: 1; min-width: 160px;
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            padding: 14px 20px;
            border-radius: 10px;
            font-size: 14px; font-weight: 600;
            text-decoration: none;
            border: none; cursor: pointer;
            transition: transform .15s, box-shadow .15s;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,.15); }
        .btn-primary { background: linear-gradient(135deg, #059669, #10b981); color: #fff; }
        .btn-outline  { background: #fff; color: #1e293b; border: 2px solid #e2e8f0; }
        .btn-blue     { background: linear-gradient(135deg, #2563eb, #3b82f6); color: #fff; }

        .badge {
            display: inline-block; padding: 4px 10px;
            border-radius: 20px; font-size: 12px; font-weight: 700;
        }
        /* The badge color turns yellow due to the Order Status 'Pending'. */
        .badge-status { background: #fef3c7; color: #92400e; }
    </style>
</head>
<body>
<div class="card">

    <div class="banner">
        <div class="check-circle">
            <i class="fas fa-check"></i>
        </div>
        <h1>Payment Successful!</h1>
        <p>Your payment is completed. Your order is now pending for processing.</p>
    </div>

    <div class="body">

        <div class="order-meta">
            <div class="meta-item">
                <div class="meta-label">Order ID</div>
                <div class="meta-value"><?= $order_ref ?></div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Date</div>
                <div class="meta-value"><?= date('d M Y', strtotime($order['orderDate'])) ?></div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Customer</div>
                <div class="meta-value"><?= htmlspecialchars($order['fullName']) ?></div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Order Status</div>
                <div class="meta-value">
                    <span class="badge badge-status">
                        <?= htmlspecialchars($status_label) ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="items-title">Items Ordered</div>
        <?php foreach ($items as $item): ?>
        <div class="item-row">
            <img class="item-img"
                 src="../assets/products/<?= htmlspecialchars($item['productImage'] ?: 'default.jpg') ?>"
                 alt="<?= htmlspecialchars($item['productName']) ?>">
            <div>
                <div class="item-name"><?= htmlspecialchars($item['productName']) ?></div>
                <div class="item-sub">Qty: <?= $item['quantity'] ?> &times; LKR <?= number_format($item['unitPrice'], 2) ?></div>
            </div>
            <div class="item-price">LKR <?= number_format($item['unitPrice'] * $item['quantity'], 2) ?></div>
        </div>
        <?php endforeach; ?>

        <div class="total-row">
            <span>Total Paid</span>
            <span>LKR <?= number_format($order['totalAmount'], 2) ?></span>
        </div>

        <div class="actions">
            <?php if ($is_paid): ?>
            <a href="invoice.php?order_id=<?= $order_id ?>" class="btn btn-primary" target="_blank">
                <i class="fas fa-file-pdf"></i> Download Invoice
            </a>
            <?php endif; ?>
            <a href="../profile.php?tab=orders" class="btn btn-outline">
                <i class="fas fa-list-ul"></i> My Orders
            </a>
            <a href="../Index.php" class="btn btn-blue">
                <i class="fas fa-store"></i> Continue Shopping
            </a>
        </div>

    </div></div></body>
</html>