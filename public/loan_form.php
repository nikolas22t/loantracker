<?php
require_once 'config.php'; require_login();
require_once 'layout.php';
$db  = get_db();
$id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;
// Fetch borrowers for dropdown
$borrower_list = $db->prepare("SELECT name FROM borrowers WHERE user_id = ? ORDER BY name");
$borrower_list->execute([current_user_id()]);
$borrower_list = $borrower_list->fetchAll(PDO::FETCH_COLUMN);
$loan = null;
if ($id) {
    $s = $db->prepare("SELECT * FROM loans WHERE id=? AND user_id=?");
    $s->execute([$id, current_user_id()]);
    $loan = $s->fetch(PDO::FETCH_ASSOC);
    if (!$loan) { flash('Loan not found.','error'); header('Location: dashboard.php'); exit; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name'            => trim($_POST['name']),
        'borrower'        => implode(', ', array_filter(array_map('trim', (array)($_POST['borrowers'] ?? [])))),
        'principal'       => (float)$_POST['principal'],
        'interest_rate'   => (float)$_POST['interest_rate'],
        'compound_months' => max(1,(int)$_POST['compound_months']),
        'start_date'      => $_POST['start_date'],
        'notes'           => trim($_POST['notes']),
        'status'          => $_POST['status'],
    ];

    if ($loan) {
        $db->prepare("
            UPDATE loans
            SET name=?, borrower=?, principal=?, interest_rate=?, compound_months=?,
                start_date=?, notes=?, status=?, updated_at=CURRENT_TIMESTAMP
            WHERE id=? AND user_id=?
        ")->execute([
            $data['name'],
            $data['borrower'],
            $data['principal'],
            $data['interest_rate'],
            $data['compound_months'],
            $data['start_date'],
            $data['notes'],
            $data['status'],
            $id,
            current_user_id()
        ]);
        log_history($db, $id, 'edited', 'Loan updated — Rate: '.$data['interest_rate'].'%, Compound: every '.$data['compound_months'].' month(s), Principal: '.$data['principal']);
        flash('Loan updated successfully.');
        header('Location: loan_detail.php?id='.$id); exit;
    } else {
        $db->prepare("
            INSERT INTO loans (user_id, name, borrower, principal, interest_rate, compound_months, start_date, notes, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            current_user_id(),
            $data['name'],
            $data['borrower'],
            $data['principal'],
            $data['interest_rate'],
            $data['compound_months'],
            $data['start_date'],
            $data['notes'],
            $data['status']
        ]);
        $new_id = $db->lastInsertId();
        log_history($db, $new_id, 'created', 'Loan registered: '.$data['name'].' — Principal: '.$data['principal'].', Rate: '.$data['interest_rate'].'%');
        flash('Loan created successfully.');
        header('Location: loan_detail.php?id='.$new_id); exit;
    }
}

render_head($loan ? 'Edit Loan' : 'New Loan');
?>
<div class="flex-between mt-2" style="margin-bottom:1.5rem">
  <h1 style="font-size:1.4rem"><?= $loan ? 'Edit Loan' : 'Register New Loan' ?></h1>
  <a href="dashboard.php" class="btn btn-ghost">← Back</a>
</div>
<div class="card">
<form method="POST">
  <div class="grid-2">
    <div class="form-group">
      <label>Loan Name / Label</label>
      <input name="name" required value="<?= htmlspecialchars($loan['name'] ?? '') ?>" placeholder="e.g. Personal loan to John">
    </div>
    <div class="form-group">
      <label>Borrower(s) <a href="borrowers.php" style="font-size:.78rem;margin-left:.5rem" tabindex="-1">+ Manage</a></label>
      <div style="display:flex;flex-wrap:wrap;gap:.6rem 1.2rem;padding:.65rem .9rem;background:#12151f;border:1px solid var(--border);border-radius:8px;min-height:42px">
        <?php
          $_sel = array_map('trim', explode(',', $loan['borrower'] ?? ''));
          foreach($borrower_list as $bn):
            $chk = in_array($bn, $_sel) ? 'checked' : '';
        ?>
        <label style="display:flex;align-items:center;gap:.4rem;cursor:pointer;color:var(--text);font-size:.9rem;white-space:nowrap;margin:0">
          <input type="checkbox" name="borrowers[]" value="<?= htmlspecialchars($bn) ?>" <?= $chk ?> style="width:auto;accent-color:var(--accent);cursor:pointer">
          <?= htmlspecialchars($bn) ?>
        </label>
        <?php endforeach; ?>
        <?php if(empty($borrower_list)): ?>
        <span class="text-muted" style="font-size:.85rem">No borrowers yet — <a href="borrowers.php">add one</a></span>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="grid-3">
    <div class="form-group">
      <label>Principal Amount (€)</label>
      <input type="number" step="0.01" name="principal" required value="<?= $loan['principal'] ?? '' ?>" placeholder="0.00">
    </div>
    <div class="form-group">
      <label>Yearly Interest Rate (%)</label>
      <input type="number" step="0.01" min="0" name="interest_rate" id="rate_input"
             value="<?= $loan['interest_rate'] ?? '0' ?>"
             placeholder="0 = interest-free"
             oninput="toggleCompound()">
    </div>
    <div class="form-group" id="compound_group" style="<?= ($loan['interest_rate'] ?? 0) > 0 ? '' : 'opacity:.4;pointer-events:none' ?>">
      <label>Compound Every (months)</label>
      <select name="compound_months">
        <?php for ($m = 1; $m <= 12; $m++): ?>
        <option value="<?= $m ?>" <?= ($loan['compound_months'] ?? 1) == $m ? 'selected' : '' ?>>
          <?= $m ?> month<?= $m > 1 ? 's' : '' ?>
        </option>
        <?php endfor; ?>
      </select>
    </div>
  </div>
  <div class="grid-2">
    <div class="form-group">
      <label>Start Date</label>
      <input type="date" name="start_date" required value="<?= $loan['start_date'] ?? date('Y-m-d') ?>">
    </div>
    <div class="form-group">
      <label>Status</label>
      <select name="status">
        <option value="active"  <?= ($loan['status'] ?? 'active') === 'active'  ? 'selected' : '' ?>>Active</option>
        <option value="paused"  <?= ($loan['status'] ?? '')        === 'paused'  ? 'selected' : '' ?>>Paused</option>
        <option value="closed"  <?= ($loan['status'] ?? '')        === 'closed'  ? 'selected' : '' ?>>Closed</option>
      </select>
    </div>
  </div>
  <div class="form-group">
    <label>Notes</label>
    <textarea name="notes" rows="3" placeholder="Any details..."><?= htmlspecialchars($loan['notes'] ?? '') ?></textarea>
  </div>
  <div class="flex" style="justify-content:flex-end;gap:.75rem">
    <a href="<?= $loan ? 'loan_detail.php?id='.$id : 'dashboard.php' ?>" class="btn btn-ghost">Cancel</a>
    <button type="submit" class="btn btn-primary"><?= $loan ? 'Save Changes' : 'Register Loan' ?></button>
  </div>
</form>
</div>

<script>
function toggleCompound() {
  var r = parseFloat(document.getElementById('rate_input').value) || 0;
  var g = document.getElementById('compound_group');
  g.style.opacity      = r > 0 ? '1'    : '0.4';
  g.style.pointerEvents= r > 0 ? 'auto' : 'none';
}
// Run on page load in case editing a loan with rate > 0
toggleCompound();
</script>
<?php render_foot(); ?>
