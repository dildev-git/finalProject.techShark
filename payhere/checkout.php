<?php
/**
 * Nothing from this page is saved to the database.
 * This only creates a temporary Order ID and sends the data to PayHere.
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

// ── Taking customer details to fill out the PayHere form ───────────────────────────────
$stmt = mysqli_prepare($conn, 'SELECT fullName, email, contactNo, address, city FROM Customer WHERE customerID = ?');
mysqli_stmt_bind_param($stmt, 'i', $customer_id);
mysqli_stmt_execute($stmt);
$customer = mysqli_stmt_get_result($stmt)->fetch_assoc();
mysqli_stmt_close($stmt);

if (!$customer) {
    die('Customer not found.');
}

// ── Get cart details and total amount ─────────────────────────────────────────
$cart_items = [];
$total      = 0.00;

$stmt = mysqli_prepare($conn,
    'SELECT c.quantity, p.productID, p.productName, p.price, p.quantity_in_stock
     FROM Cart c
     JOIN Product p ON c.productID = p.productID
     WHERE c.customerID = ?');
mysqli_stmt_bind_param($stmt, 'i', $customer_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($res)) {
    // Checking if there are more items in the cart than in stock
    if ($row['quantity'] > $row['quantity_in_stock']) {
        header('Location: ../cart.php?err=stock');
        exit;
    }
    $cart_items[] = $row;
    $total += ($row['price'] * $row['quantity']);
}
mysqli_stmt_close($stmt);

if (empty($cart_items)) {
    header('Location: ../cart.php');
    exit;
}

// ── Creating a temporary Order ID without saving it to the database ─────────────────────────────
// Using time() always generates a new ID.
$temp_order_id = time() . rand(10, 99); 

// Set a session variable to only save when the money is paid
$_SESSION['temp_checkout'] = true; 

// ── Creating PayHere Security Hash ────────────────────────────────────────────
// Formula: md5(merchant_id + order_id + amount_formatted + currency + md5(strtoupper(merchant_secret)))
$amount_formatted = number_format($total, 2, '.', ''); // Ex: "449999.00"

$hash = strtoupper(md5(
    PAYHERE_MERCHANT_ID .
    $temp_order_id      .
    $amount_formatted   .
    PAYHERE_CURRENCY    .
    strtoupper(md5(PAYHERE_SECRET))
));

// ── Splitting the customer's name into two parts (for PayHere) ────────────────────
$name_parts = explode(' ', trim($customer['fullName']), 2);
$first_name = $name_parts[0];
$last_name  = $name_parts[1] ?? '';

// Combining the item names into one line
$item_desc = implode(', ', array_map(fn($i) => $i['productName'], $cart_items));
$item_desc = mb_substr($item_desc, 0, 255); // Limit to 255 characters
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Redirecting to PayHere…</title>
    <link rel="icon" type="image/png" href="../assets/logo.png">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; background: #f8fafc; }
        .box { text-align: center; background: #fff; padding: 40px 60px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; }
        .spinner { width: 50px; height: 50px; border: 4px solid #e2e8f0; border-top-color: #2563eb; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 24px; }
        @keyframes spin { to { transform: rotate(360deg); } }
        h2 { margin: 0 0 10px; color: #1e293b; font-size: 22px; }
        p  { color: #64748b; margin: 0; font-size: 15px; }
        .brand { margin-bottom: 20px; font-weight: bold; color: #2563eb; font-size: 18px; letter-spacing: 1px; }
    </style>
</head>
<body>
<div class="box">
    <div class="brand">TECH SHARK SECURE CHECKOUT</div>
    <div class="spinner"></div>
    <h2>Connecting to PayHere…</h2>
    <p>Please wait, you are being securely redirected.</p>
</div>

<form id="payhere-form" method="POST" action="<?= PAYHERE_CHECKOUT_URL ?>">
    <input type="hidden" name="merchant_id"    value="<?= PAYHERE_MERCHANT_ID ?>">
    <input type="hidden" name="return_url"     value="<?= RETURN_URL ?>">
    <input type="hidden" name="cancel_url"     value="<?= CANCEL_URL ?>">
    <input type="hidden" name="notify_url"     value="<?= NOTIFY_URL ?>">

    <input type="hidden" name="order_id"       value="<?= $temp_order_id ?>">
    <input type="hidden" name="items"          value="<?= htmlspecialchars($item_desc) ?>">
    <input type="hidden" name="currency"       value="<?= PAYHERE_CURRENCY ?>">
    <input type="hidden" name="amount"         value="<?= $amount_formatted ?>">

    <input type="hidden" name="first_name"     value="<?= htmlspecialchars($first_name) ?>">
    <input type="hidden" name="last_name"      value="<?= htmlspecialchars($last_name) ?>">
    <input type="hidden" name="email"          value="<?= htmlspecialchars($customer['email']) ?>">
    <input type="hidden" name="phone"          value="<?= htmlspecialchars($customer['contactNo'] ?? '') ?>">
    <input type="hidden" name="address"        value="<?= htmlspecialchars($customer['address'] ?? '') ?>">
    <input type="hidden" name="city"           value="<?= htmlspecialchars($customer['city'] ?? '') ?>">
    <input type="hidden" name="country"        value="Sri Lanka">

    <input type="hidden" name="hash"           value="<?= $hash ?>">
</form>

<script>
    // Redirect to PayHere automatically after 1 second
    setTimeout(() => {
        document.getElementById('payhere-form').submit();
    }, 1000);
</script>
</body>
</html>