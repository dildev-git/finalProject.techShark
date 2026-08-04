<?php
session_start();
include('includes/dbconnection.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Customer') {
    header("Location: login.php");
    exit;
}

$customer_id = $_SESSION['user_id'];
$msg = '';
$msg_type = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['remove_item'])) {
        $cart_id = (int)$_POST['cart_id'];
        $sql = "DELETE FROM Cart WHERE cartID = ? AND customerID = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $cart_id, $customer_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    } elseif (isset($_POST['update_qty'])) {
        $cart_id = (int)$_POST['cart_id'];
        $qty = (int)$_POST['quantity'];
        if ($qty > 0) {
            $sql = "UPDATE Cart SET quantity = ? WHERE cartID = ? AND customerID = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "iii", $qty, $cart_id, $customer_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }
    } elseif (isset($_POST['checkout'])) {
        $payment_method = $_POST['payment_method'];

        // Calculate total
        $total = 0;
        $items = [];
        $sql = "SELECT c.*, p.price, p.quantity_in_stock FROM Cart c JOIN Product p ON c.productID = p.productID WHERE c.customerID = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $customer_id);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            while($row = mysqli_fetch_assoc($res)) {
                $total += ($row['price'] * $row['quantity']);
                $items[] = $row;
            }
            mysqli_stmt_close($stmt);
        }

        if (count($items) > 0) {
            mysqli_begin_transaction($conn);
            try {
                // 1. Create Order
                $orderDate = date('Y-m-d H:i:s');
                $status = 'Pending';
                $order_sql = "INSERT INTO `Order` (orderDate, totalAmount, status, customerID) VALUES (?, ?, ?, ?)";
                $stmt1 = mysqli_prepare($conn, $order_sql);
                mysqli_stmt_bind_param($stmt1, "sdsi", $orderDate, $total, $status, $customer_id);
                mysqli_stmt_execute($stmt1);
                $order_id = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt1);

                // 2. Insert Order Details & Update Stock
                $detail_sql = "INSERT INTO Order_Details (quantity, unitPrice, orderID, productID) VALUES (?, ?, ?, ?)";
                $stock_sql = "UPDATE Product SET quantity_in_stock = quantity_in_stock - ? WHERE productID = ?";
                $stmt2 = mysqli_prepare($conn, $detail_sql);
                $stmt3 = mysqli_prepare($conn, $stock_sql);

                foreach($items as $item) {
                    mysqli_stmt_bind_param($stmt2, "idii", $item['quantity'], $item['price'], $order_id, $item['productID']);
                    mysqli_stmt_execute($stmt2);

                    mysqli_stmt_bind_param($stmt3, "ii", $item['quantity'], $item['productID']);
                    mysqli_stmt_execute($stmt3);
                }
                mysqli_stmt_close($stmt2);
                mysqli_stmt_close($stmt3);

                // 3. Create Payment
                $payStatus = ($payment_method == 'Credit Card' || $payment_method == 'Debit Card') ? 'Completed' : 'Pending';
                $payment_sql = "INSERT INTO Payment (orderID, paymentDate, amount, method, status) VALUES (?, ?, ?, ?, ?)";
                $stmt4 = mysqli_prepare($conn, $payment_sql);
                mysqli_stmt_bind_param($stmt4, "isdss", $order_id, $orderDate, $total, $payment_method, $payStatus);
                mysqli_stmt_execute($stmt4);
                mysqli_stmt_close($stmt4);

                // 4. Clear Cart
                $clear_sql = "DELETE FROM Cart WHERE customerID = ?";
                $stmt5 = mysqli_prepare($conn, $clear_sql);
                mysqli_stmt_bind_param($stmt5, "i", $customer_id);
                mysqli_stmt_execute($stmt5);
                mysqli_stmt_close($stmt5);

                mysqli_commit($conn);
                
                header("Location: profile.php?tab=orders&msg=Order+Placed");
                exit;

            } catch (Exception $e) {
                mysqli_rollback($conn);
                $msg = "Error processing checkout: " . $e->getMessage();
                $msg_type = "danger";
            }
        } else {
            $msg = "Your cart is empty.";
            $msg_type = "danger";
        }
    }
}

