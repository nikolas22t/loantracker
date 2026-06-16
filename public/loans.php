<?php
require_once 'config.php'; require_login();
require_once 'layout.php';
$db     = get_db();
$filter = $_GET['status'] ?? 'all';
$sql    = "SELECT * FROM loans WHERE user_id=?".($filter!=='all'?" AND status='$filter'":'')." ORDER BY created_at DESC";
$s      = $db->prepare($sql);
$s->execute([current_user_id()]);
$loans  = $s->fetchAll(PDO::FETCH_ASSOC);

render_head('All Loans');
?>
<div class="flex-between mt-2" style="margin-bottom:1.5rem">
  <h1 style="font-size:1.4rem">All Loans</h1>
  <div class="flex">
    <div class="flex" style="gap:.4rem">
      <?php foreach(['all','active','paused','closed'] as $st): ?>
      <a href="?status=<?= $st ?>" class="btn btn-sm <?= $filter===$st?'btn-primary':'btn-ghost' ?>"><?= ucfirst($st) ?></a>
      <?php endforeach; ?>
    </div>
    <a href="loan_form.php" class="btn btn-primary">+ New Loan</a>
  </div>
</div>
<div class="card">
<table>
  <thead><tr><th>Name</th><th>Borrower</th><th>Principal</th><th>Rate</th><th>Start Date</th><th>Balance</th><th>Status</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach($loans as $loan):
    $pmts = $db->prepare("SELECT * FROM payments WHERE loan_id=?");
    $pmts->execute([$loan['id']]);
    $bal = calculate_balance($loan, $pmts->fetchAll(PDO::FETCH_ASSOC));
  ?>
  <tr>
    <td><a href="loan_detail.php?id=<?= $loan['id'] ?>"><?= htmlspecialchars($loan['name']) ?></a></td>
    <td><?= htmlspecialchars($loan['borrower']) ?></td>
    <td><?= number_format($loan['principal'],2) ?></td>
    <td><?= $loan['interest_rate']>0 ? $loan['interest_rate'].'%/yr' : '0%' ?></td>
    <td><?= $loan['start_date'] ?></td>
    <td class="<?= $bal['balance']>0?'negative':'positive' ?>"><?= number_format($bal['balance'],2) ?></td>
    <td><span class="badge badge-<?= $loan['status']==='active'?'green':($loan['status']==='closed'?'blue':'yellow') ?>"><?= ucfirst($loan['status']) ?></span></td>
    <td class="flex">
      <a href="loan_detail.php?id=<?= $loan['id'] ?>" class="btn btn-sm btn-ghost">View</a>
      <a href="loan_form.php?id=<?= $loan['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
    </td>
  </tr>
  <?php endforeach; ?>
  <?php if(empty($loans)): ?>
  <tr><td colspan="8" class="text-muted" style="text-align:center;padding:2rem">No loans found.</td></tr>
  <?php endif; ?>
  </tbody>
</table>
</div>
<?php render_foot(); ?>
