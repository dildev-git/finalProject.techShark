<?php
session_start();
include('includes/dbconnection.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Customer') {
    header("Location: login.php");
    exit;
}

$customer_id = $_SESSION['user_id'];
$repairs = [];

$sql = "SELECT r.*, s.fullName as technicianName 
        FROM Repair r 
        LEFT JOIN Staff s ON r.staffID = s.staffID 
        WHERE r.customerID = ? ORDER BY r.repairID DESC";

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $customer_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while($row = mysqli_fetch_assoc($res)) {
        $repairs[] = $row;
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Repairs - Tech Shark</title>
    <link rel="icon" type="image/png" href="assets/logo.png"/>
    <link rel="stylesheet" href="includes/css/customer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .repairs-container { margin: 40px auto; background: white; padding: 40px; border-radius: 8px; box-shadow: var(--shadow-sm); }
        .repair-card { border: 1px solid var(--medium-gray); border-radius: 8px; padding: 25px; margin-bottom: 20px; display: flex; flex-direction: column; gap: 15px; }
        .repair-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--medium-gray); padding-bottom: 15px; }
        .r-title { font-size: 20px; font-weight: 600; color: var(--primary-dark); }
        .r-status { padding: 6px 12px; border-radius: 20px; font-size: 13px; font-weight: bold; }
        .status-Pending { background: #fef3c7; color: #92400e; }
        .status-In-Progress { background: #dbeafe; color: #1e40af; }
        .status-Completed { background: #d1fae5; color: #065f46; }
        .r-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .r-field { margin-bottom: 10px; }
        .r-label { font-size: 13px; color: var(--text-light); text-transform: uppercase; font-weight: 600; }
        .r-val { font-size: 16px; color: var(--text-color); }
    </style>
</head>
<body>
    <?php include 'includes/customer_header.php'; ?>

    <div class="container">
        <div class="repairs-container">
            <h1 class="section-title">My Repair Tracking</h1>
            <p style="margin-bottom: 30px; color:var(--text-light);">Track the live status of your devices currently stationed at our repair lab.</p>

            <?php if(empty($repairs)): ?>
                <div style="text-align:center; padding: 40px;">
                    <i class="fas fa-tools" style="font-size: 48px; color:var(--medium-gray); margin-bottom: 20px;"></i>
                    <h3>You have no active repair requests.</h3>
                    <p style="color:var(--text-light);">Need a repair? Bring your device to our service center or contact our inquiry manager.</p>
                </div>
            <?php else: ?>
                <?php foreach($repairs as $r): ?>
                    <?php 
                        $statusClass = 'status-' . str_replace(' ', '-', $r['repairStatus']);
                    ?>
                    <div class="repair-card">
                        <div class="repair-header">
                            <div class="r-title"><?php echo htmlspecialchars($r['deviceName']); ?> (#REP-<?php echo str_pad($r['repairID'], 4, '0', STR_PAD_LEFT); ?>)</div>
                            <div class="r-status <?php echo $statusClass; ?>"><?php echo htmlspecialchars($r['repairStatus']); ?></div>
                        </div>
                        <div class="r-grid">
                            <div class="r-field">
                                <div class="r-label">Issue Description</div>
                                <div class="r-val"><?php echo nl2br(htmlspecialchars($r['issueDescription'])); ?></div>
                            </div>
                            <div class="r-field">
                                <div class="r-label">Estimated Cost</div>
                                <div class="r-val"><?php echo $r['estimatedCost'] ? 'LKR ' . number_format($r['estimatedCost'], 2) : 'Pending Assessment'; ?></div>
                            </div>
                            <div class="r-field">
                                <div class="r-label">Technician</div>
                                <div class="r-val"><?php echo htmlspecialchars($r['technicianName']) ?: 'Unassigned'; ?></div>
                            </div>
                            <div class="r-field">
                                <div class="r-label">Dates</div>
                                <div class="r-val">
                                    Started: <?php echo date('M d, Y', strtotime($r['startDate'])); ?><br>
                                    Completed: <?php echo $r['completionDate'] ? date('M d, Y', strtotime($r['completionDate'])) : 'TBD'; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Poll Cart
        const updateCartCount = async () => {
            const badge = document.getElementById('cartCountBadge');
            try {
                const res = await fetch('api/get_cart_count.php');
                const data = await res.json();
                badge.textContent = data.count;
                badge.style.display = data.count > 0 ? 'flex' : 'none';
            } catch(e) {}
        };
        updateCartCount();
        setInterval(updateCartCount, 5000);
    </script>
</body>
</html>