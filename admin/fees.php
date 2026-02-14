<?php
require_once '../config.php';
require_once '../includes/auth_check.php';

$message = '';

if (isset($_POST['pay_fee'])) {
    $student_id = $_POST['student_id'];
    $fee_type = $_POST['fee_type'];
    $amount = ($fee_type == 'Admission') ? 800 : 3000;
    $receipt_number = 'REC-' . time() . rand(10, 99);
    $payment_date = date('Y-m-d');

    $stmt = $pdo->prepare("INSERT INTO fees (student_id, fee_type, amount, status, payment_date, receipt_number) VALUES (?, ?, ?, 'Paid', ?, ?)");
    $stmt->execute([$student_id, $fee_type, $amount, $payment_date, $receipt_number]);
    $message = "Fee payment recorded successfully!";
}

include '../includes/header.php';
?>

<style>
    .stylish-header-bar {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        padding: 20px 35px;
        margin-bottom: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }
    .black-title { color: #000; font-weight: 800; font-size: 2.2rem; letter-spacing: -1.8px; margin: 0; }
    .main-card { background: #fff; border-radius: 28px; overflow: hidden; box-shadow: 0 15px 40px rgba(0,0,0,0.4); border: none; }
    .table thead th { background: #000; color: #fff; padding: 18px; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px; }
    .badge-paid { background: #e3fcef; color: #00a854; border: 1px solid #b7eb8f; padding: 5px 12px; border-radius: 50px; }
    .badge-unpaid { background: #fff1f0; color: #f5222d; border: 1px solid #ffa39e; padding: 5px 12px; border-radius: 50px; }
</style>

<div class="container py-4">
    <?php if ($message): ?>
        <div class="alert alert-success border-0 shadow-sm rounded-4"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="stylish-header-bar">
        <div>
            <h2 class="black-title">Fees Management</h2>
            <p class="text-muted small mb-0">Track admissions and monthly tuition status</p>
        </div>
    </div>

    <div class="main-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Student Name</th>
                        <th>Class</th>
                        <th>Admission</th>
                        <th>Monthly Fee</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody style="color:#000; font-weight: 600;">
                    <?php
                    $students = $pdo->query("
                        SELECT s.id, s.name, c.class_name,
                        (SELECT status FROM fees WHERE student_id = s.id AND fee_type = 'Admission' LIMIT 1) as admission_status,
                        (SELECT status FROM fees WHERE student_id = s.id AND fee_type = 'Monthly' ORDER BY id DESC LIMIT 1) as monthly_status
                        FROM students s 
                        LEFT JOIN classes c ON s.class_id = c.id
                    ")->fetchAll();

                    foreach ($students as $s):
                    ?>
                    <tr>
                        <td class="ps-4"><?php echo htmlspecialchars($s['name']); ?></td>
                        <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($s['class_name']); ?></span></td>
                        <td><?php echo ($s['admission_status'] == 'Paid') ? '<span class="badge-paid">Paid</span>' : '<span class="badge-unpaid">Unpaid</span>'; ?></td>
                        <td><?php echo ($s['monthly_status'] == 'Paid') ? '<span class="badge-paid">Paid</span>' : '<span class="badge-unpaid">Unpaid</span>'; ?></td>
                        <td class="text-center">
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-dark dropdown-toggle rounded-pill px-3" data-bs-toggle="dropdown">Pay Fee</button>
                                <ul class="dropdown-menu shadow border-0">
                                    <li>
                                        <form method="POST">
                                            <input type="hidden" name="student_id" value="<?php echo $s['id']; ?>">
                                            <input type="hidden" name="fee_type" value="Admission">
                                            <button type="submit" name="pay_fee" class="dropdown-item" <?php echo $s['admission_status'] == 'Paid' ? 'disabled' : ''; ?>>Admission (800)</button>
                                        </form>
                                    </li>
                                    <li>
                                        <form method="POST">
                                            <input type="hidden" name="student_id" value="<?php echo $s['id']; ?>">
                                            <input type="hidden" name="fee_type" value="Monthly">
                                            <button type="submit" name="pay_fee" class="dropdown-item">Monthly (3000)</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                            <a href="receipts.php?student_id=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 ms-2">History</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
