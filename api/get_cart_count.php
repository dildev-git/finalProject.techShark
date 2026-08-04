<?php
session_start();
include('../includes/dbconnection.php');

/** @var mysqli $conn Provided by dbconnection.php */
if (!isset($conn) || !$conn) {
    echo json_encode(['count' => 0]);
    exit;
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0]);
    exit;
}

$customer_id = $_SESSION['user_id'];
$sql = "SELECT SUM(quantity) as total_items FROM Cart WHERE customerID = ?";

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $customer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $row = mysqli_fetch_assoc($result);
    $count = $row['total_items'] ? (int)$row['total_items'] : 0;
    
    echo json_encode(['count' => $count]);
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['count' => 0]);
}
?>
