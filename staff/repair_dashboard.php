<?php
session_start();
include(__DIR__ . '/../includes/dbconnection.php');

// Security Check: If not a Repair Technician, will be redirected to the login page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Repair Technician') {
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
// 1. REPAIR LOGIC (CRUD Operations)
// ==========================================

// ----- UPDATE REPAIR -----
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
    
    // Send Notification to Customer
    $notif_msg = "Your repair status for '$devName' has been updated to '$status'.";
    $safe_msg = mysqli_real_escape_string($conn, $notif_msg);
    mysqli_query($conn, "INSERT INTO Notification (message, type, date, customerID, is_read) VALUES ('$safe_msg', 'Repair Update', NOW(), $cid, 0)");
    
    $msg = "Repair updated successfully."; $msg_type = "success";
}

// ----- ADD NEW REPAIR -----
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_repair'])) {
    $devName = mysqli_real_escape_string($conn, trim($_POST['device_name']));
    $issue = mysqli_real_escape_string($conn, trim($_POST['issue']));
    $cid = (int)$_POST['customer_id'];
    $cost = (float)$_POST['cost'];
    
    $sql = "INSERT INTO Repair (deviceName, issueDescription, repairStatus, estimatedCost, startDate, customerID, staffID)
            VALUES ('$devName', '$issue', 'Pending', $cost, NOW(), $cid, $staff_id)";
            
    if(mysqli_query($conn, $sql)) {

        // Send Notification to Customer
        $notif_msg = "A new repair job for your device '$devName' has been registered successfully.";
        $safe_msg = mysqli_real_escape_string($conn, $notif_msg);
        mysqli_query($conn, "INSERT INTO Notification (message, type, date, customerID, is_read) VALUES ('$safe_msg', 'New Repair Job', NOW(), $cid, 0)");
        
        $msg = "Repair job added successfully."; $msg_type = "success";
    } else {
        $msg = "Failed to add repair: ".mysqli_error($conn); $msg_type = "danger";
    }
}

