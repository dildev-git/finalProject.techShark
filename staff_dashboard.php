<?php
session_start();
include('includes/dbconnection.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'Customer') {
    header("Location: login.php");
    exit;
}

$staff_id = $_SESSION['user_id'];
$staff_role = $_SESSION['role'];
$staff_name = $_SESSION['name'];
$view = isset($_GET['view']) ? $_GET['view'] : 'dashboard';

// Logic Handlers for various modules
$msg = '';
$msg_type = '';

/* --- ADMIN LOGIC --- */
if ($staff_role == 'Administrator') {

    // ----- DELETE CUSTOMER -----
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_customer'])) {
        $del_id = (int)$_POST['customer_id'];
        // Remove dependent cart & order data first, then the customer
        mysqli_query($conn, "DELETE FROM Cart WHERE customerID = $del_id");
        mysqli_query($conn, "DELETE FROM Customer WHERE customerID = $del_id");
        $msg = "Customer profile deleted."; $msg_type = "success";
    }

    // ----- DELETE STAFF -----
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_staff'])) {
        $del_id = (int)$_POST['staff_id'];
        mysqli_query($conn, "DELETE FROM Staff WHERE staffID = $del_id");
        $msg = "Staff member deleted."; $msg_type = "success";
    }

    // ----- ADD STAFF -----
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_staff'])) {
        $sName    = mysqli_real_escape_string($conn, trim($_POST['s_fullName']));
        $sNIC     = mysqli_real_escape_string($conn, trim($_POST['s_NIC']));
        $sEmail   = mysqli_real_escape_string($conn, trim($_POST['s_email']));
        $sUser    = mysqli_real_escape_string($conn, trim($_POST['s_userName']));
        $sPass    = password_hash(trim($_POST['s_password']), PASSWORD_DEFAULT);
        $sPhone   = mysqli_real_escape_string($conn, trim($_POST['s_contactNo']));
        $sAddr    = mysqli_real_escape_string($conn, trim($_POST['s_address']));
        $sCity    = mysqli_real_escape_string($conn, trim($_POST['s_city']));
        $sGender  = mysqli_real_escape_string($conn, $_POST['s_gender']);
        $sType    = mysqli_real_escape_string($conn, $_POST['s_staff_type']);
        $sDOB     = mysqli_real_escape_string($conn, $_POST['s_dob']);

        $sql = "INSERT INTO Staff (fullName, NIC, email, userName, password, contactNo, address, city, gender, staff_type, date_of_birth)
                VALUES ('$sName','$sNIC','$sEmail','$sUser','$sPass','$sPhone','$sAddr','$sCity','$sGender','$sType','$sDOB')";

        if (mysqli_query($conn, $sql)) {
            $msg = "Staff member added successfully."; $msg_type = "success";
        } else {
            $msg = "Error adding staff: " . mysqli_error($conn); $msg_type = "danger";
        }
    }

    // ----- UPDATE STAFF -----
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_staff'])) {
        $sid    = (int)$_POST['edit_staff_id'];
        $sName  = mysqli_real_escape_string($conn, trim($_POST['edit_fullName']));
        $sNIC   = mysqli_real_escape_string($conn, trim($_POST['edit_NIC']));
        $sEmail = mysqli_real_escape_string($conn, trim($_POST['edit_email']));
        $sUser  = mysqli_real_escape_string($conn, trim($_POST['edit_userName']));
        $sPhone = mysqli_real_escape_string($conn, trim($_POST['edit_contactNo']));
        $sAddr  = mysqli_real_escape_string($conn, trim($_POST['edit_address']));
        $sCity  = mysqli_real_escape_string($conn, trim($_POST['edit_city']));
        $sGender= mysqli_real_escape_string($conn, trim($_POST['edit_gender']));
        $sType  = mysqli_real_escape_string($conn, $_POST['edit_staff_type']);
        $sDOB   = mysqli_real_escape_string($conn, trim($_POST['edit_dob']));

        $sql = "UPDATE Staff SET fullName='$sName', NIC='$sNIC', email='$sEmail', userName='$sUser', contactNo='$sPhone', address='$sAddr', city='$sCity', gender='$sGender', staff_type='$sType', date_of_birth='$sDOB' WHERE staffID = $sid";
        
        if (mysqli_query($conn, $sql)) {
            $msg = "Staff member updated."; $msg_type = "success";
        } else {
            $msg = "Update failed: " . mysqli_error($conn); $msg_type = "danger";
        }
    }
}

/* --- MANAGER LOGIC (Analytics View Handled Inline) --- */

/* --- INVENTORY LOGIC --- */
if ($staff_role == 'Stock Keeper') {

    // ----- UPDATE STOCK QTY -----
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_stock'])) {
        $pid = (int)$_POST['product_id'];
        $qty = (int)$_POST['stock_qty'];
        mysqli_query($conn, "UPDATE Product SET quantity_in_stock = $qty WHERE productID = $pid");
        $msg = "Stock updated."; $msg_type = "success";
    }

    // ----- ADD PRODUCT -----
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
        $pName  = mysqli_real_escape_string($conn, trim($_POST['p_name']));
        $pBrand = mysqli_real_escape_string($conn, trim($_POST['p_brand']));
        $pDesc  = mysqli_real_escape_string($conn, trim($_POST['p_description']));
        $pPrice = (float)$_POST['p_price'];
        $pOld   = (float)$_POST['p_old_price'];
        $pQty   = (int)$_POST['p_qty'];
        $pWar   = (int)$_POST['p_warranty'];
        $pCat   = (int)$_POST['p_categoryID'];
        $pStatus= mysqli_real_escape_string($conn, $_POST['p_status']);
        $pDate  = date('Y-m-d');
        
        // Handle Image Upload
        $pImg = 'default.jpg'; 
        if (isset($_FILES['p_image']) && $_FILES['p_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'assets/products/';
            if(!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
            $fileExt = strtolower(pathinfo($_FILES['p_image']['name'], PATHINFO_EXTENSION));
            $fileName = uniqid('prod_') . '.' . $fileExt;
            $uploadFile = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['p_image']['tmp_name'], $uploadFile)) {
                $pImg = $fileName;
            }
        }

        $sql = "INSERT INTO Product (productName, description, brand, price, oldPrice, quantity_in_stock,
                    warrantyPeriod, categoryID, status, productImage, addedDate)
                VALUES ('$pName','$pDesc','$pBrand',$pPrice,$pOld,$pQty,$pWar,$pCat,'$pStatus','$pImg','$pDate')";
        
        if (mysqli_query($conn, $sql)) {
            // අලුතෙන් add වුන product එකේ ID එක ගන්නවා
            $new_product_id = mysqli_insert_id($conn);

            // Specifications ටික Product_Specification table එකට insert කරනවා
            if(isset($_POST['spec_names']) && isset($_POST['spec_values'])){
                for($i = 0; $i < count($_POST['spec_names']); $i++){
                    $spec_name = mysqli_real_escape_string($conn, trim($_POST['spec_names'][$i]));
                    $spec_val = mysqli_real_escape_string($conn, trim($_POST['spec_values'][$i]));
                    
                    if(!empty($spec_name) && !empty($spec_val)){
                        $spec_sql = "INSERT INTO Product_Specification (productID, attributeName, attributeValue) 
                                     VALUES ($new_product_id, '$spec_name', '$spec_val')";
                        mysqli_query($conn, $spec_sql);
                    }
                }
            }

            $msg = "Product '$pName' added successfully with specifications."; $msg_type = "success";
        } else {
            $msg = "Error adding product: " . mysqli_error($conn); $msg_type = "danger";
        }
    }

    // ----- UPDATE PRODUCT DETAILS -----
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_product'])) {
        $pid    = (int)$_POST['edit_product_id'];
        $pName  = mysqli_real_escape_string($conn, trim($_POST['edit_name']));
        $pBrand = mysqli_real_escape_string($conn, trim($_POST['edit_brand']));
        $pDesc  = mysqli_real_escape_string($conn, trim($_POST['edit_description']));
        $pCat   = (int)$_POST['edit_categoryID'];
        $pPrice = (float)$_POST['edit_price'];
        $pOld   = (float)$_POST['edit_old_price'];
        $pQty   = (int)$_POST['edit_qty'];
        $pWar   = (int)$_POST['edit_warranty'];
        $pStatus= mysqli_real_escape_string($conn, $_POST['edit_status']);

        $imgUpdateSql = "";
        if (isset($_FILES['edit_image']) && $_FILES['edit_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'assets/products/';
            if(!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
            $fileExt = strtolower(pathinfo($_FILES['edit_image']['name'], PATHINFO_EXTENSION));
            $fileName = uniqid('prod_') . '.' . $fileExt;
            if (move_uploaded_file($_FILES['edit_image']['tmp_name'], $uploadDir . $fileName)) {
                $imgUpdateSql = ", productImage='$fileName'";
            }
        }

        $sql = "UPDATE Product SET productName='$pName', brand='$pBrand', description='$pDesc', categoryID=$pCat, price=$pPrice,
                    oldPrice=$pOld, quantity_in_stock=$pQty, warrantyPeriod=$pWar, status='$pStatus' $imgUpdateSql
                WHERE productID=$pid";
        if (mysqli_query($conn, $sql)) {
            // පරණ Specs ටික Delete කරලා අලුත් ටික Insert කරනවා
            mysqli_query($conn, "DELETE FROM Product_Specification WHERE productID=$pid");
            
            if(isset($_POST['edit_spec_names']) && isset($_POST['edit_spec_values'])){
                for($i = 0; $i < count($_POST['edit_spec_names']); $i++){
                    $spec_name = mysqli_real_escape_string($conn, trim($_POST['edit_spec_names'][$i]));
                    $spec_val = mysqli_real_escape_string($conn, trim($_POST['edit_spec_values'][$i]));
                    if(!empty($spec_name) && !empty($spec_val)){
                        mysqli_query($conn, "INSERT INTO Product_Specification (productID, attributeName, attributeValue) VALUES ($pid, '$spec_name', '$spec_val')");
                    }
                }
            }
            $msg = "Product updated successfully."; $msg_type = "success";
        } else {
            $msg = "Update failed: " . mysqli_error($conn); $msg_type = "danger";
        }
    }

    // ----- DELETE PRODUCT -----
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_product'])) {
        $pid = (int)$_POST['product_id'];
        // Remove specs first (FK), then the product
        mysqli_query($conn, "DELETE FROM Product_Specification WHERE productID = $pid");
        if (mysqli_query($conn, "DELETE FROM Product WHERE productID = $pid")) {
            $msg = "Product deleted."; $msg_type = "success";
        } else {
            $msg = "Cannot delete — product may have existing orders."; $msg_type = "danger";
        }
    }
}

/* --- SALES LOGIC --- */
if ($staff_role == 'Sales Representative') {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_order'])) {
        $oid = (int)$_POST['order_id'];
        $status = $_POST['status'];
        mysqli_query($conn, "UPDATE `Order` SET status = '$status' WHERE orderID = $oid");
        
        // --- NOTIFICATION ---
        $res = mysqli_query($conn, "SELECT customerID FROM `Order` WHERE orderID = $oid");
        if($row = mysqli_fetch_assoc($res)) {
            $cid = $row['customerID'];
            $notif_msg = "Your order #ORD-" . str_pad($oid, 4, '0', STR_PAD_LEFT) . " status has been updated to '$status'.";
            $safe_msg = mysqli_real_escape_string($conn, $notif_msg);
            mysqli_query($conn, "INSERT INTO Notification (message, type, date, customerID, is_read) VALUES ('$safe_msg', 'Order Update', NOW(), $cid, 0)");
        }
        
        $msg = "Order status updated."; $msg_type = "success";
    }
}

/* --- INQUIRY LOGIC --- */
if ($staff_role == 'Inquiry Manager') {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reply_inquiry'])) {
        $inq_id = (int)$_POST['inquiry_id'];
        $reply = mysqli_real_escape_string($conn, trim($_POST['response']));
        mysqli_query($conn, "UPDATE Inquiry SET response = '$reply', status = 'Resolved' WHERE inquiryID = $inq_id");
        
        // --- NOTIFICATION ---
        $res = mysqli_query($conn, "SELECT customerID FROM Inquiry WHERE inquiryID = $inq_id");
        if($row = mysqli_fetch_assoc($res)) {
            $cid = $row['customerID'];
            $notif_msg = "You have received a reply to your inquiry #INQ-" . str_pad($inq_id, 4, '0', STR_PAD_LEFT) . ".";
            $safe_msg = mysqli_real_escape_string($conn, $notif_msg);
            mysqli_query($conn, "INSERT INTO Notification (message, type, date, customerID, is_read) VALUES ('$safe_msg', 'Inquiry Update', NOW(), $cid, 0)");
        }
        
        $msg = "Inquiry replied."; $msg_type = "success";
    }
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_inquiry'])) {
        $inq_id = (int)$_POST['inquiry_id'];
        if(mysqli_query($conn, "DELETE FROM Inquiry WHERE inquiryID = $inq_id")) {
            $msg = "Inquiry deleted."; $msg_type = "success";
        } else {
            $msg = "Failed to delete inquiry."; $msg_type = "danger";
        }
    }
}

