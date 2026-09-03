<?php
session_start();
require 'includes/db.php';
require 'email.php'; // ✅ Correct path to Email Helper

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: admin-login.php");
    exit;
}

$success = "";
$error = "";

// Handle Approve/Reject
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $req_id = intval($_POST['req_id']);
    $action = $_POST['action']; // 'approve' or 'reject'
    $note = trim($_POST['admin_note']);
    
    try {
        $pdo->beginTransaction();
        
        // Get request details
        $stmt = $pdo->prepare("SELECT * FROM withdrawal_requests WHERE id = ? AND status = 'pending'");
        $stmt->execute([$req_id]);
        $req = $stmt->fetch();
        
        if ($req) {
            // ✅ Get vendor email and details for notification BEFORE processing
            $stmt_vendor = $pdo->prepare("SELECT email, owner_name, shop_name FROM vendors WHERE id = ?");
            $stmt_vendor->execute([$req['vendor_id']]);
            $vendor = $stmt_vendor->fetch();
            
            if ($action == 'approve') {
                // Already deducted from vendor balance when requested, so just update status
                $stmt = $pdo->prepare("UPDATE withdrawal_requests SET status = 'approved', admin_note = ?, processed_at = NOW() WHERE id = ?");
                $stmt->execute([$note, $req_id]);
                $success = "Withdrawal approved successfully.";
                
                // ✅ Email Vendor: Approved
                if ($vendor) {
                    $emailHtml = "
                        <h2>Withdrawal Approved! ✅</h2>
                        <p>Hi " . htmlspecialchars($vendor['owner_name']) . ",</p>
                        <p>Great news! Your withdrawal request of <strong>₦" . number_format($req['amount']) . "</strong> for <strong>" . htmlspecialchars($vendor['shop_name']) . "</strong> has been approved and processed.</p>
                        <p>The funds should reflect in your bank account shortly.</p>
                    ";
                    sendCampusEmail($vendor['email'], $vendor['owner_name'], "Withdrawal Approved ✅", $emailHtml);
                }
            } else {
                // Reject: Refund the amount back to the vendor's balance
                $stmt = $pdo->prepare("UPDATE vendors SET wallet_balance = wallet_balance + ? WHERE id = ?");
                $stmt->execute([$req['amount'], $req['vendor_id']]);
                
                $stmt = $pdo->prepare("UPDATE withdrawal_requests SET status = 'rejected', admin_note = ?, processed_at = NOW() WHERE id = ?");
                $stmt->execute([$note, $req_id]);
                $success = "Withdrawal rejected and funds refunded to vendor.";
                
                // ✅ Email Vendor: Rejected
                if ($vendor) {
                    $emailHtml = "
                        <h2>Withdrawal Request Update ⚠️</h2>
                        <p>Hi " . htmlspecialchars($vendor['owner_name']) . ",</p>
                        <p>Your withdrawal request of <strong>₦" . number_format($req['amount']) . "</strong> was unfortunately rejected.</p>
                        " . (!empty($note) ? "<p><strong>Reason:</strong> " . htmlspecialchars($note) . "</p>" : "") . "
                        <p>The funds have been refunded to your Campus Delivery wallet balance.</p>
                    ";
                    sendCampusEmail($vendor['email'], $vendor['owner_name'], "Withdrawal Request Update ⚠️", $emailHtml);
                }
            }
        }
        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error processing request: " . $e->getMessage();
    }
}

// Get all requests
$requests = $pdo->query("
    SELECT w.*, v.shop_name, v.account_name, v.account_number, v.bank_name 
    FROM withdrawal_requests w 
    JOIN vendors v ON w.vendor_id = v.id 
    ORDER BY w.requested_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdrawal Requests - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .sidebar { min-height: 100vh; background: linear-gradient(180deg, #dc3545 0%, #c82333 100%); }
        .sidebar a { color: white; text-decoration: none; padding: 12px 20px; display: block; border-left: 4px solid transparent; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.1); border-left-color: white; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include 'admin-sidebar.php'; ?>
        
        <div class="col-md-10 p-4 bg-light">
            <h2 class="mb-4"><i class="bi bi-cash-stack text-success"></i> Withdrawal Requests</h2>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <?php if (empty($requests)): ?>
                        <p class="text-muted text-center py-5">No withdrawal requests.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Vendor</th>
                                        <th>Bank Details</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($requests as $req): ?>
                                        <tr>
                                            <td><?= date('M d, Y', strtotime($req['requested_at'])) ?></td>
                                            <td><strong><?= htmlspecialchars($req['shop_name']) ?></strong></td>
                                            <td>
                                                <?= htmlspecialchars($req['bank_name']) ?><br>
                                                <small class="text-muted"><?= htmlspecialchars($req['account_number']) ?> (<?= htmlspecialchars($req['account_name']) ?>)</small>
                                            </td>
                                            <td><strong class="text-success">₦<?= number_format($req['amount']) ?></strong></td>
                                            <td>
                                                <span class="badge bg-<?= $req['status'] == 'pending' ? 'warning' : ($req['status'] == 'approved' ? 'success' : 'danger') ?>">
                                                    <?= ucfirst($req['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($req['status'] == 'pending'): ?>
                                                    <button class="btn btn-sm btn-success me-1" data-bs-toggle="modal" data-bs-target="#actionModal" onclick="setAction(<?= $req['id'] ?>, 'approve')">Approve</button>
                                                    <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#actionModal" onclick="setAction(<?= $req['id'] ?>, 'reject')">Reject</button>
                                                <?php else: ?>
                                                    <small class="text-muted"><?= htmlspecialchars($req['admin_note'] ?: 'No note') ?></small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Action Modal -->
<div class="modal fade" id="actionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Process Withdrawal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="req_id" id="req_id">
                    <input type="hidden" name="action" id="action">
                    <div class="mb-3">
                        <label class="form-label">Admin Note (Optional)</label>
                        <textarea name="admin_note" class="form-control" rows="3" placeholder="e.g., Processed via bank transfer or Invalid account number"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn" id="submitBtn">Confirm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function setAction(id, action) {
    document.getElementById('req_id').value = id;
    document.getElementById('action').value = action;
    document.getElementById('modalTitle').textContent = action === 'approve' ? 'Approve Withdrawal' : 'Reject Withdrawal';
    document.getElementById('submitBtn').className = action === 'approve' ? 'btn btn-success' : 'btn btn-danger';
    document.getElementById('submitBtn').textContent = action === 'approve' ? 'Approve' : 'Reject';
}
</script>
</body>
</html>