// ----- DELETE REPAIR -----
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_repair'])) {
    $rid = (int)$_POST['repair_id'];
    if(mysqli_query($conn, "DELETE FROM Repair WHERE repairID = $rid")) {
        $msg = "Repair deleted."; $msg_type = "success";
    } else {
        $msg = "Failed to delete repair: ".mysqli_error($conn); $msg_type = "danger";
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
            echo "<div class='dashboard-cards'>";
            
            $rt_tot = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM Repair"));
            $rt_pend = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM Repair WHERE repairStatus='Pending'"));
            
            echo "<div class='card'><div class='info'><p>Total Repairs</p><h3 style='color:#4f46e5;'>{$rt_tot['c']}</h3></div><i class='fas fa-tools' style='color:#4f46e5;opacity:0.7;'></i></div>";
            echo "<div class='card'><div class='info'><p>Pending Repairs</p><h3 style='color:#f59e0b;'>{$rt_pend['c']}</h3></div><i class='fas fa-clock' style='color:#f59e0b;opacity:0.7;'></i></div>";
            echo "</div>";
            echo "<div class='panel'><h3>Quick Message</h3><p>Ensure sensitive data is handled according to policy. Role functionality is restricted server-side.</p></div>";
        }
        
        // --- 2. REPAIRS MANAGEMENT VIEW ---
        elseif ($view == 'repairs') {
            // Fetch customers with full contact details for the tooltip
            $cust_res = mysqli_query($conn, "SELECT customerID, fullName, email, contactNo, address FROM Customer ORDER BY fullName ASC");
            $customers_for_js = [];
            $customers_list   = [];
            while ($c = mysqli_fetch_assoc($cust_res)) {
                $customers_list[] = $c;
                $customers_for_js[$c['customerID']] = [
                    'name'    => $c['fullName'],
                    'email'   => $c['email']   ?: 'N/A',
                    'phone'   => $c['contactNo'] ?: 'N/A',
                    'address' => $c['address']  ?: 'N/A',
                ];
            }
            ?>

            
            <div class='panel'>
                <h2 style='margin-top:0;'>Add New Repair Job</h2>
                <form method='POST' style='display:grid;grid-template-columns:1fr 1fr;gap:15px;'>
                    <div class='form-group'><label>Device Name</label><input type='text' name='device_name' class='form-control' required></div>
                    <div class='form-group'><label>Customer / Owner</label>
                        <div style='display:flex; gap:8px; align-items:center;'>
                            <select name='customer_id' id='add_customer_select' class='form-control' required style='flex:1;'>
                                <option value=''>-- Select Customer --</option>
                                <?php foreach($customers_list as $c): ?>
                                    <option value='<?php echo $c['customerID']; ?>'><?php echo htmlspecialchars($c['fullName']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class='cust-preview-wrapper' id='add_cust_preview_wrap'>
                                <button type='button' class='cust-view-btn' id='add_cust_view_btn' title='View customer details'>
                                    <i class='fas fa-eye'></i>
                                </button>
                                <div class='cust-detail-tooltip' id='add_cust_tooltip'>
                                    <div class='cust-tooltip-row'><i class='fas fa-envelope'></i><span id='add_tip_email'></span></div>
                                    <div class='cust-tooltip-row'><i class='fas fa-phone'></i><span id='add_tip_phone'></span></div>
                                    <div class='cust-tooltip-row'><i class='fas fa-map-marker-alt'></i><span id='add_tip_address'></span></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class='form-group' style='grid-column:span 2;'><label>Issue Description</label><textarea name='issue' class='form-control' rows='2' required></textarea></div>
                    <div class='form-group'><label>Initial Estimated Cost (LKR)</label><input type='number' step='0.01' name='cost' class='form-control' value='0'></div>
                    <div class='form-group' style='display:flex;align-items:flex-end;'>
                        <button type='submit' name='add_repair' class='btn btn-primary' style='width:100%;'><i class='fas fa-plus'></i> Add Repair Job</button>
                    </div>
                </form>
            </div>

            <?php
            // Fetch all repairs
            $res = mysqli_query($conn, "SELECT r.*, c.fullName FROM Repair r LEFT JOIN Customer c ON r.customerID = c.customerID ORDER BY r.repairID DESC");
            $repairs = ['Pending' => [], 'In Progress' => [], 'Completed' => []];
            while($r = mysqli_fetch_assoc($res)){
                $repairs[$r['repairStatus']][] = $r;
            }
            ?>
            
            <h2 style='margin-top:30px;'>Manage Repairs</h2>
            <div style='position:relative; margin-bottom:18px; width:100%; max-width:420px;'>
                <input type='text' id='repairSearch' placeholder='Search by Repair ID or Customer Name...'
                    style='width:100%; box-sizing:border-box; padding:9px 35px 9px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; outline:none;'>
                <i class='fas fa-search' style='position:absolute; right:14px; top:50%; transform:translateY(-50%); color:#9ca3af; pointer-events:none;'></i>
            </div>

            <?php
            $panels = [
                'Pending' => ['color' => '#f59e0b', 'title' => 'Pending Repairs'],
                'In Progress' => ['color' => '#3b82f6', 'title' => 'In Progress Repairs'],
                'Completed' => ['color' => '#10b981', 'title' => 'Completed Repairs']
            ];

            foreach($panels as $status => $pinfo) {
                $reps = $repairs[$status];
                $count = count($reps);
                if($count == 0) continue;

                $color = $pinfo['color'];
                $panel_id = 'repair-panel-' . strtolower(str_replace(' ', '-', $status));

                echo "<div class='panel repair-panel' style='border-left:4px solid {$color}; padding-bottom:0; margin-bottom: 18px;'>";
                // Collapsible header
                echo "<div class='collapsible-panel-header' onclick='togglePanel(\"body-{$panel_id}\", this.querySelector(\".panel-toggle-btn\"))'>
                        <div style='display:flex;align-items:center;gap:10px;'>
                            <i class='fas fa-tools' style='color:{$color};'></i>
                            <span style='font-size:15px;font-weight:700;color:var(--text-primary);'>{$pinfo['title']}</span>
                            <span style='background:{$color};color:#fff;font-size:11px;padding:2px 10px;border-radius:20px;font-weight:600;'>{$count} repairs</span>
                        </div>
                        <button class='panel-toggle-btn' type='button'>
                            <i class='fas fa-chevron-down toggle-icon'></i>
                            <span class='toggle-text'>Expand</span>
                        </button>
                      </div>";
                // Collapsible body (collapsed by default)
                echo "<div class='collapsible-body' id='body-{$panel_id}'>";
                echo "<div style='overflow-x:auto; padding: 14px 0 6px;'><table><tr><th>Repair ID</th><th>Device</th><th>Owner</th><th>Issue</th><th>Estimated Cost</th><th>Status</th><th>Action</th></tr>";
                echo "<tbody>";
                
                foreach($reps as $r) {
                    $owner = $r['fullName'] ? htmlspecialchars($r['fullName']) : 'Unknown';
                    $rprIdFormatted = 'rpr-' . str_pad($r['repairID'], 4, '0', STR_PAD_LEFT);
                    
                    echo "<tr class='repair-row' data-search='" . strtolower($owner) . " {$rprIdFormatted}'>
                    <td>#RPR-" . str_pad($r['repairID'], 4, '0', STR_PAD_LEFT) . "</td>
                    <td><strong>".htmlspecialchars($r['deviceName'])."</strong></td>
                    <td>{$owner}</td>
                    <td><small>".htmlspecialchars($r['issueDescription'])."</small></td>
                    <td>LKR ".number_format($r['estimatedCost'], 2)."</td>
                    <td>{$r['repairStatus']}</td>
                    <td>
                        <button class='btn btn-primary btn-edit-repair' style='padding:5px 10px;font-size:12px;' 
                            data-id='{$r['repairID']}' data-device='".htmlspecialchars(addslashes($r['deviceName']))."' 
                            data-cid='{$r['customerID']}' data-issue='".htmlspecialchars(addslashes($r['issueDescription']))."' 
                            data-cost='{$r['estimatedCost']}' data-status='{$r['repairStatus']}'>
                            <i class='fas fa-edit'></i> Edit
                        </button>
                        <form method='POST' style='display:inline;' onsubmit='return confirm(\"Delete this repair record?\");'>
                            <input type='hidden' name='repair_id' value='{$r['repairID']}'>
                            <button type='submit' name='delete_repair' class='btn btn-danger' style='padding:5px 10px;font-size:12px;margin-left:5px;'><i class='fas fa-trash'></i></button>
                        </form>
                    </td></tr>";
                }
                echo "</tbody></table></div>";
                echo "</div>"; // collapsible-body
                echo "</div>"; // panel
            }
            ?>
            
            <div id='repairModal' style='display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:9999;align-items:center;justify-content:center;'>
                <div style='background:#fff;border-radius:8px;padding:30px;width:500px;max-width:90%;box-shadow:0 10px 25px rgba(0,0,0,0.5);'>
                    <h2 style='margin-top:0;'>Edit Repair Job</h2>
                    <form method='POST' style='display:flex;flex-direction:column;gap:15px;'>
                        <input type='hidden' name='repair_id' id='edit_repair_id'>
                        <div class='form-group'><label>Device Name</label><input type='text' name='device_name' id='edit_r_device' class='form-control' required></div>
                        <div class='form-group'><label>Customer Owner</label>
                            <div style='display:flex; gap:8px; align-items:center;'>
                                <select name='customer_id' id='edit_r_customer' class='form-control' required style='flex:1;'>
                                    <option value=''>-- Select Customer --</option>
                                    <?php foreach($customers_list as $c): ?>
                                        <option value='<?php echo $c['customerID']; ?>'><?php echo htmlspecialchars($c['fullName']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class='cust-preview-wrapper' id='edit_cust_preview_wrap'>
                                    <button type='button' class='cust-view-btn' id='edit_cust_view_btn' title='View customer details'>
                                        <i class='fas fa-eye'></i>
                                    </button>
                                    <div class='cust-detail-tooltip' id='edit_cust_tooltip'>
                                        <div class='cust-tooltip-row'><i class='fas fa-envelope'></i><span id='edit_tip_email'></span></div>
                                        <div class='cust-tooltip-row'><i class='fas fa-phone'></i><span id='edit_tip_phone'></span></div>
                                        <div class='cust-tooltip-row'><i class='fas fa-map-marker-alt'></i><span id='edit_tip_address'></span></div>
                                    </div>
                                </div>
                            </div>
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
                            <button type='button' id='closeRepairModal' class='btn btn-danger' style='flex:1;'>Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php } ?>
    </div>
</main>

<script src="assets/js/repair_dashboard.js"></script>
<script src="assets/js/notes_board.js"></script>
<script>
const CUST_DATA = <?php echo json_encode($customers_for_js); ?>;

function getCustomerDetails(cid) {
    return CUST_DATA[cid] || null;
}

function bindCustomerPreview(selectId, emailId, phoneId, addressId, btnId) {
    const sel = document.getElementById(selectId);
    const btn = document.getElementById(btnId);
    if (!sel || !btn) return;

    function updateTooltip() {
        const cid = sel.value;
        const d = cid ? getCustomerDetails(cid) : null;
        document.getElementById(emailId).textContent   = d ? d.email   : '—';
        document.getElementById(phoneId).textContent   = d ? d.phone   : '—';
        document.getElementById(addressId).textContent = d ? d.address : '—';
        btn.style.opacity = cid ? '1' : '0.35';
        btn.style.pointerEvents = cid ? 'auto' : 'none';
    }

    sel.addEventListener('change', updateTooltip);
    updateTooltip();
}

document.addEventListener('DOMContentLoaded', function() {
    bindCustomerPreview('add_customer_select',  'add_tip_email',  'add_tip_phone',  'add_tip_address',  'add_cust_view_btn');
    bindCustomerPreview('edit_r_customer',       'edit_tip_email', 'edit_tip_phone', 'edit_tip_address', 'edit_cust_view_btn');
});

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

<style>
/* ── Customer Preview Button ── */
.cust-preview-wrapper {
    position: relative;
    flex-shrink: 0;
}

.cust-view-btn {
    width: 38px;
    height: 38px;
    border-radius: var(--radius-md, 8px);
    border: 1px solid var(--border, #e2e8f0);
    background: var(--surface-2, #f8fafc);
    color: var(--primary, #6366f1);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.cust-view-btn:hover {
    background: var(--primary, #6366f1);
    color: #fff;
    border-color: var(--primary, #6366f1);
    box-shadow: 0 4px 12px rgba(99,102,241,0.35);
}

/* ── Tooltip Popup ── */
.cust-detail-tooltip {
    display: none;
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    min-width: 260px;
    background: var(--surface, #1e293b);
    border: 1px solid var(--border, #334155);
    border-radius: 10px;
    padding: 14px 16px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.25);
    z-index: 9999;
    pointer-events: none;
}

.cust-view-btn:hover + .cust-detail-tooltip,
.cust-detail-tooltip:hover {
    display: block;
}

.cust-tooltip-row {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 10px;
    font-size: 13px;
    color: var(--text-secondary, #94a3b8);
    line-height: 1.5;
}

.cust-tooltip-row:last-child {
    margin-bottom: 0;
}

.cust-tooltip-row i {
    color: var(--primary, #6366f1);
    width: 14px;
    flex-shrink: 0;
    margin-top: 2px;
    font-size: 12px;
}

.cust-tooltip-row span {
    color: var(--text-primary, #e2e8f0);
    font-weight: 500;
    word-break: break-word;
}
</style>

</body>
</html>