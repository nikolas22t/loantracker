<?php
define('DB_PATH', '/var/www/data/loans.db');
session_start();

function get_db() {
    static $db = null;
    if ($db === null) {
        $db = new PDO('sqlite:' . DB_PATH);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec('PRAGMA journal_mode=WAL');
        init_db($db);
    }
    return $db;
}

function init_db($db) {
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS loans (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            borrower TEXT NOT NULL,
            principal REAL NOT NULL,
            interest_rate REAL DEFAULT 0,
            compound_months INTEGER DEFAULT 1,
            start_date DATE NOT NULL,
            notes TEXT,
            status TEXT DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(user_id) REFERENCES users(id)
        );
        CREATE TABLE IF NOT EXISTS payments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            loan_id INTEGER NOT NULL,
            amount REAL NOT NULL,
            payment_date DATE NOT NULL,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(loan_id) REFERENCES loans(id)
        );
        CREATE TABLE IF NOT EXISTS borrowers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            email TEXT,
            phone TEXT,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(user_id, name),
            FOREIGN KEY(user_id) REFERENCES users(id)
        );
        CREATE TABLE IF NOT EXISTS loan_history (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            loan_id INTEGER NOT NULL,
            action TEXT NOT NULL,
            description TEXT,
            changed_by INTEGER,
            changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(loan_id) REFERENCES loans(id)
        );
    ");

    $stmt = $db->query("SELECT COUNT(*) FROM users");
    if ($stmt->fetchColumn() == 0) {
        $ins = $db->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $ins->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT)]);
        $ins->execute(['demo',  password_hash('demo123',  PASSWORD_DEFAULT)]);
    }
    // Seed default borrowers — customise as needed
    $ins_b = $db->prepare("INSERT OR IGNORE INTO borrowers (user_id, name) VALUES (?, ?)");
    foreach ($db->query("SELECT id FROM users")->fetchAll(PDO::FETCH_COLUMN) as $uid) {
        $ins_b->execute([$uid, 'Borrower A']);
        $ins_b->execute([$uid, 'Borrower B']);
    }
}

function is_logged_in()   { return isset($_SESSION['user_id']); }
function require_login()  { if (!is_logged_in()) { header('Location: index.php'); exit; } }
function current_user()   { return $_SESSION['username'] ?? ''; }
function current_user_id(){ return $_SESSION['user_id'] ?? 0; }

function calculate_balance($loan, $payments, $as_of = null) {
    if ($as_of === null) $as_of = date('Y-m-d');
    $principal   = $loan['principal'];
    $rate        = $loan['interest_rate'] / 100;
    $comp_months = max(1, (int)$loan['compound_months']);
    $start       = new DateTime($loan['start_date']);
    $now         = new DateTime($as_of);
    $balance     = $principal;
    if ($rate > 0) {
        $days        = (int)$start->diff($now)->days;
        $periods     = floor($days / ($comp_months * 30.4375));
        $period_rate = $rate * $comp_months / 12;
        $balance     = $principal * pow(1 + $period_rate, $periods);
    }
    $paid = 0;
    foreach ($payments as $p) {
        if ($p['payment_date'] <= $as_of) $paid += $p['amount'];
    }
    return [
        'balance' => round($balance - $paid, 2),
        'accrued' => round($balance - $principal, 2),
        'paid'    => round($paid, 2),
        'principal'=> $principal
    ];
}

function flash($msg, $type = 'success') { $_SESSION['flash'] = ['msg'=>$msg,'type'=>$type]; }
function get_flash() {
    if (isset($_SESSION['flash'])) { $f=$_SESSION['flash']; unset($_SESSION['flash']); return $f; }
    return null;
}
function log_history($db, $loan_id, $action, $desc) {
    $db->prepare("INSERT INTO loan_history (loan_id,action,description,changed_by) VALUES (?,?,?,?)")
       ->execute([$loan_id, $action, $desc, current_user_id()]);
}
?>
