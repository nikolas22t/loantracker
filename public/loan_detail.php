<?php
require_once 'config.php'; require_login();
require_once 'layout.php';
$db = get_db();
$id = (int)($_GET['id']??0);

$s = $db->prepare("SELECT * FROM loans WHERE id=? AND user_id=?");
$s->execute([$id, current_user_id()]);
$loan = $s->fetch(PDO::FETCH_ASSOC);
if (!$loan) { flash('Loan not found.','error'); header('Location: dashboard.php'); exit; }

$pmts = $db->prepare("SELECT * FROM payments WHERE loan_id=? ORDER BY payment_date DESC");
$pmts->execute([$id]);
$payments = $pmts->fetchAll(PDO::FETCH_ASSOC);

$hist = $db->prepare("SELECT lh.*, u.username FROM loan_history lh LEFT JOIN users u ON u.id=lh.changed_by WHERE lh.loan_id=? ORDER BY lh.changed_at DESC");
$hist->execute([$id]);
$history = $hist->fetchAll(PDO::FETCH_ASSOC);

$bal = calculate_balance($loan, array_reverse($payments));
render_head($loan['name']);
?>
<div class="flex-between mt-2" style="margin-bottom:1.5rem">
  <h1 style="font-size:1.4rem"><?= htmlspecialchars($loan['name']) ?></h1>
  <div class="flex">
    <a href="loan_form.php?id=<?= $id ?>" class="btn btn-warning">✏️ Edit</a>
    <a href="payment_form.php?loan_id=<?= $id ?>" class="btn btn-success">+ Payment</a>
    <a href="dashboard.php" class="btn btn-ghost">← Back</a>
  </div>
</div>

<div class="grid-4">
  <div class="stat-card"><div class="label">Principal</div><div class="value"><?= number_format($loan['principal'],2) ?></div></div>
  <div class="stat-card"><div class="label">Accrued Interest</div><div class="value <?= $bal['accrued']>0?'negative':'' ?>"><?= number_format($bal['accrued'],2) ?></div></div>
  <div class="stat-card"><div class="label">Total Paid</div><div class="value positive"><?= number_format($bal['paid'],2) ?></div></div>
  <div class="stat-card"><div class="label">Current Balance</div><div class="value <?= $bal['balance']>0?'negative':'positive' ?>"><?= number_format($bal['balance'],2) ?></div><div class="sub"><?= $bal['balance']<=0 ? '✅ Fully paid' : 'Outstanding' ?></div></div>
</div>

<div class="grid-2 mt-3">
<div class="card">
  <h2>Loan Details</h2>
  <table>
    <tr><th>Borrower</th><td><?= htmlspecialchars($loan['borrower']) ?></td></tr>
    <tr><th>Start Date</th><td><?= $loan['start_date'] ?></td></tr>
    <tr><th>Interest Rate</th><td><?= $loan['interest_rate']>0 ? $loan['interest_rate'].'% / year' : '<span class="badge badge-green">0% — Interest Free</span>' ?></td></tr>
    <tr><th>Compounding</th><td><?= $loan['interest_rate']>0 ? 'Every '.$loan['compound_months'].' month(s)' : '—' ?></td></tr>
    <tr><th>Status</th><td><span class="badge badge-<?= $loan['status']==='active'?'green':($loan['status']==='closed'?'blue':'yellow') ?>"><?= ucfirst($loan['status']) ?></span></td></tr>
    <tr><th>Notes</th><td><?= nl2br(htmlspecialchars($loan['notes']??'—')) ?></td></tr>
  </table>
</div>

<div class="card">
  <div class="flex-between"><h2>Payments</h2><a href="payment_form.php?loan_id=<?= $id ?>" class="btn btn-sm btn-success">+ Add</a></div>
  <?php if(empty($payments)): ?>
  <p class="text-muted" style="padding:.5rem 0">No payments recorded yet.</p>
  <?php else: ?>
  <table>
    <thead><tr><th>Date</th><th>Amount</th><th>Notes</th><th></th></tr></thead>
    <tbody>
    <?php foreach($payments as $p): ?>
    <tr>
      <td><?= $p['payment_date'] ?></td>
      <td class="positive"><?= number_format($p['amount'],2) ?></td>
      <td class="text-muted"><?= htmlspecialchars($p['notes']??'') ?></td>
      <td><a href="delete_payment.php?id=<?= $p['id'] ?>&loan_id=<?= $id ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this payment?')">✕</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
</div>

<div class="card mt-3">
  <h2>📋 Full History</h2>
  <?php if(empty($history)): ?>
  <p class="text-muted">No history yet.</p>
  <?php else: ?>
  <table>
    <thead><tr><th>Date/Time</th><th>Action</th><th>Description</th><th>By</th></tr></thead>
    <tbody>
    <?php foreach($history as $h): ?>
    <tr>
      <td class="text-muted"><?= $h['changed_at'] ?></td>
      <td><span class="badge badge-<?= $h['action']==='created'?'green':($h['action']==='payment'?'blue':'yellow') ?>"><?= ucfirst($h['action']) ?></span></td>
      <td><?= htmlspecialchars($h['description']) ?></td>
      <td><?= htmlspecialchars($h['username']??'—') ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
<?php render_foot(); ?>
