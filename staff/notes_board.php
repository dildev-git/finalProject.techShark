<?php
session_start();
include(__DIR__ . '/../includes/dbconnection.php');

// Security Check: If you are logged in as a customer, log out.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'Customer') {
    header("Location: ../login.php");
    exit;
}

$staff_id = $_SESSION['user_id'];
$staff_role = $_SESSION['role'];
$staff_name = $_SESSION['name'];

// ==========================================
// 1. NOTES LOGIC (Read & Add)
// ==========================================

// ----- MARK NOTES AS READ -----
// Update "Last viewed time" as soon as the user clicks on this page
if ($staff_role !== 'Administrator') {
    mysqli_query($conn, "UPDATE Staff SET last_note_viewed_at = NOW() WHERE staffID = $staff_id");
} else {
    // Because the Admin does not have a record in the Staff table, save it in Session and Cookie
    $time_res = mysqli_query($conn, "SELECT NOW() as db_time");
    if ($time_row = mysqli_fetch_assoc($time_res)) {
        $_SESSION['notes_last_viewed'] = $time_row['db_time'];
        setcookie('admin_notes_last_viewed', $time_row['db_time'], time() + (86400 * 30), "/"); 
    }
}

// ----- ADD NEW NOTE -----
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_note']) && !empty(trim($_POST['note']))) {
    $note_content = mysqli_real_escape_string($conn, trim($_POST['note']));

    if ($staff_role !== 'Administrator') {
        // If it is a normal staff member, insert directly
        mysqli_query($conn, "INSERT INTO Staff_Notes (staffID, noteContent) VALUES ($staff_id, '$note_content')");
    } else {
        // Because the Admin does not have a record in the Staff table, use the first Staff ID in the system as a proxy
        $first_staff = mysqli_fetch_assoc(mysqli_query($conn, "SELECT staffID FROM Staff LIMIT 1"));
        if ($first_staff) {
            $proxy_id = (int)$first_staff['staffID'];
            $label = mysqli_real_escape_string($conn, "[Admin: $staff_name] $note_content");
            mysqli_query($conn, "INSERT INTO Staff_Notes (staffID, noteContent) VALUES ($proxy_id, '$label')");
        }
    }
    // Redirect back to this page after form submission (Prevents double submission on refresh)
    header("Location: notes_board.php?added=1");
    exit;
}

// ==========================================
// 2. UI & VIEWS
// ==========================================
include('includes/header.php');
include('includes/sidebar.php');

