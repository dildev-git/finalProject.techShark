<?php
session_start();
include('includes/dbconnection.php');

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$msg = '';
$msg_type = '';

// Handle Add to Cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Customer') {
        header("Location: login.php");
        exit;
    }
    
    $customer_id = $_SESSION['user_id'];
    $qty = (int)$_POST['quantity'];
    
    // Get the current stock quantity of the product
    $stock_check_sql = "SELECT quantity_in_stock FROM Product WHERE productID = ?";
    $stmt_stock = mysqli_prepare($conn, $stock_check_sql);
    mysqli_stmt_bind_param($stmt_stock, "i", $product_id);
    mysqli_stmt_execute($stmt_stock);
    $res_stock = mysqli_stmt_get_result($stmt_stock);
    $product_data = mysqli_fetch_assoc($res_stock);
    $current_stock = $product_data['quantity_in_stock'];
    
    // Check if it's already in the cart
    $check_cart = "SELECT cartID, quantity FROM Cart WHERE customerID = ? AND productID = ?";
    if ($stmt = mysqli_prepare($conn, $check_cart)) {
        mysqli_stmt_bind_param($stmt, "ii", $customer_id, $product_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            // New total (current count + new count)
            $new_total_qty = $row['quantity'] + $qty;
            
            // Prevent if the total is more than the stock
            if ($new_total_qty > $current_stock) {
                $msg = "Cannot add more. Total in cart will exceed available stock!";
                $msg_type = "danger";
            } else {
                $update_sql = "UPDATE Cart SET quantity = ? WHERE cartID = ?";
                $upd = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($upd, "ii", $new_total_qty, $row['cartID']);
                mysqli_stmt_execute($upd);
                $msg = "Added to cart successfully!";
                $msg_type = "success";
            }
        } else {
            // New entry (there is no problem here because the max is already set in the HTML)
            $insert_sql = "INSERT INTO Cart (customerID, productID, quantity) VALUES (?, ?, ?)";
            $ins = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($ins, "iii", $customer_id, $product_id, $qty);
            mysqli_stmt_execute($ins);
            $msg = "Added to cart successfully!";
            $msg_type = "success";
        }
    }   
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_feedback'])) {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Customer') {
        header("Location: login.php");
        exit;
    }
    
    $customer_id = $_SESSION['user_id'];
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);
    $date = date('Y-m-d H:i:s');
    
    $feed_sql = "INSERT INTO Feedback (rating, comment, Date, customerID, productID) VALUES (?, ?, ?, ?, ?)";
    if ($stmt = mysqli_prepare($conn, $feed_sql)) {
        mysqli_stmt_bind_param($stmt, "issii", $rating, $comment, $date, $customer_id, $product_id);
        if(mysqli_stmt_execute($stmt)) {
            $msg = "Feedback submitted successfully!";
            $msg_type = "success";
            $is_feedback = true;
        } else {
            $msg = "Error submitting feedback.";
            $msg_type = "danger";
        }
        mysqli_stmt_close($stmt);
    }
}

// Fetch Product
$sql = "SELECT p.*, c.categoryName FROM Product p 
        LEFT JOIN Category c ON p.categoryID = c.categoryID 
        WHERE p.productID = ?";
$product = null;
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
}

if (!$product) {
    die("Product not found.");
}

