<?php
require_once 'config.php'; require_login();
require_once 'layout.php';
$db      = get_db();
$loan_id = (int)($_GET['loan_id']??$_POST['loan_id']??0);

$s = $db->prepare("SELECT * FROM loans WHERE id=? AND user_id=?");
$s->execute([$loan_id, current_user_id()]);
$loan = $s->fetch(PDO::FETCH_ASSOC);
if (!$loan) { flash('Loan not found.','error'); header('Location: dashboard.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (float)$_POST['amount'];
    $date   = $_POST['payment_date'];
    $notes  = trim($_POST['notes']);
    $db->prepare("INSERT INTO payments (loan_id,amount,payment_date,notes) VALUES (?,?,?,?)")
       ->execute([$loan_id, $amount, $date, $notes]);
    log_history($db, $loan_id, 'payment', 'Payment of '.$amount.' on '.$date.($notes?' — '.$notes:''));
    flash('Payment recorded successfully.');
    header('Location: loan_detail.php?id='.$loan_id); exit;
}

render_head('Add Payment');
?>
<div class="flex-between mt-2" style="margin-bottom:1.5rem">
  <h1 style="font-size:1.4rem">Add Payment — <?= htmlspecialchars($loan['name']) ?></h1>
  <a href="loan_detail.php?id=<?= $loan_id ?>" class="btn btn-ghost">← Back</a>
</div>
<div class="card" style="max-width:500px">
<form method="POST">
  <input type="hidden" name="loan_id" value="<?= $loan_id ?>">
  <div class="form-group"><label>Payment Amount (€)</label><input type="number" step="0.01" min="0.01" name="amount" required autofocus placeholder="0.00"></div>
  <div class="form-group"><label>Payment Date</label><input type="date" name="payment_date" required value="<?= date('Y-m-d') ?>"></div>
  <div class="form-group"><label>Notes (optional)</label><textarea name="notes" rows="2" placeholder="e.g. Monthly installment"></textarea></div>
  <div class="flex" style="justify-content:flex-end;gap:.75rem">
    <a href="loan_detail.php?id=<?= $loan_id ?>" class="btn btn-ghost">Cancel</a>
    <button type="submit" class="btn btn-success">Record Payment</button>
  </div>
</form>
</div>
<?php render_foot(); ?>