// Role colour map for note cards
$roleColors = [
    'Administrator'        => ['bg' => 'rgba(239,68,68,0.1)',   'color' => '#dc2626', 'icon' => 'fa-shield-halved'],
    'Manager'              => ['bg' => 'rgba(16,185,129,0.1)',  'color' => '#059669', 'icon' => 'fa-chart-line'],
    'Stock Keeper'         => ['bg' => 'rgba(245,158,11,0.1)',  'color' => '#d97706', 'icon' => 'fa-boxes-stacked'],
    'Sales Representative' => ['bg' => 'rgba(59,130,246,0.1)',  'color' => '#2563eb', 'icon' => 'fa-bag-shopping'],
    'Inquiry Manager'      => ['bg' => 'rgba(139,92,246,0.1)', 'color' => '#7c3aed', 'icon' => 'fa-headset'],
    'Repair Technician'    => ['bg' => 'rgba(244,63,94,0.1)',   'color' => '#e11d48', 'icon' => 'fa-screwdriver-wrench'],
];
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
        <div class="notes-page-header">
            <div>
                <h2 style="margin-bottom:5px;"><i class="fas fa-bullhorn" style="color:var(--primary);margin-right:10px;"></i>Global Staff Notes</h2>
                <p style="color:var(--text-muted);font-size:14px;margin:0;">Cross-departmental broadcast board - visible to all staff.</p>
            </div>
        </div>

        <?php if (isset($_GET['added'])): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> Note broadcasted successfully to all staff.</div>
        <?php endif; ?>

        <!-- Broadcast Form -->
        <div class="panel" style="margin-bottom:24px;">
            <h2 style="margin-bottom:16px;font-size:15px;">Write a Broadcast Note</h2>
            <form method='POST' action='notes_board.php' style='display:flex; gap:12px; align-items:flex-end;'>
                <div style="flex:1;">
                    <input type='text' name='note' class='form-control' placeholder='Share an update, announcement, or note with all staff...' required style="margin:0;">
                </div>
                <button type='submit' name='add_note' class='btn btn-primary' style="flex-shrink:0;">
                    <i class='fas fa-bullhorn'></i> Broadcast
                </button>
            </form>
        </div>

        <!-- Notes Feed -->
        <div class="notes-feed">
            <?php
            // Retrieve Notes from Database
            $notes_q = mysqli_query($conn, "SELECT n.noteContent, n.createdAt, s.fullName, s.staff_type
                                            FROM Staff_Notes n
                                            JOIN Staff s ON n.staffID = s.staffID
                                            ORDER BY n.createdAt DESC");

            if (!$notes_q || mysqli_num_rows($notes_q) == 0) {
                echo "
                <div class='notes-empty'>
                    <i class='fas fa-comment-slash'></i>
                    <p>No notes yet. Be the first to broadcast!</p>
                </div>";
            } else {
                $note_count = 0;
                while ($note_row = mysqli_fetch_assoc($notes_q)) {
                    $note_count++;
                    $content  = htmlspecialchars($note_row['noteContent']);
                    $author   = htmlspecialchars($note_row['fullName']);
                    $role_lbl = htmlspecialchars($note_row['staff_type']);
                    $is_admin = false;

                    // Remove admin proxy labels and display admin name separately
                    if (preg_match('/^\[Admin: (.+?)\] (.+)$/s', $note_row['noteContent'], $m)) {
                        $author   = htmlspecialchars($m[1]);
                        $role_lbl = 'Administrator';
                        $content  = htmlspecialchars($m[2]);
                        $is_admin = true;
                    }

                    // Build initials for avatar
                    $avatar_initials = '';
                    foreach (explode(' ', $author) as $p) {
                        if (!empty($p)) { $avatar_initials .= strtoupper($p[0]); if (strlen($avatar_initials) >= 2) break; }
                    }
                    if (empty($avatar_initials)) $avatar_initials = 'ST';

                    // Role styling
                    $role_style = isset($roleColors[$role_lbl]) ? $roleColors[$role_lbl] : ['bg' => 'rgba(99,102,241,0.1)', 'color' => '#6366f1', 'icon' => 'fa-user'];
                    $accentColor = $role_style['color'];
                    $accentBg    = $role_style['bg'];
                    $roleIcon    = $role_style['icon'];

                    // Time formatting
                    $ts = strtotime($note_row['createdAt']);
                    $time_fmt = date('M d, Y', $ts);
                    $time_rel = date('g:i A', $ts);

                    echo "
                    <div class='note-card' style='border-left-color:{$accentColor};'>
                        <div class='note-card-left'>
                            <div class='note-avatar' style='background:linear-gradient(135deg, {$accentColor} 0%, {$accentColor}cc 100%);'>
                                {$avatar_initials}
                            </div>
                        </div>
                        <div class='note-card-body'>
                            <div class='note-card-header'>
                                <div class='note-author-info'>
                                    <span class='note-author-name'>" . ($is_admin ? "{$author} <span class='note-admin-tag'>Admin</span>" : $author) . "</span>
                                    <span class='note-role-badge' style='background:{$accentBg}; color:{$accentColor};'>
                                        <i class='fas {$roleIcon}' style='font-size:9px;'></i> {$role_lbl}
                                    </span>
                                </div>
                                <div class='note-meta'>
                                    <i class='fas fa-clock' style='font-size:11px;color:var(--text-muted);'></i>
                                    <span>{$time_fmt} &middot; {$time_rel}</span>
                                </div>
                            </div>
                            <div class='note-content'>{$content}</div>
                        </div>
                    </div>";
                }
            }
            ?>
        </div>
    </div>
</main>

<script src="assets/js/notes_board.js"></script>
<script>
// Sidebar toggle for this page
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