/* --- REPAIR LOGIC --- */
if ($staff_role == 'Repair Technician') {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_repair'])) {
        $rid = (int)$_POST['repair_id'];
        $status = $_POST['status'];
        $cost = (float)$_POST['cost'];
        $devName = mysqli_real_escape_string($conn, trim($_POST['device_name']));
        $issue = mysqli_real_escape_string($conn, trim($_POST['issue']));
        $cid = (int)$_POST['customer_id'];
        
        $q = "UPDATE Repair SET deviceName = '$devName', issueDescription = '$issue', customerID = $cid, repairStatus = '$status', estimatedCost = $cost";
        if($status == 'Completed'){
            $q .= ", completionDate = NOW()";
        }
        $q .= " WHERE repairID = $rid";
        mysqli_query($conn, $q);
        
        // --- NOTIFICATION ---
        $notif_msg = "Your repair status for '$devName' has been updated to '$status'.";
        $safe_msg = mysqli_real_escape_string($conn, $notif_msg);
        mysqli_query($conn, "INSERT INTO Notification (message, type, date, customerID, is_read) VALUES ('$safe_msg', 'Repair Update', NOW(), $cid, 0)");
        
        $msg = "Repair updated."; $msg_type = "success";
    }
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_repair'])) {
        $devName = mysqli_real_escape_string($conn, trim($_POST['device_name']));
        $issue = mysqli_real_escape_string($conn, trim($_POST['issue']));
        $cid = (int)$_POST['customer_id'];
        $cost = (float)$_POST['cost'];
        $sql = "INSERT INTO Repair (deviceName, issueDescription, repairStatus, estimatedCost, startDate, customerID, staffID)
                VALUES ('$devName', '$issue', 'Pending', $cost, NOW(), $cid, $staff_id)";
        if(mysqli_query($conn, $sql)) {
            $msg = "Repair job added successfully."; $msg_type = "success";
        } else {
            $msg = "Failed to add repair: ".mysqli_error($conn); $msg_type = "danger";
        }
    }
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_repair'])) {
        $rid = (int)$_POST['repair_id'];
        if(mysqli_query($conn, "DELETE FROM Repair WHERE repairID = $rid")) {
            $msg = "Repair deleted."; $msg_type = "success";
        } else {
            $msg = "Failed to delete repair: ".mysqli_error($conn); $msg_type = "danger";
        }
    }
}

// Fetch Notes count (for sidebar badge)
$notes_count = 0;
$notes_res = mysqli_query($conn, "SELECT COUNT(*) as c FROM Staff_Notes");
if($notes_res) { $notes_count = mysqli_fetch_assoc($notes_res)['c']; }

// ----- ADD NOTE (handled here BEFORE HTML output so redirect header() works) -----
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_note']) && !empty(trim($_POST['note']))) {
    $note_content = mysqli_real_escape_string($conn, trim($_POST['note']));

    if ($staff_role !== 'Administrator') {
        // Staff members are in the Staff table — insert normally
        $ins = mysqli_query($conn, "INSERT INTO Staff_Notes (staffID, noteContent) VALUES ($staff_id, '$note_content')");
    } else {
        // Administrators are NOT in the Staff table.
        // Use staffID = 1 as a proxy (first staff member) or skip FK entirely.
        // Best fix: insert with the first available staffID as author proxy.
        $first_staff = mysqli_fetch_assoc(mysqli_query($conn, "SELECT staffID FROM Staff LIMIT 1"));
        if ($first_staff) {
            $proxy_id = (int)$first_staff['staffID'];
            $label = mysqli_real_escape_string($conn, "[Admin: $staff_name] $note_content");
            $ins = mysqli_query($conn, "INSERT INTO Staff_Notes (staffID, noteContent) VALUES ($proxy_id, '$label')");
        }
    }

    header("Location: staff_dashboard.php?view=notes&added=1");
    exit;
}

