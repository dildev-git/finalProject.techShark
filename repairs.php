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
        /* ── Page Layout ── */
        .repairs-page { margin: 40px auto; }

        /* ── Hero Banner ── */
        .repairs-hero {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            color: #fff;
            padding: 48px 40px;
            border-radius: 14px;
            margin-bottom: 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 8px 30px rgba(37,99,235,0.25);
        }
        .repairs-hero-text h1 { font-size: 30px; margin: 0 0 8px; font-weight: 700; }
        .repairs-hero-text p  { margin: 0; opacity: 0.88; font-size: 15px; }
        .repairs-hero-icon { font-size: 64px; opacity: 0.18; }

        /* ── Count pill ── */
        .repair-count-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,0.18);
            border: 1px solid rgba(255,255,255,0.3);
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            padding: 5px 14px;
            border-radius: 20px;
            margin-top: 14px;
        }

        /* ── Repair Cards ── */
        .repair-list { display: flex; flex-direction: column; gap: 22px; }

        .repair-card {
            background: #fff;
            border: 1px solid #e8edf3;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            transition: box-shadow 0.2s ease, transform 0.2s ease;
        }
        .repair-card:hover {
            box-shadow: 0 8px 28px rgba(0,0,0,0.11);
            transform: translateY(-2px);
        }

        /* Top meta bar inside card */
        .rc-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 13px 22px;
            background: #f8fafc;
            border-bottom: 1px solid #e8edf3;
            font-size: 13px;
            color: #64748b;
            flex-wrap: wrap;
            gap: 8px;
        }
        .rc-meta-left { display: flex; align-items: center; gap: 12px; }

        .rc-id-badge {
            background: var(--primary-color);
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 11px;
            border-radius: 20px;
            letter-spacing: 0.4px;
        }

        /* Status pill */
        .rc-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }
        .rc-status.pending    { background: #fef3c7; color: #92400e; }
        .rc-status.in-progress{ background: #dbeafe; color: #1e40af; }
        .rc-status.completed  { background: #d1fae5; color: #065f46; }

        /* Card body */
        .rc-body {
            padding: 22px 24px;
        }

        .rc-device {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .rc-device i { color: var(--primary-color); font-size: 18px; }

        /* Progress stepper */
        .rc-stepper {
            display: flex;
            align-items: center;
            margin-bottom: 22px;
        }
        .rc-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            position: relative;
        }
        .rc-step-dot {
            width: 30px; height: 30px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px;
            border: 2px solid #e2e8f0;
            background: #f8fafc;
            color: #94a3b8;
            z-index: 1;
            transition: all 0.3s;
        }
        .rc-step-dot.active   { background: var(--primary-color); border-color: var(--primary-color); color: #fff; }
        .rc-step-dot.done     { background: #10b981; border-color: #10b981; color: #fff; }
        .rc-step-label {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #94a3b8;
            margin-top: 6px;
            text-align: center;
        }
        .rc-step-label.active { color: var(--primary-color); }
        .rc-step-label.done   { color: #10b981; }
        .rc-step-line {
            flex: 1;
            height: 2px;
            background: #e2e8f0;
            margin: 0 -1px;
            margin-bottom: 22px;
        }
        .rc-step-line.done { background: #10b981; }
        .rc-step-line.active { background: var(--primary-color); }

        /* Info grid */
        .rc-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 16px;
        }
        @media (max-width: 640px) { .rc-grid { grid-template-columns: 1fr 1fr; } }

        .rc-field {
            background: #f8fafc;
            border: 1px solid #e8edf3;
            border-radius: 10px;
            padding: 14px 16px;
        }
        .rc-field-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #94a3b8;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .rc-field-val {
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
            line-height: 1.5;
        }

        /* Issue description — full width */
        .rc-issue {
            grid-column: 1 / -1;
        }

        /* Empty state */
        .repairs-empty {
            text-align: center;
            padding: 70px 30px;
            background: #fff;
            border-radius: 14px;
            border: 2px dashed #e2e8f0;
            color: #94a3b8;
        }
        .repairs-empty i { font-size: 52px; margin-bottom: 18px; display: block; opacity: 0.45; }
        .repairs-empty h3 { margin: 0 0 10px; color: #64748b; font-size: 20px; }
        .repairs-empty p  { margin: 0 0 20px; font-size: 14px; line-height: 1.7; }
    </style>
</head>
<body>
    <?php include 'includes/customer_header.php'; ?>

    <div class="container repairs-page">

        <!-- Hero Banner -->
        <div class="repairs-hero">
            <div class="repairs-hero-text">
                <h1><i class="fas fa-tools" style="margin-right:10px;"></i>My Repair Tracking</h1>
                <p>Live status updates for your devices at our repair lab.</p>
                <?php if(!empty($repairs)): ?>
                <span class="repair-count-pill">
                    <i class="fas fa-clipboard-list"></i>
                    <?php echo count($repairs); ?> <?php echo count($repairs) == 1 ? 'Repair Job' : 'Repair Jobs'; ?>
                </span>
                <?php endif; ?>
            </div>
            <i class="fas fa-screwdriver-wrench repairs-hero-icon"></i>
        </div>

        <?php if(empty($repairs)): ?>
            <div class="repairs-empty">
                <i class="fas fa-tools"></i>
                <h3>No repair jobs found</h3>
                <p>You have no active or past repair requests.<br>Need a repair? Bring your device to our service center or reach out via the Contact page.</p>
                <a href="contact.php" class="btn" style="display:inline-flex; align-items:center; gap:8px;">
                    <i class="fas fa-headset"></i> Contact Support
                </a>
            </div>
        <?php else: ?>
            <div class="repair-list">
            <?php foreach($repairs as $r):
                $status = $r['repairStatus'];
                $isPending    = $status === 'Pending';
                $isInProgress = $status === 'In Progress';
                $isCompleted  = $status === 'Completed';
                $statusKey    = strtolower(str_replace(' ', '-', $status));
                $statusIcon   = $isPending ? 'fa-hourglass-half' : ($isInProgress ? 'fa-spinner' : 'fa-check-circle');
            ?>
                <div class="repair-card">

                    <!-- Meta bar -->
                    <div class="rc-meta">
                        <div class="rc-meta-left">
                            <span class="rc-id-badge">#REP-<?php echo str_pad($r['repairID'], 4, '0', STR_PAD_LEFT); ?></span>
                            <span>
                                <i class="far fa-calendar-alt" style="margin-right:4px;"></i>
                                Started <?php echo date('M d, Y', strtotime($r['startDate'])); ?>
                            </span>
                        </div>
                        <span class="rc-status <?php echo $statusKey; ?>">
                            <i class="fas <?php echo $statusIcon; ?>"></i>
                            <?php echo htmlspecialchars($status); ?>
                        </span>
                    </div>

                    <!-- Card body -->
                    <div class="rc-body">

                        <!-- Device name -->
                        <div class="rc-device">
                            <i class="fas fa-laptop"></i>
                            <?php echo htmlspecialchars($r['deviceName']); ?>
                        </div>

                        <!-- Progress stepper -->
                        <div class="rc-stepper">
                            <!-- Step 1: Pending -->
                            <div class="rc-step">
                                <div class="rc-step-dot <?php echo $isPending ? 'active' : 'done'; ?>">
                                    <i class="fas <?php echo $isPending ? 'fa-hourglass-half' : 'fa-check'; ?>"></i>
                                </div>
                                <span class="rc-step-label <?php echo $isPending ? 'active' : 'done'; ?>">Received</span>
                            </div>
                            <div class="rc-step-line <?php echo $isInProgress || $isCompleted ? 'done' : ''; ?>"></div>
                            <!-- Step 2: In Progress -->
                            <div class="rc-step">
                                <div class="rc-step-dot <?php echo $isInProgress ? 'active' : ($isCompleted ? 'done' : ''); ?>">
                                    <i class="fas <?php echo $isCompleted ? 'fa-check' : 'fa-wrench'; ?>"></i>
                                </div>
                                <span class="rc-step-label <?php echo $isInProgress ? 'active' : ($isCompleted ? 'done' : ''); ?>">In Repair</span>
                            </div>
                            <div class="rc-step-line <?php echo $isCompleted ? 'done' : ''; ?>"></div>
                            <!-- Step 3: Completed -->
                            <div class="rc-step">
                                <div class="rc-step-dot <?php echo $isCompleted ? 'done' : ''; ?>">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <span class="rc-step-label <?php echo $isCompleted ? 'done' : ''; ?>">Completed</span>
                            </div>
                        </div>

                        <!-- Info grid -->
                        <div class="rc-grid">
                            <div class="rc-field rc-issue">
                                <div class="rc-field-label"><i class="fas fa-exclamation-circle"></i> Issue Description</div>
                                <div class="rc-field-val"><?php echo nl2br(htmlspecialchars($r['issueDescription'])); ?></div>
                            </div>
                            <div class="rc-field">
                                <div class="rc-field-label"><i class="fas fa-coins"></i> Estimated Cost</div>
                                <div class="rc-field-val" style="color:<?php echo $r['estimatedCost'] ? 'var(--primary-color)' : '#94a3b8'; ?>">
                                    <?php echo $r['estimatedCost'] ? 'LKR ' . number_format($r['estimatedCost'], 2) : 'Pending Assessment'; ?>
                                </div>
                            </div>
                            <div class="rc-field">
                                <div class="rc-field-label"><i class="fas fa-user-cog"></i> Technician</div>
                                <div class="rc-field-val"><?php echo htmlspecialchars($r['technicianName']) ?: 'Unassigned'; ?></div>
                            </div>
                            <div class="rc-field">
                                <div class="rc-field-label"><i class="fas fa-flag-checkered"></i> Completion</div>
                                <div class="rc-field-val" style="color:<?php echo $r['completionDate'] ? '#10b981' : '#94a3b8'; ?>">
                                    <?php echo $r['completionDate'] ? date('M d, Y', strtotime($r['completionDate'])) : 'Pending'; ?>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>

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
