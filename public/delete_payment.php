<?php
require_once 'config.php'; require_login();
$db      = get_db();
$id      = (int)($_GET['id']??0);
$loan_id = (int)($_GET['loan_id']??0);

$s = $db->prepare("SELECT p.*, l.user_id FROM payments p JOIN loans l ON l.id=p.loan_id WHERE p.id=? AND l.user_id=?");
$s->execute([$id, current_user_id()]);
$p = $s->fetch(PDO::FETCH_ASSOC);
if ($p) {
    $db->prepare("DELETE FROM payments WHERE id=?")->execute([$id]);
    log_history($db, $loan_id, 'payment_deleted', 'Payment of '.$p['amount'].' on '.$p['payment_date'].' deleted.');
    flash('Payment deleted.');
}
header('Location: loan_detail.php?id='.$loan_id); exit;
