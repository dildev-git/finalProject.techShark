<?php
/**
 * =============================================================================
 * ajax_cart.php — Shopping Cart AJAX Handler
 * =============================================================================
 * Handles all cart-related AJAX requests from product listing and detail pages.
 *
 * Supported actions (passed via ?action=):
 *   count  — Returns the total number of items currently in the logged-in
 *             customer's cart as JSON.
 *   add    — Adds a product to the cart (POST). If the item already exists,
 *             its quantity is incremented instead of inserting a duplicate.
 *
 * All responses are JSON: { success, message, cart_count }
 * Only accessible by logged-in Customers.
 * =============================================================================
 */

session_start();
include('includes/dbconnection.php');

// All responses must be JSON
header('Content-Type: application/json');

// Default response structure
$response = ['success' => false, 'message' => '', 'cart_count' => 0];

// ── Auth guard — only logged-in Customers may use this endpoint ──────────────
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Customer') {
    $response['message'] = 'not_logged_in';
    echo json_encode($response);
    exit;
}

$customer_id = $_SESSION['user_id'];
$action      = isset($_GET['action']) ? $_GET['action'] : '';

// ── ACTION: count ─────────────────────────────────────────────────────────────
// Returns the SUM of quantities across all cart rows for this customer.
if ($action === 'count') {
    $sql = "SELECT SUM(quantity) as total_qty FROM Cart WHERE customerID = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $customer_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        $count = $row['total_qty'] ? (int)$row['total_qty'] : 0;

        $response['success']    = true;
        $response['cart_count'] = $count;
        mysqli_stmt_close($stmt);
    }
    echo json_encode($response);
    exit;
}

// ── ACTION: add ───────────────────────────────────────────────────────────────
// Adds one unit of a product to the cart, or increments if already present.
if ($action === 'add') {
    // Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response['message'] = 'Invalid request method';
        echo json_encode($response);
        exit;
    }

    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity   = isset($_POST['quantity'])   ? (int)$_POST['quantity']   : 1;

    if ($product_id <= 0) {
        $response['message'] = 'Invalid product ID';
        echo json_encode($response);
        exit;
    }

    // Check whether this product is already in the customer's cart
    $check_sql = "SELECT cartID, quantity FROM Cart WHERE customerID = ? AND productID = ?";
    if ($stmt = mysqli_prepare($conn, $check_sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $customer_id, $product_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($res) > 0) {
            // Product already in cart — increment quantity
            $row     = mysqli_fetch_assoc($res);
            $new_qty = $row['quantity'] + $quantity;
            $cart_id = $row['cartID'];

            $update_sql = "UPDATE Cart SET quantity = ? WHERE cartID = ?";
            if ($up_stmt = mysqli_prepare($conn, $update_sql)) {
                mysqli_stmt_bind_param($up_stmt, "ii", $new_qty, $cart_id);
                mysqli_stmt_execute($up_stmt);
                mysqli_stmt_close($up_stmt);
            }
        } else {
            // Product not in cart — insert new row
            $insert_sql = "INSERT INTO Cart (customerID, productID, quantity, addedDate) VALUES (?, ?, ?, NOW())";
            if ($in_stmt = mysqli_prepare($conn, $insert_sql)) {
                mysqli_stmt_bind_param($in_stmt, "iii", $customer_id, $product_id, $quantity);
                mysqli_stmt_execute($in_stmt);
                mysqli_stmt_close($in_stmt);
            }
        }
        mysqli_stmt_close($stmt);
    }

    // Re-fetch the updated cart count to return to the client
    $sql = "SELECT SUM(quantity) as total_qty FROM Cart WHERE customerID = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $customer_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        $count = $row['total_qty'] ? (int)$row['total_qty'] : 0;

        $response['success']    = true;
        $response['message']    = 'Added to cart successfully';
        $response['cart_count'] = $count;
        mysqli_stmt_close($stmt);
    }

    echo json_encode($response);
    exit;
}

// ── Fallback — unknown action ─────────────────────────────────────────────────
$response['message'] = 'Invalid action';
echo json_encode($response);
?>
