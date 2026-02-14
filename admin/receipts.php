<?php
require_once '../config.php';
require_once '../includes/auth_check.php';

$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : null;
$receipt_id = isset($_GET['print']) ? $_GET['print'] : null;

// --- PART 1: PRINT VIEW ---
if ($receipt_id) {
    $stmt = $pdo->prepare("
        SELECT f.*, s.id as sid, s.name as student_name, s.father_name, c.class_name 
        FROM fees f 
        JOIN students s ON f.student_id = s.id 
        JOIN classes c ON s.class_id = c.id 
        WHERE f.id = ?
    ");
    $stmt->execute([$receipt_id]);
    $r = $stmt->fetch();

    if (!$r) die("Receipt not found in database.");

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
            .copy-tag { background: #000; color: #fff; font-size: 10px; padding: 2px 8px; position: absolute; top: 0; right: 10px; }
            .logo-img { width: 40px; height: 40px; margin-bottom: 5px; }
            .label { font-size: 9px; color: #666; text-transform: uppercase; font-weight: bold; }
            .value { font-size: 12px; border-bottom: 1px solid #eee; margin-bottom: 10px; font-weight: 600; display: block; }
            .amount-box { background: #f9f9f9; border: 1px solid #000; text-align: center; padding: 10px; font-size: 1.2rem; font-weight: 800; }
            @media print { .no-print-nav { display: none; } }
        </style>
    </head>
    <body onload="window.print()">
        <div class="no-print-nav">
            <button onclick="window.print()" class="btn btn-dark">Print Now</button>
            <a href="receipts.php?student_id=<?php echo $r['student_id']; ?>" class="btn btn-secondary">Back</a>
        </div>
        <div class="receipt-wrapper">
            <?php foreach ($copies as $copy): ?>
            <div class="receipt-box">
                <div class="copy-tag"><?php echo $copy; ?></div>
                <div class="text-center mb-3">
                    <img src="../uploads/Logo Web.jpg" class="logo-img"><br>
                    <strong style="font-size: 14px;">AI FUTURE LEADERS ACADEMY</strong>
                </div>
                <div class="row gx-1">
                    <div class="col-6"><span class="label">Receipt</span><span class="value">#<?php echo $r['receipt_number']; ?></span></div>
                    <div class="col-6"><span class="label">ID</span><span class="value">STU-<?php echo $r['sid']; ?></span></div>
                </div>
                <span class="label">Student Name</span><span class="value"><?php echo strtoupper($r['student_name']); ?></span>
                <span class="label">Father Name</span><span class="value"><?php echo strtoupper($r['father_name']); ?></span>
                <span class="label">Class</span><span class="value"><?php echo $r['class_name']; ?></span>
                <span class="label">Date</span><span class="value"><?php echo date('d-M-Y', strtotime($r['payment_date'])); ?></span>
                <div class="mt-auto">
                    <div class="amount-box"><?php echo number_format($r['amount']); ?> PKR</div>
                    <div class="text-center mt-3" style="border-top: 1px solid #000; font-size: 10px;">Signature</div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </body>
    </html>
    <?php exit;
}

// --- PART 2: LEDGER VIEW ---
if (!$student_id) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Error: Student ID is missing. Please select a student from the Fees page.</div><a href='fees.php' class='btn btn-dark'>Go Back</a></div>";
    exit;
}

include '../includes/header.php';
?>
<style>
    .stylish-header-bar { background: #fff; border-radius: 20px; padding: 20px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
    .black-title { color: #000; font-weight: 800; font-size: 2rem; }
</style>
<div class="container py-5">
    <div class="stylish-header-bar">
        <h2 class="black-title">Payment Ledger</h2>
        <a href="fees.php" class="btn btn-outline-dark rounded-pill">← Back</a>
    </div>
    <div class="card shadow border-0 rounded-4 overflow-hidden">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th class="ps-4">Receipt #</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->prepare("SELECT * FROM fees WHERE student_id = ? ORDER BY id DESC");
                $stmt->execute([$student_id]);
                while ($row = $stmt->fetch()) {
                    echo "<tr>
                        <td class='ps-4 fw-bold'>#{$row['receipt_number']}</td>
                        <td>" . date('d M, Y', strtotime($row['payment_date'])) . "</td>
                        <td class='fw-bold'>{$row['amount']} PKR</td>
                        <td class='text-center'>
                            <a href='receipts.php?print={$row['id']}' target='_blank' class='btn btn-sm btn-info text-white rounded-pill px-3'>Print Slip</a>
                        </td>
                    </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
