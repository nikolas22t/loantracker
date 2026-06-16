<?php
require_once 'config.php'; require_login();
require_once 'layout.php';
$db = get_db();
$s  = $db->prepare("SELECT p.*, l.name as loan_name, l.borrower FROM payments p JOIN loans l ON l.id=p.loan_id WHERE l.user_id=? ORDER BY p.payment_date DESC");
$s->execute([current_user_id()]);
$payments = $s->fetchAll(PDO::FETCH_ASSOC);

render_head('All Payments');
?>
<div class="flex-between mt-2" style="margin-bottom:1.5rem">
  <h1 style="font-size:1.4rem">All Payments</h1>
</div>
<div class="card">
<table>
  <thead><tr><th>Date</th><th>Loan</th><th>Borrower</th><th>Amount</th><th>Notes</th></tr></thead>
  <tbody>
  <?php foreach($payments as $p): ?>
  <tr>
    <td><?= $p['payment_date'] ?></td>
    <td><a href="loan_detail.php?id=<?= $p['loan_id'] ?>"><?= htmlspecialchars($p['loan_name']) ?></a></td>
    <td><?= htmlspecialchars($p['borrower']) ?></td>
    <td class="positive"><?= number_format($p['amount'],2) ?></td>
    <td class="text-muted"><?= htmlspecialchars($p['notes']??'') ?></td>
  </tr>
  <?php endforeach; ?>
  <?php if(empty($payments)): ?>
  <tr><td colspan="5" class="text-muted" style="text-align:center;padding:2rem">No payments recorded yet.</td></tr>
  <?php endif; ?>
  </tbody>
</table>
</div>
<?php render_foot(); ?>
