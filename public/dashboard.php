<?php
require_once 'config.php'; require_login();
require_once 'layout.php';
$db = get_db();

// Borrower filter
$filter_borrower = trim($_GET['borrower'] ?? '');

// Fetch all borrowers for filter dropdown
$all_borrowers = $db->prepare("SELECT name FROM borrowers WHERE user_id = ? ORDER BY name");
$all_borrowers->execute([current_user_id()]);
$all_borrowers = $all_borrowers->fetchAll(PDO::FETCH_COLUMN);

if ($filter_borrower !== '') {
    $loans = $db->prepare("SELECT * FROM loans WHERE user_id = ? AND borrower LIKE ? ORDER BY start_date DESC");
    $loans->execute([current_user_id(), '%' . $filter_borrower . '%']);
} else {
    $loans = $db->prepare("SELECT * FROM loans WHERE user_id = ? ORDER BY start_date DESC");
    $loans->execute([current_user_id()]);
}
$loans = $loans->fetchAll(PDO::FETCH_ASSOC);

$total_principal = 0; $total_balance = 0; $total_paid = 0;
$summaries = [];
foreach ($loans as $loan) {
    $pmts = $db->prepare("SELECT * FROM payments WHERE loan_id = ? ORDER BY payment_date");
    $pmts->execute([$loan['id']]);
    $pmts = $pmts->fetchAll(PDO::FETCH_ASSOC);
    $bal  = calculate_balance($loan, $pmts);
    $total_principal += $loan['principal'];
    $total_balance   += $bal['balance'];
    $total_paid      += $bal['paid'];
    $summaries[]      = array_merge($loan, $bal);
}

render_head('Dashboard');
?>
<div class="flex-between mt-2" style="margin-bottom:1rem">
  <h1 style="font-size:1.4rem">Dashboard</h1>
  <a href="loan_form.php" class="btn btn-primary">+ New Loan</a>
</div>
<form method="GET" style="margin-bottom:1.5rem;display:flex;align-items:center;gap:.75rem;flex-wrap:wrap">
  <label style="color:var(--muted);font-size:.85rem;margin:0">Filter by Borrower:</label>
  <select name="borrower" onchange="this.form.submit()" style="width:auto;min-width:160px;padding:.4rem .8rem">
    <option value="">All Borrowers</option>
    <?php foreach($all_borrowers as $bn): ?>
    <option value="<?= htmlspecialchars($bn) ?>" <?= $filter_borrower===$bn?'selected':'' ?>><?= htmlspecialchars($bn) ?></option>
    <?php endforeach; ?>
  </select>
  <?php if($filter_borrower !== ''): ?>
  <a href="dashboard.php" class="btn btn-ghost btn-sm">✕ Clear</a>
  <?php endif; ?>
</form>
<div class="grid-4">
  <div class="stat-card"><div class="label">Total Loans</div><div class="value"><?= count($loans) ?></div></div>
  <div class="stat-card"><div class="label">Total Principal</div><div class="value"><?= number_format($total_principal,2) ?></div></div>
  <div class="stat-card"><div class="label">Outstanding</div><div class="value <?= $total_balance>0?'negative':'positive' ?>"><?= number_format($total_balance,2) ?></div><div class="sub">incl. interest</div></div>
  <div class="stat-card"><div class="label">Total Received</div><div class="value positive"><?= number_format($total_paid,2) ?></div></div>
</div>
<div class="card mt-3">
  <div class="flex-between" style="margin-bottom:1rem"><h2>Loans Overview</h2></div>
  <table>
    <thead><tr><th>Name</th><th>Borrower</th><th>Principal</th><th>Rate</th><th>Compound</th><th>Accrued</th><th>Paid</th><th>Balance</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($summaries as $l): ?>
    <tr>
      <td><a href="loan_detail.php?id=<?= $l['id'] ?>"><?= htmlspecialchars($l['name']) ?></a></td>
      <td><?= htmlspecialchars($l['borrower']) ?></td>
      <td><?= number_format($l['principal'],2) ?></td>
      <td><?= $l['interest_rate']>0 ? $l['interest_rate'].'%/yr' : '<span class="text-muted">0%</span>' ?></td>
      <td><?= $l['interest_rate']>0 ? 'Every '.$l['compound_months'].' mo.' : '—' ?></td>
      <td class="<?= $l['accrued']>0?'negative':'' ?>"><?= number_format($l['accrued'],2) ?></td>
      <td class="positive"><?= number_format($l['paid'],2) ?></td>
      <td class="<?= $l['balance']>0?'negative':'positive' ?>"><?= number_format($l['balance'],2) ?></td>
      <td><span class="badge badge-<?= $l['status']==='active'?'green':($l['status']==='closed'?'blue':'yellow') ?>"><?= ucfirst($l['status']) ?></span></td>
      <td class="flex">
        <a href="loan_detail.php?id=<?= $l['id'] ?>" class="btn btn-sm btn-ghost">View</a>
        <a href="loan_form.php?id=<?= $l['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if(empty($summaries)): ?>
    <tr><td colspan="10" class="text-muted" style="text-align:center;padding:2rem">No loans yet. <a href="loan_form.php">Add your first loan</a>.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php render_foot(); ?>
