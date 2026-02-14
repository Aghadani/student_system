<?php
require_once '../config.php';
require_once '../includes/auth_check.php';

// DEBUGGING: Remove these two lines after it starts working
// var_dump($_GET); 

// We check for both 'student_id' (from ledger link) and 'print' (from print link)
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : null;
$receipt_id = isset($_GET['print']) ? $_GET['print'] : null;

// --- PART 1: PRINT VIEW ---
if ($receipt_id) {
    // We need to fetch student and class info for the receipt
    $stmt = $pdo->prepare("
        SELECT f.*, s.id as sid, s.name as student_name, s.father_name, c.class_name 
        FROM fees f 
        JOIN students s ON f.student_id = s.id 
        JOIN classes c ON s.class_id = c.id 
        WHERE f.id = ?
    ");
    $stmt->execute([$receipt_id]);
    $r = $stmt->fetch();

    if (!$r) {
        die("<div style='padding:20px; background:#fff1f0; color:#f5222d; border:1px solid #ffa39e;'>
                <strong>Error:</strong> Receipt #$receipt_id not found in database.
             </div>");
    }

    $copies = ['Office Copy', 'Student Copy', 'Teacher Copy'];
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Receipt_<?php echo $r['receipt_number']; ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { font-family: 'Inter', sans-serif; color: #000; background: #fff; }
            @page { size: A4 landscape; margin: 5mm; }
            .no-print-nav { background: #f8f9fa; padding: 10px; text-align: center; border-bottom: 1px solid #ddd; }
            .receipt-wrapper { display: flex; flex-direction: row; justify-content: space-between; gap: 10px; padding: 10px; }
            .receipt-box { flex: 1; border: 1.5px dashed #000; padding: 15px; position: relative; min-height: 180mm; display: flex; flex-direction: column; }
            .copy-tag { background: #000; color: #fff; font-size: 10px; padding: 2px 8px; position: absolute; top: 0; right: 10px; font-weight: bold; }
            .logo-img { width: 45px; height: 45px; border-radius: 50%; margin-bottom: 5px; }
            .label { font-size: 9px; color: #555; text-transform: uppercase; font-weight: 700; display: block; }
            .value { font-size: 12px; border-bottom: 1px solid #f0f0f0; margin-bottom: 12px; font-weight: 600; display: block; padding-bottom: 2px; }
            .amount-section { background: #f9f9f9; border: 1px solid #000; padding: 10px; text-align: center; border-radius: 5px; margin-top: 10px; }
            .amount-text { font-size: 1.3rem; font-weight: 800; }
            @media print { .no-print-nav { display: none; } }
        </style>
    </head>
    <body onload="window.print()">
        <div class="no-print-nav">
            <button onclick="window.print()" class="btn btn-dark btn-sm">Print Now</button>
            <a href="receipts.php?student_id=<?php echo $r['student_id']; ?>" class="btn btn-outline-secondary btn-sm">Back to Ledger</a>
        </div>
        <div class="receipt-wrapper">
            <?php foreach ($copies as $copy): ?>
            <div class="receipt-box">
                <div class="copy-tag"><?php echo $copy; ?></div>
                <div class="text-center mb-3">
                    <img src="../uploads/Logo Web.jpg" class="logo-img">
                    <h1 style="font-size: 1rem; font-weight: 800; margin:0;">AI FUTURE LEADERS ACADEMY</h1>
                </div>
                
                <div class="row gx-2">
                    <div class="col-6"><span class="label">Receipt No</span><span class="value">#<?php echo $r['receipt_number']; ?></span></div>
                    <div class="col-6"><span class="label">Student ID</span><span class="value">STU-<?php echo $r['sid']; ?></span></div>
                </div>

                <span class="label">Student Name</span>
                <span class="value text-uppercase"><?php echo htmlspecialchars($r['student_name']); ?></span>

                <span class="label">Father's Name</span>
                <span class="value text-uppercase"><?php echo htmlspecialchars($r['father_name']); ?></span>

                <div class="row gx-2">
                    <div class="col-7"><span class="label">Class / Course</span><span class="value"><?php echo $r['class_name']; ?></span></div>
                    <div class="col-5"><span class="label">Date</span><span class="value"><?php echo date('d-m-Y', strtotime($r['payment_date'])); ?></span></div>
                </div>

                <span class="label">Fee Category</span>
                <span class="value"><?php echo ($r['amount'] == 800) ? "Admission (One-Time)" : "Monthly Tuition"; ?></span>

                <div class="amount-section">
                    <span class="label">Net Amount Received</span>
                    <div class="amount-text"><?php echo number_format($r['amount']); ?> PKR</div>
                </div>

                <div style="margin-top: auto; border-top: 1.5px solid #000; text-align: center; padding-top: 5px; font-weight: 700; font-size: 11px;">
                    Authorized Stamp & Signature
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// --- PART 2: LEDGER VIEW ---
if ($student_id) {
    include '../includes/header.php';
    ?>
    <style>
        body { background: linear-gradient(rgba(6,11,40,.9), rgba(6,11,40,.9)), url('../uploads/background.png') center/cover; min-height: 100vh; }
        .ledger-card { background: #fff; border-radius: 25px; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.3); }
        .table thead th { background: #000; color: #fff; border: none; text-transform: uppercase; font-size: 12px; padding: 15px; }
        .btn-print { background: #00d4ff; color: #000; font-weight: 700; border-radius: 50px; border: none; padding: 5px 15px; transition: 0.3s; }
        .btn-print:hover { background: #000; color: #fff; }
    </style>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded-4 shadow-sm">
            <h2 class="mb-0 fw-800 text-dark">Student Payment Ledger</h2>
            <a href="fees.php" class="btn btn-dark rounded-pill">← Back</a>
        </div>

        <div class="ledger-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Ref #</th>
                            <th>Fee Type</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->prepare("SELECT * FROM fees WHERE student_id = ? ORDER BY id DESC");
                        $stmt->execute([$student_id]);
                        $rows = $stmt->fetchAll();

                        if(count($rows) > 0) {
                            foreach ($rows as $row) {
                                $is_adm = ($row['amount'] == 800);
                                echo "<tr>
                                    <td class='ps-4 fw-bold'>#{$row['receipt_number']}</td>
                                    <td>" . ($is_adm ? '<span class="badge bg-success">Admission</span>' : '<span class="badge bg-info">Tuition</span>') . "</td>
                                    <td class='fw-bold'>" . number_format($row['amount']) . " PKR</td>
                                    <td>" . date('d M, Y', strtotime($row['payment_date'])) . "</td>
                                    <td class='text-center'>
                                        <a href='receipts.php?print={$row['id']}' target='_blank' class='btn btn-print btn-sm'>Print Slip</a>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-center py-5 text-muted'>No payment records found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
    include '../includes/footer.php';
} else {
    // If NO ID is provided at all
    die("<div style='padding:50px; text-align:center; font-family:sans-serif;'>
            <h2 style='color:#f5222d;'>Access Denied</h2>
            <p>No Student ID or Receipt ID was detected in the URL.</p>
            <a href='fees.php' style='color:#1890ff; text-decoration:none;'>Click here to go back to Fees Management</a>
         </div>");
}
