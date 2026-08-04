<?php
session_start();
include(__DIR__ . '/../includes/dbconnection.php');

// Security Check: Redirect to login if not an Inquiry Manager
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Inquiry Manager') {
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
// 1. INQUIRY LOGIC (Reply & Delete)
// ==========================================

// ----- REPLY TO INQUIRY -----
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reply_inquiry'])) {
    $inq_id = (int)$_POST['inquiry_id'];
    $reply = mysqli_real_escape_string($conn, trim($_POST['response']));
    
    // Updating the inquiry
    mysqli_query($conn, "UPDATE Inquiry SET response = '$reply', status = 'Resolved' WHERE inquiryID = $inq_id");
    
    // Sending a notification to the customer
    $res = mysqli_query($conn, "SELECT customerID FROM Inquiry WHERE inquiryID = $inq_id");
    if($row = mysqli_fetch_assoc($res)) {
        $cid = $row['customerID'];
        $notif_msg = "You have received a reply to your inquiry #INQ-" . str_pad($inq_id, 4, '0', STR_PAD_LEFT) . ".";
        $safe_msg = mysqli_real_escape_string($conn, $notif_msg);
        mysqli_query($conn, "INSERT INTO Notification (message, type, date, customerID, is_read) VALUES ('$safe_msg', 'Inquiry Update', NOW(), $cid, 0)");
    }
    
    $msg = "Inquiry replied successfully."; $msg_type = "success";
}

