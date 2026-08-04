<?php
$current_page = basename($_SERVER['PHP_SELF']);

// Fetch initial cart count and notifications dynamically
$header_cart_count = 0;
$unread_notif_count = 0;
$top_notifications = [];

if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'Customer') {
    if (isset($conn)) {
        // Fetch Cart Count
        $stmt_cart = mysqli_prepare($conn, "SELECT SUM(quantity) FROM Cart WHERE customerID = ?");
        if ($stmt_cart) {
            mysqli_stmt_bind_param($stmt_cart, "i", $_SESSION['user_id']);
            mysqli_stmt_execute($stmt_cart);
            mysqli_stmt_bind_result($stmt_cart, $header_cart_count);
            mysqli_stmt_fetch($stmt_cart);
            mysqli_stmt_close($stmt_cart);
        }

        // Fetch Unread Notification Count
        $stmt_un = mysqli_prepare($conn, "SELECT COUNT(*) FROM Notification WHERE customerID = ? AND is_read = 0");
        if ($stmt_un) {
            mysqli_stmt_bind_param($stmt_un, "i", $_SESSION['user_id']);
            mysqli_stmt_execute($stmt_un);
            mysqli_stmt_bind_result($stmt_un, $unread_notif_count);
            mysqli_stmt_fetch($stmt_un);
            mysqli_stmt_close($stmt_un);
        }

        // Fetch Top 5 Notifications
        $stmt_notifs = mysqli_prepare($conn, "SELECT * FROM Notification WHERE customerID = ? ORDER BY date DESC LIMIT 5");
        if ($stmt_notifs) {
            mysqli_stmt_bind_param($stmt_notifs, "i", $_SESSION['user_id']);
            mysqli_stmt_execute($stmt_notifs);
            $res = mysqli_stmt_get_result($stmt_notifs);
            while($row = mysqli_fetch_assoc($res)) {
                $top_notifications[] = $row;
            }
            mysqli_stmt_close($stmt_notifs);
        }
    }
}
$header_cart_count = $header_cart_count ? (int)$header_cart_count : 0;
$unread_notif_count = $unread_notif_count ? (int)$unread_notif_count : 0;

