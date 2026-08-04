<?php
/**
 * =============================================================================
 * read_notif.php — Notification Read-and-Redirect Handler
 * =============================================================================
 * Acts as a bridge when a customer clicks a notification link.
 * It marks the specified notification as read in the database, then
 * redirects the user to the target URL that was embedded in the link.
 *
 * Expected GET parameters:
 *   id  — notificationID (integer) of the notification to mark as read.
 *   url — URL-encoded destination to redirect to after marking as read.
 *
 * Security: The UPDATE statement is parameterised and filters by both
 * notificationID AND the session's customerID, so users cannot mark
 * another customer's notifications as read.
 * =============================================================================
 */

session_start();
include('includes/dbconnection.php');

// Require the user to be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['id']) && isset($_GET['url'])) {
    $notif_id   = (int)$_GET['id'];
    $url        = $_GET['url'];
    $customer_id= $_SESSION['user_id'];

    // Mark only this customer's notification as read (prevents ID spoofing)
    $stmt = mysqli_prepare($conn, "UPDATE Notification SET is_read = 1 WHERE notificationID = ? AND customerID = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $notif_id, $customer_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    // Forward the user to the intended destination (e.g., profile.php?tab=orders)
    header("Location: " . $url);
    exit;
} else {
    // Missing parameters — fall back to the homepage
    header("Location: Index.php");
    exit;
}
?>
