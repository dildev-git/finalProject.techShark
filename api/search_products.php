<?php
// api/search_products.php
include('../includes/dbconnection.php');

/** @var mysqli $conn Provided by dbconnection.php */
if (!isset($conn) || !$conn) {
    echo json_encode(['count' => 0]);
    exit;
}

header('Content-Type: application/json');

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$search_term = "%{$query}%";

// The SQL is created by checking if a category has been came.
if ($category > 0) {
    $sql = "SELECT productID, productName, price, productImage 
            FROM Product 
            WHERE status = 'Active' 
            AND categoryID = ? 
            AND productName LIKE ? 
            LIMIT 5";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "is", $category, $search_term);
} else {
    // If you come from regular pages like index.php, all will be searched.
    $sql = "SELECT productID, productName, price, productImage 
            FROM Product 
            WHERE status = 'Active' 
            AND productName LIKE ? 
            LIMIT 5";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $search_term);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$products = [];
while ($row = mysqli_fetch_assoc($result)) {
    $products[] = $row;
}

echo json_encode($products);
mysqli_stmt_close($stmt);
?>