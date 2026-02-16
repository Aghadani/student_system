<?php
require_once '../config.php';
require_once '../includes/auth_check.php';

// 1. Capture variables from URL safely (The ?? null prevents the Warning)
// This was line 14 - now fixed to check if key exists first
$student_id = $_GET['student_id'] ?? null;
$receipt_id = $_GET['print'] ?? null;

// --- MODE 1: PRINTING A SPECIFIC RECEIPT ---
if ($receipt_id) {
    // SQL query to fetch all details including Father Name and Class
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
        die("<div style='text-align:center; padding:50px;'><h1>Receipt Not Found</h1><a href='fees.php'>Back</a></div>");
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
            @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap');
            body { font-family: 'Inter', sans-serif; background: #fff; color: #000; font-size: 12px; }
            @page { size: A4 landscape; margin: 5mm; }
            
            .no-print-nav { background: #343a40; padding: 10px; text-align: center; color: white; margin-bottom: 20px; }
            .receipt-wrapper { display: flex; flex-direction: row; justify-content: space-between; gap: 15px; padding: 10px; }
            
            .receipt-box { 
                flex: 1; 
                border: 2px solid #000; 
                padding: 20px; 
                position: relative; 
                min-height: 170mm; 
                display: flex; 
                flex-direction: column;
                border-radius: 10px;
            }

            .copy-tag { 
                background: #000; color: #fff; font-size: 10px; 
                padding: 3px 10px; position: absolute; top: 0; right: 20px; 
                font-weight: bold; border-radius: 0 0 5px 5px;
            }

            .header-section { text-align: center; border-bottom: 2px solid #000; margin-bottom: 15px; padding-bottom: 10px; }
            .logo-img { width: 50px; height: 50px; border-radius: 50%; margin-bottom: 5px; }
            .school-name { font-size: 16px; font-weight: 800; margin: 0; text-transform: uppercase; letter-spacing: 1px; }
            
            /* Two columns per row layout */
            .info-row { display: flex; justify-content: space-between; margin-bottom: 12px; border-bottom: 1px solid #eee; padding-bottom: 4px; }
            .info-item { flex: 1; }
            
            .label { font-size: 9px; color: #555; text-transform: uppercase; font-weight: 700; display: block; }
            .value { font-size: 13px; font-weight: 600; color: #000; }

            .amount-section { 
                background: #f8f9fa; 
                border: 2px solid #000; 
                padding: 15px; 
                text-align: center; 
                margin-top: 20px;
                border-radius: 8px;
            }
            .amount-text { font-size: 20px; font-weight: 800; }

            .footer-sign { 
                margin-top: auto; 
                display: flex; 
                justify-content: space-between; 
                align-items: flex-end;
                padding-top: 20px;
            }
            .sign-box { border-top: 1.5px solid #000; width: 120px; text-align: center; font-weight: 700; font-size: 10px; padding-top: 5px; }

            @media print { .no-print-nav { display: none; } body { padding: 0; } }
        </style>
    </head>
    <body onload="window.print()">
        <div class="no-print-nav">
            <button onclick="window.print()" class="btn btn-light btn-sm fw-bold">Click to Print Receipt</button>
            <a href="receipts.php?student_id=<?php echo $r['student_id']; ?>" class="btn btn-outline-light btn-sm ms-3">Back to Ledger</a>
        </div>

        <div class="receipt-wrapper">
            <?php foreach ($copies as $copy): ?>
            <div class="receipt-box">
                <div class="copy-tag"><?php echo $copy; ?></div>
                
                <div class="header-section">
                    <img src="../uploads/Logo Web.png" class="logo-img">
                    <h1 class="school-name">AI Future Leaders Academy</h1>
                    <small>Education for a Better Tomorrow</small>
                </div>
                
                <div class="info-row">
                    <div class="info-item">
                        <span class="label">Receipt Number</span>
                        <span class="value">#<?php echo $r['receipt_number']; ?></span>
                    </div>
                    <div class="info-item text-end">
                        <span class="label">Student ID</span>
                        <span class="value">STU-<?php echo $r['sid']; ?></span>
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-item">
                        <span class="label">Student Name</span>
                        <span class="value text-uppercase"><?php echo htmlspecialchars($r['student_name']); ?></span>
                    </div>
                    <div class="info-item text-end">
                        <span class="label">Father's Name</span>
                        <span class="value text-uppercase"><?php echo htmlspecialchars($r['father_name'] ?? 'N/A'); ?></span>
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-item">
                        <span class="label">Class / Grade</span>
                        <span class="value"><?php echo htmlspecialchars($r['class_name']); ?></span>
                    </div>
                    <div class="info-item text-end">
                        <span class="label">Payment Date</span>
                        <span class="value"><?php echo date('d-M-Y', strtotime($r['payment_date'])); ?></span>
                    </div>
                </div>

                <div class="info-row" style="border: none;">
                    <div class="info-item">
                        <span class="label">Fee Category</span>
                        <span class="value"><?php echo ($r['amount'] == 800) ? "Admission Fee (One-Time)" : "Monthly Tuition Fee"; ?></span>
                    </div>
                </div>

                <div class="amount-section">
                    <span class="label">Total Amount Paid</span>
                    <div class="amount-text"><?php echo number_format($r['amount']); ?> PKR</div>
                </div>

                <div class="footer-sign">
                    <div style="font-size: 9px; color: #777;">Generated on: <?php echo date('d/m/Y H:i'); ?></div>
                    <div class="sign-box">Cashier Signature</div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// --- MODE 2: VIEWING THE LEDGER ---
if ($student_id) {
    include '../includes/header.php';
    ?>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold">Student Payment History</h2>
            <a href="fees.php" class="btn btn-secondary rounded-pill px-4">Back to List</a>
        </div>

        <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-4">Receipt #</th>
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

                    if ($rows) {
                        foreach ($rows as $row) {
                            $type = ($row['amount'] == 800) ? 'Admission' : 'Monthly';
                            echo "<tr>
                                <td class='ps-4 fw-bold'>#{$row['receipt_number']}</td>
                                <td><span class='badge bg-light text-dark border'>$type</span></td>
                                <td class='fw-bold'>" . number_format($row['amount']) . " PKR</td>
                                <td>" . date('d M, Y', strtotime($row['payment_date'])) . "</td>
                                <td class='text-center'>
                                    <a href='receipts.php?print={$row['id']}' target='_blank' class='btn btn-primary btn-sm rounded-pill px-3'>Print Receipt</a>
                                </td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' class='text-center py-4'>No payment history found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    include '../includes/footer.php';
} else {
    die("<div style='text-align:center; margin-top:100px;'><h1>Invalid Access</h1><p>Please select a student from Fees Management.</p></div>");
}
