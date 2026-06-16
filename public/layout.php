<?php
function render_head($title = 'Loan Tracker') {
    $flash = get_flash(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($title) ?> — Loan Tracker</title>
<style>
:root{--bg:#0f1117;--card:#1a1d27;--border:#2a2d3e;--accent:#4f8ef7;--accent2:#7c5cfc;--green:#22c55e;--red:#ef4444;--yellow:#f59e0b;--text:#e2e8f0;--muted:#64748b}
*{box-sizing:border-box;margin:0;padding:0}
body{background:var(--bg);color:var(--text);font-family:'Segoe UI',system-ui,sans-serif;min-height:100vh}
a{color:var(--accent);text-decoration:none}a:hover{text-decoration:underline}
.navbar{background:var(--card);border-bottom:1px solid var(--border);padding:0 2rem;display:flex;align-items:center;justify-content:space-between;height:56px}
.navbar .brand{font-size:1.2rem;font-weight:700;background:linear-gradient(135deg,var(--accent),var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;text-decoration:none}
.navbar nav a{color:var(--text);margin-left:1.5rem;font-size:.9rem;opacity:.8}
.navbar nav a:hover{opacity:1;text-decoration:none}
.navbar .user{font-size:.85rem;color:var(--muted);margin-left:1.5rem}
.container{max-width:1200px;margin:2rem auto;padding:0 1.5rem}
.card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:1.5rem;margin-bottom:1.5rem}
.card h2{font-size:1.1rem;margin-bottom:1rem}
.btn{display:inline-block;padding:.5rem 1.2rem;border-radius:8px;border:none;cursor:pointer;font-size:.9rem;font-weight:500;transition:opacity .15s}
.btn-primary{background:var(--accent);color:#fff}.btn-primary:hover{opacity:.85}
.btn-success{background:var(--green);color:#fff}.btn-success:hover{opacity:.85}
.btn-danger{background:var(--red);color:#fff}.btn-danger:hover{opacity:.85}
.btn-warning{background:var(--yellow);color:#000}.btn-warning:hover{opacity:.85}
.btn-sm{padding:.3rem .8rem;font-size:.8rem}
.btn-ghost{background:transparent;border:1px solid var(--border);color:var(--text)}.btn-ghost:hover{background:var(--border)}
table{width:100%;border-collapse:collapse;font-size:.9rem}
th{text-align:left;padding:.6rem .8rem;border-bottom:1px solid var(--border);color:var(--muted);font-weight:500;font-size:.8rem;text-transform:uppercase;letter-spacing:.05em}
td{padding:.7rem .8rem;border-bottom:1px solid var(--border)}
tr:hover td{background:rgba(255,255,255,.03)}
.badge{display:inline-block;padding:.2rem .6rem;border-radius:20px;font-size:.75rem;font-weight:600}
.badge-green{background:rgba(34,197,94,.15);color:var(--green)}
.badge-red{background:rgba(239,68,68,.15);color:var(--red)}
.badge-yellow{background:rgba(245,158,11,.15);color:var(--yellow)}
.badge-blue{background:rgba(79,142,247,.15);color:var(--accent)}
.form-group{margin-bottom:1rem}
label{display:block;font-size:.85rem;color:var(--muted);margin-bottom:.35rem}
input,select,textarea{width:100%;background:#12151f;border:1px solid var(--border);border-radius:8px;color:var(--text);padding:.55rem .9rem;font-size:.9rem}
input:focus,select:focus,textarea:focus{outline:none;border-color:var(--accent)}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem}
.grid-4{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem}
.stat-card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:1.2rem}
.stat-card .label{font-size:.78rem;color:var(--muted);text-transform:uppercase;letter-spacing:.06em}
.stat-card .value{font-size:1.6rem;font-weight:700;margin-top:.3rem}
.stat-card .sub{font-size:.8rem;color:var(--muted);margin-top:.2rem}
.flash{padding:.8rem 1.2rem;border-radius:8px;margin-bottom:1.2rem;font-size:.9rem}
.flash-success{background:rgba(34,197,94,.15);border:1px solid rgba(34,197,94,.3);color:var(--green)}
.flash-error{background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.3);color:var(--red)}
.positive{color:var(--green)}.negative{color:var(--red)}
.text-muted{color:var(--muted)}
.mt-1{margin-top:.5rem}.mt-2{margin-top:1rem}.mt-3{margin-top:1.5rem}
.flex{display:flex;align-items:center;gap:.75rem}
.flex-between{display:flex;align-items:center;justify-content:space-between}
@media(max-width:768px){.grid-2,.grid-3,.grid-4{grid-template-columns:1fr}}
.site-footer{text-align:center;padding:1.5rem;margin-top:2rem;border-top:1px solid var(--border);font-size:.78rem;color:var(--muted)}
.site-footer a{color:var(--muted);text-decoration:none}
.site-footer a:hover{color:var(--accent);text-decoration:underline}
</style>
</head>
<body>
<?php if(is_logged_in()): ?>
<nav class="navbar">
  <a href="dashboard.php" class="brand">💰 Loan Tracker</a>
  <a href="https://www.it4co.com" target="_blank" rel="noopener" style="font-size:.72rem;color:var(--muted);text-decoration:none;margin-left:.6rem;opacity:.7">by IT4Co</a>
  <nav>
    <a href="dashboard.php">Dashboard</a>
    <a href="loans.php">All Loans</a>
    <a href="payments.php">Payments</a>
    <a href="borrowers.php">Borrowers</a>
    <span class="user">👤 <?= htmlspecialchars(current_user()) ?></span>
    <a href="logout.php" style="color:var(--red);margin-left:1rem">Logout</a>
  </nav>
</nav>
<?php endif; ?>
<div class="container">
<?php if($flash): ?>
<div class="flash flash-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['msg']) ?></div>
<?php endif; ?>
<?php } ?>
<?php function render_foot() { ?>
</div>
<footer class="site-footer">
  Powered by <a href="https://www.it4co.com" target="_blank" rel="noopener">IT4Co</a>
</footer>
</body></html>
<?php } ?>
