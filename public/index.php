<?php
require_once 'config.php';
if (is_logged_in()) { header('Location: dashboard.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db   = get_db();
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([trim($_POST['username'])]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($_POST['password'], $user['password'])) {
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: dashboard.php'); exit;
    }
    flash('Invalid username or password.', 'error');
    header('Location: index.php'); exit;
}
require_once 'layout.php';
$flash = get_flash();
render_head('Login');
?>
<style>
.login-wrap{min-height:80vh;display:flex;align-items:center;justify-content:center}
.login-box{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:2.5rem;width:100%;max-width:400px;text-align:center}
.login-box h1{font-size:1.5rem;margin-bottom:.4rem}
.login-box p{color:var(--muted);font-size:.85rem;margin-bottom:1.8rem}
</style>
<?php if($flash): ?><div class="flash flash-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['msg']) ?></div><?php endif; ?>
<div class="login-wrap">
<div class="login-box">
  <h1>💰 Loan Tracker</h1>
  <p>Sign in to manage your loans</p>
  <form method="POST" style="text-align:left">
    <div class="form-group"><label>Username</label><input name="username" required autofocus></div>
    <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
    <button class="btn btn-primary" style="width:100%;margin-top:.5rem">Sign In</button>
  </form>
</div>
</div>
<?php render_foot(); ?>