// Determine dynamic search placeholder based on current page
$search_placeholder = "Search for products...";
$page_lower = strtolower($current_page);
switch ($page_lower) {
    case 'laptops.php':
        $search_placeholder = "Search for laptops...";
        break;
    case 'desktops.php':
        $search_placeholder = "Search for desktops...";
        break;
    case 'components.php':
        $search_placeholder = "Search for components...";
        break;
    case 'accessories.php':
        $search_placeholder = "Search for accessories...";
        break;
    case 'audio.php':
        $search_placeholder = "Search for audio devices...";
        break;
    case 'storage.php':
        $search_placeholder = "Search for storage devices...";
        break;
    case 'repairs.php':
        $search_placeholder = "Search for products...";
        break;
}
?>
<header class="customer-header">
    <div class="container">
        <div class="header-top">
            <div class="logo">
                <img src="assets/logo.png" alt="Tech Shark Logo">
                <span>Tech Shark</span>
            </div>
            
            <?php 
            $hide_search_pages = ['repairs.php', 'notification.php', 'about.php', 'blog.php', 'careers.php', 'faq.php', 'privacy.php', 'terms.php'];
            if (!in_array($page_lower, $hide_search_pages)): 
            ?>
            <div class="header-search">
                <div class="search-box">
                    <!-- autocomplete="off" will prevent the browser from showing old search history, only live results will be visible -->
                    <input type="text" id="searchInput" placeholder="<?= htmlspecialchars($search_placeholder) ?>" autocomplete="off">
                    <i class="fas fa-search search-icon"></i>
                </div>
                <!-- Live Search Results Dropdown -->
                <div id="searchResults" class="search-results-dropdown" style="display: none;">
                    <!-- Results will be injected here by JavaScript -->
                </div>
            </div>
            <?php else: ?>
            <!-- Search bar disabled on specific pages to maintain header alignment -->
            <div class="header-search" style="visibility: hidden;"></div>
            <?php endif; ?>
            
            <div class="header-actions">
                <a href="profile.php" class="action-link"><i class="fas fa-user"></i> My Profile</a>
                <?php if(isset($_SESSION['user_id'])): ?>
                <!-- Notification Dropdown Wrapper -->
                <div class="notification-dropdown-wrapper" style="position: relative; display: inline-block;">
                    <a href="javascript:void(0)" class="action-link notif-btn" id="notifBtn" onclick="toggleNotifDropdown()" style="position: relative;">
                        <i class="fas fa-bell"></i>
                        <?php if ($unread_notif_count > 0): ?>
                            <span class="notif-dot" id="notifBadge"></span>
                        <?php endif; ?>
                    </a>
                    
                    <!-- Dropdown Box -->
                    <div id="notifDropdown" class="notif-dropdown" style="display: none;">
                        <div class="notif-header">
                            <span style="font-weight: 600; font-size: 15px; color: #1f2937;">Notifications</span>
                            <?php if ($unread_notif_count > 0): ?>
                            <a href="javascript:void(0)" onclick="markAllAsRead()" id="markAllReadBtn" style="font-size: 12px; color: #2563eb; text-decoration: none;">Mark all as read</a>
                            <?php endif; ?>
                        </div>
                        <div class="notif-body">
                            <?php if (empty($top_notifications)): ?>
                                <div style="padding: 20px; text-align: center; color: #6b7280; font-size: 14px;">No notifications yet.</div>
                            <?php else: ?>
                                <?php foreach($top_notifications as $n): 
                                    $bg_color = ($n['is_read'] == 0) ? '#e0f2fe' : '#ffffff';
                                    $icon = 'fas fa-info-circle';
                                    $icon_color = '#3b82f6';
                                    $link = '#';
                                    
                                    if (stripos($n['type'], 'payment') !== false) {
                                        $icon = 'fas fa-check-circle';
                                        $icon_color = '#10b981';
                                        $link = 'profile.php?tab=orders';
                                    } elseif (stripos($n['type'], 'order') !== false) {
                                        $icon = 'fas fa-box';
                                        $icon_color = '#f59e0b';
                                        $link = 'profile.php?tab=orders';
                                    } elseif (stripos($n['type'], 'inquiry') !== false) {
                                        $icon = 'fas fa-reply';
                                        $icon_color = '#8b5cf6';
                                        $link = 'contact.php#support-history';
                                    } elseif (stripos($n['type'], 'repair') !== false) {
                                        $icon = 'fas fa-tools';
                                        $icon_color = '#3b82f6';
                                        $link = 'repairs.php';
                                    }
                                ?>
                                <a href="read_notif.php?id=<?= $n['notificationID'] ?>&url=<?= urlencode($link) ?>" class="notif-item" style="background-color: <?= $bg_color ?>; display: flex; text-decoration: none; color: inherit;">
                                    <div class="notif-icon" style="color: <?= $icon_color ?>;">
                                        <i class="<?= $icon ?>"></i>
                                    </div>
                                    <div class="notif-content">
                                        <div class="notif-text"><?= htmlspecialchars($n['message']) ?></div>
                                        <div class="notif-time"><?= date('d M Y, h:i A', strtotime($n['date'])) ?></div>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="notif-footer">
                            <a href="notification.php" style="color: #2563eb; font-size: 13px; text-decoration: none; font-weight: 500;">View All Notifications</a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <a href="cart.php" class="action-link cart">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count"><?= $header_cart_count ?></span>
                </a> 
                
                <?php if(isset($_SESSION['user_id'])): ?>
                <a href="logout.php" class="action-link logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
                <?php else: ?>
                <a href="login.php" class="action-link login-btn">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
                <?php endif; ?>                                       
            </div>
        </div>
        
        <nav class="header-nav">
            <ul>
                <li><a href="Index.php" class="<?= (strtolower($current_page) == 'index.php') ? 'active' : '' ?>">Home</a></li>
                <li><a href="laptops.php" class="<?= ($current_page == 'laptops.php') ? 'active' : '' ?>">Laptops</a></li>
                <li><a href="desktops.php" class="<?= ($current_page == 'desktops.php') ? 'active' : '' ?>">Desktops</a></li>
                <li><a href="components.php" class="<?= ($current_page == 'components.php') ? 'active' : '' ?>">Components</a></li>
                <li><a href="accessories.php" class="<?= ($current_page == 'accessories.php') ? 'active' : '' ?>">Accessories</a></li>
                <li><a href="audio.php" class="<?= ($current_page == 'audio.php') ? 'active' : '' ?>">Audio</a></li>
                <li><a href="storage.php" class="<?= ($current_page == 'storage.php') ? 'active' : '' ?>">Storage</a></li>
                <li><a href="repairs.php" class="<?= ($current_page == 'repairs.php') ? 'active' : '' ?>">Repairs</a></li>
            </ul>
        </nav>
    </div>
