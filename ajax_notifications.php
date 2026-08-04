<?php
/**
 * =============================================================================
 * ajax_notifications.php — Notification Dropdown AJAX Handler
 * =============================================================================
 * Returns the latest 5 notifications for the logged-in customer as JSON,
 * along with the total unread count and pre-rendered HTML for the dropdown.
 *
 * Called by: includes/customer_header.php (JavaScript polling / on page load)
 * Response format: { success, unread_count, html }
 * Only accessible by logged-in Customers.
 *
 * Notification type → icon/link mapping:
 *   payment  → green check-circle → profile.php?tab=orders
 *   order    → amber box          → profile.php?tab=orders
 *   inquiry  → purple reply       → contact.php
 *   repair   → blue tools         → repairs.php
 *   (other)  → blue info-circle   → #
 * =============================================================================
 */

session_start();
include('includes/dbconnection.php');

// All responses must be JSON
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Customer') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$customer_id = $_SESSION['user_id'];

// Get unread count
$stmt_count = mysqli_prepare($conn, "SELECT COUNT(*) as unread FROM Notification WHERE customerID = ? AND is_read = 0");
mysqli_stmt_bind_param($stmt_count, "i", $customer_id);
mysqli_stmt_execute($stmt_count);
$res_count = mysqli_stmt_get_result($stmt_count);
$unread_count = mysqli_fetch_assoc($res_count)['unread'];
mysqli_stmt_close($stmt_count);

// Get top 5 notifications
$top_notifications = [];
$stmt_top = mysqli_prepare($conn, "SELECT * FROM Notification WHERE customerID = ? ORDER BY date DESC LIMIT 5");
mysqli_stmt_bind_param($stmt_top, "i", $customer_id);
mysqli_stmt_execute($stmt_top);
$res_top = mysqli_stmt_get_result($stmt_top);
while($row = mysqli_fetch_assoc($res_top)) {
    $top_notifications[] = $row;
}
mysqli_stmt_close($stmt_top);

// Generate HTML
ob_start();
if (empty($top_notifications)) {
    echo '<div style="padding: 20px; text-align: center; color: #6b7280; font-size: 14px;">No notifications yet.</div>';
} else {
    foreach($top_notifications as $n) {
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
            $link = 'contact.php';
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
        <?php
    }
}
$html = ob_get_clean();

echo json_encode([
    'success' => true,
    'unread_count' => $unread_count,
    'html' => $html
]);
