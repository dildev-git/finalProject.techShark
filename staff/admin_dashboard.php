<?php
session_start();
include(__DIR__ . '/../includes/dbconnection.php');

// Security Check: Redirect to login if not an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Administrator') {
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
// 1. ADMIN LOGIC (CRUD Operations)
// ==========================================

// ----- DELETE CUSTOMER -----
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_customer'])) {
    $del_id = (int)$_POST['customer_id'];
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
        // --- DASHBOARD VIEW ---
        if ($view == 'dashboard'): 
        ?>
            <h2>Dashboard Overview</h2>
            <div class='dashboard-cards'>
                <?php
                $rc = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM Customer"));
                $rs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM Staff"));
                ?>
                <div class='card'>
                    <div class='info'><p>Total Customers</p><h3><?php echo $rc['c']; ?></h3></div>
                    <i class='fas fa-users' style='color:#000000;opacity:1;'></i>
                </div>
                <div class='card'>
                    <div class='info'><p>Total Staff</p><h3><?php echo $rs['c']; ?></h3></div>
                    <i class='fas fa-user-tie' style='color:#000000;opacity:1;'></i>
                </div>
            </div>
            <div class='panel'>
                <h3>Quick Message</h3>
                <p>Ensure sensitive data is handled according to policy. Role functionality is restricted server-side.</p>
            </div>

        <?php 
        // --- CUSTOMERS VIEW ---
        elseif ($view == 'customers'): 
        ?>
            <?php $rc = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM Customer")); ?>
            <div style='display:flex;gap:20px;margin-bottom:20px;'>
                <div class='card' style='flex:1'><div class='info'><p>Total Customers</p><h3><?php echo $rc['c']; ?></h3></div><i class='fas fa-users' style='color:#000000;opacity:1;'></i></div>
            </div>

            <div class='panel'>
                <h2>Customer Management</h2>
                <div style='position:relative; margin-bottom:12px; width:100%; max-width:420px;'>
                    <input type='text' id='customerSearch' placeholder='Search by Customer ID, Name or Email...' style='width:100%; box-sizing:border-box; padding:9px 35px 9px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; outline:none;'>
                    <i class='fas fa-search' style='position:absolute; right:14px; top:50%; transform:translateY(-50%); color:#9ca3af; pointer-events:none;'></i>
                </div>
                <div style='overflow-x:auto;'>
                    <table>
                        <thead><tr><th>Customer ID</th><th>Full Name</th><th>Email</th><th>Contact</th><th>City</th><th>Gender</th><th>DOB</th><th>Action</th></tr></thead>
                        <tbody id='customerTableBody'>
                        <?php
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
                                        <button type='submit' name='delete_customer' class='btn btn-danger' style='padding:5px 10px;font-size:12px;'><i class='fas fa-trash'></i> Delete</button>
                                    </form>
                                </td>
                            </tr>";
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php 
        // --- STAFF VIEW ---
        elseif ($view == 'staff'): 
        ?>
            <?php $rs_c = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM Staff")); ?>
            <div style='display:flex;gap:20px;margin-bottom:20px;'>
                <div class='card' style='flex:1'><div class='info'><p>Total Staff Members</p><h3><?php echo $rs_c['c']; ?></h3></div><i class='fas fa-user-tie' style='color:#000000;opacity:1;'></i></div>
            </div>

            <div class='panel'>
                <h2>Add New Staff Member</h2>
                <form method='POST' style='display:grid;grid-template-columns:1fr 1fr;gap:15px;'>
                    <div class='form-group'><label>Full Name</label><input type='text' name='s_fullName' class='form-control' required></div>
                    <div class='form-group'><label>NIC</label><input type='text' name='s_NIC' class='form-control' required></div>
                    <div class='form-group'><label>Email</label><input type='email' name='s_email' class='form-control' required></div>
                    <div class='form-group'><label>Username</label><input type='text' name='s_userName' class='form-control' required></div>
                    <div class='form-group'>
                        <label>Password</label>
                        <div class="password-wrapper">   
                            <i class="fas fa-eye-slash eye-icon" id="show-password"></i>
                            <input type='password' id='s_password' name='s_password' class='form-control' required>
                        </div>
                    </div>
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
                        <select name='s_staff_type' class='form-control'>
                            <?php foreach(['Manager','Stock Keeper','Sales Representative','Repair Technician','Inquiry Manager'] as $st) echo "<option value='$st'>$st</option>"; ?>
                        </select>
                    </div>
                    <div class='form-group'><label>Date of Birth</label><input type='date' name='s_dob' class='form-control'></div>
                    <div class='form-group' style='display:flex;align-items:flex-end;'>
                        <button type='submit' name='add_staff' class='btn btn-primary' style='width:100%;'><i class='fas fa-plus'></i> Add Staff</button>
                    </div>

                    <script>
                    // show and hide password
                    const showPassword = document.querySelector("#show-password");
                    const passwordField = document.querySelector("#s_password");

                    showPassword.addEventListener("click", function () {

                    // toggle password visibility
                    const type = passwordField.getAttribute("type") === "password"
                        ? "text"
                        : "password";
                    passwordField.setAttribute("type", type);

                    // toggle eye icon
                    this.classList.toggle("fa-eye");
                    this.classList.toggle("fa-eye-slash");
                    });
                </script>

                </form>
            </div>

            <div class='panel'>
                <h2>All Staff Members</h2>
                <div style='position:relative; margin-bottom:12px; width:100%; max-width:420px;'>
                    <input type='text' id='staffSearch' placeholder='Search by Staff ID, NIC or Name...' style='width:100%; box-sizing:border-box; padding:9px 35px 9px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; outline:none;'>
                    <i class='fas fa-search' style='position:absolute; right:14px; top:50%; transform:translateY(-50%); color:#9ca3af; pointer-events:none;'></i>
                </div>
                <div style='overflow-x:auto;'>
                    <table>
                        <thead><tr><th>Staff ID</th><th>Full Name</th><th>NIC</th><th>Email</th><th>Contact</th><th>City</th><th>Role</th><th colspan='2'>Actions</th></tr></thead>
                        <tbody id='staffTableBody'>
                        <?php
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
                                    <button class='btn btn-primary btn-edit-staff' style='padding:5px 10px;font-size:12px;'
                                        data-id='{$r['staffID']}' data-name='".htmlspecialchars(addslashes($r['fullName']))."' data-nic='{$r['NIC']}' data-email='{$r['email']}' data-uname='".htmlspecialchars(addslashes($r['userName']))."' data-phone='{$r['contactNo']}' data-addr='".htmlspecialchars(addslashes($r['address']))."' data-city='{$r['city']}' data-gender='{$r['gender']}' data-role='{$r['staff_type']}' data-dob='{$r['date_of_birth']}'>
                                        <i class='fas fa-edit'></i> Edit
                                    </button>
                                </td>
                                <td>
                                    <form method='POST' style='display:inline;' onsubmit='return confirm(\"Delete this staff member?\");'>
                                        <input type='hidden' name='staff_id' value='{$r['staffID']}'>
                                        <button type='submit' name='delete_staff' class='btn btn-danger' style='padding:5px 10px;font-size:12px;'><i class='fas fa-trash'></i> Delete</button>
                                    </form>
                                </td>
                            </tr>";
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>

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
                                    <option>Manager</option><option>Stock Keeper</option><option>Sales Representative</option><option>Repair Technician</option><option>Inquiry Manager</option>
                                </select>
                            </div>
                        </div>
                        <div style='display:flex;gap:10px;margin-top:20px;'>
                            <button type='submit' name='update_staff' class='btn btn-success' style='flex:1;'><i class='fas fa-save'></i> Save Changes</button>
                            <button type='button' id='closeModalBtn' class='btn btn-danger' style='flex:1;'>Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<script src="assets/js/admin_dashboard.js"></script>
</body>
</html>