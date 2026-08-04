<?php
session_start();
include(__DIR__ . '/../includes/dbconnection.php');

// Security Check: If not a Stock Keeper, will be redirected to the login page.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Stock Keeper') {
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
// 1. INVENTORY LOGIC (CRUD Operations)
// ==========================================

// ----- UPDATE STOCK QTY (Quick Update) -----
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
        $uploadDir = '../assets/products/';
        if(!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
        $fileExt = strtolower(pathinfo($_FILES['p_image']['name'], PATHINFO_EXTENSION));
        $fileName = uniqid('prod_') . '.' . $fileExt;
        $uploadFile = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['p_image']['tmp_name'], $uploadFile)) {
            $pImg = $fileName;
        }
    }

    $sql = "INSERT INTO Product (productName, description, brand, price, oldPrice, quantity_in_stock, warrantyPeriod, categoryID, status, productImage, addedDate)
            VALUES ('$pName','$pDesc','$pBrand',$pPrice,$pOld,$pQty,$pWar,$pCat,'$pStatus','$pImg','$pDate')";
    
    if (mysqli_query($conn, $sql)) {
        $new_product_id = mysqli_insert_id($conn);

        // Insert Product Specifications 
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
        $msg = "Product '$pName' added successfully."; $msg_type = "success";
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
        $uploadDir = '../assets/products/';
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
        // Deleting old specs and adding new ones
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
    mysqli_query($conn, "DELETE FROM Product_Specification WHERE productID = $pid");
    if (mysqli_query($conn, "DELETE FROM Product WHERE productID = $pid")) {
        $msg = "Product deleted."; $msg_type = "success";
    } else {
        $msg = "Cannot delete — product may have existing orders."; $msg_type = "danger";
    }
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
            echo "<h2>Dashboard Overview</h2>";
            echo "<div class='dashboard-cards' style='margin-bottom:20px;'>";
            $low_c = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM Product WHERE quantity_in_stock <= 3"))['c'];
            $rp    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM Product"))['c'];
            
            echo "<div class='card'><div class='info'><p>Total Products</p><h3>{$rp}</h3></div><i class='fas fa-box' style='color:#000000;opacity:1;'></i></div>";
            echo "<div class='card' style='border-left:4px solid var(--danger);'><div class='info'><p>Low Stock (&le;3 units)</p><h3 style='color:var(--danger);'>{$low_c}</h3></div><i class='fas fa-exclamation-triangle' style='color:var(--danger);opacity:1;'></i></div>";
            echo "</div>";

            // Products by category breakdown
            echo "<h3 style='margin-bottom:15px; font-size:16px; color:var(--text-primary);'>Products by Category</h3>";
            echo "<div style='display:grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 15px; margin-bottom: 30px;'>";
            $cat_count_res = mysqli_query($conn, "
                SELECT c.categoryName, COUNT(p.productID) as prod_count 
                FROM Category c 
                LEFT JOIN Product p ON c.categoryID = p.categoryID 
                GROUP BY c.categoryID, c.categoryName 
                ORDER BY prod_count DESC
            ");
            while ($cat_row = mysqli_fetch_assoc($cat_count_res)) {
                echo "<div style='background: var(--surface); padding: 15px 20px; border-radius: var(--radius-md); border: 1px solid var(--border); box-shadow: var(--shadow-sm); display:flex; justify-content:space-between; align-items:center;'>";
                echo "<span style='font-weight:600; font-size:14px; color:var(--text-secondary);'>" . htmlspecialchars($cat_row['categoryName']) . "</span>";
                echo "<span style='background: var(--primary-light); color: var(--primary); padding: 4px 12px; border-radius: 20px; font-weight: 700; font-size: 13px;'>" . $cat_row['prod_count'] . "</span>";
                echo "</div>";
            }
            echo "</div>";
            echo "<div class='panel'><h3>Quick Message</h3><p>Ensure sensitive data is handled according to policy. Role functionality is restricted server-side.</p></div>";
        }
        
        // --- 2. INVENTORY VIEW ---
        elseif ($view == 'inventory') {
            $total_p = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM Product"))['c'];
            $low_p   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM Product WHERE quantity_in_stock <= 3"))['c'];
            
            $cat_res = mysqli_query($conn, "SELECT categoryID, categoryName FROM Category ORDER BY categoryName");
            $categories = [];
            while($cat = mysqli_fetch_assoc($cat_res)) { $categories[] = $cat; }
            ?>

            <div class="dashboard-cards" style="margin-bottom:20px;">
                <div class="card"><div class="info"><p>Total Products</p><h3><?php echo $total_p; ?></h3></div><i class="fas fa-box" style='color:#000000;opacity:1;'></i></div>
                <div class="card" style="border-left:4px solid var(--danger);"><div class="info"><p>Low Stock (&le;3 units)</p><h3 style="color:var(--danger);"><?php echo $low_p; ?></h3></div><i class="fas fa-exclamation-triangle" style="color:var(--danger);opacity:1;"></i></div>
            </div>

            <!-- Low Stock Alert -->
            <?php if($low_p > 0): 
                $low_stock_query = mysqli_query($conn, "SELECT productID, productName, quantity_in_stock FROM Product WHERE quantity_in_stock <= 3 ORDER BY quantity_in_stock ASC");
            ?>
            <div style="background:#fee2e2; border:1px solid #ef4444; border-radius:6px; margin-bottom:20px; overflow:hidden;">
                <div onclick="toggleLowStock()" style="padding:12px 16px; color:#991b1b; cursor:pointer; display:flex; justify-content:space-between; align-items:center; transition:background 0.2s;" onmouseover="this.style.background='#fca5a5'" onmouseout="this.style.background='transparent'">
                    <div><i class="fas fa-exclamation-circle"></i> <strong>Critical Alert:</strong> <?php echo $low_p; ?> product(s) have 3 or fewer units in stock. Restock immediately.</div>
                    <i id="lowStockArrow" class="fas fa-chevron-down" style="transition:transform 0.3s;"></i>
                </div>
                <div id="lowStockList" style="display:none; background:#fff; padding:15px; border-top:1px solid #fca5a5;">
                    <table style="width:100%; border-collapse:collapse; font-size:14px;">
                        <thead>
                            <tr style="text-align:left; color:#7f1d1d; border-bottom:1px solid #fecaca;">
                                <th style="padding:8px;">Product ID</th><th style="padding:8px;">Product Name</th><th style="padding:8px;">Current Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($low_stock_query)): ?>
                            <tr style="border-bottom:1px solid #fef2f2;">
                                <td style="padding:8px; font-weight:600; color:#ef4444;">#PROD-<?php echo str_pad($row['productID'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td style="padding:8px; color:#450a0a;"><?php echo htmlspecialchars($row['productName']); ?></td>
                                <td style="padding:8px; font-weight:bold; color:<?php echo $row['quantity_in_stock'] == 0 ? '#b91c1c' : '#dc2626'; ?>;"><?php echo $row['quantity_in_stock']; ?> units</td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <div class="panel">
                <h2 style="margin-top:0;">Add New Product</h2>
                <form method="POST" enctype="multipart/form-data" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:15px;">
                    <div class="form-group"><label>Category</label>
                        <select name="p_categoryID" class="form-control" id="add_categorySelect">
                            <?php foreach($categories as $cat): ?>
                            <option value="<?php echo $cat['categoryID']; ?>"><?php echo htmlspecialchars($cat['categoryName']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Product Name</label><input type="text" name="p_name" class="form-control" required></div>
                    <div class="form-group"><label>Brand</label><input type="text" name="p_brand" class="form-control" list="brand_list" required></div>
                    
                    <div class="form-group" style="grid-column:span 3;">
                        <h4 style="margin-top: 0; margin-bottom: 15px; font-size: 15px;">Product Specifications</h4>
                        <div id="dynamic_specs_container" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;"></div>
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
                        <button type="submit" name="add_product" class="btn btn-primary" style="width:100%;"><i class="fas fa-plus"></i> Add Product</button>
                    </div>
                </form>
            </div>

            <div style="position: relative; margin-bottom: 18px; width: 100%; max-width: 420px;">
                <input type="text" id="productSearch" placeholder="Search by Product ID or Name..." style="width: 100%; box-sizing: border-box; padding: 9px 35px 9px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none;">
                <i class="fas fa-search" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: #9ca3af; pointer-events: none;"></i>
            </div>

            <?php
            foreach ($categories as $cat):
                $cat_id   = $cat['categoryID'];
                $cat_name = htmlspecialchars($cat['categoryName']);
                $count_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM Product WHERE categoryID = $cat_id"));
                $cat_count = $count_res['cnt'];
                $prod_res = mysqli_query($conn, "SELECT p.*, cat.categoryName FROM Product p LEFT JOIN Category cat ON cat.categoryID = p.categoryID WHERE p.categoryID = $cat_id ORDER BY p.quantity_in_stock ASC, p.productName ASC");
            ?>
            <div class="panel cat-panel" style="margin-bottom:20px; padding-bottom:0;">
                <div class='collapsible-panel-header' onclick='togglePanel("cat-table-<?php echo $cat_id; ?>", this.querySelector(".panel-toggle-btn"))'>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <i class="fas fa-tag" style="color:var(--primary);"></i>
                        <span style="font-size:15px;font-weight:700;color:var(--text-primary);"><?php echo $cat_name; ?></span>
                        <span style="background:var(--primary);color:#fff;font-size:11px;padding:2px 10px;border-radius:20px;font-weight:600;"><?php echo $cat_count; ?> products</span>
                    </div>
                    <button class='panel-toggle-btn' type='button'>
                        <i class='fas fa-chevron-down toggle-icon'></i>
                        <span class='toggle-text'>Expand</span>
                    </button>
                </div>

                <?php if ($cat_count == 0): ?>
                    <p style="color:#888; padding:10px 0 16px;">No products in this category yet.</p>
                <?php else: ?>
                <div class='collapsible-body' id="cat-table-<?php echo $cat_id; ?>">
                    <div style="overflow-x:auto; padding: 10px 0 6px;">
                    <table>
                        <thead><tr><th>Product ID</th><th>Name</th><th>Brand</th><th>Price (LKR)</th><th>Old Price</th><th>Stock</th><th>Warranty</th><th>Status</th><th colspan="2">Actions</th></tr></thead>
                        <tbody>
                        <?php while($pr = mysqli_fetch_assoc($prod_res)):
                            $low_row  = $pr['quantity_in_stock'] <= 3;
                            $stat_clr = $pr['status'] === 'Active' ? '#d1fae5;color:#065f46' : '#fee2e2;color:#991b1b';
                            
                            $pr_id = $pr['productID'];
                            $spec_q = mysqli_query($conn, "SELECT attributeName, attributeValue FROM Product_Specification WHERE productID = $pr_id");
                            $specs = [];
                            while($s = mysqli_fetch_assoc($spec_q)) { $specs[$s['attributeName']] = $s['attributeValue']; }
                            $specs_json = htmlspecialchars(json_encode($specs), ENT_QUOTES, 'UTF-8');
                        ?>
                        <tr style="<?php echo $low_row ? 'background:#fff7ed;' : ''; ?>">
                            <td>#PROD-<?php echo str_pad($pr['productID'], 4, '0', STR_PAD_LEFT); ?></td>
                            <td><?php echo htmlspecialchars($pr['productName']); ?> <?php if($low_row): ?><span style="background:#fee2e2;color:#991b1b;font-size:10px;padding:2px 6px;border-radius:8px;margin-left:5px;">LOW</span><?php endif; ?></td>
                            <td><?php echo htmlspecialchars($pr['brand']); ?></td>
                            <td><?php echo number_format($pr['price'], 2); ?></td>
                            <td><?php echo $pr['oldPrice'] > 0 ? number_format($pr['oldPrice'], 2) : '—'; ?></td>
                            <td style="<?php echo $low_row ? 'color:var(--danger);font-weight:700;' : ''; ?>"><?php echo $pr['quantity_in_stock']; ?></td>
                            <td><?php echo $pr['warrantyPeriod']; ?> mo</td>
                            <td><span style="padding:3px 8px;border-radius:12px;font-size:11px;font-weight:600;background:<?php echo $stat_clr; ?>"><?php echo $pr['status']; ?></span></td>
                            <td>
                                <button class="btn btn-primary btn-edit-prod" style="padding:5px 10px;font-size:12px;"
                                    data-id="<?php echo $pr['productID']; ?>" data-name="<?php echo htmlspecialchars($pr['productName']); ?>" data-brand="<?php echo htmlspecialchars($pr['brand']); ?>"
                                    data-cat="<?php echo $pr['categoryID']; ?>" data-desc="<?php echo htmlspecialchars($pr['description']); ?>" data-price="<?php echo $pr['price']; ?>"
                                    data-old="<?php echo $pr['oldPrice']; ?>" data-qty="<?php echo $pr['quantity_in_stock']; ?>" data-war="<?php echo $pr['warrantyPeriod']; ?>"
                                    data-stat="<?php echo $pr['status']; ?>" data-specs="<?php echo $specs_json; ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                            </td>
                            <td>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this product? This cannot be undone.');">
                                    <input type="hidden" name="product_id" value="<?php echo $pr['productID']; ?>">
                                    <button type="submit" name="delete_product" class="btn btn-danger" style="padding:5px 10px;font-size:12px;"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>

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
                                <div id="edit_dynamic_specs_container" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;"></div>
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
                            <button type="button" id="closeProdModal" class="btn btn-danger" style="flex:1;">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <datalist id="brands_computers"><option value="Dell"><option value="HP"><option value="Lenovo"><option value="Apple"><option value="ASUS"><option value="Acer"><option value="MSI"><option value="Microsoft"></datalist>
            <datalist id="brands_components"><option value="Intel"><option value="AMD"><option value="NVIDIA"><option value="Corsair"><option value="ASUS"><option value="MSI"><option value="Gigabyte"><option value="Kingston"></datalist>
            <datalist id="brands_accessories"><option value="Logitech"><option value="Razer"><option value="Corsair"><option value="Dell"><option value="LG"><option value="Samsung"><option value="Keychron"></datalist>
            <datalist id="brands_audio"><option value="Sony"><option value="Bose"><option value="JBL"><option value="Logitech"><option value="Audio-Technica"><option value="Sennheiser"><option value="Edifier"></datalist>
            <datalist id="brands_storage"><option value="Samsung"><option value="Western Digital"><option value="Seagate"><option value="Kingston"><option value="Crucial"><option value="Corsair"><option value="SanDisk"></datalist>
            <datalist id="brands_general"><option value="Other"></datalist>

            <datalist id="proc_list"><option value="Intel Core i3"><option value="Intel Core i5"><option value="Intel Core i7"><option value="Intel Core i9"><option value="AMD Ryzen 3"><option value="AMD Ryzen 5"><option value="AMD Ryzen 7"><option value="AMD Ryzen 9"><option value="Apple M1"><option value="Apple M2"><option value="Apple M3"></datalist>
            <datalist id="ram_list"><option value="4GB DDR4"><option value="8GB DDR4"><option value="16GB DDR4"><option value="32GB DDR4"><option value="8GB DDR5"><option value="16GB DDR5"><option value="32GB DDR5"><option value="16GB Unified"></datalist>
            <datalist id="storage_list"><option value="256GB SSD"><option value="512GB SSD"><option value="1TB SSD"><option value="2TB SSD"><option value="1TB HDD"><option value="2TB HDD"><option value="4TB HDD"></datalist>
            <datalist id="gpu_list"><option value="Integrated Graphics"><option value="Intel Iris Xe"><option value="NVIDIA GTX 1650"><option value="NVIDIA RTX 3050"><option value="NVIDIA RTX 3060"><option value="NVIDIA RTX 4050"><option value="NVIDIA RTX 4060"><option value="NVIDIA RTX 4070"><option value="NVIDIA RTX 4090"><option value="AMD Radeon RX 6600"></datalist>
            <datalist id="screen_list"><option value="13.3 inch"><option value="14 inch"><option value="15.6 inch"><option value="16 inch"><option value="17.3 inch"><option value="24 inch"><option value="27 inch"></datalist>
            <datalist id="use_list"><option value="Gaming"><option value="Business"><option value="Student"><option value="Professional"><option value="Office"><option value="Home"><option value="Workstation"><option value="Internal"><option value="External"></datalist>
            
            <datalist id="component_type_list"><option value="Motherboard"><option value="Processor (CPU)"><option value="Memory (RAM)"><option value="Graphics Card (GPU)"><option value="Power Supply (PSU)"><option value="Casing"><option value="Cooling System"><option value="Sound Card"></datalist>
            <datalist id="accessory_type_list"><option value="Gaming Mouse"><option value="Standard Mouse"><option value="Mechanical Keyboard"><option value="Membrane Keyboard"><option value="Monitor"><option value="Webcam"><option value="Mousepad"><option value="Cables & Adapters"></datalist>
            <datalist id="audio_type_list"><option value="Gaming Headset"><option value="Wireless Headphones"><option value="Earbuds"><option value="Bluetooth Speaker"><option value="Microphone"><option value="Studio Monitor"></datalist>
            <datalist id="storage_type_list"><option value="Internal NVMe SSD"><option value="Internal SATA SSD"><option value="External SSD"><option value="Internal HDD"><option value="External HDD"><option value="Pen Drive"></datalist>
        <?php } ?>
    </div>
</main>

<script src="assets/js/stock_keeper_dashboard.js"></script>
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