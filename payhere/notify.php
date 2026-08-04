<?php
/**
 * notify.php – Chapter 2 & 3: The Background Whisper + Database Automation
 *
 * PayHere calls this endpoint asynchronously (server-to-server POST)
 * after every payment event. This script:
 *
 *  1. Validates the md5_sig to ensure the request is genuinely from PayHere.
 *  2. Queries our DB to verify the amount matches exactly (anti-tampering).
 *  3. On successful payment (status_code == 2):
 *       a) Updates Order status → 'Paid'
 *       b) Updates Payment status → 'Completed'
 *       c) Deducts purchased quantities from Product stock
 *       d) Clears the customer's Cart
 *       e) Inserts a Notification record
 *
 * This endpoint must be reachable from the Internet (PayHere's servers).
 * On localhost/sandbox, PayHere cannot reach it — that is expected behaviour
 * for local dev. Use ngrok or a live server for end-to-end testing.
 *
 * DO NOT output anything from this file — PayHere expects an empty 200 response.
 */

require_once __DIR__ . '/../includes/dbconnection.php';
require_once __DIR__ . '/config.php';

global $conn;

// ── Log helper (writes to payhere/logs/notify.log) ────────────────────────────
function ph_log(string $msg): void
{
    $dir = __DIR__ . '/logs';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents(
        $dir . '/notify.log',
        '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}

// ── Only accept POST ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ph_log('Non-POST request rejected');
    http_response_code(405);
    exit;
}

// ── Collect PayHere POST fields ────────────────────────────────────────────────
$merchant_id      = $_POST['merchant_id']    ?? '';
$order_id         = (int) ($_POST['order_id'] ?? 0);
$payhere_amount   = $_POST['payhere_amount'] ?? '';
$payhere_currency = $_POST['payhere_currency'] ?? '';
$status_code      = (int) ($_POST['status_code'] ?? 0);
$md5_sig          = $_POST['md5sig']         ?? '';
$payment_id       = $_POST['payment_id']     ?? '';
$method           = $_POST['method']         ?? 'PayHere';
$card_holder_name = $_POST['card_holder_name'] ?? '';

ph_log("Received: order_id={$order_id} status_code={$status_code} amount={$payhere_amount}");

// ── Step 1: Verify merchant ID ────────────────────────────────────────────────
if ($merchant_id !== PAYHERE_MERCHANT_ID) {
    ph_log('FAIL: merchant_id mismatch');
    http_response_code(400);
    exit;
}

// ── Step 2: Verify the md5_sig hash ───────────────────────────────────────────
// Formula: md5(merchant_id + order_id + payhere_amount + payhere_currency + status_code + md5(strtoupper(merchant_secret)))
$local_sig = strtoupper(md5(
    PAYHERE_MERCHANT_ID  .
    $order_id            .
    $payhere_amount      .
    $payhere_currency    .
    $status_code         .
    strtoupper(md5(PAYHERE_SECRET))
));

if ($local_sig !== strtoupper($md5_sig)) {
    ph_log("FAIL: md5_sig mismatch. Expected={$local_sig} Got={$md5_sig}");
    http_response_code(400);
    exit;
}

ph_log('Hash verification PASSED');

// ── Step 3: Fetch the original order from DB (price-tampering check) ──────────
$stmt = mysqli_prepare($conn,
    'SELECT o.orderID, o.totalAmount, o.customerID, o.status
     FROM `Order` o
     WHERE o.orderID = ?');
mysqli_stmt_bind_param($stmt, 'i', $order_id);
mysqli_stmt_execute($stmt);
$order = mysqli_stmt_get_result($stmt)->fetch_assoc();
mysqli_stmt_close($stmt);

if (!$order) {
    ph_log("FAIL: Order #{$order_id} not found in DB");
    http_response_code(400);
    exit;
}

// Compare amounts to 2 decimal places to prevent price-tampering attacks
$db_amount       = number_format((float) $order['totalAmount'], 2, '.', '');
$received_amount = number_format((float) $payhere_amount,      2, '.', '');

if ($db_amount !== $received_amount) {
    ph_log("FAIL: Amount mismatch! DB={$db_amount} PayHere={$received_amount}");
    http_response_code(400);
    exit;
}

ph_log("Amount check PASSED: {$db_amount}");

// ── Step 4: Handle payment status ─────────────────────────────────────────────
/*
 * PayHere status codes:
 *   2  = Successful payment
 *   0  = Pending
 *  -1  = Cancelled
 *  -2  = Failed
 *  -3  = Chargedback
 */

if ($status_code == 2) {
    // Payment successful — avoid processing the same order twice
    if ($order['status'] === 'Paid') {
        ph_log("SKIP: Order #{$order_id} already marked Paid");
        http_response_code(200);
        exit;
    }

    $customer_id = (int) $order['customerID'];

    mysqli_begin_transaction($conn);
    try {

        // ── 4a. Update Order status → 'Paid' ──────────────────────────────────
        $stmt = mysqli_prepare($conn,
            'UPDATE `Order` SET status = ? WHERE orderID = ?');
        $paid = 'Paid';
        mysqli_stmt_bind_param($stmt, 'si', $paid, $order_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // ── 4b. Update Payment record (method, status, payment_id) ────────────
        $now = date('Y-m-d H:i:s');
        $stmt = mysqli_prepare($conn,
            'UPDATE Payment SET status = ?, method = ?, paymentDate = ?
             WHERE orderID = ?');
        $completed = 'Completed';
        mysqli_stmt_bind_param($stmt, 'sssi', $completed, $method, $now, $order_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // ── 4c. Deduct quantities from Product stock ───────────────────────────
        $stmt = mysqli_prepare($conn,
            'SELECT productID, quantity FROM Order_Details WHERE orderID = ?');
        mysqli_stmt_bind_param($stmt, 'i', $order_id);
        mysqli_stmt_execute($stmt);
        $items = mysqli_stmt_get_result($stmt)->fetch_all(MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);

        $stock_stmt = mysqli_prepare($conn,
            'UPDATE Product SET quantity_in_stock = GREATEST(0, quantity_in_stock - ?)
             WHERE productID = ?');
        foreach ($items as $item) {
            mysqli_stmt_bind_param($stock_stmt, 'ii', $item['quantity'], $item['productID']);
            mysqli_stmt_execute($stock_stmt);
        }
        mysqli_stmt_close($stock_stmt);

        // ── 4d. Clear customer's Cart ──────────────────────────────────────────
        $stmt = mysqli_prepare($conn, 'DELETE FROM Cart WHERE customerID = ?');
        mysqli_stmt_bind_param($stmt, 'i', $customer_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // ── 4e. Insert in-app Notification ────────────────────────────────────
        $notif_msg  = "Payment for Order #ORD-" . str_pad($order_id, 4, '0', STR_PAD_LEFT) . " was successful. LKR " . number_format($order['totalAmount'], 2);
        $notif_type = 'Payment Confirmation';
        $is_read    = 0;

        $stmt = mysqli_prepare($conn,
            'INSERT INTO Notification (message, type, date, customerID) VALUES (?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'sssi', $notif_msg, $notif_type, $now, $customer_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        mysqli_commit($conn);
        ph_log("SUCCESS: Order #{$order_id} fully processed");

    } catch (Exception $e) {
        mysqli_rollback($conn);
        ph_log('EXCEPTION: ' . $e->getMessage());
        http_response_code(500);
        exit;
    }

} elseif ($status_code == 0) {
    ph_log("PENDING: Order #{$order_id} payment pending");
} elseif ($status_code == -1) {
    ph_log("CANCELLED: Order #{$order_id}");
} elseif ($status_code == -2) {
    ph_log("FAILED: Order #{$order_id}");
} elseif ($status_code == -3) {
    ph_log("CHARGEDBACK: Order #{$order_id}");
}

// PayHere expects HTTP 200 with an empty body
http_response_code(200);
