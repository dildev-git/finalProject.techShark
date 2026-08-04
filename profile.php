<?php
session_start();
include('includes/dbconnection.php');

// Ensure user is logged in as Customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Customer') {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['user_id'];
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'profile';
$msg = '';
$msg_type = '';

// Fetch current user details
$customerInfo = null; // Initialize to avoid "undefined variable" warnings
$query = "SELECT * FROM Customer WHERE customerID = ?";
if ($stmt = mysqli_prepare($conn, $query)) {
    mysqli_stmt_bind_param($stmt, "i", $customer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $customerInfo = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

// Guard: if customer not found, log out and redirect
if (!$customerInfo) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $fullName = trim($_POST['fullName']);
        $contactNo = trim($_POST['contactNo']);
        $address = trim($_POST['address']);
        $city = trim($_POST['city']);
        
        $update_query = "UPDATE Customer SET fullName=?, contactNo=?, address=?, city=? WHERE customerID=?";
        if ($stmt = mysqli_prepare($conn, $update_query)) {
            mysqli_stmt_bind_param($stmt, "ssssi", $fullName, $contactNo, $address, $city, $customer_id);
            if (mysqli_stmt_execute($stmt)) {
                $msg = "Profile updated successfully!";
                $msg_type = "success";
                $_SESSION['name'] = $fullName; // Update session name
                // Refresh data
                $customerInfo['fullName'] = $fullName;
                $customerInfo['contactNo'] = $contactNo;
                $customerInfo['address'] = $address;
                $customerInfo['city'] = $city;
            } else {
                $msg = "Failed to update profile.";
                $msg_type = "danger";
            }
            mysqli_stmt_close($stmt);
        }
    } elseif (isset($_POST['change_password'])) {
        $current_pass = $_POST['current_password'];
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];
        
        // Verify current pass
        if ($current_pass === $customerInfo['password'] || password_verify($current_pass, $customerInfo['password'])) {
            if ($new_pass === $confirm_pass) {
                $hashed_new = password_hash($new_pass, PASSWORD_DEFAULT);
                $pass_query = "UPDATE Customer SET password=? WHERE customerID=?";
                if ($stmt = mysqli_prepare($conn, $pass_query)) {
                    mysqli_stmt_bind_param($stmt, "si", $hashed_new, $customer_id);
                    if (mysqli_stmt_execute($stmt)) {
                        $msg = "Password changed successfully!";
                        $msg_type = "success";
                        $customerInfo['password'] = $hashed_new;
                    } else {
                        $msg = "Database error updating password.";
                        $msg_type = "danger";
                    }
                    mysqli_stmt_close($stmt);
                }
            } else {
                $msg = "New passwords do not match.";
                $msg_type = "danger";
            }
        } else {
            $msg = "Incorrect current password.";
            $msg_type = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Tech Shark</title>
    <link rel="icon" type="image/png" href="assets/logo.png"/>
    <link rel="stylesheet" href="includes/css/customer.css">
    <link rel="stylesheet" href="includes/css/profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header (simplified inclusion for brevity, normally you'd use a shared header.php) -->
    <?php include 'includes/customer_header.php'; ?>

    <div class="container">
        <div class="profile-container">
            <!-- Sidebar -->
            <div class="profile-sidebar">
                <div class="profile-sidebar-header">
                    <div class="profile-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <h3><?php echo htmlspecialchars($customerInfo['fullName']); ?></h3>
                    <p class="text-muted" style="color: var(--text-light); font-size: 14px;"><?php echo htmlspecialchars($customerInfo['email']); ?></p>
                </div>
                <ul class="profile-nav">
                    <li><a href="?tab=profile" class="<?php echo $tab=='profile'?'active':''; ?>"><i class="fas fa-id-card"></i> Profile Info</a></li>
                    <li><a href="?tab=security" class="<?php echo $tab=='security'?'active':''; ?>"><i class="fas fa-lock"></i> Security</a></li>
                    <li><a href="?tab=orders" class="<?php echo $tab=='orders'?'active':''; ?>"><i class="fas fa-shopping-bag"></i> Order History</a></li>
                </ul>
            </div>

            <!-- Content -->
            <div class="profile-content">
                <?php if($msg): ?>
                    <div class="alert alert-<?php echo $msg_type; ?>">
                        <?php echo $msg; ?>
                    </div>
                <?php endif; ?>

                <?php if($tab == 'profile'): ?>
                <h2 class="profile-title">Profile Information</h2>
                <form method="POST" action="?tab=profile">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i>Full Name</label>
                        <input type="text" name="fullName" value="<?php echo htmlspecialchars($customerInfo['fullName']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i>Email Address (Cannot be changed)</label>
                        <input type="email" value="<?php echo htmlspecialchars($customerInfo['email']); ?>" readonly style="background:#f3f4f6;">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i>Contact Number</label>
                        <input type="text" name="contactNo" value="<?php echo htmlspecialchars($customerInfo['contactNo']); ?>">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-home"></i>Address</label>
                        <input type="text" name="address" value="<?php echo htmlspecialchars($customerInfo['address']); ?>">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-map-marker-alt"></i>District</label>
                        <select name="city" required>
                            <option value="" disabled <?php echo empty($customerInfo['city']) ? 'selected' : ''; ?>>Select District</option>
                            <?php 
                            $districts = ["Ampara", "Anuradhapura", "Badulla", "Batticaloa", "Colombo", "Galle", "Gampaha", "Hambantota", "Jaffna", "Kalutara", "Kandy", "Kegalle", "Kilinochchi", "Kurunegala", "Mannar", "Matale", "Matara", "Monaragala", "Mullaitivu", "Nuwara Eliya", "Polonnaruwa", "Puttalam", "Ratnapura", "Trincomalee", "Vavuniya"];
                            foreach ($districts as $district) {
                                $selected = (isset($customerInfo['city']) && $customerInfo['city'] === $district) ? 'selected' : '';
                                echo "<option value=\"$district\" $selected>$district</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <button type="submit" name="update_profile" class="btn">Update Profile</button>
                </form>

                <?php elseif($tab == 'security'): ?>
                <h2 class="profile-title">Change Password</h2>
                <form method="POST" action="?tab=security">
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i>Current Password</label>
                        <div class="password-wrapper"> 
                            <i class="fas fa-eye-slash eye-icon toggle-password" id="show-password"></i>
                            <input type="password" id="current-password" name="current_password" required>
                        </div> 
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i>New Password</label>
                        <div class="password-wrapper"> 
                            <i class="fas fa-eye-slash eye-icon toggle-password" id="show-password"></i>
                            <input type="password" id="new-password" name="new_password" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i>Confirm New Password</label>
                        <div class="password-wrapper"> 
                            <i class="fas fa-eye-slash eye-icon toggle-password" id="show-password"></i>
                            <input type="password" id="confirm-password" name="confirm_password" required>
                        </div> 
                    </div>

                <script>
                    // select all icons through class
                    const toggleIcons = document.querySelectorAll(".toggle-password");

                    // add event listener to each icon
                    toggleIcons.forEach(icon => {
                        icon.addEventListener("click", function () {

                            // find the input field within the wrapper where the clicked icon is located
                            const passwordField = this.parentElement.querySelector("input");

                            // toggle the password visibility
                            const type = passwordField.getAttribute("type") === "password" ? "text" : "password";
                            passwordField.setAttribute("type", type);

                            // toggle the eye icon
                            this.classList.toggle("fa-eye");
                            this.classList.toggle("fa-eye-slash");
                        });
                    });
                </script>

                    <button type="submit" name="change_password" class="btn">Change Password</button>
                </form>

                <?php elseif($tab == 'orders'): ?>
                <h2 class="profile-title">Order History</h2>
                <table class="order-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $ordersData = []; // Send to JavaScript to store data in an array
                        
                        // Get the order with payment details.
                        $ord_query = "
                            SELECT o.*, p.method as paymentMethod, p.status as paymentStatus, p.paymentDate
                            FROM `Order` o
                            LEFT JOIN Payment p ON o.orderID = p.orderID
                            WHERE o.customerID = ? 
                            ORDER BY o.orderDate DESC";
                            
                        if ($stmt = mysqli_prepare($conn, $ord_query)) {
                            mysqli_stmt_bind_param($stmt, "i", $customer_id);
                            mysqli_stmt_execute($stmt);
                            $ords = mysqli_stmt_get_result($stmt);
                            
                            if (mysqli_num_rows($ords) > 0) {
                                while($order = mysqli_fetch_assoc($ords)) {
                                    $oid = $order['orderID'];
                                    
                                    // Pick up the items related to the order.
                                    $items_query = "SELECT od.quantity, od.unitPrice, pr.productName 
                                                    FROM Order_Details od
                                                    JOIN Product pr ON od.productID = pr.productID
                                                    WHERE od.orderID = $oid";
                                    $items_res = mysqli_query($conn, $items_query);
                                    $items = [];
                                    while($it = mysqli_fetch_assoc($items_res)) {
                                        $items[] = $it;
                                    }
                                    $order['items'] = $items;
                                    $ordersData[$oid] = $order; // Adding to the array
                                    
                                    $statusClass = 'status-' . strtolower($order['status']);
                                    echo "<tr>";
                                    echo "<td>#ORD-" . str_pad($order['orderID'], 4, '0', STR_PAD_LEFT) . "</td>";
                                    echo "<td>" . date('M d, Y', strtotime($order['orderDate'])) . "</td>";
                                    echo "<td>LKR " . number_format($order['totalAmount'], 2) . "</td>";
                                    echo "<td><span class='status-badge {$statusClass}'>{$order['status']}</span></td>";
                                    
                                    // The View button now opens the modal instead of going to a separate page.
                                    echo "<td><button type='button' class='btn btn-outline' style='border-color:var(--primary-color);color:var(--primary-color);padding:5px 10px;font-size:12px;cursor:pointer;' onclick='showOrderDetails({$order['orderID']})'><i class='fas fa-eye'></i> View</button></td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5' style='text-align:center;'>No orders found.</td></tr>";
                            }
                            mysqli_stmt_close($stmt);
                        }
                        ?>
                    </tbody>
                </table>
                <div id="orderModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:9999;align-items:center;justify-content:center;padding:20px;">
                    <div style="background:#fff;border-radius:12px;padding:30px;width:700px;max-width:100%;max-height:90vh;overflow-y:auto;box-shadow:0 25px 50px -12px rgba(0,0,0,0.5);">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;border-bottom:1px solid #eee;padding-bottom:15px;">
                            <h2 style="margin:0;color:#111;">Order Details <span id="mod_oid" style="color:#7286d3;"></span></h2>
                            <button onclick="document.getElementById('orderModal').style.display='none'" style="background:none;border:none;font-size:24px;cursor:pointer;color:#888;">&times;</button>
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:25px;">
                            <div style="background:#f8fafc;padding:15px;border-radius:8px;border:1px solid #e2e8f0;">
                                <h4 style="margin-top:0;color:#3e54ac;border-bottom:1px solid #cbd5e1;padding-bottom:5px;">Delivery Info</h4>
                                <p style="margin:5px 0; font-size:14px;"><strong>Name:</strong> <?php echo htmlspecialchars($customerInfo['fullName']); ?></p>
                                <p style="margin:5px 0; font-size:14px;"><strong>Phone:</strong> <?php echo htmlspecialchars($customerInfo['contactNo']); ?></p>
                                <p style="margin:5px 0; font-size:14px;"><strong>Address:</strong> <?php echo htmlspecialchars($customerInfo['address']); ?>, <?php echo htmlspecialchars($customerInfo['city']); ?></p>
                            </div>
                            <div style="background:#f8fafc;padding:15px;border-radius:8px;border:1px solid #e2e8f0;">
                                <h4 style="margin-top:0;color:#3e54ac;border-bottom:1px solid #cbd5e1;padding-bottom:5px;">Payment Info</h4>
                                <p style="margin:5px 0; font-size:14px;"><strong>Method:</strong> <span id="mod_pmethod"></span></p>
                                <p style="margin:5px 0; font-size:14px;"><strong>Status:</strong> <span id="mod_pstatus"></span></p>
                                <p style="margin:5px 0; font-size:14px;"><strong>Date:</strong> <span id="mod_pdate"></span></p>
                                <p style="margin:5px 0; font-size:14px;"><strong>Order Status:</strong> <span id="mod_ostatus" style="font-weight:bold;"></span></p>
                            </div>
                        </div>

                        <h4 style="margin:0 0 10px 0;color:#111;">Ordered Items</h4>
                        <table style="width:100%;border-collapse:collapse;margin-bottom:20px;">
                            <thead style="background:#f1f5f9;">
                                <tr>
                                    <th style="padding:10px;text-align:left;border-bottom:2px solid #cbd5e1;font-size:14px;">Product</th>
                                    <th style="padding:10px;text-align:center;border-bottom:2px solid #cbd5e1;font-size:14px;">Qty</th>
                                    <th style="padding:10px;text-align:right;border-bottom:2px solid #cbd5e1;font-size:14px;">Unit Price (LKR)</th>
                                    <th style="padding:10px;text-align:right;border-bottom:2px solid #cbd5e1;font-size:14px;">Subtotal (LKR)</th>
                                </tr>
                            </thead>
                            <tbody id="mod_items_body">
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" style="padding:10px;text-align:right;font-weight:bold;font-size:15px;">Total Amount:</td>
                                    <td style="padding:10px;text-align:right;font-weight:bold;font-size:16px;color:#3e54ac;">LKR <span id="mod_total"></span></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-update cart badge
            fetch('ajax_cart.php?action=count')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const cartCounts = document.querySelectorAll('.cart-count');
                    cartCounts.forEach(el => el.textContent = data.cart_count);
                }
            })
            .catch(e => console.error(e));
        });
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

    <script>
        // The Array created in PHP is imported as a JS Object here.
        const ordersData = <?php echo isset($ordersData) ? json_encode($ordersData) : '{}'; ?>;

        function showOrderDetails(oid) {
            const order = ordersData[oid];
            if(!order) return;

            // ID formatting (#ORD-0001)
            const formattedOid = 'ORD-' + String(oid).padStart(4, '0');
            document.getElementById('mod_oid').innerText = '#' + formattedOid;

            // Fill Payment & Status details
            document.getElementById('mod_pmethod').innerText = order.paymentMethod || 'N/A';
            document.getElementById('mod_pstatus').innerText = order.paymentStatus || 'N/A';
            document.getElementById('mod_pdate').innerText = order.paymentDate || 'N/A';
            document.getElementById('mod_ostatus').innerText = order.status;

            // Fill the Total
            document.getElementById('mod_total').innerText = parseFloat(order.totalAmount).toLocaleString('en-US', {minimumFractionDigits: 2});

            // Add items to the table body
            const tbody = document.getElementById('mod_items_body');
            tbody.innerHTML = '';
            
            order.items.forEach(item => {
                const subtotal = item.quantity * item.unitPrice;
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td style="padding:10px;border-bottom:1px solid #e2e8f0;font-size:14px;">${item.productName}</td>
                    <td style="padding:10px;border-bottom:1px solid #e2e8f0;text-align:center;font-size:14px;">${item.quantity}</td>
                    <td style="padding:10px;border-bottom:1px solid #e2e8f0;text-align:right;font-size:14px;">${parseFloat(item.unitPrice).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                    <td style="padding:10px;border-bottom:1px solid #e2e8f0;text-align:right;font-size:14px;">${subtotal.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                `;
                tbody.appendChild(tr);
            });

            // Open the Modal
            document.getElementById('orderModal').style.display = 'flex';
        }

        // Close when clicked outside the modal.
        document.getElementById('orderModal').addEventListener('click', function(e) {
            if(e.target === this) this.style.display = 'none';
        });
    </script>
</body>
</html>