</header>

<style>
/* Notification Dropdown Styles */
.notif-dot {
    position: absolute;
    top: -2px;
    right: -2px;
    width: 10px;
    height: 10px;
    background-color: #ef4444;
    border-radius: 50%;
    border: 2px solid #fff;
}
.notif-dropdown {
    position: absolute;
    top: 45px;
    right: -50px;
    width: 320px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    z-index: 1000;
    border: 1px solid #e5e7eb;
    overflow: hidden;
    text-align: left;
}
.notif-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
}
.notif-body {
    max-height: 350px;
    overflow-y: auto;
}
/* Scrollbar styling for dropdown */
.notif-body::-webkit-scrollbar { width: 6px; }
.notif-body::-webkit-scrollbar-track { background: #f1f1f1; }
.notif-body::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 4px; }

.notif-item {
    display: flex;
    padding: 12px 15px;
    border-bottom: 1px solid #f3f4f6;
    transition: background 0.2s;
    align-items: flex-start;
}
.notif-item:last-child { border-bottom: none; }
.notif-icon {
    font-size: 18px;
    margin-right: 12px;
    margin-top: 2px;
}
.notif-content { flex: 1; }
.notif-text {
    font-size: 13px;
    color: #374151;
    margin-bottom: 4px;
    line-height: 1.4;
}
.notif-time {
    font-size: 11px;
    color: #9ca3af;
}
.notif-footer {
    padding: 10px;
    text-align: center;
    background: #f9fafb;
    border-top: 1px solid #e5e7eb;
}
.notif-footer a:hover { text-decoration: underline !important; }
</style>

<script>
function toggleNotifDropdown() {
    const dropdown = document.getElementById('notifDropdown');
    dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
}

// Close dropdown if clicked outside
document.addEventListener('click', function(event) {
    const wrapper = document.querySelector('.notification-dropdown-wrapper');
    const dropdown = document.getElementById('notifDropdown');
    if (wrapper && !wrapper.contains(event.target)) {
        if(dropdown) dropdown.style.display = 'none';
    }
});

function markAllAsRead() {
    fetch('api/mark_notifications_read.php', { method: 'POST' })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Remove badge
            const badge = document.getElementById('notifBadge');
            if (badge) badge.remove();
            // Remove "Mark all as read" button
            const markBtn = document.getElementById('markAllReadBtn');
            if (markBtn) markBtn.style.display = 'none';
            // Change background of all unread items to white
            const items = document.querySelectorAll('.notif-item');
            items.forEach(item => item.style.backgroundColor = '#ffffff');
        }
    })
    .catch(err => console.error(err));
}

function pollNotifications() {
    fetch('ajax_notifications.php')
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Update badge
            const notifBtn = document.getElementById('notifBtn');
            let badge = document.getElementById('notifBadge');
            
            if (data.unread_count > 0) {
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'notif-dot';
                    badge.id = 'notifBadge';
                    notifBtn.appendChild(badge);
                }
                
                // Show "Mark all as read" button if it exists and was hidden
                const markBtn = document.getElementById('markAllReadBtn');
                if (markBtn) markBtn.style.display = 'inline';
            } else {
                if (badge) badge.remove();
            }
            
            // Update dropdown body HTML
            const notifBody = document.querySelector('.notif-body');
            if (notifBody && data.html) {
                notifBody.innerHTML = data.html;
            }
        }
    })
    .catch(err => console.error('Error polling notifications:', err));
}

// Poll every 15 seconds for real-time updates
setInterval(pollNotifications, 15000);
</script>
