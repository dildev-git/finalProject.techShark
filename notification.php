<?php
session_start();
include('includes/dbconnection.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Customer') {
    header("Location: login.php");
    exit;
}

$customer_id = $_SESSION['user_id'];

// Automatically mark all notifications as read when the user visits this dedicated page
$stmt_update = mysqli_prepare($conn, "UPDATE Notification SET is_read = 1 WHERE customerID = ? AND is_read = 0");
if ($stmt_update) {
    mysqli_stmt_bind_param($stmt_update, "i", $customer_id);
    mysqli_stmt_execute($stmt_update);
    mysqli_stmt_close($stmt_update);
}

// Fetch all notifications for the customer
$notifications = [];
$stmt = mysqli_prepare($conn, "SELECT * FROM Notification WHERE customerID = ? ORDER BY date DESC");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $customer_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while($row = mysqli_fetch_assoc($res)) {
        $notifications[] = $row;
    }
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Notifications - Tech Shark</title>
    <link rel="icon" type="image/png" href="assets/logo.png"/>
    <link rel="stylesheet" href="includes/css/customer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .notif-page-container {
            max-width: 800px;
            margin: 40px auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .notif-page-header {
            padding: 20px 30px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .notif-page-title {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .notif-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .notif-list-item {
            display: flex;
            padding: 20px 30px;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.2s;
        }
        .notif-list-item:last-child {
            border-bottom: none;
        }
        .notif-list-item:hover {
            background: #f8fafc;
        }
        .notif-list-icon {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-right: 20px;
            flex-shrink: 0;
        }
        .notif-list-content {
            flex: 1;
        }
        .notif-list-message {
            font-size: 15px;
            color: #334155;
            margin-bottom: 8px;
            line-height: 1.5;
        }
        .notif-list-meta {
            font-size: 13px;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .empty-notifs {
            padding: 80px 20px;
            text-align: center;
            color: #94a3b8;
        }
        .empty-notifs i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #cbd5e1;
        }
    </style>
</head>
<body>
    <?php include 'includes/customer_header.php'; ?>

    <div class="container">
        <div class="notif-page-container">
            <div class="notif-page-header">
                <h1 class="notif-page-title"><i class="fas fa-bell" style="color: var(--primary-color);"></i> My Notifications</h1>
            </div>
            
            <div class="notif-list-wrapper">
                <?php if (empty($notifications)): ?>
                    <div class="empty-notifs">
                        <i class="far fa-bell-slash"></i>
                        <h3>No notifications yet</h3>
                        <p>When you place an order, make a payment, or receive a reply, it will appear here.</p>
                    </div>
                <?php else: ?>
                    <ul class="notif-list">
                        <?php foreach($notifications as $n): 
                            $icon = 'fas fa-info';
                            $icon_bg = '#e0f2fe';
                            $icon_color = '#0ea5e9';
                            $link = '#';
                            
                            if (stripos($n['type'], 'payment') !== false) {
                                $icon = 'fas fa-check';
                                $icon_bg = '#d1fae5';
                                $icon_color = '#10b981';
                                $link = 'profile.php?tab=orders';
                            } elseif (stripos($n['type'], 'order') !== false) {
                                $icon = 'fas fa-box';
                                $icon_bg = '#fef3c7';
                                $icon_color = '#f59e0b';
                                $link = 'profile.php?tab=orders';
                            } elseif (stripos($n['type'], 'inquiry') !== false) {
                                $icon = 'fas fa-reply';
                                $icon_bg = '#ede9fe';
                                $icon_color = '#8b5cf6';
                                $link = 'contact.php';
                            } elseif (stripos($n['type'], 'repair') !== false) {
                                $icon = 'fas fa-tools';
                                $icon_bg = '#e0f2fe';
                                $icon_color = '#3b82f6';
                                $link = 'repairs.php';
                            }
                        ?>
                        <li class="notif-list-item" onclick="window.location.href='<?= $link ?>'" style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='var(--shadow-md)';" onmouseout="this.style.transform='none'; this.style.boxShadow='var(--shadow-sm)';">
                            <div class="notif-list-icon" style="background-color: <?= $icon_bg ?>; color: <?= $icon_color ?>;">
                                <i class="<?= $icon ?>"></i>
                            </div>
                            <div class="notif-list-content">
                                <div class="notif-list-message"><?= htmlspecialchars($n['message']) ?></div>
                                <div class="notif-list-meta">
                                    <span><i class="far fa-clock"></i> <?= date('d M Y', strtotime($n['date'])) ?> at <?= date('h:i A', strtotime($n['date'])) ?></span>
                                    <span><i class="fas fa-tag"></i> <?= htmlspecialchars($n['type']) ?></span>
                                </div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