// ----- DELETE INQUIRY -----
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_inquiry'])) {
    $inq_id = (int)$_POST['inquiry_id'];
    if(mysqli_query($conn, "DELETE FROM Inquiry WHERE inquiryID = $inq_id")) {
        $msg = "Inquiry deleted."; $msg_type = "success";
    } else {
        $msg = "Failed to delete inquiry."; $msg_type = "danger";
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
            
            $rinq_tot = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM Inquiry"));
            $rinq = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM Inquiry WHERE status='Pending'"));
            
            echo "<div class='card'><div class='info'><p>Total Inquiries</p><h3  style='color:#4f46e5;'>{$rinq_tot['c']}</h3></div><i class='fas fa-inbox' style='color:#4f46e5;opacity:0.7;'></i></div>";
            echo "<div class='card'><div class='info'><p>New Inquiries</p><h3 style='color:#f59e0b;'>{$rinq['c']}</h3></div><i class='fas fa-envelope-open-text' style='color:#f59e0b;opacity:0.7;'></i></div>";
            echo "</div>";
            echo "<div class='panel'><h3>Quick Message</h3><p>Ensure sensitive data is handled according to policy. Try to reply to pending inquiries as soon as possible.</p></div>";
        }
        
        // --- 2. INQUIRIES MANAGEMENT VIEW ---
        elseif ($view == 'inquiries') {
            echo "<h2>Customer Inquiries</h2>";
            echo "<div style='position:relative; margin-bottom:18px; width:100%; max-width:420px;'>
                    <input type='text' id='inquirySearch' placeholder='Search by Inquiry ID or Customer Name...'
                        style='width:100%; box-sizing:border-box; padding:9px 35px 9px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; outline:none;'>
                    <i class='fas fa-search' style='position:absolute; right:14px; top:50%; transform:translateY(-50%); color:#9ca3af; pointer-events:none;'></i>
                </div>";
                
            $resPending = mysqli_query($conn, "SELECT i.*, c.fullName, c.email FROM Inquiry i LEFT JOIN Customer c ON i.customerID = c.customerID WHERE i.status='Pending' ORDER BY i.dateSubmitted DESC");
            $resResolved = mysqli_query($conn, "SELECT i.*, c.fullName, c.email FROM Inquiry i LEFT JOIN Customer c ON i.customerID = c.customerID WHERE i.status='Resolved' ORDER BY i.dateSubmitted DESC");
            
            $pendingCount = mysqli_num_rows($resPending);
            $resolvedCount = mysqli_num_rows($resResolved);
            
            // --- Pending Table ---
            echo "<div class='panel inquiry-panel' style='border-left:4px solid #f59e0b; padding-bottom:0;'>";
            echo "<div class='collapsible-panel-header' onclick='togglePanel(\"body-inquiry-pending\", this.querySelector(\".panel-toggle-btn\"))'>
                    <div style='display:flex;align-items:center;gap:10px;'>
                        <i class='fas fa-clock' style='color:#f59e0b;'></i>
                        <span style='font-size:15px;font-weight:700;color:var(--text-primary);'>Pending Inquiries</span>
                        <span style='background:#f59e0b;color:#fff;font-size:11px;padding:2px 10px;border-radius:20px;font-weight:600;'>{$pendingCount} inquiries</span>
                    </div>
                    <button class='panel-toggle-btn' type='button'>
                        <i class='fas fa-chevron-down toggle-icon'></i>
                        <span class='toggle-text'>Expand</span>
                    </button>
                  </div>";
            echo "<div class='collapsible-body' id='body-inquiry-pending'>";
            echo "<div style='overflow-x:auto; padding: 14px 0 6px;'><table><tr><th>Inquiry ID</th><th>Date</th><th>Customer Info</th><th>Message</th><th>Response Action</th></tr>";
            echo "<tbody>";
            while($r = mysqli_fetch_assoc($resPending)){
                $cname = $r['fullName'] ? htmlspecialchars($r['fullName']) : 'Guest / Unknown';
                $cemail = $r['email'] ? htmlspecialchars($r['email']) : 'N/A';
                $inqIdFormatted = 'inq-' . str_pad($r['inquiryID'], 4, '0', STR_PAD_LEFT);
                
                echo "<tr class='inquiry-row' data-search='" . strtolower(strip_tags($cname)) . " {$inqIdFormatted}'>
                <td>#INQ-" . str_pad($r['inquiryID'], 4, '0', STR_PAD_LEFT) . "</td>
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
            if(mysqli_num_rows($resPending) == 0) echo "<tr><td colspan='5' style='text-align:center;'>No pending inquiries.</td></tr>";
            echo "</tbody></table></div>";
            echo "</div>"; // collapsible-body
            echo "</div>"; // panel

            // --- Resolved Table ---
            echo "<div class='panel inquiry-panel' style='border-left:4px solid #10b981; padding-bottom:0;'>";
            echo "<div class='collapsible-panel-header' onclick='togglePanel(\"body-inquiry-resolved\", this.querySelector(\".panel-toggle-btn\"))'>
                    <div style='display:flex;align-items:center;gap:10px;'>
                        <i class='fas fa-check-circle' style='color:#10b981;'></i>
                        <span style='font-size:15px;font-weight:700;color:var(--text-primary);'>Resolved Inquiries</span>
                        <span style='background:#10b981;color:#fff;font-size:11px;padding:2px 10px;border-radius:20px;font-weight:600;'>{$resolvedCount} inquiries</span>
                    </div>
                    <button class='panel-toggle-btn' type='button'>
                        <i class='fas fa-chevron-down toggle-icon'></i>
                        <span class='toggle-text'>Expand</span>
                    </button>
                  </div>";
            echo "<div class='collapsible-body' id='body-inquiry-resolved'>";
            echo "<div style='overflow-x:auto; padding: 14px 0 6px;'><table><tr><th>Inquiry ID</th><th>Date</th><th>Customer Info</th><th>Message</th><th>Response Given</th></tr>";
            echo "<tbody>";
            while($r = mysqli_fetch_assoc($resResolved)){
                $cname = $r['fullName'] ? htmlspecialchars($r['fullName']) : 'Guest / Unknown';
                $cemail = $r['email'] ? htmlspecialchars($r['email']) : 'N/A';
                $inqIdFormatted = 'inq-' . str_pad($r['inquiryID'], 4, '0', STR_PAD_LEFT);
                
                echo "<tr class='inquiry-row' data-search='" . strtolower(strip_tags($cname)) . " {$inqIdFormatted}'>
                <td>#INQ-" . str_pad($r['inquiryID'], 4, '0', STR_PAD_LEFT) . "</td>
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
            if(mysqli_num_rows($resResolved) == 0) echo "<tr><td colspan='5' style='text-align:center;'>No resolved inquiries.</td></tr>";
            echo "</tbody></table></div>";
            echo "</div>"; // collapsible-body
            echo "</div>"; // panel
        }
        ?>
    </div>
</main>

<script src="assets/js/inquiry_dashboard.js"></script>
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