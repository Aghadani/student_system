<?php
require_once '../config.php';
require_once '../includes/auth_check.php';

// 1. SAFE VARIABLE CAPTURE (Prevents the Line 14 Warning)
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : null;
$receipt_id = isset($_GET['print']) ? $_GET['print'] : null;

// --- MODE 1: PRINTING A SPECIFIC RECEIPT ---
if ($receipt_id) {
    // 2. DETAILED SQL QUERY
    // Ensure 'father_name' exists in your 'students' table
    $stmt = $pdo->prepare("
        SELECT f.*, 
               s.id as sid, 
               s.name as student_name, 
               s.father_name as f_name, 
               c.class_name 
        FROM fees f 
        LEFT JOIN students s ON f.student_id = s.id 
        LEFT JOIN classes c ON s.class_id = c.id 
        WHERE f.id = ?
    ");
    $stmt->execute([$receipt_id]);
    $r = $stmt->fetch();

    if (!$r) {
        die("Receipt ID #$receipt_id not found.");
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
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #fff; color: #000; }
            @page { size: A4 landscape; margin: 5mm; }
            .no-print-nav { background: #eee; padding: 10px; text-align: center; border-bottom: 1px solid #ccc; }
            .receipt-wrapper { display: flex; gap: 15px; padding: 10px; }
            .receipt-box { 
                flex: 1; border: 2px solid #000; padding: 15px; border-radius: 8px;
                display: flex; flex-direction: column; min-height: 175mm;
            }
            .header { text-align: center; border-bottom: 2px solid #000; margin-bottom: 15px; }
            .school-title { font-weight: 900; font-size: 16px; margin: 0; text-transform: uppercase; }
            
            /* Grid Layout for details */
            .detail-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
            .detail-col { flex: 1; border-bottom: 1px solid #ddd; padding-bottom: 2px; }
            
            .label { font-size: 10px; font-weight: bold; color: #444; text-transform: uppercase; display: block; }
            .value { font-size: 13px; font-weight: 600; }
            
            .amount-box { 
                margin-top: 20px; background: #f9f9f9; border: 2px solid #000; 
                padding: 10px; text-align: center; border-radius: 5px; 
            }
            .footer { margin-top: auto; display: flex; justify-content: space-between; padding-top: 30px; }
            .sign-line { border-top: 1px solid #000; width: 120px; text-align: center; font-size: 11px; font-weight: bold; }
            @media print { .no-print-nav { display: none; } }
        </style>
    </head>
    <body onload="window.print()">
        <div class="no-print-nav">
            <button onclick="window.print()" class="btn btn-dark btn-sm">PRINT RECEIPT</button>
            <a href="receipts.php?student_id=<?php echo $r['student_id']; ?>" class="btn btn-secondary btn-sm">BACK TO LEDGER</a>
        </div>

        <div class="receipt-wrapper">
            <?php foreach ($copies as $copy): ?>
            <div class="receipt-box">
                <div class="header">
                    <img src="../uploads/Logo Web.png" style="width:50px; border-radius:50%;">
                    <h1 class="school-title">AI FUTURE LEADERS ACADEMY</h1>
                    <div style="font-size:10px; font-weight:bold;"><?php echo $copy; ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-col">
                        <span class="label">Receipt No</span>
                        <span class="value">#<?php echo $r['receipt_number']; ?></span>
                    </div>
                    <div class="detail-col text-end">
                        <span class="label">Student ID</span>
                        <span class="value">STU-<?php echo $r['sid']; ?></span>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-col">
                        <span class="label">Student Name</span>
                        <span class="value"><?php echo strtoupper($r['student_name']); ?></span>
                    </div>
                    <div class="detail-col text-end">
                        <span class="label">Father Name</span>
                        <span class="value"><?php echo strtoupper($r['f_name'] ?? 'N/A'); ?></span>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-col">
                        <span class="label">Class / Grade</span>
                        <span class="value"><?php echo $r['class_name']; ?></span>
                    </div>
                    <div class="detail-col text-end">
                        <span class="label">Payment Date</span>
                        <span class="value"><?php echo date('d-m-Y', strtotime($r['payment_date'])); ?></span>
                    </div>
                </div>

                <div class="mt-3">
                    <span class="label">Fee Category</span>
                    <span class="value"><?php echo ($r['amount'] == 800) ? "One-Time Admission Fee" : "Monthly Tuition Fee"; ?></span>
                </div>

                <div class="amount-box">
                    <span class="label">Net Amount Paid</span>
                    <div style="font-size: 24px; font-weight: 900;"><?php echo number_format($r['amount']); ?> PKR</div>
                </div>

                <div class="footer">
                    <div style="font-size: 9px;">System Generated</div>
                    <div class="sign-line">Authorized Sign</div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// --- MODE 2: LEDGER VIEW ---
if ($student_id) {
    include '../includes/header.php';
    ?>
    <div class="container py-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0 fw-bold">Fee History</h4>
                <a href="fees.php" class="btn btn-outline-dark btn-sm">Back</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Receipt #</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->prepare("SELECT * FROM fees WHERE student_id = ? ORDER BY id DESC");
                        $stmt->execute([$student_id]);
                        while ($row = $stmt->fetch()) {
                            echo "<tr>
                                <td class='fw-bold'>#{$row['receipt_number']}</td>
                                <td>" . number_format($row['amount']) . " PKR</td>
                                <td>" . date('d M, Y', strtotime($row['payment_date'])) . "</td>
                                <td class='text-center'>
                                    <a href='receipts.php?print={$row['id']}' target='_blank' class='btn btn-primary btn-sm rounded-pill'>Print</a>
                                </td>
                            </tr>";
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
    die("<div style='text-align:center; padding:100px;'><h3>Please select a student first.</h3><a href='fees.php'>Go Back</a></div>");
}