// Fetch Cart Data
$cart_items = [];
$subtotal = 0;
$total_qty = 0;
$sql = "SELECT c.cartID, c.quantity, p.productID, p.productName, p.price, p.productImage, p.quantity_in_stock 
        FROM Cart c 
        JOIN Product p ON c.productID = p.productID 
        WHERE c.customerID = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $customer_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while($row = mysqli_fetch_assoc($res)) {
        $cart_items[] = $row;
        $subtotal += ($row['price'] * $row['quantity']);
        $total_qty += $row['quantity'];
    }
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Tech Shark</title>
    <link rel="icon" type="image/png" href="assets/logo.png"/>
    <link rel="stylesheet" href="includes/css/customer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .cart-wrapper { display: flex; gap: 30px; margin: 40px auto; align-items: flex-start; }
        .cart-items-section { flex: 2; background: white; padding: 30px; border-radius: 8px; box-shadow: var(--shadow-sm); }
        .cart-summary { flex: 1; background: white; padding: 30px; border-radius: 8px; box-shadow: var(--shadow-sm); position: sticky; top: 100px; }
        .cart-table { width: 100%; border-collapse: collapse; }
        .cart-table th, .cart-table td { padding: 15px 0; border-bottom: 1px solid var(--light-gray); text-align: left; }
        .cart-item-row { display: flex; align-items: center; gap: 15px; }
        .cart-item-row img { width: 60px; height: 60px; object-fit: cover; border-radius: 4px; }
        .cart-actions { display: flex; align-items: center; gap: 10px; }
        .qty-input { width: 60px; text-align: center; border: 1px solid var(--medium-gray); padding: 5px; border-radius: 4px;}
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 15px; }
        .total-row { border-top: 1px solid var(--medium-gray); padding-top: 15px; font-weight: 700; font-size: 20px; }
        .payment-method { width: 100%; padding: 10px; margin-bottom: 20px; border: 1px solid var(--medium-gray); border-radius: 4px; }
    </style>
