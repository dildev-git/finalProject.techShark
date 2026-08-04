<?php
session_start();
include('../includes/dbconnection.php');

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Customer') {
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_SESSION['user_id'];
    
    // Update all unread notifications to read (1) for this customer
    $stmt = mysqli_prepare($conn, "UPDATE Notification SET is_read = 1 WHERE customerID = ? AND is_read = 0");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $customer_id);
        if (mysqli_stmt_execute($stmt)) {
            $response['success'] = true;
            $response['message'] = 'Notifications marked as read';
        } else {
            $response['message'] = 'Database update failed';
        }
        mysqli_stmt_close($stmt);
    } else {
        $response['message'] = 'Database prepare failed';
    }
} else {
    $response['message'] = 'Invalid request method';
}

echo json_encode($response);
?>
