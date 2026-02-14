<?php
require_once '../config.php';
require_once '../includes/auth_check.php';

// Get parameters
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : null;
$receipt_id = isset($_GET['print']) ? $_GET['print'] : null;

// --- PART 1: PRINT VIEW (The 3-Copy Slip) ---
if ($receipt_id) {
    // We fetch EVERYTHING needed for the slip here
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
        die("Error: Receipt Record not found in database. Please check if ID $receipt_id exists.");
    }

    $copies = ['Office Copy', 'Student Copy', 'Teacher Copy'];
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Receipt_<?php echo $r['receipt_number']; ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Inter', sans-serif; color: #000; background: #fff; margin: 0; padding: 0; }
            @page { size: A4 landscape; margin: 5mm; }
            .no-print-nav { background: #f8f9fa; padding: 10px; text-align: center; border-bottom: 1px solid #ddd; }
            .receipt-wrapper { display: flex; flex-direction: row; justify-content: space-between; gap: 12px; padding: 10px; width: 100%; }
            .receipt-box {
                flex: 1; border: 1.5px dashed #000; padding: 15px; background: #fff;
                position: relative; min-height: 185mm; display: flex; flex-direction: column;
            }
            .copy-tag {
                background: #000; color: #fff; font-size: 9px; padding: 2px 8px;
                font-weight: bold; position: absolute; top: 0; right: 10px; border-radius: 0 0 5px 5px;
            }
            .header-section { text-align: center; margin-bottom: 12px; border-bottom: 1px solid #eee; padding-bottom: 8px; }
            .logo-img { width: 45px; height: 45px; border-radius: 50%; margin-bottom: 5px; }
            .academy-name { font-size: 0.95rem; font-weight: 800; text-transform: uppercase; margin: 0; line-height: 1.1; }
            .label { font-size: 8px; font-weight: 700; color: #555; text-transform: uppercase; display: block; margin-bottom: 1px; }
            .value { font-size: 11px; font-weight: 600; color: #000; display: block; border-bottom: 1px solid #f0f0f0; margin-bottom: 8px; }
            .amount-section { background: #f9f9f9; border: 1px solid #000; padding: 8px; margin-top: 10px; text-align: center; border-radius: 5px; }
            .amount-text { font-size: 1.2rem; font-weight: 800; }
            .footer-signature { margin-top: auto; padding-top: 40px; }
            .signature-line { border-top: 1.2px solid #000; font-size: 10px; font-weight: 700; text-align: center; padding-top: 5px; }
            @media print { .no-print-nav { display: none; } body { background: #fff; } }
        </style>
    </head>
    <body onload="window.print()">
        <div class="no-print-nav">
            <button onclick="window.print()" class="btn btn-dark btn-sm">Print Now</button>
            <a href="receipts.php?student_id=<?php echo $r['student_id']; ?>" class="btn btn-outline-secondary btn-sm">Back to Ledger</a>
        </div>
        <div class="receipt-wrapper">
            <?php foreach ($copies as $copy_name): ?>
            <div class="receipt-box">
                <div class="copy-tag"><?php echo $copy_name; ?></div>
                <div class="header-section">
                    <img src="../uploads/Logo Web.jpg" class="logo-img">
                    <h1 class="academy-name">AI Future Leaders Academy</h1>
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
                    <div class="col-7"><span class="label">Class</span><span class="value"><?php echo htmlspecialchars($r['class_name']); ?></span></div>
                    <div class="col-5"><span class="label">Date</span><span class="value"><?php echo date('d-m-Y', strtotime($r['payment_date'])); ?></span></div>
                </div>

                <span class="label">Fee Category</span>
                <span class="value"><?php echo ($r['amount'] == 800) ? "Admission (One-Time)" : "Monthly Tuition"; ?></span>

                <div class="amount-section">
                    <span class="label">Net Received</span>
                    <div class="amount-text"><?php echo number_format($r['amount']); ?> PKR</div>
                </div>

                <div class="footer-signature">
                    <div class="signature-line">Authorized Signature</div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// --- PART 2: LEDGER VIEW (The Table) ---
if (!$student_id) {
    die("Error: No Student ID provided. Please go back to the Fees Management page and select a student.");
}

include '../includes/header.php';
?>

<style>
    body { background: linear-gradient(rgba(6, 11, 40, 0.9), rgba(6, 11, 40, 0.9)), url('../uploads/background.png'); background-size: cover; min-height: 100vh; }
    .stylish-header-bar { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-radius: 20px; padding: 20px 35px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
    .black-title { color: #000; font-weight: 800; font-size: 2.2rem; letter-spacing: -1.8px; margin: 0; }
    .history-card { background: #fff; border-radius: 28px; color: #000; overflow: hidden; border: none; }
    .table thead th { background: #000; color: #fff; padding: 18px; border: none; font-size: 0.75rem; text-transform: uppercase; }
    .fee-badge-admission { background: #e3fcef; color: #00a854; border: 1px solid #b7eb8f; padding: 5px 12px; border-radius: 50px; font-size: 11px; font-weight: 700; }
    .fee-badge-monthly { background: #e6f7ff; color: #1890ff; border: 1px solid #91d5ff; padding: 5px 12px; border-radius: 50px; font-size: 11px; font-weight: 700; }
    .btn-generate { background: #00d4ff; color: #000; font-weight: 700; border-radius: 50px; padding: 8px 24px; border: none; transition: 0.3s; }
    .btn-generate:hover { background: #000; color: #fff; }
</style>

<div class="container py-5">
    <div class="stylish-header-bar">
        <div>
            <h2 class="black-title">Payment Ledger</h2>
            <p class="text-muted small mb-0">Student History & Receipt Generation</p>
        </div>
        <a href="fees.php" class="btn btn-dark rounded-pill px-4 fw-bold">← Back to Fees</a>
    </div>

    <div class="history-card shadow-lg">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Ref #</th>
                        <th>Fee Categorization</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM fees WHERE student_id = ? ORDER BY id DESC");
                    $stmt->execute([$student_id]);
                    $receipts = $stmt->fetchAll();

                    if (count($receipts) > 0):
                        foreach ($receipts as $r):
                            $is_admission = ($r['amount'] == 800);
                    ?>
                        <tr>
                            <td class="ps-4 fw-bold">#<?php echo $r['receipt_number']; ?></td>
                            <td>
                                <?php if($is_admission): ?>
                                    <span class="fee-badge-admission">ADMISSION (ONE-TIME)</span>
                                <?php else: ?>
                                    <span class="fee-badge-monthly">MONTHLY TUITION</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight: 800;"><?php echo number_format($r['amount']); ?> PKR</td>
                            <td><small class="text-muted">Paid on <?php echo date('d M, Y', strtotime($r['payment_date'])); ?></small></td>
                            <td class="text-center">
                                <a href="receipts.php?print=<?php echo $r['id']; ?>" target="_blank" class="btn btn-sm btn-generate">
                                    Generate Slip
                                </a>
                            </td>
                        </tr>
                    <?php 
                        endforeach; 
                    else:
                    ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">No payment history found for this student.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