</head>
<body>
    <?php include 'includes/customer_header.php'; ?>

    <div class="container">
        <h1 class="section-title" style="margin-top: 40px;">Shopping Cart</h1>
        <?php if($msg): ?>
            <div style="color: red; padding: 10px; background: #fee2e2; border-radius: 4px; margin-bottom: 20px;"><?php echo $msg; ?></div>
        <?php endif; ?>

        <?php if(empty($cart_items)): ?>
            <div style="text-align:center; padding: 50px; background:white; border-radius:8px;">
                <i class="fas fa-shopping-cart" style="font-size: 48px; color:var(--medium-gray); margin-bottom: 20px;"></i>
                <h2>Your cart is empty.</h2>
                <a href="index.php" class="btn" style="margin-top: 20px;">Continue Shopping</a>
            </div>
        <?php else: ?>
        <div class="cart-wrapper">
            <div class="cart-items-section">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($cart_items as $item): ?>
                        <tr>
                            <td>
                                <div class="cart-item-row">
                                    <img src="assets/products/<?php echo $item['productImage'] ?: 'default.jpg'; ?>">
                                    <a href="product_details.php?id=<?php echo $item['productID']; ?>" style="color:inherit; text-decoration:none; font-weight:500;">
                                        <?php echo htmlspecialchars($item['productName']); ?>
                                    </a>
                                </div>
                            </td>
                            <td>LKR <?php echo number_format($item['price'], 2); ?></td>
                            <td>
                                <div class="cart-actions">
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="cart_id" value="<?php echo $item['cartID']; ?>">
                                        <input type="number" name="quantity" class="qty-input" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['quantity_in_stock']; ?>">
                                        <button type="submit" name="update_qty" style="background:none;border:none;cursor:pointer;color:var(--primary-color)"><i class="fas fa-sync-alt"></i></button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="cart_id" value="<?php echo $item['cartID']; ?>">
                                        <button type="submit" name="remove_item" style="background:none;border:none;cursor:pointer;color:var(--danger-color)"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                            <td style="font-weight:600;">LKR <?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="cart-summary">
                <h3>Order Summary</h3>
                <div class="summary-row" style="margin-top:20px;">
                    <span>Subtotal</span>
                    <span>LKR <?php echo number_format($subtotal, 2); ?></span>
                </div>
                <!-- Optional: Add Tax or Shipping here -->
                <div class="summary-row total-row">
                    <span>Total</span>
                    <span>LKR <?php echo number_format($subtotal, 2); ?></span>
                </div>

                <form method="POST" action="cart.php" style="margin-top: 30px;">
                    <a href="payhere/checkout.php"
                       id="payhere-checkout-btn"
                       style="display:flex;align-items:center;justify-content:center;gap:10px;
                              width:100%;padding:14px;border-radius:8px;
                              background:linear-gradient(135deg,#059669,#10b981);
                              color:#fff;font-weight:700;font-size:15px;
                              text-decoration:none;transition:opacity .2s;"
                       onmouseover="this.style.opacity='.88'"
                       onmouseout="this.style.opacity='1'">
                        <i class="fas fa-lock"></i> Pay Securely with PayHere
                    </a>
                    <p style="font-size:11px;color:#6b7280;text-align:center;margin-top:10px;">
                        <i class="fas fa-shield-alt"></i> 256-bit SSL encrypted &bull; Visa &bull; Mastercard &bull; eZCash
                    </p>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Search function
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('searchInput');
            const searchResults = document.getElementById('searchResults');

            // Live Search
            let timeout = null;
            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    clearTimeout(timeout);
                    const query = e.target.value.trim();

                    if (query.length < 2) {
                        searchResults.style.display = 'none';
                        return;
                    }

                    timeout = setTimeout(async () => {
                        try {
                            // Page identification and related category ID are required.
                            const currentPath = window.location.pathname;
                            let categoryParam = '';

                            if (currentPath.includes('laptops.php')) {
                                categoryParam = '&category=1';
                            } else if (currentPath.includes('desktops.php')) {
                                categoryParam = '&category=2';
                            } else if (currentPath.includes('components.php')) {
                                categoryParam = '&category=3'; // Components ID
                            } else if (currentPath.includes('accessories.php')) {
                                categoryParam = '&category=4'; // Accessories ID
                            } else if (currentPath.includes('audio.php')) {
                                categoryParam = '&category=5'; // Audio ID
                            } else if (currentPath.includes('storage.php')) {
                                categoryParam = '&category=6'; // Storage ID
                            }

                            // Fetch products based on the query AND category
                            const res = await fetch('api/search_products.php?q=' + encodeURIComponent(query) + categoryParam);
                            const items = await res.json();

                            searchResults.innerHTML = '';
                            if (items.length > 0) {
                                items.forEach(item => {
                                    const div = document.createElement('a');
                                    div.href = 'product_details.php?id=' + item.productID;
                                    div.style = 'display:flex; align-items:center; padding:10px; text-decoration:none; color:#1f2937; border-bottom:1px solid #f3f4f6;';

                                    // Image
                                    const img = document.createElement('img');
                                    img.src = 'assets/products/' + (item.productImage || 'default.jpg');
                                    img.style = 'width:40px; height:40px; object-fit:cover; border-radius:4px; margin-right:10px;';

                                    // Text Container
                                    const textDiv = document.createElement('div');
                                    textDiv.style = 'flex:1;';

                                    const title = document.createElement('div');
                                    title.style = 'font-weight:600; font-size:14px;';
                                    title.textContent = item.productName;

                                    const price = document.createElement('div');
                                    price.style = 'color:#2563eb; font-size:13px; font-weight:700;';
                                    price.textContent = 'LKR ' + parseFloat(item.price).toLocaleString('en-US', { minimumFractionDigits: 2 });

                                    textDiv.appendChild(title);
                                    textDiv.appendChild(price);

                                    div.appendChild(img);
                                    div.appendChild(textDiv);

                                    // Hover effect
                                    div.addEventListener('mouseover', () => div.style.backgroundColor = '#f9fafb');
                                    div.addEventListener('mouseout', () => div.style.backgroundColor = 'transparent');

                                    searchResults.appendChild(div);
                                });
                                searchResults.style.display = 'block';
                            } else {
                                searchResults.innerHTML = '<div style="padding:15px; color:#6b7280; text-align:center;">No products found in this category.</div>';
                                searchResults.style.display = 'block';
                            }
                        } catch (e) { console.error(e); }
                    }, 300); // 300ms debounce
                });

                // Close search when clicking outside
                document.addEventListener('click', (e) => {
                    if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                        searchResults.style.display = 'none';
                    }
                });
            }
        });
    </script>
</body>
</html>