// Fetch Specifications
$specs = [];
$spec_sql = "SELECT * FROM Product_Specification WHERE productID = ?";
if ($stmt = mysqli_prepare($conn, $spec_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $spec_res = mysqli_stmt_get_result($stmt);
    while($row = mysqli_fetch_assoc($spec_res)) {
        $specs[$row['attributeName']] = $row['attributeValue'];
    }
    mysqli_stmt_close($stmt);
}

// Fetch Reviews (assuming feedback table)
$reviews = [];
$rev_sql = "SELECT f.*, c.fullName FROM Feedback f JOIN Customer c ON f.customerID = c.customerID WHERE f.productID = ? ORDER BY f.Date DESC LIMIT 5";
if ($stmt = mysqli_prepare($conn, $rev_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $rev_res = mysqli_stmt_get_result($stmt);
    while($row = mysqli_fetch_assoc($rev_res)) {
        $reviews[] = $row;
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['productName']); ?> - Tech Shark</title>
    <link rel="icon" type="image/png" href="assets/logo.png"/>
    <link rel="stylesheet" href="includes/css/customer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .product-detail-container { display: flex; gap: 40px; margin: 40px auto; background: var(--white); padding: 40px; border-radius: 8px; box-shadow: var(--shadow-sm); }
        .product-gallery { flex: 1; text-align: center; }
        .product-gallery img { max-width: 100%; border-radius: 8px; box-shadow: var(--shadow-sm); }
        .product-info-panel { flex: 1; }
        .p-category { color: var(--primary-color); font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 1px; margin-bottom: 10px; }
        .p-title { font-size: 32px; margin-bottom: 15px; }
        .p-rating { color: var(--accent-color); margin-bottom: 20px; font-size: 16px; }
        .p-price { font-size: 28px; font-weight: 700; color: var(--primary-color); margin-bottom: 20px; }
        .p-desc { color: var(--text-light); margin-bottom: 30px; line-height: 1.8; }
        .spec-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .spec-table th, .spec-table td { padding: 10px; border-bottom: 1px solid var(--medium-gray); text-align: left; }
        .spec-table th { width: 30%; color: var(--text-light); font-weight: 500; }
        .cart-action { display: flex; gap: 15px; align-items: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--medium-gray); }
        .qty-input { width: 80px; padding: 10px; border: 1px solid var(--medium-gray); border-radius: 4px; text-align: center; }
        .alert-success { background-color: #d1fae5; color: #065f46; border: 1px solid #10b981; padding: 15px; border-radius: 4px; margin-bottom: 20px; text-align: center;}
        
        .reviews-section { margin-top: 40px; background: var(--white); padding: 40px; border-radius: 8px; box-shadow: var(--shadow-sm); }
        .review-card { border-bottom: 1px solid var(--medium-gray); padding: 20px 0; }
        .review-header { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .reviewer-name { font-weight: 600; }
        .review-date { color: var(--text-light); font-size: 12px; }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include 'includes/customer_header.php'; ?>

    <div class="container">
        <div class="product-detail-container">
            <div class="product-gallery">
                <img src="assets/products/<?php echo htmlspecialchars($product['productImage']); ?>" alt="<?php echo htmlspecialchars($product['productName']); ?>" onerror="this.src='assets/logo.png'">
            </div>
            <!-- Add to cart and feedback submitted successfully alert msg -->
            <div class="product-info-panel">
                <?php if($msg): ?>
                <div class="alert-<?php echo $msg_type; ?>"> <!-- Replaced alert-success with a dynamic class. -->
                    <?php echo $msg; ?> 
                    
                    <?php if(!isset($is_feedback) && $msg_type == 'success'): ?> 
                        <a href="cart.php">View Cart</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="p-category"><?php echo htmlspecialchars($product['categoryName']); ?> / <?php echo htmlspecialchars($product['brand']); ?></div>
                <h1 class="p-title"><?php echo htmlspecialchars($product['productName']); ?></h1>
                
                <div class="p-rating">
                    <?php 
                    $rating = (float)$product['rating'];
                    for($i=1; $i<=5; $i++) {
                        if($i <= $rating) echo '<i class="fas fa-star"></i>';
                        else echo '<i class="far fa-star"></i>';
                    }
                    ?>
                </div>
                
                <div class="p-price">LKR <?php echo number_format($product['price'], 2); ?></div>
                
                <p class="p-desc">
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </p>

                <?php if(!empty($specs) || $product['warrantyPeriod'] > 0): ?>
                <h3>Specifications</h3>
                <table class="spec-table">
                    <?php 
                    // Array that converts short names in the database into beautiful names
                    $spec_names = [
                        'processor' => 'Processor',
                        'ram'       => 'RAM (Memory)',
                        'storage'   => 'Storage Capacity',
                        'scrSiz'    => 'Screen Size',
                        'grpCard'   => 'Graphics Card (GPU)',
                        'useType'   => 'Usage Type / Category Type',
                        'warranty'  => 'Warranty Period'
                    ];

                    foreach($specs as $key => $val): 
                        // It checks if the array has a name, or if it just takes the normal name (with the first letter capitalized).
                        $display_name = isset($spec_names[$key]) ? $spec_names[$key] : ucfirst($key);
                    ?>
                    <tr>
                        <th><?php echo htmlspecialchars($display_name); ?></th>
                        <td><?php echo htmlspecialchars($val); ?></td>
                    </tr>
                    <?php endforeach; ?>

                    <?php 
                    // Show warrantyPeriod from Product table if not already shown via Product_Specification
                    $warrantyAlreadyInSpecs = isset($specs['warranty']) || isset($specs['Warranty Period']) || isset($specs['warrantyPeriod']);
                    if (!$warrantyAlreadyInSpecs && $product['warrantyPeriod'] > 0): 
                        $warrantyLabel = $product['warrantyPeriod'] == 1 ? '1 Month' : $product['warrantyPeriod'] . ' Months';
                    ?>
                    <tr>
                        <th>Warranty Period</th>
                        <td><?php echo htmlspecialchars($warrantyLabel); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            <?php endif; ?>


                <form method="POST" action="product_details.php?id=<?php echo $product_id; ?>" class="cart-action">
                    <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['quantity_in_stock']; ?>" class="qty-input">
                    <button type="submit" name="add_to_cart" class="btn" <?php echo $product['quantity_in_stock'] > 0 ? '' : 'disabled'; ?> style="flex:1;">
                        <i class="fas fa-shopping-cart"></i> 
                        <?php echo $product['quantity_in_stock'] > 0 ? 'Add to Cart' : 'Out of Stock'; ?>
                    </button>
                </form>
                <?php if($product['quantity_in_stock'] > 0): ?>
                    <div style="color:var(--secondary-color); margin-top:10px; font-size:14px;"><i class="fas fa-check-circle"></i> In Stock (<?php echo $product['quantity_in_stock']; ?> available)</div>
                <?php else: ?>
                    <div style="color:var(--danger-color); margin-top:10px; font-size:14px;"><i class="fas fa-times-circle"></i> Out of Stock</div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Reviews section -->
        <div class="reviews-grid">
            <!-- Left Side: Customer Reviews -->
            <div class="reviews-display">
                <h2 style="margin-top:0; margin-bottom:25px; font-size: 28px;">Customer Reviews</h2>
                <?php if(!empty($reviews)): ?>
                    <?php foreach($reviews as $rev): ?>
                    <div class="review-card-small">
                        <div class="reviewer-info" style="display:flex; justify-content:space-between; align-items:center;">
                            <span class="reviewer-name-large"><?php echo htmlspecialchars($rev['fullName']); ?></span>
                            <span style="color:#9ca3af; font-size:14px;"><?php echo date('M d, Y', strtotime($rev['Date'])); ?></span>
                        </div>
                        <div style="color:var(--accent-color); margin-top:8px; font-size:14px;">
                            <?php 
                            for($i=1; $i<=5; $i++) {
                                echo ($i <= $rev['rating']) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                            }
                            ?>
                        </div>
                        <p class="review-text-large"><?php echo nl2br(htmlspecialchars($rev['comment'])); ?></p>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:#6b7280; font-size:16px;">No reviews yet. Be the first to review!</p>
                <?php endif; ?>
            </div>

                <!-- Right Side: Submit Review Form -->
                <?php if(isset($_SESSION['user_id']) && $_SESSION['role'] == 'Customer'): ?>
                <div class="review-form-side">
                    <h3 style="margin-top:0; margin-bottom:20px; font-size: 24px;">Write a Review</h3>
                    <form method="POST" action="product_details.php?id=<?php echo $product_id; ?>">
                        <div style="margin-bottom:20px;">
                            <label style="font-size:16px; font-weight:500;">Rating</label>
                            <div class="star-rating" style="font-size: 30px;">
                                <input type="radio" id="star5" name="rating" value="5" required/><label for="star5" class="fas fa-star"></label>
                                <input type="radio" id="star4" name="rating" value="4"/><label for="star4" class="fas fa-star"></label>
                                <input type="radio" id="star3" name="rating" value="3"/><label for="star3" class="fas fa-star"></label>
                                <input type="radio" id="star2" name="rating" value="2"/><label for="star2" class="fas fa-star"></label>
                                <input type="radio" id="star1" name="rating" value="1"/><label for="star1" class="fas fa-star"></label>
                            </div>
                        </div>
                        <div style="margin-bottom:20px;">
                            <label style="font-size:16px; font-weight:500;">Your Comment</label>
                            <textarea name="comment" required rows="5" style="width:100%; padding:15px; border:1px solid var(--medium-gray); border-radius:8px; margin-top:10px; box-sizing:border-box; font-size:16px;"></textarea>
                        </div>
                        <button type="submit" name="submit_feedback" class="btn" style="width:100%; padding:15px; font-size:16px; font-weight:600;">Submit Review</button>
                    </form>
                </div>
                <?php endif; ?>
        </div>

    <script>
        // Update Cart Badge Polling
        const updateCartCount = async () => {
            const badge = document.getElementById('cartCountBadge');
            try {
                const res = await fetch('api/get_cart_count.php');
                const data = await res.json();
                badge.textContent = data.count;
                badge.style.display = data.count > 0 ? 'flex' : 'none';
            } catch(e) {}
        };
        updateCartCount();
        setInterval(updateCartCount, 5000);
    </script>

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
                            // Identifying the page and applying the relevant Category ID
                            const currentPath = window.location.pathname;
                            let categoryParam = '';

                            if (currentPath.includes('laptops.php')) {
                                categoryParam = '&category=1'; // Laptops ID
                            } else if (currentPath.includes('desktops.php')) {
                                categoryParam = '&category=2'; // Desktops ID
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