// ----- MARK NOTES AS READ when the user opens the notes view -----
if ($view === 'notes') {
    if ($staff_role !== 'Administrator') {
        // Record the current timestamp as "last viewed" for this staff member
        mysqli_query($conn, "UPDATE Staff SET last_note_viewed_at = NOW() WHERE staffID = $staff_id");
    } else {
        // Database එකේ වෙලාවම අරන් Session සහ Cookie දෙකටම දානවා
        $time_res = mysqli_query($conn, "SELECT NOW() as db_time");
        if ($time_row = mysqli_fetch_assoc($time_res)) {
            $_SESSION['notes_last_viewed'] = $time_row['db_time'];
            // Cookie එකකුත් සේව් කරනවා දවස් 30කට
            setcookie('admin_notes_last_viewed', $time_row['db_time'], time() + (86400 * 30), "/"); 
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Tech Shark</title>
    <link rel="icon" type="image/png" href="assets/logo.png"/>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Staff Global Styles -->
    <link rel="stylesheet" href="staff/assets/css/staff_style.css">
</head>
<body>

<?php
// Generate initials from staff name
$_sd_initials = '';
$_sd_parts = explode(' ', trim($staff_name));
foreach ($_sd_parts as $_sd_p) {
    if (!empty($_sd_p)) { $_sd_initials .= strtoupper($_sd_p[0]); if (strlen($_sd_initials) >= 2) break; }
}
if (empty($_sd_initials)) $_sd_initials = 'ST';
?>
<aside class="sidebar">
    <!-- Brand -->
    <div class="sidebar-header">
        <div class="brand-icon"><i class="fas fa-bolt"></i></div>
        <h2>Tech Shark<span>Staff Portal</span></h2>
    </div>

    <!-- Profile -->
    <div class="profile-sec">
        <div class="avatar-initials" title="<?php echo htmlspecialchars($staff_name); ?>">
            <?php echo htmlspecialchars($_sd_initials); ?>
        </div>
        <div class="profile-info">
            <div class="profile-name"><?php echo htmlspecialchars($staff_name); ?></div>
            <span class="role"><?php echo htmlspecialchars($staff_role); ?></span>
        </div>
    </div>

    <!-- Navigation -->
    <ul class="nav-links">
        <li><a href="?view=dashboard" class="<?php echo $view=='dashboard'?'active':''; ?>" title="Home"><i class="fas fa-house"></i> Home</a></li>

        <?php if($staff_role == 'Administrator'): ?>
        <li><a href="?view=customers" class="<?php echo $view=='customers'?'active':''; ?>" title="Customers"><i class="fas fa-users"></i> Customers</a></li>
        <li><a href="?view=staff" class="<?php echo $view=='staff'?'active':''; ?>" title="Staff"><i class="fas fa-user-tie"></i> Staff</a></li>
        <?php endif; ?>

        <?php if($staff_role == 'Manager'): ?>
        <li><a href="?view=analytics" class="<?php echo $view=='analytics'?'active':''; ?>" title="Analytics"><i class="fas fa-chart-pie"></i> Analytics</a></li>
        <li><a href="?view=reports" class="<?php echo $view=='reports'?'active':''; ?>" title="Reports"><i class="fas fa-file-lines"></i> Reports</a></li>
        <?php endif; ?>

        <?php if($staff_role == 'Stock Keeper'): ?>
        <li><a href="?view=inventory" class="<?php echo $view=='inventory'?'active':''; ?>" title="Inventory"><i class="fas fa-boxes-stacked"></i> Inventory</a></li>
        <?php endif; ?>

        <?php if($staff_role == 'Sales Representative'): ?>
        <li><a href="?view=orders" class="<?php echo $view=='orders'?'active':''; ?>" title="Orders"><i class="fas fa-bag-shopping"></i> Orders</a></li>
        <?php endif; ?>

        <?php if($staff_role == 'Inquiry Manager'): ?>
        <li><a href="?view=inquiries" class="<?php echo $view=='inquiries'?'active':''; ?>" title="Inquiries"><i class="fas fa-headset"></i> Inquiries</a></li>
        <?php endif; ?>

        <?php if($staff_role == 'Repair Technician'): ?>
        <li><a href="?view=repairs" class="<?php echo $view=='repairs'?'active':''; ?>" title="Repairs"><i class="fas fa-screwdriver-wrench"></i> Repairs</a></li>
        <?php endif; ?>

        <li><a href="?view=notes" class="<?php echo $view=='notes'?'active':''; ?>" id="notesNavLink" title="Notes">
            <i class="fas fa-note-sticky"></i> Notes
            <span class="badge" id="notesBadge" style="display:none;">0</span>
        </a></li>
    </ul>

    <!-- Sidebar Footer: Quick Sign Out -->
    <div class="sidebar-footer">
        <a href="logout.php" class="btn btn-ghost" style="width:100%; justify-content:flex-start; gap:10px; padding:10px 14px; border-radius:10px; font-size:13px; color:var(--nav-text);">
            <i class="fas fa-arrow-right-from-bracket" style="width:18px; text-align:center; color:var(--danger);"></i>
            Sign Out
        </a>
    </div>
</aside>

<main class="main-content">
    <header class="topbar">
        <div><h3 style="margin:0; font-weight:normal;">Welcome back, <b><?php echo htmlspecialchars($staff_name); ?></b></h3></div>
        <div>
            <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>

    <div class="content-area">
        <?php if($msg): ?>
            <div class="alert alert-<?php echo $msg_type; ?>"><?php echo $msg; ?></div>
        <?php endif; ?>

        <?php 
        /* --- ROUTING VIEWS --- */
        if ($view == 'dashboard') {
            echo "<h2>Dashboard Overview</h2>";
            echo "<div class='dashboard-cards'>";
            
            // Administrator sees only Customer & Staff counts
            if ($staff_role == 'Administrator') {
                $rc = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM Customer"));
                $rs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM Staff"));
                echo "<div class='card'><div class='info'><p>Total Customers</p><h3>{$rc['c']}</h3></div><i class='fas fa-users'></i></div>";
                echo "<div class='card'><div class='info'><p>Total Staff</p><h3>{$rs['c']}</h3></div><i class='fas fa-user-tie'></i></div>";
            }
            if($staff_role == 'Manager') {
                $ro = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM `Order`"));
                echo "<div class='card'><div class='info'><p>Total Orders</p><h3>{$ro['c']}</h3></div><i class='fas fa-shopping-cart'></i></div>";
            }
            if($staff_role == 'Sales Representative') {
                $ro = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM `Order`"));
                $rp_pend = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM `Order` WHERE status='Pending'"));
                $rp_proc = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM `Order` WHERE status='Processing'"));
                $rp_ship = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM `Order` WHERE status='Shipped'"));
                
                echo "<div class='card'><div class='info'><p>Total Orders</p><h3>{$ro['c']}</h3></div><i class='fas fa-shopping-cart'></i></div>";
                echo "<div class='card'><div class='info'><p>Pending Orders</p><h3 style='color:#f59e0b;'>{$rp_pend['c']}</h3></div><i class='fas fa-clock' style='color:#f59e0b;opacity:0.3;'></i></div>";
                echo "<div class='card'><div class='info'><p>Processing</p><h3 style='color:#3b82f6;'>{$rp_proc['c']}</h3></div><i class='fas fa-box-open' style='color:#3b82f6;opacity:0.3;'></i></div>";
                echo "<div class='card'><div class='info'><p>Shipped Orders</p><h3 style='color:#10b981;'>{$rp_ship['c']}</h3></div><i class='fas fa-truck' style='color:#10b981;opacity:0.3;'></i></div>";
            }
            if($staff_role == 'Stock Keeper') {
                // Low stock count (qty <= 3)
                $low_c = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM Product WHERE quantity_in_stock <= 3"))['c'];
                $rp    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM Product"));
                echo "<div class='card'><div class='info'><p>Total Products</p><h3>{$rp['c']}</h3></div><i class='fas fa-box'></i></div>";
                echo "<div class='card' style='border-left:4px solid var(--danger);'><div class='info'><p>Low Stock (&le;3 units)</p><h3 style='color:var(--danger);'>{$low_c}</h3></div><i class='fas fa-exclamation-triangle' style='color:var(--danger);opacity:0.3;'></i></div>";
            }
            if($staff_role == 'Inquiry Manager') {
                $rinq_tot = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM Inquiry"));
                $rinq = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM Inquiry WHERE status='Pending'"));
                echo "<div class='card'><div class='info'><p>Total Inquiries</p><h3>{$rinq_tot['c']}</h3></div><i class='fas fa-inbox'></i></div>";
                echo "<div class='card'><div class='info'><p>New Inquiries</p><h3 style='color:#8b5cf6;'>{$rinq['c']}</h3></div><i class='fas fa-envelope-open-text' style='color:#8b5cf6;opacity:0.3;'></i></div>";
            }
            if($staff_role == 'Repair Technician') {
                $rt_tot = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM Repair"));
                $rt_pend = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM Repair WHERE repairStatus='Pending'"));
                echo "<div class='card'><div class='info'><p>Total Repairs</p><h3>{$rt_tot['c']}</h3></div><i class='fas fa-tools'></i></div>";
                echo "<div class='card'><div class='info'><p>Pending Repairs</p><h3 style='color:#f59e0b;'>{$rt_pend['c']}</h3></div><i class='fas fa-clock' style='color:#f59e0b;opacity:0.3;'></i></div>";
            }
            // Staff Notes card — shown only to roles other than Admin, Manager, Stock Keeper, Sales Rep, Inquiry Manager, Repair Tech
            if(!in_array($staff_role, ['Administrator', 'Manager', 'Stock Keeper', 'Sales Representative', 'Inquiry Manager', 'Repair Technician'])) {
                echo "<div class='card'><div class='info'><p>Staff Notes</p><h3>{$notes_count}</h3></div><i class='fas fa-comment-dots'></i></div>";
            }
            echo "</div>";
            
            echo "<div class='panel'><h3>Quick Message</h3><p>Ensure sensitive data is handled according to policy. Role functionality is restricted server-side.</p></div>";
        }
        elseif ($view == 'customers' && $staff_role == 'Administrator') {
            // Counts banner
            $rc = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM Customer"));
            echo "<div style='display:flex;gap:20px;margin-bottom:20px;'>";
            echo "<div class='card' style='flex:1'><div class='info'><p>Total Customers</p><h3>{$rc['c']}</h3></div><i class='fas fa-users'></i></div>";
            echo "</div>";

            echo "<div class='panel'><h2>Customer Management</h2>";
            echo "<div style='position:relative; margin-bottom:12px; width:100%; max-width:420px;'>
                    <input type='text' id='customerSearch' onkeyup='filterSingleTable(\"customerSearch\",\"customerTableBody\")'
                        placeholder='Search by Customer ID, Name or Email...'
                        style='width:100%; box-sizing:border-box; padding:9px 35px 9px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; outline:none;'>
                    <i class='fas fa-search' style='position:absolute; right:14px; top:50%; transform:translateY(-50%); color:#9ca3af; pointer-events:none;'></i>
                </div>";
            echo "<div style='overflow-x:auto;'>";
            echo "<table><thead><tr><th>Customer ID</th><th>Full Name</th><th>Email</th><th>Contact</th><th>City</th><th>Gender</th><th>DOB</th><th>Action</th></tr></thead><tbody id='customerTableBody'>";

            $res = mysqli_query($conn, "SELECT * FROM Customer ORDER BY customerID DESC");
            while($r = mysqli_fetch_assoc($res)) {
                echo "<tr>
                    <td>#CUST-" . str_pad($r['customerID'], 4, '0', STR_PAD_LEFT) . "</td>
                    <td>".htmlspecialchars($r['fullName'])."</td>
                    <td>".htmlspecialchars($r['email'])."</td>
                    <td>".htmlspecialchars($r['contactNo'])."</td>
                    <td>".htmlspecialchars($r['city'])."</td>
                    <td>".htmlspecialchars($r['gender'])."</td>
                    <td>".htmlspecialchars($r['date_of_birth'])."</td>
                    <td>
                        <form method='POST' style='display:inline;' onsubmit='return confirm(\"Delete this customer? This cannot be undone.\");'>
                            <input type='hidden' name='customer_id' value='{$r['customerID']}'>
                            <button type='submit' name='delete_customer' class='btn btn-danger' style='padding:5px 10px;font-size:12px;'>
                                <i class='fas fa-trash'></i> Delete
                            </button>
                        </form>
                    </td>
                </tr>";
            }
            echo "</tbody></table></div></div>";
        }
        elseif ($view == 'staff' && $staff_role == 'Administrator') {
            // Counts banner
            $rs_c = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM Staff"));
            echo "<div style='display:flex;gap:20px;margin-bottom:20px;'>";
            echo "<div class='card' style='flex:1'><div class='info'><p>Total Staff Members</p><h3>{$rs_c['c']}</h3></div><i class='fas fa-user-tie'></i></div>";
            echo "</div>";

            // ---- Add Staff Form ----
            $staff_types = ['Manager','Stock Keeper','Sales Representative','Repair Technician','Inquiry Manager'];
            echo "<div class='panel'>
                <h2>Add New Staff Member</h2>
                <form method='POST' style='display:grid;grid-template-columns:1fr 1fr;gap:15px;'>
                    <div class='form-group'><label>Full Name</label><input type='text' name='s_fullName' class='form-control' required></div>
                    <div class='form-group'><label>NIC</label><input type='text' name='s_NIC' class='form-control' required></div>
                    <div class='form-group'><label>Email</label><input type='email' name='s_email' class='form-control' required></div>
                    <div class='form-group'><label>Username</label><input type='text' name='s_userName' class='form-control' required></div>
                    <div class='form-group'><label>Password</label><input type='password' name='s_password' class='form-control' required></div>
                    <div class='form-group'><label>Contact No</label><input type='text' name='s_contactNo' class='form-control'></div>
                    <div class='form-group'><label>Address</label><input type='text' name='s_address' class='form-control'></div>
                    <div class='form-group'><label>City</label><input type='text' name='s_city' class='form-control'></div>
                    <div class='form-group'><label>Gender</label>
                        <select name='s_gender' class='form-control'>
                            <option value='Male'>Male</option>
                            <option value='Female'>Female</option>
                        </select>
                    </div>
                    <div class='form-group'><label>Role</label>
                        <select name='s_staff_type' class='form-control'>";
            foreach($staff_types as $st) {
                echo "<option value='$st'>$st</option>";
            }
            echo "       </select>
                    </div>
                    <div class='form-group'><label>Date of Birth</label><input type='date' name='s_dob' class='form-control'></div>
                    <div class='form-group' style='display:flex;align-items:flex-end;'>
                        <button type='submit' name='add_staff' class='btn btn-primary' style='width:100%;'><i class='fas fa-plus'></i> Add Staff</button>
                    </div>
                </form>
            </div>";

            // ---- Staff Table ----
            echo "<div class='panel'><h2>All Staff Members</h2>";
            echo "<div style='position:relative; margin-bottom:12px; width:100%; max-width:420px;'>
                    <input type='text' id='staffSearch' onkeyup='filterSingleTable(\"staffSearch\",\"staffTableBody\")'
                        placeholder='Search by Staff ID, NIC or Name...'
                        style='width:100%; box-sizing:border-box; padding:9px 35px 9px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; outline:none;'>
                    <i class='fas fa-search' style='position:absolute; right:14px; top:50%; transform:translateY(-50%); color:#9ca3af; pointer-events:none;'></i>
                </div>";
            echo "<div style='overflow-x:auto;'>";
            echo "<table><thead><tr><th>Staff ID</th><th>Full Name</th><th>NIC</th><th>Email</th><th>Contact</th><th>City</th><th>Role</th><th colspan='2'>Actions</th></tr></thead><tbody id='staffTableBody'>";

            $res = mysqli_query($conn, "SELECT * FROM Staff ORDER BY staffID DESC");
            while($r = mysqli_fetch_assoc($res)) {
                echo "<tr id='row-{$r['staffID']}'>
                    <td>#STF-" . str_pad($r['staffID'], 4, '0', STR_PAD_LEFT) . "</td>
                    <td>".htmlspecialchars($r['fullName'])."</td>
                    <td>".htmlspecialchars($r['NIC'])."</td>
                    <td>".htmlspecialchars($r['email'])."</td>
                    <td>".htmlspecialchars($r['contactNo'])."</td>
                    <td>".htmlspecialchars($r['city'])."</td>
                    <td><span style='padding:3px 8px;background:#e0e7ff;color:#3730a3;border-radius:12px;font-size:12px;font-weight:600;'>".htmlspecialchars($r['staff_type'])."</span></td>
                    <td>
                        <button class='btn btn-primary' style='padding:5px 10px;font-size:12px;'
                            onclick=\"openEditModal('{$r['staffID']}','".htmlspecialchars(addslashes($r['fullName']))."','".htmlspecialchars($r['NIC'])."','".htmlspecialchars($r['email'])."','".htmlspecialchars(addslashes($r['userName']))."','".htmlspecialchars($r['contactNo'])."','".htmlspecialchars(addslashes($r['address']))."','".htmlspecialchars($r['city'])."','".htmlspecialchars($r['gender'])."','".htmlspecialchars($r['staff_type'])."','".htmlspecialchars($r['date_of_birth'])."')\">
                            <i class='fas fa-edit'></i> Edit
                        </button>
                    </td>
                    <td>
                        <form method='POST' style='display:inline;' onsubmit='return confirm(\"Delete this staff member?\");'>
                            <input type='hidden' name='staff_id' value='{$r['staffID']}'>
                            <button type='submit' name='delete_staff' class='btn btn-danger' style='padding:5px 10px;font-size:12px;'>
                                <i class='fas fa-trash'></i> Delete
                            </button>
                        </form>
                    </td>
                </tr>";
            }
            echo "</tbody></table></div></div>";

            // ---- Edit Modal ----
            echo "
            <div id='editModal' style='display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;'>
                <div style='background:#fff;border-radius:8px;padding:30px;width:600px;max-width:95%;box-shadow:0 20px 60px rgba(0,0,0,0.3); max-height: 90vh; overflow-y: auto;'>
                    <h3 style='margin-top:0;'>Edit Staff Member</h3>
                    <form method='POST'>
                        <input type='hidden' name='edit_staff_id' id='edit_staff_id'>
                        <div style='display:grid;grid-template-columns:1fr 1fr;gap:15px;'>
                            <div class='form-group'><label>Full Name</label><input type='text' name='edit_fullName' id='edit_fullName' class='form-control' required></div>
                            <div class='form-group'><label>NIC</label><input type='text' name='edit_NIC' id='edit_NIC' class='form-control' required></div>
                            <div class='form-group'><label>Email</label><input type='email' name='edit_email' id='edit_email' class='form-control' required></div>
                            <div class='form-group'><label>Username</label><input type='text' name='edit_userName' id='edit_userName' class='form-control' required></div>
                            <div class='form-group'><label>Contact No</label><input type='text' name='edit_contactNo' id='edit_contactNo' class='form-control'></div>
                            <div class='form-group'><label>City</label><input type='text' name='edit_city' id='edit_city' class='form-control'></div>
                            <div class='form-group' style='grid-column:span 2'><label>Address</label><input type='text' name='edit_address' id='edit_address' class='form-control'></div>
                            <div class='form-group'><label>Gender</label>
                                <select name='edit_gender' id='edit_gender' class='form-control'>
                                    <option value='Male'>Male</option>
                                    <option value='Female'>Female</option>
                                </select>
                            </div>
                            <div class='form-group'><label>Date of Birth</label><input type='date' name='edit_dob' id='edit_dob' class='form-control'></div>
                            <div class='form-group' style='grid-column:span 2'><label>Role</label>
                                <select name='edit_staff_type' id='edit_staff_type' class='form-control'>
                                    <option>Manager</option>
                                    <option>Stock Keeper</option>
                                    <option>Sales Representative</option>
                                    <option>Repair Technician</option>
                                    <option>Inquiry Manager</option>
                                </select>
                            </div>
                        </div>
                        <div style='display:flex;gap:10px;margin-top:20px;'>
                            <button type='submit' name='update_staff' class='btn btn-success' style='flex:1;'><i class='fas fa-save'></i> Save Changes</button>
                            <button type='button' onclick=\"document.getElementById('editModal').style.display='none'\" class='btn btn-danger' style='flex:1;'>Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
            <script>
            function openEditModal(id, name, nic, email, uname, phone, addr, city, gender, role, dob) {
                document.getElementById('edit_staff_id').value   = id;
                document.getElementById('edit_fullName').value   = name;
                document.getElementById('edit_NIC').value        = nic;
                document.getElementById('edit_email').value      = email;
                document.getElementById('edit_userName').value   = uname;
                document.getElementById('edit_contactNo').value  = phone;
                document.getElementById('edit_address').value    = addr;
                document.getElementById('edit_city').value       = city;
                document.getElementById('edit_dob').value        = dob;
                
                const genSel = document.getElementById('edit_gender');
                for(let i=0; i<genSel.options.length; i++) {
                    if(genSel.options[i].value === gender) { genSel.selectedIndex=i; break; }
                }

                const roleSel = document.getElementById('edit_staff_type');
                for(let i=0; i<roleSel.options.length; i++) {
                    if(roleSel.options[i].value === role) { roleSel.selectedIndex=i; break; }
                }
                
                document.getElementById('editModal').style.display = 'flex';
            }
            
            // Close modal when clicking outside
            document.getElementById('editModal').addEventListener('click', function(e) {
                if(e.target === this) this.style.display = 'none';
            });
            </script>";
        }
        elseif ($view == 'analytics' && $staff_role == 'Manager') {

            // ── 1. Get the Date Range from URL (Default: 12 months) ──────────
            $range = isset($_GET['range']) ? $_GET['range'] : '12_months';

            $date_condition = "";
            $group_format_label = "";
            $group_format_key = "";

            // Range එක අනුව SQL Condition එක සහ Chart එකේ Label Format එක වෙනස් කිරීම
            if ($range == '7_days') {
                $date_condition = ">= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                $group_format_label = "'%b %d'"; // දින අනුව (උදා: May 01)
                $group_format_key = "'%Y-%m-%d'";
            } elseif ($range == '30_days') {
                $date_condition = ">= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                $group_format_label = "'%b %d'"; 
                $group_format_key = "'%Y-%m-%d'";
            } elseif ($range == '3_months') {
                $date_condition = ">= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
                $group_format_label = "'%b %Y'"; // මාස අනුව (උදා: May 2026)
                $group_format_key = "'%Y-%m'";
            } elseif ($range == '6_months') {
                $date_condition = ">= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
                $group_format_label = "'%b %Y'"; 
                $group_format_key = "'%Y-%m'";
            } else { 
                $date_condition = ">= DATE_SUB(NOW(), INTERVAL 12 MONTH)";
                $group_format_label = "'%b %Y'"; 
                $group_format_key = "'%Y-%m'";
            }

            // ── 2. Stat Totals (තෝරාගත් කාල සීමාවට අදාළව පමණක්) ─────────────────────
            $total_orders  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM `Order` WHERE orderDate $date_condition"))['c'];
            $total_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(totalAmount),0) as t FROM `Order` WHERE status != 'Rejected' AND orderDate $date_condition"))['t'];
            $total_customers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM Customer"))['c']; 

            // ── 3. Line Chart Query (Dynamic Time Grouping) ──────────────────────
            $monthly_res = mysqli_query($conn,
                "SELECT DATE_FORMAT(orderDate, $group_format_label) AS time_label,
                        DATE_FORMAT(orderDate, $group_format_key) AS time_key,
                        SUM(totalAmount) AS revenue,
                        COUNT(*) AS orders
                 FROM `Order`
                 WHERE orderDate $date_condition
                   AND status != 'Rejected'
                 GROUP BY time_key, time_label
                 ORDER BY time_key ASC");

            $line_labels = []; $line_revenue = []; $line_orders = [];
            while ($mr = mysqli_fetch_assoc($monthly_res)) {
                $line_labels[]  = $mr['time_label'];
                $line_revenue[] = (float)$mr['revenue'];
                $line_orders[]  = (int)$mr['orders'];
            }

            // ── 4. Pie Chart Query (තෝරාගත් කාලයට අදාළව පමණක්) ────────────────────────
            $cat_res = mysqli_query($conn,
                "SELECT c.categoryName,
                        COALESCE(SUM(od.unitPrice * od.quantity), 0) AS cat_revenue
                 FROM Category c
                 LEFT JOIN Product p ON p.categoryID = c.categoryID
                 LEFT JOIN Order_Details od ON od.productID = p.productID
                 LEFT JOIN `Order` o ON o.orderID = od.orderID AND o.status != 'Rejected'
                 WHERE o.orderDate $date_condition
                 GROUP BY c.categoryID, c.categoryName
                 HAVING cat_revenue > 0
                 ORDER BY cat_revenue DESC");

            $pie_labels = []; $pie_data = [];
            while ($cr = mysqli_fetch_assoc($cat_res)) {
                $pie_labels[] = $cr['categoryName'];
                $pie_data[]   = (float)$cr['cat_revenue'];
            }

            // ── 5. Top Products Query (තෝරාගත් කාලයට අදාළව වැඩිපුරම විකිණුන 10) ────────
            $top_products_res = mysqli_query($conn,
                "SELECT p.productName, p.productID,
                        SUM(od.quantity) as soldQty, 
                        SUM(od.quantity * od.unitPrice) as totalRev
                 FROM Order_Details od
                 JOIN Product p ON od.productID = p.productID
                 JOIN `Order` o ON o.orderID = od.orderID
                 WHERE o.status != 'Rejected' AND o.orderDate $date_condition
                 GROUP BY p.productID
                 ORDER BY soldQty DESC
                 LIMIT 10");

            $pie_colors = ['#6366f1','#f59e0b','#10b981','#3b82f6','#ec4899','#8b5cf6'];
            
            // Labels හැදීම
            $range_titles = [
                '7_days' => 'Last 7 Days',
                '30_days' => 'Last 30 Days',
                '3_months' => 'Last 3 Months',
                '6_months' => 'Last 6 Months',
                '12_months' => 'Last 12 Months'
            ];
            $current_title = $range_titles[$range];
            ?>

            <style>
                .tab-btn {
                    background: transparent; border: none; padding: 12px 24px;
                    font-size: 15px; font-weight: 600; color: #64748b;
                    cursor: pointer; border-bottom: 3px solid transparent;
                    transition: 0.3s; outline: none; margin-right: 5px;
                }
                .tab-btn:hover { color: var(--secondary); background: #f8fafc; border-radius: 6px 6px 0 0; }
                .tab-btn.active {
                    color: var(--secondary);
                    border-bottom: 3px solid var(--secondary);
                }
                .tab-content { display: none; animation: fadeIn 0.4s; }
                @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
                .top-prod-table th { background: #f1f5f9; color: #334155; font-weight: 600; text-transform: uppercase; font-size: 12px; }
                .top-prod-table td { font-size: 14px; color: #475569; }
            </style>

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
                <div class="card">
                    <div class="info"><p>Orders (<?php echo $current_title; ?>)</p><h3><?php echo number_format($total_orders); ?></h3></div>
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="card">
                    <div class="info"><p>Revenue (<?php echo $current_title; ?>)</p><h3>LKR <?php echo number_format($total_revenue, 0); ?></h3></div>
                    <i class="fas fa-coins"></i>
                </div>
                <div class="card">
                    <div class="info"><p>Total Customers</p><h3><?php echo number_format($total_customers); ?></h3></div>
                    <i class="fas fa-users"></i>
                </div>
            </div>

            <div style="margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; display:flex;">
                <button class="tab-btn active" onclick="openTab('salesTab', this)"><i class="fas fa-chart-line"></i> Sales Trend</button>
                <button class="tab-btn" onclick="openTab('categoryTab', this)"><i class="fas fa-chart-pie"></i> Revenue by Category</button>
                <button class="tab-btn" onclick="openTab('topProductsTab', this)"><i class="fas fa-star"></i> Top Products</button>
            </div>

            <div id="salesTab" class="tab-content" style="display:block;">
                <div class="panel">
                    <h2 style="margin-top:0;">Sales Overview <span style="font-size:13px;font-weight:400;color:#888;">(<?php echo $current_title; ?>)</span>
                        <a href="api/generate_pdf.php?type=sales" target="_blank" class="btn btn-primary" style="font-size:12px; float:right;"><i class="fas fa-file-pdf"></i> Export Report</a>
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

            <script>
            // Tabs Script
            function openTab(tabId, btn) {
                const contents = document.querySelectorAll('.tab-content');
                contents.forEach(c => c.style.display = 'none');
                
                const btns = document.querySelectorAll('.tab-btn');
                btns.forEach(b => b.classList.remove('active'));
                
                document.getElementById(tabId).style.display = 'block';
                btn.classList.add('active');
            }

            // Sales Line Chart (Chart.js)
            new Chart(document.getElementById('salesLineChart'), {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($line_labels); ?>,
                    datasets: [
                        {
                            label: 'Revenue (LKR)',
                            data: <?php echo json_encode($line_revenue); ?>,
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99,102,241,0.08)',
                            tension: 0.45,
                            fill: true,
                            pointRadius: 5,
                            pointBackgroundColor: '#6366f1',
                            yAxisID: 'y'
                        },
                        {
                            label: 'Orders',
                            data: <?php echo json_encode($line_orders); ?>,
                            borderColor: '#f59e0b',
                            backgroundColor: 'rgba(245,158,11,0.06)',
                            tension: 0.45,
                            fill: true,
                            pointRadius: 5,
                            pointBackgroundColor: '#f59e0b',
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: {
                            callbacks: {
                                label: ctx => ctx.dataset.yAxisID === 'y'
                                    ? ' LKR ' + ctx.parsed.y.toLocaleString()
                                    : ' ' + ctx.parsed.y + ' orders'
                            }
                        }
                    },
                    scales: {
                        y:  { type:'linear', display:true, position:'left',
                              ticks: { callback: v => 'LKR ' + v.toLocaleString() } },
                        y1: { type:'linear', display:true, position:'right',
                              grid: { drawOnChartArea:false },
                              ticks: { stepSize:1 } }
                    }
                }
            });

            // Category Pie Chart (Chart.js)
            new Chart(document.getElementById('categoryPieChart'), {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode($pie_labels); ?>,
                    datasets: [{
                        data: <?php echo json_encode($pie_data); ?>,
                        backgroundColor: <?php echo json_encode(array_slice($pie_colors, 0, count($pie_labels))); ?>,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => ' LKR ' + ctx.parsed.toLocaleString()
                            }
                        }
                    }
                }
            });
            </script>
            <?php
        }
        elseif ($view == 'reports' && $staff_role == 'Manager') {
            // Stat summary for the reports page
            $total_orders    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM `Order`"))['c'];
            $total_revenue   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(totalAmount),0) as t FROM `Order` WHERE status != 'Rejected'"))['t'];
            $total_customers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM Customer"))['c'];
            ?>
            <h2 style="margin:0 0 20px;">PDF Reports</h2>
            <div class="dashboard-cards" style="margin-bottom:25px;">
                <div class="card"><div class="info"><p>Total Orders</p><h3><?php echo number_format($total_orders); ?></h3></div><i class="fas fa-shopping-cart"></i></div>
                <div class="card"><div class="info"><p>Total Revenue</p><h3>LKR <?php echo number_format($total_revenue, 0); ?></h3></div><i class="fas fa-coins"></i></div>
                <div class="card"><div class="info"><p>Customers</p><h3><?php echo number_format($total_customers); ?></h3></div><i class="fas fa-users"></i></div>
            </div>
            <div class="panel">
                <h2 style="margin-top:0;">Generate Analytical Reports</h2>
                <p style="color:#666;margin-bottom:25px;">Click a report below to open a print-ready PDF containing full analytics data.</p>
                <div style="display:flex;gap:15px;flex-wrap:wrap;">
                    <a href="api/generate_pdf.php?type=sales" target="_blank" class="btn btn-primary" style="padding:12px 24px;font-size:14px;">
                        <i class="fas fa-chart-line"></i> &nbsp;Sales Analytics Report
                    </a>
                    <a href="api/generate_pdf.php?type=inventory" target="_blank" class="btn btn-success" style="padding:12px 24px;font-size:14px;">
                        <i class="fas fa-boxes"></i> &nbsp;Inventory Report
                    </a>
                    <a href="api/generate_pdf.php?type=customers" target="_blank" class="btn" style="background:#8b5cf6;padding:12px 24px;font-size:14px;">
                        <i class="fas fa-users"></i> &nbsp;Customer Report
                    </a>
                </div>
            </div>
            <?php
        }
        elseif ($view == 'inventory' && $staff_role == 'Stock Keeper') {

            // ── Summary count cards ──────────────────────────────────────────
            $total_p = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM Product"))['c'];
            $low_p   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM Product WHERE quantity_in_stock <= 3"))['c'];
            $cat_res = mysqli_query($conn, "SELECT categoryID, categoryName FROM Category ORDER BY categoryName");
            $categories = [];
            while($cat = mysqli_fetch_assoc($cat_res)) { $categories[] = $cat; }
            ?>

            <div class="dashboard-cards" style="margin-bottom:25px;">
                <div class="card">
                    <div class="info"><p>Total Products</p><h3><?php echo $total_p; ?></h3></div>
                    <i class="fas fa-box"></i>
                </div>
                <div class="card" style="border-left:4px solid var(--danger);">
                    <div class="info"><p>Low Stock (&le;3 units)</p><h3 style="color:var(--danger);"><?php echo $low_p; ?></h3></div>
                    <i class="fas fa-exclamation-triangle" style="color:var(--danger);opacity:0.3;"></i>
                </div>
            </div>

            <?php if($low_p > 0): 
                $low_stock_query = mysqli_query($conn, "SELECT productID, productName, quantity_in_stock FROM Product WHERE quantity_in_stock <= 3 ORDER BY quantity_in_stock ASC");
            ?>
            <div style="background:#fee2e2; border:1px solid #ef4444; border-radius:6px; margin-bottom:20px; overflow:hidden;">
                <!-- Header / Toggle -->
                <div onclick="toggleLowStock()" style="padding:12px 16px; color:#991b1b; cursor:pointer; display:flex; justify-content:space-between; align-items:center; transition:background 0.2s;" onmouseover="this.style.background='#fca5a5'" onmouseout="this.style.background='transparent'">
                    <div>
                        <i class="fas fa-exclamation-circle"></i>
                        <strong>Critical Alert:</strong> <?php echo $low_p; ?> product(s) have 3 or fewer units in stock. Restock immediately.
                    </div>
                    <i id="lowStockArrow" class="fas fa-chevron-down" style="transition:transform 0.3s;"></i>
                </div>
                <!-- Expandable List -->
                <div id="lowStockList" style="display:none; background:#fff; padding:15px; border-top:1px solid #fca5a5;">
                    <table style="width:100%; border-collapse:collapse; font-size:14px;">
                        <thead>
                            <tr style="text-align:left; color:#7f1d1d; border-bottom:1px solid #fecaca;">
                                <th style="padding:8px;">Product ID</th>
                                <th style="padding:8px;">Product Name</th>
                                <th style="padding:8px;">Current Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($low_stock_query)): ?>
                            <tr style="border-bottom:1px solid #fef2f2;">
                                <td style="padding:8px; font-weight:600; color:#ef4444;">#PROD-<?php echo str_pad($row['productID'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td style="padding:8px; color:#450a0a;"><?php echo htmlspecialchars($row['productName']); ?></td>
                                <td style="padding:8px; font-weight:bold; color:<?php echo $row['quantity_in_stock'] == 0 ? '#b91c1c' : '#dc2626'; ?>;">
                                    <?php echo $row['quantity_in_stock']; ?> units
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <script>
                function toggleLowStock() {
                    const list = document.getElementById('lowStockList');
                    const arrow = document.getElementById('lowStockArrow');
                    if (list.style.display === 'none') {
                        list.style.display = 'block';
                        arrow.style.transform = 'rotate(180deg)';
                    } else {
                        list.style.display = 'none';
                        arrow.style.transform = 'rotate(0deg)';
                    }
                }
            </script>
            <?php endif; ?>

            <!-- ── Add Product Form ────────────────────────────────────────── -->
            <div class="panel">
                <h2 style="margin-top:0;">Add New Product</h2>
                <form method="POST" enctype="multipart/form-data" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:15px;">
                    <div class="form-group"><label>Category</label>
                        <select name="p_categoryID" class="form-control">
                            <?php foreach($categories as $cat): ?>
                            <option value="<?php echo $cat['categoryID']; ?>"><?php echo htmlspecialchars($cat['categoryName']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Product Name</label><input type="text" name="p_name" class="form-control" required></div>
                    <div class="form-group"><label>Brand</label><input type="text" name="p_brand" class="form-control" list="brand_list" required></div>
                    <div class="form-group" style="grid-column:span 3;">
                        <h4 style="margin-top: 0; margin-bottom: 15px; font-size: 15px;">Product Specifications</h4>
                        <div id="dynamic_specs_container" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                        </div>
                    </div>
                    <div class="form-group" style="grid-column:span 3"><label>Description</label><input type="text" name="p_description" class="form-control"></div>
                    <div class="form-group"><label>Price (LKR)</label><input type="number" step="0.01" name="p_price" class="form-control" required></div>
                    <div class="form-group"><label>Old Price (LKR)</label><input type="number" step="0.01" name="p_old_price" class="form-control" value="0"></div>
                    <div class="form-group"><label>Stock Qty</label><input type="number" name="p_qty" class="form-control" min="0" required></div>
                    <div class="form-group"><label>Warranty (months)</label><input type="number" name="p_warranty" class="form-control" value="12"></div>
                    <div class="form-group"><label>Product Image</label><input type="file" name="p_image" class="form-control" accept="image/*"></div>
                    <div class="form-group"><label>Status</label>
                        <select name="p_status" class="form-control">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="form-group" style="display:flex;align-items:flex-end;">
                        <button type="submit" name="add_product" class="btn btn-primary" style="width:100%;">
                            <i class="fas fa-plus"></i> Add Product
                        </button>
                    </div>
                </form>
            </div>

            <!-- ── Products Tables (Category-wise) ───────────────────────── -->
            <div style="position: relative; margin-bottom: 18px; width: 100%; max-width: 420px;">
                <input type="text" id="productSearch" oninput="filterProductTables()"
                    placeholder="Search by Product ID or Name..."
                    style="width: 100%; box-sizing: border-box; padding: 9px 35px 9px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none;">
                <!-- search icon -->
                <i class="fas fa-search" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: #9ca3af; pointer-events: none;"></i>
            </div>
            <?php
            // Fetch all categories
            $cat_list_res = mysqli_query($conn, "SELECT categoryID, categoryName FROM Category ORDER BY categoryName ASC");
            $all_categories = [];
            while ($c = mysqli_fetch_assoc($cat_list_res)) {
                $all_categories[] = $c;
            }

            foreach ($all_categories as $cat):
                $cat_id   = $cat['categoryID'];
                $cat_name = htmlspecialchars($cat['categoryName']);

                // Count products in this category
                $count_res = mysqli_fetch_assoc(mysqli_query($conn,
                    "SELECT COUNT(*) AS cnt FROM Product WHERE categoryID = $cat_id"));
                $cat_count = $count_res['cnt'];

                // Fetch products in this category, low-stock first
                $prod_res = mysqli_query($conn,
                    "SELECT p.*, cat.categoryName FROM Product p
                     LEFT JOIN Category cat ON cat.categoryID = p.categoryID
                     WHERE p.categoryID = $cat_id
                     ORDER BY p.quantity_in_stock ASC, p.productName ASC");
            ?>
            <div class="panel" style="margin-bottom:20px;">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:15px;">
                    <h2 style="margin:0;">
                        <i class="fas fa-tag" style="color:var(--primary);margin-right:8px;"></i>
                        <?php echo $cat_name; ?>
                        <span style="background:var(--primary);color:#fff;font-size:12px;padding:2px 10px;border-radius:20px;margin-left:10px;font-weight:500;">
                            <?php echo $cat_count; ?> product<?php echo $cat_count != 1 ? 's' : ''; ?>
                        </span>
                    </h2>
                    <!-- Collapse/expand toggle -->
                    <button type="button"
                        onclick="toggleTable('cat-table-<?php echo $cat_id; ?>', this)"
                        style="background:none;border:1px solid var(--border);padding:4px 12px;border-radius:6px;cursor:pointer;font-size:13px;color:var(--text-muted);">
                        <i class="fas fa-chevron-up"></i> Collapse
                    </button>
                </div>

                <?php if ($cat_count == 0): ?>
                <p style="color:var(--text-muted); padding:10px 0;">No products in this category yet.</p>
                <?php else: ?>
                <div id="cat-table-<?php echo $cat_id; ?>" style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Product ID</th><th>Name</th><th>Brand</th>
                            <th>Price (LKR)</th><th>Old Price</th><th>Stock</th><th>Warranty</th>
                            <th>Status</th><th colspan="2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while($pr = mysqli_fetch_assoc($prod_res)):
                        $low_row  = $pr['quantity_in_stock'] <= 3;
                        $stat_clr = $pr['status'] === 'Active' ? '#d1fae5;color:#065f46' : '#fee2e2;color:#991b1b';
                        
                        // මේ Product එකට අදාළ Specs ටික අරගෙන JSON කරනවා
                        $pr_id = $pr['productID'];
                        $spec_q = mysqli_query($conn, "SELECT attributeName, attributeValue FROM Product_Specification WHERE productID = $pr_id");
                        $specs = [];
                        while($s = mysqli_fetch_assoc($spec_q)) { 
                            $specs[$s['attributeName']] = $s['attributeValue']; 
                        }
                        $specs_json = htmlspecialchars(json_encode($specs), ENT_QUOTES, 'UTF-8');
                    ?>
                    <tr style="<?php echo $low_row ? 'background:#fff7ed;' : ''; ?>">
                        <td>#PROD-<?php echo str_pad($pr['productID'], 4, '0', STR_PAD_LEFT); ?></td>
                        <td>
                            <?php echo htmlspecialchars($pr['productName']); ?>
                            <?php if($low_row): ?>
                            <span style="background:#fee2e2;color:#991b1b;font-size:10px;padding:2px 6px;border-radius:8px;margin-left:5px;">LOW</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($pr['brand']); ?></td>
                        <td><?php echo number_format($pr['price'], 2); ?></td>
                        <td><?php echo $pr['oldPrice'] > 0 ? number_format($pr['oldPrice'], 2) : '—'; ?></td>
                        <td style="<?php echo $low_row ? 'color:var(--danger);font-weight:700;' : ''; ?>">
                            <?php echo $pr['quantity_in_stock']; ?>
                        </td>
                        <td><?php echo $pr['warrantyPeriod']; ?> mo</td>
                        <td><span style="padding:3px 8px;border-radius:12px;font-size:11px;font-weight:600;background:<?php echo $stat_clr; ?>"><?php echo $pr['status']; ?></span></td>
                        <td>
                            <button class="btn btn-primary" style="padding:5px 10px;font-size:12px;"
                                onclick="openProdModal(
                                    '<?php echo $pr['productID']; ?>',
                                    '<?php echo addslashes(htmlspecialchars($pr['productName'])); ?>',
                                    '<?php echo addslashes(htmlspecialchars($pr['brand'])); ?>',
                                    '<?php echo $pr['categoryID']; ?>',
                                    '<?php echo addslashes(htmlspecialchars($pr['description'])); ?>',
                                    '<?php echo $pr['price']; ?>',
                                    '<?php echo $pr['oldPrice']; ?>',
                                    '<?php echo $pr['quantity_in_stock']; ?>',
                                    '<?php echo $pr['warrantyPeriod']; ?>',
                                    '<?php echo $pr['status']; ?>',
                                    '<?php echo $specs_json; ?>' )">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                        </td>
                        <td>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this product? This cannot be undone.');">
                                <input type="hidden" name="product_id" value="<?php echo $pr['productID']; ?>">
                                <button type="submit" name="delete_product" class="btn btn-danger" style="padding:5px 10px;font-size:12px;">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>

            <script>
            // Toggle collapse/expand for each category table
            function toggleTable(tableId, btn) {
                const el = document.getElementById(tableId);
                if (el.style.display === 'none') {
                    el.style.display = '';
                    btn.innerHTML = '<i class="fas fa-chevron-up"></i> Collapse';
                } else {
                    el.style.display = 'none';
                    btn.innerHTML = '<i class="fas fa-chevron-down"></i> Expand';
                }
            }
            </script>


            <!-- ── Edit Product Modal ─────────────────────────────────────── -->
            <div id="prodModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;padding:20px;">
                <div style="background:#fff;border-radius:8px;padding:30px;width:560px;max-width:95%; max-height:90vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,0.3);">
                    <h3 style="margin-top:0;">Edit Product</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="edit_product_id" id="edit_product_id">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                            <div class="form-group"><label>Product Name</label><input type="text" name="edit_name" id="edit_name" class="form-control" required></div>
                            <div class="form-group"><label>Brand</label><input type="text" name="edit_brand" id="edit_brand" class="form-control" list="brand_list"></div>
                            <div class="form-group"><label>Category</label>
                                <select name="edit_categoryID" id="edit_categoryID" class="form-control">
                                    <?php foreach($categories as $cat): ?>
                                    <option value="<?php echo $cat['categoryID']; ?>"><?php echo htmlspecialchars($cat['categoryName']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group"><label>Price (LKR)</label><input type="number" step="0.01" name="edit_price" id="edit_price" class="form-control" required></div>
                            <div class="form-group" style="grid-column:span 2"><label>Description</label><input type="text" name="edit_description" id="edit_description" class="form-control"></div>
                            <div class="form-group" style="grid-column:span 2; border-top: 1px solid #eee; padding-top: 15px;">
                                <h4 style="margin-top: 0; margin-bottom: 15px; font-size: 15px;">Product Specifications</h4>
                                <div id="edit_dynamic_specs_container" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                </div>
                            </div>
                            <div class="form-group"><label>Old Price (LKR)</label><input type="number" step="0.01" name="edit_old_price" id="edit_old_price" class="form-control"></div>
                            <div class="form-group"><label>Stock Qty</label><input type="number" name="edit_qty" id="edit_qty" class="form-control" min="0"></div>
                            <div class="form-group"><label>Warranty (months)</label><input type="number" name="edit_warranty" id="edit_warranty" class="form-control"></div>
                            <div class="form-group"><label>New Image (optional)</label><input type="file" name="edit_image" class="form-control" accept="image/*"></div>
                            <div class="form-group" style="grid-column:span 2"><label>Status</label>
                                <select name="edit_status" id="edit_status" class="form-control">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div style="display:flex;gap:10px;margin-top:15px;">
                            <button type="submit" name="update_product" class="btn btn-success" style="flex:1;"><i class="fas fa-save"></i> Save Changes</button>
                            <button type="button" onclick="document.getElementById('prodModal').style.display='none'" class="btn btn-danger" style="flex:1;">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
            <script>
            // --- Edit Product Modal එක සඳහා ---
                function loadEditSpecFields(categoryText, specsObj = {}) {
                    const container = document.getElementById('edit_dynamic_specs_container');
                    const brandInput = document.getElementById('edit_brand');
                    let html = '';
                    const cat = categoryText.toLowerCase();
                    const getVal = (key) => specsObj[key] ? specsObj[key] : '';

                    if (cat.includes('laptop') || cat.includes('desktop')) {
                        if(brandInput) brandInput.setAttribute('list', 'brands_computers');
                        html = `
                            <div><label>Processor</label><input type="hidden" name="edit_spec_names[]" value="processor"><input type="text" name="edit_spec_values[]" class="form-control" list="proc_list" value="${getVal('processor')}"></div>
                            <div><label>RAM</label><input type="hidden" name="edit_spec_names[]" value="ram"><input type="text" name="edit_spec_values[]" class="form-control" list="ram_list" value="${getVal('ram')}"></div>
                            <div><label>Storage</label><input type="hidden" name="edit_spec_names[]" value="storage"><input type="text" name="edit_spec_values[]" class="form-control" list="storage_list" value="${getVal('storage')}"></div>
                            <div><label>Graphics Card</label><input type="hidden" name="edit_spec_names[]" value="grpCard"><input type="text" name="edit_spec_values[]" class="form-control" list="gpu_list" value="${getVal('grpCard')}"></div>
                            ${cat.includes('laptop') ? `<div><label>Screen Size</label><input type="hidden" name="edit_spec_names[]" value="scrSiz"><input type="text" name="edit_spec_values[]" class="form-control" list="screen_list" value="${getVal('scrSiz')}"></div>` : ''}
                            <div><label>Use Type</label><input type="hidden" name="edit_spec_names[]" value="useType"><input type="text" name="edit_spec_values[]" class="form-control" list="use_list" value="${getVal('useType')}"></div>
                        `;
                    } else if (cat.includes('component')) {
                        if(brandInput) brandInput.setAttribute('list', 'brands_components');
                        html = `<div><label>Component Type</label><input type="hidden" name="edit_spec_names[]" value="useType"><input type="text" name="edit_spec_values[]" class="form-control" list="component_type_list" value="${getVal('useType')}"></div>`;
                    } else if (cat.includes('accessori')) {
                        if(brandInput) brandInput.setAttribute('list', 'brands_accessories');
                        html = `<div><label>Accessory Type</label><input type="hidden" name="edit_spec_names[]" value="useType"><input type="text" name="edit_spec_values[]" class="form-control" list="accessory_type_list" value="${getVal('useType')}"></div>`;
                    } else if (cat.includes('audio')) {
                        if(brandInput) brandInput.setAttribute('list', 'brands_audio');
                        html = `<div><label>Audio Type</label><input type="hidden" name="edit_spec_names[]" value="useType"><input type="text" name="edit_spec_values[]" class="form-control" list="audio_type_list" value="${getVal('useType')}"></div>`;
                    } else if (cat.includes('storage')) {
                        if(brandInput) brandInput.setAttribute('list', 'brands_storage');
                        html = `
                            <div><label>Storage Capacity</label><input type="hidden" name="edit_spec_names[]" value="storage"><input type="text" name="edit_spec_values[]" class="form-control" list="storage_list" value="${getVal('storage')}"></div>
                            <div><label>Storage Type</label><input type="hidden" name="edit_spec_names[]" value="useType"><input type="text" name="edit_spec_values[]" class="form-control" list="storage_type_list" value="${getVal('useType')}"></div>
                        `;
                    } else {
                        if(brandInput) brandInput.setAttribute('list', 'brands_general');
                        html = `<div><label>Product Type</label><input type="hidden" name="edit_spec_names[]" value="useType"><input type="text" name="edit_spec_values[]" class="form-control" list="use_list" value="${getVal('useType')}"></div>`;
                    }
                    container.innerHTML = html;
                }

            // Category වෙනස් කරද්දි හිස් fields පෙන්නන්න
            document.getElementById('edit_categoryID').addEventListener('change', function() {
                loadEditSpecFields(this.options[this.selectedIndex].text, {});
            });

            // අලුත් openProdModal Function එක (specsJson පරාමිතියත් එක්ක)
            function openProdModal(id, name, brand, catID, desc, price, oldPrice, qty, warranty, status, specsJson) {
                document.getElementById('edit_product_id').value = id;
                document.getElementById('edit_name').value       = name;
                document.getElementById('edit_brand').value      = brand;
                document.getElementById('edit_description').value = desc;
                document.getElementById('edit_price').value      = price;
                document.getElementById('edit_old_price').value  = oldPrice;
                document.getElementById('edit_qty').value        = qty;
                document.getElementById('edit_warranty').value   = warranty;
                
                const catSel = document.getElementById('edit_categoryID');
                for (let i = 0; i < catSel.options.length; i++) {
                    if (catSel.options[i].value == catID) { catSel.selectedIndex = i; break; }
                }

                const statSel = document.getElementById('edit_status');
                for (let i = 0; i < statSel.options.length; i++) {
                    if (statSel.options[i].value === status) { statSel.selectedIndex = i; break; }
                }

                // Specs JSON එක Object එකක් කරලා Fields වලට පුරවනවා
                let specsObj = {};
                if (specsJson) {
                    try { specsObj = JSON.parse(specsJson); } catch(e) {}
                }
                loadEditSpecFields(catSel.options[catSel.selectedIndex].text, specsObj);

                document.getElementById('prodModal').style.display = 'flex';
            }

            document.getElementById('prodModal').addEventListener('click', function(e) {
                if (e.target === this) this.style.display = 'none';
            });;
            </script>
            <?php
        }
        elseif ($view == 'orders' && $staff_role == 'Sales Representative') {
            echo "<h2>Manage Orders</h2>";
            echo "<div style='position:relative; margin-bottom:18px; width:100%; max-width:420px;'>
                    <input type='text' id='orderSearch' oninput='filterOrderPanels()'
                        placeholder='Search by Order ID or Customer Name...'
                        style='width:100%; box-sizing:border-box; padding:9px 35px 9px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; outline:none;'>
                    <i class='fas fa-search' style='position:absolute; right:14px; top:50%; transform:translateY(-50%); color:#9ca3af; pointer-events:none;'></i>
                </div>";

            // We will collect order details to pass to JS
            $ordersData = [];
            $ordersByStatus = [
                'Pending' => [],
                'Processing' => [],
                'Shipped' => [],
                'Delivered' => [],
                'Rejected' => []
            ];

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
                // Fetch items for this order
                $oid = $r['orderID'];
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

                // Group by status
                $status = $r['status'];
                if (!isset($ordersByStatus[$status])) {
                    $ordersByStatus[$status] = []; // Fallback if unexpected status
                }
                $ordersByStatus[$status][] = $r;
            }

            // Render tables for each status category
            $statusColors = [
                'Pending' => '#f59e0b',
                'Processing' => '#3b82f6',
                'Shipped' => '#10b981',
                'Delivered' => '#64748b',
                'Rejected' => '#ef4444'
            ];

            foreach ($ordersByStatus as $status => $ordersList) {
                if (empty($ordersList)) continue; // Skip if no orders in this category

                $color = isset($statusColors[$status]) ? $statusColors[$status] : '#333';
                
                echo "<div class='panel' style='border-left:4px solid {$color};'>";
                echo "<h3 style='margin-top:0; color:{$color};'>{$status} Orders (" . count($ordersList) . ")</h3>";
                echo "<table><tr><th>Order ID</th><th>Date</th><th>Amount</th><th>Status</th><th colspan='2'>Action</th></tr>";

                foreach ($ordersList as $r) {
                    $custSearch = strtolower($r['customerName']);
                    $ordIdFormatted = 'ord-' . str_pad($r['orderID'], 4, '0', STR_PAD_LEFT);
                    echo "<tr data-search='{$custSearch} {$ordIdFormatted}'>
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
                        <button class='btn btn-primary' style='padding:6px 12px;font-size:12px;' onclick='showOrderDetails({$r['orderID']})'>
                            <i class='fas fa-eye'></i> View
                        </button>
                    </td></tr>";
                }
                echo "</table></div>";
            }
            ?>
            <!-- Order Details Modal -->
            <div id="orderModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:9999;align-items:center;justify-content:center;padding:20px;">
                <div style="background:#fff;border-radius:12px;padding:30px;width:700px;max-width:100%;max-height:90vh;overflow-y:auto;box-shadow:0 25px 50px -12px rgba(0,0,0,0.5);">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;border-bottom:1px solid #eee;padding-bottom:15px;">
                        <h2 style="margin:0;color:var(--dark);">Order Details <span id="mod_oid" style="color:var(--accent);"></span></h2>
                        <button onclick="document.getElementById('orderModal').style.display='none'" style="background:none;border:none;font-size:24px;cursor:pointer;color:#888;">&times;</button>
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

            <script>
            const ordersData = <?php echo json_encode($ordersData); ?>;

            function showOrderDetails(oid) {
                const order = ordersData[oid];
                if(!order) return;

                const formattedOid = 'ORD-' + String(oid).padStart(4, '0');
                document.getElementById('mod_oid').innerText = '#' + formattedOid;
                document.getElementById('mod_cname').innerText = order.customerName;
                document.getElementById('mod_cemail').innerText = order.customerEmail;
                document.getElementById('mod_cphone').innerText = order.customerPhone;
                document.getElementById('mod_caddr').innerText = order.customerAddress;
                document.getElementById('mod_ccity').innerText = order.customerCity;

                document.getElementById('mod_pmethod').innerText = order.paymentMethod || 'N/A';
                document.getElementById('mod_pstatus').innerText = order.paymentStatus || 'N/A';
                document.getElementById('mod_pdate').innerText = order.paymentDate || 'N/A';
                document.getElementById('mod_ostatus').innerText = order.status;

                document.getElementById('mod_total').innerText = parseFloat(order.totalAmount).toLocaleString('en-US', {minimumFractionDigits: 2});

                const tbody = document.getElementById('mod_items_body');
                tbody.innerHTML = '';
                
                order.items.forEach(item => {
                    const subtotal = item.quantity * item.unitPrice;
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td style="padding:10px;border-bottom:1px solid #e2e8f0;">${item.productName}</td>
                        <td style="padding:10px;border-bottom:1px solid #e2e8f0;text-align:center;">${item.quantity}</td>
                        <td style="padding:10px;border-bottom:1px solid #e2e8f0;text-align:right;">${parseFloat(item.unitPrice).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                        <td style="padding:10px;border-bottom:1px solid #e2e8f0;text-align:right;">${subtotal.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                    `;
                    tbody.appendChild(tr);
                });

                document.getElementById('orderModal').style.display = 'flex';
            }

            // Close modal on outside click
            document.getElementById('orderModal').addEventListener('click', function(e) {
                if(e.target === this) this.style.display = 'none';
            });
            </script>
            <?php
        }
        elseif ($view == 'inquiries' && $staff_role == 'Inquiry Manager') {
            echo "<h2>Customer Inquiries</h2>";
            echo "<div style='position:relative; margin-bottom:18px; width:100%; max-width:420px;'>
                    <input type='text' id='inquirySearch' oninput='filterInquiryTables()'
                        placeholder='Search by Inquiry ID or Customer Name...'
                        style='width:100%; box-sizing:border-box; padding:9px 35px 9px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; outline:none;'>
                    <i class='fas fa-search' style='position:absolute; right:14px; top:50%; transform:translateY(-50%); color:#9ca3af; pointer-events:none;'></i>
                </div>";
            $resPending = mysqli_query($conn, "SELECT i.*, c.fullName, c.email FROM Inquiry i LEFT JOIN Customer c ON i.customerID = c.customerID WHERE i.status='Pending' ORDER BY i.dateSubmitted DESC");
            $resResolved = mysqli_query($conn, "SELECT i.*, c.fullName, c.email FROM Inquiry i LEFT JOIN Customer c ON i.customerID = c.customerID WHERE i.status='Resolved' ORDER BY i.dateSubmitted DESC");
            
            // Pending Table
            echo "<div class='panel' style='border-left:4px solid #f59e0b;'>";
            echo "<h3 style='margin-top:0; color:#f59e0b;'>Pending Inquiries</h3>";
            echo "<table><tr><th>Inquiry ID</th><th>Date</th><th>Customer Info</th><th>Message</th><th>Response Action</th></tr>";
            while($r = mysqli_fetch_assoc($resPending)){
                $cname = $r['fullName'] ? htmlspecialchars($r['fullName']) : 'Guest / Unknown';
                $cemail = $r['email'] ? htmlspecialchars($r['email']) : 'N/A';
                $inqIdFormatted = 'inq-' . str_pad($r['inquiryID'], 4, '0', STR_PAD_LEFT);
                echo "<tr data-search='" . strtolower(strip_tags($cname)) . " {$inqIdFormatted}'><td>#INQ-" . str_pad($r['inquiryID'], 4, '0', STR_PAD_LEFT) . "</td>
                <td style='width:100px;'>{$r['dateSubmitted']}</td>
                <td style='width:200px;'><strong>{$cname}</strong><br><small>{$cemail}</small></td>
                <td style='width:300px;'>".nl2br(htmlspecialchars($r['message']))."</td>
                <td>
                    <form method='POST' style='display:flex; flex-direction:column; gap:5px;'>
                        <input type='hidden' name='inquiry_id' value='{$r['inquiryID']}'>
                        <textarea name='response' class='form-control' rows='2' placeholder='Type reply...' required></textarea>
                        <button type='submit' name='reply_inquiry' class='btn btn-primary'>Send Reply & Resolve</button>
                    </form>
                </td></tr>";
            }
            if(mysqli_num_rows($resPending) == 0) echo "<tr><td colspan='4'>No pending inquiries.</td></tr>";
            echo "</table></div>";

            // Resolved Table
            echo "<div class='panel' style='border-left:4px solid #10b981;'>";
            echo "<h3 style='margin-top:0; color:#10b981;'>Resolved Inquiries</h3>";
            echo "<table><tr><th>Inquiry ID</th><th>Date</th><th>Customer Info</th><th>Message</th><th>Response Given</th></tr>";
            while($r = mysqli_fetch_assoc($resResolved)){
                $cname = $r['fullName'] ? htmlspecialchars($r['fullName']) : 'Guest / Unknown';
                $cemail = $r['email'] ? htmlspecialchars($r['email']) : 'N/A';
                $inqIdFormatted = 'inq-' . str_pad($r['inquiryID'], 4, '0', STR_PAD_LEFT);
                echo "<tr data-search='" . strtolower(strip_tags($cname)) . " {$inqIdFormatted}'><td>#INQ-" . str_pad($r['inquiryID'], 4, '0', STR_PAD_LEFT) . "</td>
                <td style='width:100px;'>{$r['dateSubmitted']}</td>
                <td style='width:200px;'><strong>{$cname}</strong><br><small>{$cemail}</small></td>
                <td style='width:300px;'>".nl2br(htmlspecialchars($r['message']))."</td>
                <td>
                    <div style='color:green;margin-bottom:5px;'><i class='fas fa-check'></i> Resolved</div>
                    <form method='POST' style='display:flex; flex-direction:column; gap:5px;'>
                        <input type='hidden' name='inquiry_id' value='{$r['inquiryID']}'>
                        <textarea name='response' class='form-control' rows='2' required>".htmlspecialchars($r['response'] ?? '')."</textarea>
                        <div style='display:flex; gap:5px;'>
                            <button type='submit' name='reply_inquiry' class='btn btn-success' style='flex:1; font-size:12px;'><i class='fas fa-edit'></i> Update Reply</button>
                            <button type='submit' name='delete_inquiry' class='btn btn-danger' style='padding:5px 10px; font-size:12px;' onclick=\"return confirm('Delete this inquiry? This cannot be undone.');\"><i class='fas fa-trash'></i></button>
                        </div>
                    </form>
                </td></tr>";
            }
            if(mysqli_num_rows($resResolved) == 0) echo "<tr><td colspan='4'>No resolved inquiries.</td></tr>";
            echo "</table></div>";
        }
        elseif ($view == 'repairs' && $staff_role == 'Repair Technician') {
            $cust_res = mysqli_query($conn, "SELECT customerID, fullName FROM Customer ORDER BY fullName ASC");
            
            echo "<div class='panel'>
                <h2 style='margin-top:0;'>Add New Repair Job</h2>
                <form method='POST' style='display:grid;grid-template-columns:1fr 1fr;gap:15px;'>
                    <div class='form-group'><label>Device Name</label><input type='text' name='device_name' class='form-control' required></div>
                    <div class='form-group'><label>Customer Owner</label>
                        <select name='customer_id' class='form-control' required>
                            <option value=''>-- Select Customer --</option>";
                            while($c = mysqli_fetch_assoc($cust_res)){
                                echo "<option value='{$c['customerID']}'>".htmlspecialchars($c['fullName'])."</option>";
                            }
                        echo "</select>
                    </div>
                    <div class='form-group' style='grid-column:span 2;'><label>Issue Description</label><textarea name='issue' class='form-control' rows='2' required></textarea></div>
                    <div class='form-group'><label>Initial Estimated Cost (LKR)</label><input type='number' step='0.01' name='cost' class='form-control' value='0'></div>
                    <div class='form-group' style='display:flex;align-items:flex-end;'>
                        <button type='submit' name='add_repair' class='btn btn-primary' style='width:100%;'><i class='fas fa-plus'></i> Add Repair Job</button>
                    </div>
                </form>
            </div>";

            // Fetch all repairs
            $res = mysqli_query($conn, "
                SELECT r.*, c.fullName 
                FROM Repair r 
                LEFT JOIN Customer c ON r.customerID = c.customerID 
                ORDER BY r.repairID DESC
            ");
            
            $repairs = ['Pending' => [], 'In Progress' => [], 'Completed' => []];
            while($r = mysqli_fetch_assoc($res)){
                $repairs[$r['repairStatus']][] = $r;
            }
            
            echo "<h2 style='margin-top:30px;'>Manage Repairs</h2>";
            echo "<div style='position:relative; margin-bottom:18px; width:100%; max-width:420px;'>
                    <input type='text' id='repairSearch' oninput='filterRepairPanels()'
                        placeholder='Search by Repair ID or Customer Name...'
                        style='width:100%; box-sizing:border-box; padding:9px 35px 9px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; outline:none;'>
                    <i class='fas fa-search' style='position:absolute; right:14px; top:50%; transform:translateY(-50%); color:#9ca3af; pointer-events:none;'></i>
                </div>";

            $panels = [
                'Pending' => ['color' => '#f59e0b', 'title' => 'Pending Repairs'],
                'In Progress' => ['color' => '#3b82f6', 'title' => 'In Progress Repairs'],
                'Completed' => ['color' => '#10b981', 'title' => 'Completed Repairs']
            ];

            foreach($panels as $status => $pinfo) {
                $reps = $repairs[$status];
                $count = count($reps);
                if($count == 0) continue;

                echo "<div class='panel' style='border-left:4px solid {$pinfo['color']}; margin-bottom: 25px;'>";
                echo "<h3 style='margin-top:0; color:{$pinfo['color']};'>{$pinfo['title']} <span style='background:#f1f5f9;color:#64748b;padding:2px 8px;border-radius:12px;font-size:14px;margin-left:10px;'>{$count}</span></h3>";
                echo "<table><tr><th>Repair ID</th><th>Device</th><th>Owner</th><th>Issue</th><th>Estimated Cost</th><th>Status</th><th>Action</th></tr>";
                foreach($reps as $r) {
                    $owner = $r['fullName'] ? htmlspecialchars($r['fullName']) : 'Unknown';
                    $rprIdFormatted = 'rpr-' . str_pad($r['repairID'], 4, '0', STR_PAD_LEFT);
                    echo "<tr data-search='" . strtolower($owner) . " {$rprIdFormatted}'>
                    <td>#RPR-" . str_pad($r['repairID'], 4, '0', STR_PAD_LEFT) . "</td>
                    <td><strong>".htmlspecialchars($r['deviceName'])."</strong></td>
                    <td>{$owner}</td>
                    <td><small>".htmlspecialchars($r['issueDescription'])."</small></td>
                    <td>LKR ".number_format($r['estimatedCost'], 2)."</td>
                    <td>{$r['repairStatus']}</td>
                    <td>
                        <button class='btn btn-primary' style='padding:5px 10px;font-size:12px;' onclick='openRepairEdit({$r['repairID']}, ".json_encode($r['deviceName']).", {$r['customerID']}, ".json_encode($r['issueDescription']).", {$r['estimatedCost']}, \"{$r['repairStatus']}\")'><i class='fas fa-edit'></i> Edit</button>
                        <form method='POST' style='display:inline;' onsubmit='return confirm(\"Delete this repair record?\");'>
                            <input type='hidden' name='repair_id' value='{$r['repairID']}'>
                            <button type='submit' name='delete_repair' class='btn btn-danger' style='padding:5px 10px;font-size:12px;margin-left:5px;'><i class='fas fa-trash'></i></button>
                        </form>
                    </td></tr>";
                }
                echo "</table></div>";
            }
            
            // Repair Edit Modal
            echo "
            <div id='repairModal' style='display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:9999;align-items:center;justify-content:center;'>
                <div style='background:#fff;border-radius:8px;padding:30px;width:500px;max-width:90%;box-shadow:0 10px 25px rgba(0,0,0,0.5);'>
                    <h2 style='margin-top:0;'>Edit Repair Job</h2>
                    <form method='POST' style='display:flex;flex-direction:column;gap:15px;'>
                        <input type='hidden' name='repair_id' id='edit_repair_id'>
                        <div class='form-group'><label>Device Name</label><input type='text' name='device_name' id='edit_r_device' class='form-control' required></div>
                        <div class='form-group'><label>Customer Owner</label>
                            <select name='customer_id' id='edit_r_customer' class='form-control' required>
                                <option value=''>-- Select Customer --</option>";
                                mysqli_data_seek($cust_res, 0);
                                while($c = mysqli_fetch_assoc($cust_res)){
                                    echo "<option value='{$c['customerID']}'>".htmlspecialchars($c['fullName'])."</option>";
                                }
                            echo "</select>
                        </div>
                        <div class='form-group'><label>Issue Description</label><textarea name='issue' id='edit_r_issue' class='form-control' rows='2' required></textarea></div>
                        <div class='form-group'><label>Estimated Cost (LKR)</label><input type='number' step='0.01' name='cost' id='edit_r_cost' class='form-control' required></div>
                        <div class='form-group'><label>Status</label>
                            <select name='status' id='edit_r_status' class='form-control' required>
                                <option value='Pending'>Pending</option>
                                <option value='In Progress'>In Progress</option>
                                <option value='Completed'>Completed</option>
                            </select>
                        </div>
                        <div style='display:flex;gap:10px;margin-top:10px;'>
                            <button type='submit' name='update_repair' class='btn btn-success' style='flex:1;'><i class='fas fa-save'></i> Save Changes</button>
                            <button type='button' class='btn btn-danger' style='flex:1;' onclick='document.getElementById(\"repairModal\").style.display=\"none\"'>Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
            <script>
            function openRepairEdit(id, device, cid, issue, cost, status) {
                document.getElementById('edit_repair_id').value = id;
                document.getElementById('edit_r_device').value = device;
                document.getElementById('edit_r_issue').value = issue;
                document.getElementById('edit_r_cost').value = cost;
                
                const cSel = document.getElementById('edit_r_customer');
                for (let i = 0; i < cSel.options.length; i++) {
                    if (cSel.options[i].value == cid) { cSel.selectedIndex = i; break; }
                }
                
                const sSel = document.getElementById('edit_r_status');
                for (let i = 0; i < sSel.options.length; i++) {
                    if (sSel.options[i].value === status) { sSel.selectedIndex = i; break; }
                }

                document.getElementById('repairModal').style.display = 'flex';
            }
            // Close on outside click
            document.getElementById('repairModal').addEventListener('click', function(e) {
                if (e.target === this) this.style.display = 'none';
            });
            </script>
            ";
        }
        elseif ($view == 'notes') {
            $added = isset($_GET['added']) ? true : false;
            echo "<div class='panel'><h2>Global Staff Notes</h2><p>Cross-departmental broadcast board — visible to all staff.</p>";

            if ($added) {
                echo "<div style='background:#d1fae5;color:#065f46;border:1px solid #10b981;padding:12px;border-radius:4px;margin-bottom:15px;'>
                        <i class='fas fa-check-circle'></i> Note broadcasted successfully.
                      </div>";
            }

            echo "<form method='POST' action='staff_dashboard.php?view=notes' style='margin-bottom:20px; display:flex; gap:10px;'>
                    <input type='text' name='note' class='form-control' style='margin:0; flex:1;' placeholder='Type a note to broadcast to all staff...' required>
                    <button type='submit' name='add_note' class='btn btn-primary'><i class='fas fa-bullhorn'></i> Broadcast</button>
                  </form>";

            // Fetch all notes, join to Staff for author name
            $notes_q = mysqli_query($conn, "SELECT n.noteContent, n.createdAt, s.fullName, s.staff_type
                                            FROM Staff_Notes n
                                            JOIN Staff s ON n.staffID = s.staffID
                                            ORDER BY n.createdAt DESC");

            if (!$notes_q || mysqli_num_rows($notes_q) == 0) {
                echo "<div style='text-align:center;color:#aaa;padding:30px;'><i class='fas fa-inbox' style='font-size:32px;'></i><p>No notes yet. Be the first to broadcast!</p></div>";
            } else {
                while ($note_row = mysqli_fetch_assoc($notes_q)) {
                    // Strip [Admin: ...] prefix for display and label separately
                    $content = htmlspecialchars($note_row['noteContent']);
                    $author  = htmlspecialchars($note_row['fullName']);
                    $role_lbl= htmlspecialchars($note_row['staff_type']);

                    // If admin used the proxy method, extract the embedded label
                    if (preg_match('/^\[Admin: (.+?)\] (.+)$/s', $note_row['noteContent'], $m)) {
                        $author  = htmlspecialchars($m[1]) . ' <span style="color:#ef4444;font-size:11px;">(Admin)</span>';
                        $role_lbl= 'Administrator';
                        $content = htmlspecialchars($m[2]);
                    }

                    echo "<div style='background:#f9f9f9; padding:15px; border-left:3px solid var(--accent); margin-bottom:10px; border-radius:4px;'>
                            <div style='font-size:12px; color:#888; margin-bottom:8px;'>
                                <b>$author</b>
                                <span style='padding:2px 7px;background:#e0e7ff;color:#3730a3;border-radius:10px;font-size:11px;margin-left:6px;'>$role_lbl</span>
                                <span style='margin-left:8px;'>&mdash; ".date('M d, Y g:i A', strtotime($note_row['createdAt']))."</span>
                            </div>
                            <div>$content</div>
                          </div>";
                }
            }

            echo "</div>";
        }
        ?>
    </div>
</main>

<script>
// =====================================================================
// Real-time unread notes badge
// Polls api/get_unread_notes.php every 5 seconds.
// Badge is hidden when already viewing notes (server already marked read).
// =====================================================================
const notesBadge    = document.getElementById('notesBadge');
const currentView   = '<?php echo $view; ?>';

function updateNotesBadge() {
    // No point polling if we're on the notes page — it's already been marked read
    if (currentView === 'notes') {
        notesBadge.style.display = 'none';
        return;
    }

    fetch('api/get_unread_notes.php')
        .then(res => res.json())
        .then(data => {
            const count = data.count || 0;
            if (count > 0) {
                notesBadge.textContent = count > 99 ? '99+' : count;
                if (notesBadge.style.display === 'none') {
                    // Animate in when a new note appears
                    notesBadge.style.display = 'inline-block';
                    notesBadge.style.transform = 'scale(0)';
                    setTimeout(() => {
                        notesBadge.style.transition = 'transform 0.3s ease';
                        notesBadge.style.transform  = 'scale(1)';
                    }, 10);
                } else {
                    notesBadge.textContent = count > 99 ? '99+' : count;
                }
            } else {
                notesBadge.style.display = 'none';
            }
        })
        .catch(() => {}); // Silent fail — badge just stays as-is
}

// Run immediately on page load, then every 5 seconds
updateNotesBadge();
setInterval(updateNotesBadge, 5000);
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const categorySelect = document.querySelector('select[name="p_categoryID"]');
    const specsContainer = document.getElementById('dynamic_specs_container');

    // --- 1. Add Product Form එක සඳහා ---
    function loadSpecFields() {
        const categoryText = categorySelect.options[categorySelect.selectedIndex].text.toLowerCase();
        let html = '';
        const brandInput = document.querySelector('input[name="p_brand"]');

        if (categoryText.includes('laptop') || categoryText.includes('desktop')) {
            if(brandInput) brandInput.setAttribute('list', 'brands_computers');
            html = `
                <div><label>Processor</label><input type="hidden" name="spec_names[]" value="processor"><input type="text" name="spec_values[]" class="form-control" list="proc_list" placeholder="e.g. Intel Core i7"></div>
                <div><label>RAM</label><input type="hidden" name="spec_names[]" value="ram"><input type="text" name="spec_values[]" class="form-control" list="ram_list" placeholder="e.g. 16GB DDR5"></div>
                <div><label>Storage</label><input type="hidden" name="spec_names[]" value="storage"><input type="text" name="spec_values[]" class="form-control" list="storage_list" placeholder="e.g. 512GB SSD"></div>
                <div><label>Graphics Card</label><input type="hidden" name="spec_names[]" value="grpCard"><input type="text" name="spec_values[]" class="form-control" list="gpu_list" placeholder="e.g. NVIDIA RTX 4060"></div>
                ${categoryText.includes('laptop') ? '<div><label>Screen Size</label><input type="hidden" name="spec_names[]" value="scrSiz"><input type="text" name="spec_values[]" class="form-control" list="screen_list" placeholder="e.g. 15.6 inch"></div>' : ''}
                <div><label>Use Type</label><input type="hidden" name="spec_names[]" value="useType"><input type="text" name="spec_values[]" class="form-control" list="use_list" placeholder="e.g. Gaming / Business"></div>
            `;
        } else if (categoryText.includes('component')) {
            if(brandInput) brandInput.setAttribute('list', 'brands_components');
            html = `<div><label>Component Type</label><input type="hidden" name="spec_names[]" value="useType"><input type="text" name="spec_values[]" class="form-control" list="component_type_list" placeholder="e.g. Motherboard / RAM"></div>`;
        } else if (categoryText.includes('accessori')) {
            if(brandInput) brandInput.setAttribute('list', 'brands_accessories');
            html = `<div><label>Accessory Type</label><input type="hidden" name="spec_names[]" value="useType"><input type="text" name="spec_values[]" class="form-control" list="accessory_type_list" placeholder="e.g. Gaming Mouse"></div>`;
        } else if (categoryText.includes('audio')) {
            if(brandInput) brandInput.setAttribute('list', 'brands_audio');
            html = `<div><label>Audio Type</label><input type="hidden" name="spec_names[]" value="useType"><input type="text" name="spec_values[]" class="form-control" list="audio_type_list" placeholder="e.g. Headphones"></div>`;
        } else if (categoryText.includes('storage')) {
            if(brandInput) brandInput.setAttribute('list', 'brands_storage');
            html = `
                <div><label>Storage Capacity</label><input type="hidden" name="spec_names[]" value="storage"><input type="text" name="spec_values[]" class="form-control" list="storage_list" placeholder="e.g. 1TB / 2TB"></div>
                <div><label>Storage Type</label><input type="hidden" name="spec_names[]" value="useType"><input type="text" name="spec_values[]" class="form-control" list="storage_type_list" placeholder="e.g. Internal NVMe SSD"></div>
            `;
        } else {
            if(brandInput) brandInput.setAttribute('list', 'brands_general');
            html = `<div><label>Product Type</label><input type="hidden" name="spec_names[]" value="useType"><input type="text" name="spec_values[]" class="form-control" list="use_list" placeholder="e.g. Router"></div>`;
        }
        specsContainer.innerHTML = html;
    }

    // Category එක වෙනස් කරද්දී load වෙන්න
    if(categorySelect){
        categorySelect.addEventListener('change', loadSpecFields);
        // Page එක load වෙද්දි මුලින්ම තියෙන එකට අදාලව පෙන්නන්න
        loadSpecFields();
    }
});
</script>

<datalist id="brands_computers">
    <option value="Dell"><option value="HP"><option value="Lenovo"><option value="Apple">
    <option value="ASUS"><option value="Acer"><option value="MSI"><option value="Microsoft">
</datalist>

<datalist id="brands_components">
    <option value="Intel"><option value="AMD"><option value="NVIDIA"><option value="Corsair">
    <option value="ASUS"><option value="MSI"><option value="Gigabyte"><option value="Kingston">
</datalist>

<datalist id="brands_accessories">
    <option value="Logitech"><option value="Razer"><option value="Corsair"><option value="Dell">
    <option value="LG"><option value="Samsung"><option value="Keychron">
</datalist>

<datalist id="brands_audio">
    <option value="Sony"><option value="Bose"><option value="JBL"><option value="Logitech">
    <option value="Audio-Technica"><option value="Sennheiser"><option value="Edifier">
</datalist>

<datalist id="brands_storage">
    <option value="Samsung"><option value="Western Digital"><option value="Seagate">
    <option value="Kingston"><option value="Crucial"><option value="Corsair"><option value="SanDisk">
</datalist>

<datalist id="brands_general">
    <option value="Other">
</datalist>

<datalist id="proc_list">
    <option value="Intel Core i3"><option value="Intel Core i5"><option value="Intel Core i7">
    <option value="Intel Core i9"><option value="AMD Ryzen 3"><option value="AMD Ryzen 5">
    <option value="AMD Ryzen 7"><option value="AMD Ryzen 9"><option value="Apple M1">
    <option value="Apple M2"><option value="Apple M3">
</datalist>

<datalist id="ram_list">
    <option value="4GB DDR4"><option value="8GB DDR4"><option value="16GB DDR4"><option value="32GB DDR4">
    <option value="8GB DDR5"><option value="16GB DDR5"><option value="32GB DDR5"><option value="16GB Unified">
</datalist>

<datalist id="storage_list">
    <option value="256GB SSD"><option value="512GB SSD"><option value="1TB SSD"><option value="2TB SSD">
    <option value="1TB HDD"><option value="2TB HDD"><option value="4TB HDD">
</datalist>

<datalist id="gpu_list">
    <option value="Integrated Graphics"><option value="Intel Iris Xe"><option value="NVIDIA GTX 1650">
    <option value="NVIDIA RTX 3050"><option value="NVIDIA RTX 3060"><option value="NVIDIA RTX 4050">
    <option value="NVIDIA RTX 4060"><option value="NVIDIA RTX 4070"><option value="NVIDIA RTX 4090">
    <option value="AMD Radeon RX 6600">
</datalist>

<datalist id="screen_list">
    <option value="13.3 inch"><option value="14 inch"><option value="15.6 inch">
    <option value="16 inch"><option value="17.3 inch"><option value="24 inch"><option value="27 inch">
</datalist>

<datalist id="use_list">
    <option value="Gaming"><option value="Business"><option value="Student"><option value="Professional">
    <option value="Office"><option value="Home"><option value="Workstation"><option value="Internal"><option value="External">
</datalist>

<datalist id="component_type_list">
    <option value="Motherboard"><option value="Processor (CPU)"><option value="Memory (RAM)">
    <option value="Graphics Card (GPU)"><option value="Power Supply (PSU)"><option value="Casing">
    <option value="Cooling System"><option value="Sound Card">
</datalist>

<datalist id="accessory_type_list">
    <option value="Gaming Mouse"><option value="Standard Mouse"><option value="Mechanical Keyboard">
    <option value="Membrane Keyboard"><option value="Monitor"><option value="Webcam">
    <option value="Mousepad"><option value="Cables & Adapters">
</datalist>

<datalist id="audio_type_list">
    <option value="Gaming Headset"><option value="Wireless Headphones"><option value="Earbuds">
    <option value="Bluetooth Speaker"><option value="Microphone"><option value="Studio Monitor">
</datalist>

<datalist id="storage_type_list">
    <option value="Internal NVMe SSD"><option value="Internal SATA SSD"><option value="External SSD">
    <option value="Internal HDD"><option value="External HDD"><option value="Pen Drive">
</datalist>

<script>
/* ── Reusable: filter a single table body by all cell text ─────── */
function filterSingleTable(inputId, tbodyId) {
    const q = document.getElementById(inputId).value.toLowerCase();
    const rows = document.getElementById(tbodyId).querySelectorAll('tr');
    rows.forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}

/* ── Products: filter across all category tables ──────────────── */
function filterProductTables() {
    const q = document.getElementById('productSearch').value.toLowerCase();
    // Each category panel has class "panel" and contains a table
    document.querySelectorAll('.panel').forEach(panel => {
        const rows = panel.querySelectorAll('tbody tr');
        if (!rows.length) return;
        let visible = 0;
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const show = text.includes(q);
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        // Hide the entire category panel if no rows match
        panel.style.display = (!q || visible > 0) ? '' : 'none';
    });
}

/* ── Orders: filter rows using data-search (customerName + ordID) */
function filterOrderPanels() {
    const q = document.getElementById('orderSearch').value.toLowerCase();
    document.querySelectorAll('.panel').forEach(panel => {
        const rows = panel.querySelectorAll('tr[data-search]');
        if (!rows.length) return;
        let visible = 0;
        rows.forEach(row => {
            const search = (row.getAttribute('data-search') || '') + row.textContent.toLowerCase();
            const show = search.includes(q);
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        panel.style.display = (!q || visible > 0) ? '' : 'none';
    });
}

/* ── Inquiries: filter both pending and resolved tables ─────────── */
function filterInquiryTables() {
    const q = document.getElementById('inquirySearch').value.toLowerCase();
    document.querySelectorAll('.panel').forEach(panel => {
        const rows = panel.querySelectorAll('tr[data-search]');
        if (!rows.length) return;
        let visible = 0;
        rows.forEach(row => {
            const search = (row.getAttribute('data-search') || '') + row.textContent.toLowerCase();
            const show = search.includes(q);
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        panel.style.display = (!q || visible > 0) ? '' : 'none';
    });
}

/* ── Repairs: filter rows across all repair status panels ─────── */
function filterRepairPanels() {
    const q = document.getElementById('repairSearch').value.toLowerCase();
    document.querySelectorAll('.panel').forEach(panel => {
        const rows = panel.querySelectorAll('tr[data-search]');
        if (!rows.length) return;
        let visible = 0;
        rows.forEach(row => {
            const search = (row.getAttribute('data-search') || '') + row.textContent.toLowerCase();
            const show = search.includes(q);
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        panel.style.display = (!q || visible > 0) ? '' : 'none';
    });
}
</script>

</body>
</html>
