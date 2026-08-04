<?php
// Ensure session is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pull session variables safely
$staff_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$staff_name = isset($_SESSION['name']) ? $_SESSION['name'] : '';

// Generate initials from the staff name (up to 2 chars)
$initials = '';
$name_parts = explode(' ', trim($staff_name));
foreach ($name_parts as $part) {
    if (!empty($part)) {
        $initials .= strtoupper($part[0]);
        if (strlen($initials) >= 2) break;
    }
}
if (empty($initials)) $initials = 'ST';

// Determine current page and view
$current_page = basename($_SERVER['PHP_SELF']);
$view = isset($_GET['view']) ? $_GET['view'] : 'dashboard';

// Map role → dashboard file
$dashboard_mapping = [
    'Administrator'        => 'admin_dashboard.php',
    'Manager'              => 'manager_dashboard.php',
    'Stock Keeper'         => 'stock_keeper_dashboard.php',
    'Sales Representative' => 'sales_dashboard.php',
    'Inquiry Manager'      => 'inquiry_dashboard.php',
    'Repair Technician'    => 'repair_dashboard.php'
];

$main_dashboard = isset($dashboard_mapping[$staff_role]) ? $dashboard_mapping[$staff_role] : 'admin_dashboard.php';
?>

<aside class="sidebar" id="mainSidebar">

    <!-- ── Brand / Logo ── -->
    <div class="sidebar-header">
        <img src="../assets/logo.png" alt="Tech Shark Logo" class="sidebar-logo">
        <div class="sidebar-brand-text">
            <span class="sidebar-brand-name">Tech Shark System</span>
        </div>
    </div>

    <!-- ── Profile Block ── -->
    <div class="profile-sec">
        <div class="avatar-initials" title="<?php echo htmlspecialchars($staff_name); ?>">
            <?php echo htmlspecialchars($initials); ?>
        </div>
        <div class="profile-info">
            <div class="profile-name"><?php echo htmlspecialchars($staff_name); ?></div>
            <span class="role"><?php echo htmlspecialchars($staff_role); ?></span>
        </div>
    </div>

    <!-- ── Navigation ── -->
    <ul class="nav-links">

        <li>
            <a href="<?php echo $main_dashboard; ?>?view=dashboard"
               class="<?php echo ($view == 'dashboard' && $current_page != 'notes_board.php') ? 'active' : ''; ?>"
               title="Home">
                <i class="fas fa-house"></i>
                <span class="nav-label-text">Home</span>
            </a>
        </li>

        <?php if($staff_role == 'Administrator'): ?>
            <li>
                <a href="admin_dashboard.php?view=customers"
                   class="<?php echo ($view == 'customers') ? 'active' : ''; ?>"
                   title="Customers">
                    <i class="fas fa-users"></i>
                    <span class="nav-label-text">Customers</span>
                </a>
            </li>
            <li>
                <a href="admin_dashboard.php?view=staff"
                   class="<?php echo ($view == 'staff') ? 'active' : ''; ?>"
                   title="Staff">
                    <i class="fas fa-user-tie"></i>
                    <span class="nav-label-text">Staff</span>
                </a>
            </li>
        <?php endif; ?>

        <?php if($staff_role == 'Manager'): ?>
            <li>
                <a href="manager_dashboard.php?view=analytics"
                   class="<?php echo ($view == 'analytics') ? 'active' : ''; ?>"
                   title="Analytics">
                    <i class="fas fa-chart-pie"></i>
                    <span class="nav-label-text">Analytics</span>
                </a>
            </li>
            <li>
                <a href="manager_dashboard.php?view=reports"
                   class="<?php echo ($view == 'reports') ? 'active' : ''; ?>"
                   title="Reports">
                    <i class="fas fa-file-lines"></i>
                    <span class="nav-label-text">Reports</span>
                </a>
            </li>
        <?php endif; ?>

        <?php if($staff_role == 'Stock Keeper'): ?>
            <li>
                <a href="stock_keeper_dashboard.php?view=inventory"
                   class="<?php echo ($view == 'inventory') ? 'active' : ''; ?>"
                   title="Inventory">
                    <i class="fas fa-boxes-stacked"></i>
                    <span class="nav-label-text">Inventory</span>
                </a>
            </li>
        <?php endif; ?>

        <?php if($staff_role == 'Sales Representative'): ?>
            <li>
                <a href="sales_dashboard.php?view=orders"
                   class="<?php echo ($view == 'orders') ? 'active' : ''; ?>"
                   title="Orders">
                    <i class="fas fa-bag-shopping"></i>
                    <span class="nav-label-text">Orders</span>
                </a>
            </li>
        <?php endif; ?>

        <?php if($staff_role == 'Inquiry Manager'): ?>
            <li>
                <a href="inquiry_dashboard.php?view=inquiries"
                   class="<?php echo ($view == 'inquiries') ? 'active' : ''; ?>"
                   title="Inquiries">
                    <i class="fas fa-headset"></i>
                    <span class="nav-label-text">Inquiries</span>
                </a>
            </li>
        <?php endif; ?>

        <?php if($staff_role == 'Repair Technician'): ?>
            <li>
                <a href="repair_dashboard.php?view=repairs"
                   class="<?php echo ($view == 'repairs') ? 'active' : ''; ?>"
                   title="Repairs">
                    <i class="fas fa-screwdriver-wrench"></i>
                    <span class="nav-label-text">Repairs</span>
                </a>
            </li>
        <?php endif; ?>

        <li>
            <a href="notes_board.php"
               class="<?php echo ($current_page == 'notes_board.php') ? 'active' : ''; ?>"
               id="notesNavLink"
               title="Notes">
                <i class="fas fa-note-sticky"></i>
                <span class="nav-label-text">Notes</span>
                <span class="badge" id="notesBadge" style="display:none;">0</span>
            </a>
        </li>

    </ul>

    <!-- ── Sidebar Footer: Logout ── -->
    <div class="sidebar-footer">
        <a href="../logout.php" class="sidebar-logout-btn" title="Logout">
            <i class="fas fa-arrow-right-from-bracket"></i>
            <span class="nav-label-text">Logout</span>
        </a>
    </div>
</aside>

<script>
// ── Sidebar Toggle Logic ──
(function() {
    const sidebar = document.getElementById('mainSidebar');
    const closeBtn = document.getElementById('sidebarCloseBtn');

    // Restore saved state
    const saved = localStorage.getItem('sidebarCollapsed');
    if (saved === 'true') {
        sidebar.classList.add('collapsed');
        document.body.classList.add('sidebar-collapsed');
    }

    // Close button inside sidebar
    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            sidebar.classList.toggle('collapsed');
            document.body.classList.toggle('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        });
    }

    // Topbar toggle button (shared across all pages)
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('#sidebarToggle');
        if (btn) {
            sidebar.classList.toggle('collapsed');
            document.body.classList.toggle('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        }
    });
})();
</script>