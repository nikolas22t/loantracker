<?php
require_once 'config.php'; require_login();
require_once 'layout.php';
$db = get_db();

// Delete borrower
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'delete') {
    $bid = (int)$_POST['borrower_id'];
    // Check if any loans reference this borrower name
    $b = $db->prepare("SELECT name FROM borrowers WHERE id=? AND user_id=?");
    $b->execute([$bid, current_user_id()]);
    $brow = $b->fetch(PDO::FETCH_ASSOC);
    if ($brow) {
        $cnt = $db->prepare("SELECT COUNT(*) FROM loans WHERE user_id=? AND borrower=?");
        $cnt->execute([current_user_id(), $brow['name']]);
        if ($cnt->fetchColumn() > 0) {
            flash('Cannot delete — this borrower has active loans.', 'error');
        } else {
            $db->prepare("DELETE FROM borrowers WHERE id=? AND user_id=?")->execute([$bid, current_user_id()]);
            flash('Borrower deleted.');
        }
    }
    header('Location: borrowers.php'); exit;
}

// Add borrower
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'add') {
    $name  = trim($_POST['name']);
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    if ($name === '') {
        flash('Name is required.', 'error');
    } else {
        try {
            $db->prepare("INSERT INTO borrowers (user_id, name, email, phone, notes) VALUES (?,?,?,?,?)")
               ->execute([current_user_id(), $name, $email, $phone, $notes]);
            flash("Borrower \"$name\" added.");
        } catch (Exception $e) {
            flash("Borrower \"$name\" already exists.", 'error');
        }
    }
    header('Location: borrowers.php'); exit;
}

$borrowers = $db->prepare("SELECT b.*, (SELECT COUNT(*) FROM loans l WHERE l.user_id=b.user_id AND l.borrower=b.name) as loan_count FROM borrowers b WHERE b.user_id=? ORDER BY b.name");
$borrowers->execute([current_user_id()]);
$borrowers = $borrowers->fetchAll(PDO::FETCH_ASSOC);

render_head('Borrowers');
?>
<div class="flex-between mt-2" style="margin-bottom:1.5rem">
  <h1 style="font-size:1.4rem">Borrowers</h1>
  <a href="dashboard.php" class="btn btn-ghost">← Dashboard</a>
</div>

<div class="grid-2" style="align-items:start">

<!-- Left: borrower list -->
<div class="card">
  <h2 style="margin-bottom:1rem">All Borrowers</h2>
  <?php if(empty($borrowers)): ?>
  <p class="text-muted">No borrowers yet. Add one →</p>
  <?php else: ?>
  <table>
    <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Loans</th><th></th></tr></thead>
    <tbody>
    <?php foreach($borrowers as $b): ?>
    <tr>
      <td style="font-weight:600"><?= htmlspecialchars($b['name']) ?></td>
      <td class="text-muted"><?= htmlspecialchars($b['email'] ?: '—') ?></td>
      <td class="text-muted"><?= htmlspecialchars($b['phone'] ?: '—') ?></td>
      <td>
        <?php if($b['loan_count'] > 0): ?>
        <a href="dashboard.php?borrower=<?= urlencode($b['name']) ?>"><?= $b['loan_count'] ?> loan<?= $b['loan_count']>1?'s':'' ?></a>
        <?php else: ?>
        <span class="text-muted">0</span>
        <?php endif; ?>
      </td>
      <td>
        <?php if($b['loan_count'] == 0): ?>
        <form method="POST" style="display:inline" onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($b['name'])) ?>?')">
          <input type="hidden" name="_action" value="delete">
          <input type="hidden" name="borrower_id" value="<?= $b['id'] ?>">
          <button type="submit" class="btn btn-sm btn-danger">Delete</button>
        </form>
        <?php else: ?>
        <span class="text-muted btn btn-sm btn-ghost" style="cursor:default" title="Has active loans">Delete</span>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<!-- Right: add form -->
<div class="card">
  <h2 style="margin-bottom:1rem">Add New Borrower</h2>
  <form method="POST">
    <input type="hidden" name="_action" value="add">
    <div class="form-group">
      <label>Name <span style="color:var(--red)">*</span></label>
      <input name="name" required placeholder="Full name">
    </div>
    <div class="form-group">
      <label>Email (optional)</label>
      <input name="email" type="email" placeholder="email@example.com">
    </div>
    <div class="form-group">
      <label>Phone (optional)</label>
      <input name="phone" placeholder="+357 99 000000">
    </div>
    <div class="form-group">
      <label>Notes (optional)</label>
      <textarea name="notes" rows="2" placeholder="Any notes..."></textarea>
    </div>
    <div style="text-align:right">
      <button type="submit" class="btn btn-primary">Add Borrower</button>
    </div>
  </form>
</div>

</div>
<?php render_foot(); ?>
