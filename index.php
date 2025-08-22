<?php
/**
 * Smart Finance Manager — Single-file PHP + Bootstrap + MySQL (Light Theme)
 * ------------------------------------------------------------------------
 * File: finance_manager.php
 * What it does:
 *  - Auto-creates MySQL DB & tables on first run
 *  - Dashboard with month filter & KPIs (Income, Expense, Net, Upcoming EMIs)
 *  - Transactions (add/list/delete), Categories (CRUD), Loans/EMIs (CRUD)
 *  - Credit Cards (CRUD)
 *  - 12‑month savings chart (Chart.js)
 *  - CSV import/export for transactions
 *
 * SECURITY
 *  - For self-hosting/LAN. Add authentication if exposed publicly (HTTP Basic below)
 *  - Uses prepared statements + simple CSRF token
 */

// ===================== CONFIG ===================== //
$CONFIG = [
  'DB_HOST' => 'localhost',
  'DB_USER' => 'u831088057_finman',
  'DB_PASS' => 'Anakaya@05',
  'DB_NAME' => 'u831088057_finman',
  'APP_NAME' => 'Smart Finance Manager',
];

// (Optional) HTTP Basic Auth — uncomment to protect quickly
/*
$AUTH_USER = 'admin';
$AUTH_PASS = 'password';
if (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] !== $AUTH_USER || $_SERVER['PHP_AUTH_PW'] !== $AUTH_PASS) {
  header('WWW-Authenticate: Basic realm="Finance"');
  header('HTTP/1.0 401 Unauthorized');
  echo 'Authentication required.'; exit;
}
*/

// ===================== DB BOOTSTRAP ===================== //
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = new mysqli($CONFIG['DB_HOST'], $CONFIG['DB_USER'], $CONFIG['DB_PASS']);
$mysqli->set_charset('utf8mb4');
$mysqli->query("CREATE DATABASE IF NOT EXISTS `{$CONFIG['DB_NAME']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
$mysqli->select_db($CONFIG['DB_NAME']);

// Create tables if not exist
$mysqli->query("CREATE TABLE IF NOT EXISTS settings (
  id INT PRIMARY KEY AUTO_INCREMENT,
  k VARCHAR(100) UNIQUE,
  v TEXT
) ENGINE=InnoDB");

$mysqli->query("CREATE TABLE IF NOT EXISTS categories (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  type ENUM('income','expense') NOT NULL,
  UNIQUE KEY unique_cat (name, type)
) ENGINE=InnoDB");

$mysqli->query("CREATE TABLE IF NOT EXISTS transactions (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  dt DATE NOT NULL,
  type ENUM('income','expense') NOT NULL,
  category_id INT,
  amount DECIMAL(12,2) NOT NULL,
  account VARCHAR(100) DEFAULT NULL,
  notes VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB");

$mysqli->query("CREATE TABLE IF NOT EXISTS loans (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  monthly_emi DECIMAL(12,2) NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE DEFAULT NULL,
  interest_rate DECIMAL(5,2) DEFAULT NULL,
  priority INT DEFAULT 5,
  active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB");

$mysqli->query("CREATE TABLE IF NOT EXISTS credit_cards (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  credit_limit DECIMAL(12,2) DEFAULT NULL,
  interest_rate DECIMAL(5,2) DEFAULT NULL,
  closing_day TINYINT DEFAULT NULL,
  due_day TINYINT DEFAULT NULL,
  active TINYINT(1) DEFAULT 1,
  UNIQUE KEY cc_name (name)
) ENGINE=InnoDB");

// Seed default categories if empty
$hasCat = $mysqli->query("SELECT COUNT(*) c FROM categories")->fetch_assoc()['c'] ?? 0;
if ((int)$hasCat === 0) {
  $defaults = [
    ['Salary','income'], ['Rent','income'], ['Other Income','income'],
    ['EMI','expense'], ['Credit Card','expense'], ['Utility','expense'],
    ['Investment','expense'], ['Home','expense'], ['Fuel','expense'], ['Groceries','expense'], ['Other','expense']
  ];
  $stmt = $mysqli->prepare("INSERT INTO categories (name, type) VALUES (?,?)");
  foreach ($defaults as $d) { $stmt->bind_param('ss', $d[0], $d[1]); $stmt->execute(); }
}

// ===================== HELPERS ===================== //
session_start();
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function post($k,$d=null){ return $_POST[$k] ?? $d; }
function getv($k,$d=null){ return $_GET[$k] ?? $d; }
function csrf_token(){ if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(16)); return $_SESSION['csrf']; }
function check_csrf(){ if($_SERVER['REQUEST_METHOD']==='POST' && (post('csrf')!==($_SESSION['csrf']??''))) die('Bad CSRF'); }

$tab = getv('tab','dashboard');
$action = post('action') ?? getv('action');
check_csrf();

// ===================== ACTIONS ===================== //
// Category
if ($action==='save_category') {
  $id = (int)post('id',0); $name = trim(post('name')); $type = post('type');
  if ($id>0) { $stmt=$mysqli->prepare("UPDATE categories SET name=?, type=? WHERE id=?"); $stmt->bind_param('ssi',$name,$type,$id); }
  else { $stmt=$mysqli->prepare("INSERT INTO categories (name,type) VALUES (?,?)"); $stmt->bind_param('ss',$name,$type); }
  $stmt->execute(); header('Location: ?tab=categories'); exit;
}
if ($action==='delete_category') { $id=(int)post('id'); $stmt=$mysqli->prepare("DELETE FROM categories WHERE id=?"); $stmt->bind_param('i',$id); $stmt->execute(); header('Location:?tab=categories'); exit; }

// Transaction
if ($action==='save_txn') {
  $id=(int)post('id',0); $dt=post('dt'); $type=post('type'); $category_id=(int)post('category_id')?:NULL; $amount=(float)post('amount'); $account=trim(post('account'))?:NULL; $notes=trim(post('notes'))?:NULL;
  if ($id>0) { $stmt=$mysqli->prepare("UPDATE transactions SET dt=?, type=?, category_id=?, amount=?, account=?, notes=? WHERE id=?"); $stmt->bind_param('ssiddsi',$dt,$type,$category_id,$amount,$account,$notes,$id); }
  else { $stmt=$mysqli->prepare("INSERT INTO transactions (dt,type,category_id,amount,account,notes) VALUES (?,?,?,?,?,?)"); $stmt->bind_param('ssidds',$dt,$type,$category_id,$amount,$account,$notes); }
  $stmt->execute(); header('Location:?tab=transactions'); exit;
}
if ($action==='delete_txn') { $id=(int)post('id'); $stmt=$mysqli->prepare("DELETE FROM transactions WHERE id=?"); $stmt->bind_param('i',$id); $stmt->execute(); header('Location:?tab=transactions'); exit; }

// Loans
if ($action==='save_loan') {
  $id=(int)post('id',0); $name=trim(post('name')); $emi=(float)post('monthly_emi'); $start=post('start_date'); $end=post('end_date')?:NULL; $rate=post('interest_rate')!==''?(float)post('interest_rate'):NULL; $priority=(int)post('priority'); $active=(int)post('active',1);
  if ($id>0) { $stmt=$mysqli->prepare("UPDATE loans SET name=?, monthly_emi=?, start_date=?, end_date=?, interest_rate=?, priority=?, active=? WHERE id=?"); $stmt->bind_param('sdsssiii',$name,$emi,$start,$end,$rate,$priority,$active,$id); }
  else { $stmt=$mysqli->prepare("INSERT INTO loans (name,monthly_emi,start_date,end_date,interest_rate,priority,active) VALUES (?,?,?,?,?,?,?)"); $stmt->bind_param('sdsssii',$name,$emi,$start,$end,$rate,$priority,$active); }
  $stmt->execute(); header('Location:?tab=loans'); exit;
}
if ($action==='delete_loan') { $id=(int)post('id'); $stmt=$mysqli->prepare("DELETE FROM loans WHERE id=?"); $stmt->bind_param('i',$id); $stmt->execute(); header('Location:?tab=loans'); exit; }

// Credit Cards
if ($action==='save_cc') {
  $id=(int)post('id',0); $name=trim(post('name')); $limit=post('credit_limit')!==''?(float)post('credit_limit'):NULL; $rate=post('interest_rate')!==''?(float)post('interest_rate'):NULL; $closing=post('closing_day')!==''?(int)post('closing_day'):NULL; $due=post('due_day')!==''?(int)post('due_day'):NULL; $active=(int)post('active',1);
  if ($id>0) { $stmt=$mysqli->prepare("UPDATE credit_cards SET name=?, credit_limit=?, interest_rate=?, closing_day=?, due_day=?, active=? WHERE id=?"); $stmt->bind_param('sddiisi',$name,$limit,$rate,$closing,$due,$active,$id); }
  else { $stmt=$mysqli->prepare("INSERT INTO credit_cards (name,credit_limit,interest_rate,closing_day,due_day,active) VALUES (?,?,?,?,?,?)"); $stmt->bind_param('sddiis',$name,$limit,$rate,$closing,$due,$active); }
  $stmt->execute(); header('Location:?tab=cards'); exit;
}
if ($action==='delete_cc') { $id=(int)post('id'); $stmt=$mysqli->prepare("DELETE FROM credit_cards WHERE id=?"); $stmt->bind_param('i',$id); $stmt->execute(); header('Location:?tab=cards'); exit; }

// Export CSV
if ($action==='export_csv') {
  header('Content-Type: text/csv'); header('Content-Disposition: attachment; filename="transactions.csv"'); $out=fopen('php://output','w');
  fputcsv($out,['id','date','type','category','amount','account','notes']);
  $res=$mysqli->query("SELECT t.id,t.dt,t.type,c.name cat,t.amount,t.account,t.notes FROM transactions t LEFT JOIN categories c ON t.category_id=c.id ORDER BY t.dt DESC, t.id DESC");
  while($row=$res->fetch_assoc()){ fputcsv($out,$row); }
  fclose($out); exit;
}

// Import CSV
if ($action==='import_csv' && isset($_FILES['csv']) && is_uploaded_file($_FILES['csv']['tmp_name'])) {
  $f=fopen($_FILES['csv']['tmp_name'],'r'); $header=fgetcsv($f);
  $stmt=$mysqli->prepare("INSERT INTO transactions (dt,type,category_id,amount,account,notes) VALUES (?,?,?,?,?,?)");
  while(($row=fgetcsv($f))!==false){
    $date=$row[1] ?? $row[0]; $type=$row[2] ?? $row[1]; $catName=$row[3] ?? $row[2]; $amount=(float)($row[4] ?? $row[3]); $account=$row[5] ?? ($row[4] ?? NULL); $notes=$row[6] ?? ($row[5] ?? NULL);
    $catId=NULL; if ($catName) { $find=$mysqli->prepare("SELECT id,type FROM categories WHERE name=?"); $find->bind_param('s',$catName); $find->execute(); $r=$find->get_result()->fetch_assoc(); if($r){$catId=(int)$r['id'];} else { $tguess=in_array(strtolower($catName),['salary','rent','other income'])?'income':'expense'; $ins=$mysqli->prepare("INSERT INTO categories (name,type) VALUES (?,?)"); $ins->bind_param('ss',$catName,$tguess); $ins->execute(); $catId=$ins->insert_id; }}
    $stmt->bind_param('ssidds',$date,$type,$catId,$amount,$account,$notes); $stmt->execute();
  }
  fclose($f); header('Location:?tab=transactions'); exit;
}

// ===================== QUERIES (for UI) ===================== //
$ym = getv('ym') ?: date('Y-m');
$start = date('Y-m-01', strtotime($ym.'-01'));
$end = date('Y-m-t', strtotime($start));

$aggStmt=$mysqli->prepare("SELECT type, SUM(amount) s FROM transactions WHERE dt BETWEEN ? AND ? GROUP BY type");
$aggStmt->bind_param('ss',$start,$end); $aggStmt->execute(); $aggRes=$aggStmt->get_result();
$income=0; $expense=0; while($r=$aggRes->fetch_assoc()){ if($r['type']==='income') $income=(float)$r['s']; else $expense=(float)$r['s']; }
$net = $income - $expense;

// Active EMIs this month
$loansRes=$mysqli->query("SELECT * FROM loans WHERE active=1 ORDER BY priority ASC, id ASC");
$upcomingEmis=[]; $sumUpcomingEmi=0.0; while($ln=$loansRes->fetch_assoc()){ $activeNow=(strtotime($ln['start_date'])<=strtotime($end)) && (empty($ln['end_date'])||strtotime($ln['end_date'])>=strtotime($start)); if($activeNow){ $upcomingEmis[]=$ln; $sumUpcomingEmi+=(float)$ln['monthly_emi']; } }

// Last 12 months chart
$labels=[]; $series=[]; for($i=11;$i>=0;$i--){ $mStart=date('Y-m-01', strtotime("-$i months", strtotime($start))); $mEnd=date('Y-m-t', strtotime($mStart)); $labels[]=date('M Y', strtotime($mStart)); $sInc=0; $sExp=0; $stmt=$mysqli->prepare("SELECT type, SUM(amount) s FROM transactions WHERE dt BETWEEN ? AND ? GROUP BY type"); $stmt->bind_param('ss',$mStart,$mEnd); $stmt->execute(); $res=$stmt->get_result(); while($row=$res->fetch_assoc()){ if($row['type']==='income') $sInc=(float)$row['s']; else $sExp=(float)$row['s']; } $series[]=$sInc-$sExp; }

// Lists
$catsIncome=$mysqli->query("SELECT * FROM categories WHERE type='income' ORDER BY name");
$catsExpense=$mysqli->query("SELECT * FROM categories WHERE type='expense' ORDER BY name");
$allCats=$mysqli->query("SELECT * FROM categories ORDER BY type,name");
$txns=$mysqli->prepare("SELECT t.*, c.name cat FROM transactions t LEFT JOIN categories c ON t.category_id=c.id WHERE dt BETWEEN ? AND ? ORDER BY dt DESC, id DESC");
$txns->bind_param('ss',$start,$end); $txns->execute(); $txList=$txns->get_result();
$loansList=$mysqli->query("SELECT * FROM loans ORDER BY active DESC, priority ASC, id DESC");
$cardsList=$mysqli->query("SELECT * FROM credit_cards ORDER BY active DESC, name ASC");

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?=h($CONFIG['APP_NAME'])?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <style>
    body { background:#f8fafc; color:#0f172a; }
    .navbar, .card { background:#ffffff; border:1px solid #e5e7eb; }
    .navbar { box-shadow:0 2px 10px rgba(0,0,0,.04); }
    .form-control, .form-select { background:#ffffff; color:#0f172a; border-color:#e5e7eb; }
    a.nav-link { color:#334155; }
    a.nav-link.fw-bold, .nav-link.active { color:#0ea5e9; }
    .badge-soft { background:#eff6ff; color:#1d4ed8; }
    .kpi .title{ color:#64748b; font-size:.9rem; }
    .kpi .value{ font-size:1.5rem; font-weight:700; }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg mb-4">
  <div class="container-fluid">
    <a class="navbar-brand" href="?">💰 <?=h($CONFIG['APP_NAME'])?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nb"><span class="navbar-toggler-icon"></span></button>
    <div id="nb" class="collapse navbar-collapse">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link <?=($tab==='dashboard'?'fw-bold':'') ?>" href="?tab=dashboard">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link <?=($tab==='transactions'?'fw-bold':'') ?>" href="?tab=transactions">Transactions</a></li>
        <li class="nav-item"><a class="nav-link <?=($tab==='categories'?'fw-bold':'') ?>" href="?tab=categories">Categories</a></li>
        <li class="nav-item"><a class="nav-link <?=($tab==='loans'?'fw-bold':'') ?>" href="?tab=loans">Loans/EMIs</a></li>
        <li class="nav-item"><a class="nav-link <?=($tab==='cards'?'fw-bold':'') ?>" href="?tab=cards">Credit Cards</a></li>
        <li class="nav-item"><a class="nav-link <?=($tab==='import'?'fw-bold':'') ?>" href="?tab=import">Import/Export</a></li>
      </ul>
      <form class="d-flex" method="get">
        <input type="hidden" name="tab" value="<?=h($tab)?>">
        <input type="month" class="form-control me-2" name="ym" value="<?=h($ym)?>">
        <button class="btn btn-outline-primary">Go</button>
      </form>
    </div>
  </div>
</nav>
<div class="container-fluid">

<?php if($tab==='dashboard'): ?>
  <div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card p-3 kpi"><div class="title">Total Income</div><div class="value text-success">₹<?=number_format($income,2)?></div><div class="small text-muted"><?=h(date('M Y', strtotime($start)))?></div></div></div>
    <div class="col-md-3"><div class="card p-3 kpi"><div class="title">Total Expense</div><div class="value text-danger">₹<?=number_format($expense,2)?></div></div></div>
    <div class="col-md-3"><div class="card p-3 kpi"><div class="title">Net Savings</div><div class="value <?=($net>=0?'text-success':'text-danger')?>">₹<?=number_format($net,2)?></div></div></div>
    <div class="col-md-3"><div class="card p-3 kpi"><div class="title">Upcoming EMIs (This month)</div><div class="value text-warning">₹<?=number_format($sumUpcomingEmi,2)?></div></div></div>
  </div>

  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card p-3">
        <div class="d-flex justify-content-between align-items-center mb-2"><div class="fw-semibold">12‑Month Savings Trend</div></div>
        <canvas id="trend"></canvas>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card p-3">
        <div class="fw-semibold mb-2">Active EMIs</div>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead><tr><th>Name</th><th class="text-end">Monthly EMI</th></tr></thead>
            <tbody>
              <?php foreach($upcomingEmis as $e): ?>
                <tr><td><?=h($e['name'])?></td><td class="text-end">₹<?=number_format($e['monthly_emi'],2)?></td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script>
  const trendEl=document.getElementById('trend');
  new Chart(trendEl,{type:'line',data:{labels:<?=json_encode($labels)?>,datasets:[{label:'Savings',data:<?=json_encode($series)?>}]},options:{responsive:true,scales:{y:{beginAtZero:false}}}});
  </script>
<?php endif; ?>

<?php if($tab==='transactions'): ?>
  <div class="row g-3">
    <div class="col-lg-4">
      <div class="card p-3">
        <div class="fw-semibold mb-2">Add / Edit Transaction</div>
        <form method="post">
          <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
          <input type="hidden" name="action" value="save_txn">
          <input type="hidden" name="id" id="txn_id" value="">
          <div class="mb-2"><label class="form-label">Date</label>
            <input type="date" class="form-control" name="dt" value="<?=h(date('Y-m-d'))?>" required></div>
          <div class="mb-2"><label class="form-label">Type</label>
            <select name="type" class="form-select" required>
              <option value="income">Income</option>
              <option value="expense" selected>Expense</option>
            </select></div>
          <div class="mb-2"><label class="form-label">Category</label>
            <select name="category_id" class="form-select">
              <optgroup label="Income">
                <?php foreach($catsIncome as $c) echo '<option value="'.(int)$c['id'].'">'.h($c['name'])."</option>"; $catsIncome->data_seek(0); ?>
              </optgroup>
              <optgroup label="Expense">
                <?php foreach($catsExpense as $c) echo '<option value="'.(int)$c['id'].'">'.h($c['name'])."</option>"; ?>
              </optgroup>
            </select></div>
          <div class="mb-2"><label class="form-label">Amount (₹)</label>
            <input type="number" step="0.01" class="form-control" name="amount" required></div>
          <div class="mb-2"><label class="form-label">Account (optional)</label>
            <input type="text" class="form-control" name="account" placeholder="e.g., HDFC, OneCard, Cash"></div>
          <div class="mb-3"><label class="form-label">Notes</label>
            <input type="text" class="form-control" name="notes" maxlength="255"></div>
          <button class="btn btn-primary w-100">Save</button>
        </form>
      </div>
    </div>
    <div class="col-lg-8">
      <div class="card p-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="fw-semibold">Transactions — <?=h(date('M Y', strtotime($start)))?></div>
          <form method="post" class="d-flex gap-2">
            <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
            <input type="hidden" name="action" value="export_csv">
            <button class="btn btn-sm btn-outline-primary">Export CSV</button>
          </form>
        </div>
        <div class="table-responsive">
          <table class="table table-sm">
            <thead><tr><th>Date</th><th>Type</th><th>Category</th><th>Account</th><th class="text-end">Amount</th><th>Notes</th><th></th></tr></thead>
            <tbody>
              <?php while($t=$txList->fetch_assoc()): ?>
              <tr>
                <td><?=h($t['dt'])?></td>
                <td><span class="badge <?=($t['type']==='income'?'bg-success':'bg-danger')?>"><?=h($t['type'])?></span></td>
                <td><?=h($t['cat'] ?? '—')?></td>
                <td><?=h($t['account'] ?? '')?></td>
                <td class="text-end">₹<?=number_format($t['amount'],2)?></td>
                <td><?=h($t['notes'] ?? '')?></td>
                <td class="text-end">
                  <form method="post" onsubmit="return confirm('Delete transaction?')" class="d-inline">
                    <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
                    <input type="hidden" name="action" value="delete_txn">
                    <input type="hidden" name="id" value="<?= (int)$t['id']?>">
                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                  </form>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php if($tab==='categories'): ?>
  <div class="row g-3">
    <div class="col-md-4">
      <div class="card p-3">
        <div class="fw-semibold mb-2">Add / Edit Category</div>
        <form method="post">
          <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
          <input type="hidden" name="action" value="save_category">
          <input type="hidden" name="id" value="">
          <div class="mb-2"><label class="form-label">Name</label>
            <input class="form-control" name="name" required></div>
          <div class="mb-3"><label class="form-label">Type</label>
            <select class="form-select" name="type" required>
              <option value="income">Income</option>
              <option value="expense" selected>Expense</option>
            </select></div>
          <button class="btn btn-primary w-100">Save</button>
        </form>
      </div>
    </div>
    <div class="col-md-8">
      <div class="card p-3">
        <div class="fw-semibold mb-2">All Categories</div>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead><tr><th>Name</th><th>Type</th><th class="text-end">Action</th></tr></thead>
            <tbody>
              <?php while($c=$allCats->fetch_assoc()): ?>
              <tr>
                <td><?=h($c['name'])?></td>
                <td><span class="badge-soft px-2 py-1 rounded"><?=h($c['type'])?></span></td>
                <td class="text-end">
                  <form method="post" onsubmit="return confirm('Delete category?')" class="d-inline">
                    <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
                    <input type="hidden" name="action" value="delete_category">
                    <input type="hidden" name="id" value="<?= (int)$c['id']?>">
                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                  </form>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php if($tab==='loans'): ?>
  <div class="row g-3">
    <div class="col-md-4">
      <div class="card p-3">
        <div class="fw-semibold mb-2">Add / Edit Loan / EMI</div>
        <form method="post">
          <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
          <input type="hidden" name="action" value="save_loan">
          <input type="hidden" name="id" value="">
          <div class="mb-2"><label class="form-label">Name</label><input class="form-control" name="name" required placeholder="e.g., Home Loan"></div>
          <div class="mb-2"><label class="form-label">Monthly EMI (₹)</label><input type="number" step="0.01" class="form-control" name="monthly_emi" required></div>
          <div class="mb-2"><label class="form-label">Start Date</label><input type="date" class="form-control" name="start_date" required></div>
          <div class="mb-2"><label class="form-label">End Date (optional)</label><input type="date" class="form-control" name="end_date"></div>
          <div class="mb-2"><label class="form-label">Interest % (optional)</label><input type="number" step="0.01" class="form-control" name="interest_rate"></div>
          <div class="mb-2"><label class="form-label">Paydown Priority (1=highest)</label><input type="number" class="form-control" name="priority" value="5"></div>
          <div class="mb-3"><label class="form-label">Active</label>
            <select class="form-select" name="active"><option value="1" selected>Yes</option><option value="0">No</option></select>
          </div>
          <button class="btn btn-primary w-100">Save</button>
        </form>
      </div>
    </div>
    <div class="col-md-8">
      <div class="card p-3">
        <div class="fw-semibold mb-2">Loans / EMIs</div>
        <div class="table-responsive">
          <table class="table table-sm">
            <thead><tr><th>Name</th><th class="text-end">EMI</th><th>Start</th><th>End</th><th>Rate%</th><th>Priority</th><th>Active</th><th class="text-end">Action</th></tr></thead>
            <tbody>
              <?php while($l=$loansList->fetch_assoc()): ?>
              <tr>
                <td><?=h($l['name'])?></td>
                <td class="text-end">₹<?=number_format($l['monthly_emi'],2)?></td>
                <td><?=h($l['start_date'])?></td>
                <td><?=h($l['end_date'] ?? '')?></td>
                <td><?=h($l['interest_rate'] ?? '')?></td>
                <td><?=h($l['priority'])?></td>
                <td><?= $l['active']? '<span class="badge bg-success">Yes</span>':'<span class="badge bg-secondary">No</span>' ?></td>
                <td class="text-end">
                  <form method="post" onsubmit="return confirm('Delete loan?')" class="d-inline">
                    <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
                    <input type="hidden" name="action" value="delete_loan">
                    <input type="hidden" name="id" value="<?= (int)$l['id']?>">
                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                  </form>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php if($tab==='cards'): ?>
  <div class="row g-3">
    <div class="col-md-4">
      <div class="card p-3">
        <div class="fw-semibold mb-2">Add / Edit Credit Card</div>
        <form method="post">
          <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
          <input type="hidden" name="action" value="save_cc">
          <input type="hidden" name="id" value="">
          <div class="mb-2"><label class="form-label">Name</label><input class="form-control" name="name" required placeholder="e.g., OneCard"></div>
          <div class="mb-2"><label class="form-label">Limit (₹)</label><input type="number" step="0.01" class="form-control" name="credit_limit"></div>
          <div class="mb-2"><label class="form-label">Interest %</label><input type="number" step="0.01" class="form-control" name="interest_rate"></div>
          <div class="mb-2"><label class="form-label">Billing Cycle Closing Day</label><input type="number" class="form-control" name="closing_day" placeholder="e.g., 20"></div>
          <div class="mb-2"><label class="form-label">Payment Due Day</label><input type="number" class="form-control" name="due_day" placeholder="e.g., 27"></div>
          <div class="mb-3"><label class="form-label">Active</label>
            <select class="form-select" name="active"><option value="1" selected>Yes</option><option value="0">No</option></select>
          </div>
          <button class="btn btn-primary w-100">Save</button>
        </form>
      </div>
    </div>
    <div class="col-md-8">
      <div class="card p-3">
        <div class="fw-semibold mb-2">Credit Cards</div>
        <div class="table-responsive">
          <table class="table table-sm">
            <thead><tr><th>Name</th><th>Limit</th><th>Rate%</th><th>Closing</th><th>Due</th><th>Active</th><th class="text-end">Action</th></tr></thead>
            <tbody>
              <?php while($cc=$cardsList->fetch_assoc()): ?>
              <tr>
                <td><?=h($cc['name'])?></td>
                <td><?= $cc['credit_limit']!==NULL ? '₹'.number_format($cc['credit_limit'],2) : '—' ?></td>
                <td><?=h($cc['interest_rate'] ?? '—')?></td>
                <td><?=h($cc['closing_day'] ?? '—')?></td>
                <td><?=h($cc['due_day'] ?? '—')?></td>
                <td><?= $cc['active']? '<span class="badge bg-success">Yes</span>':'<span class="badge bg-secondary">No</span>' ?></td>
                <td class="text-end">
                  <form method="post" onsubmit="return confirm('Delete card?')" class="d-inline">
                    <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
                    <input type="hidden" name="action" value="delete_cc">
                    <input type="hidden" name="id" value="<?= (int)$cc['id']?>">
                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                  </form>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php if($tab==='import'): ?>
  <div class="row g-3">
    <div class="col-md-6">
      <div class="card p-3">
        <div class="fw-semibold mb-2">Import Transactions (CSV)</div>
        <form method="post" enctype="multipart/form-data">
          <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
          <input type="hidden" name="action" value="import_csv">
          <input class="form-control mb-2" type="file" name="csv" accept=".csv" required>
          <button class="btn btn-primary">Import</button>
        </form>
        <div class="small text-muted mt-2">CSV columns accepted: <code>date,type,category,amount,account,notes</code>. Extra columns are ignored.</div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card p-3">
        <div class="fw-semibold mb-2">Export Transactions</div>
        <form method="post">
          <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
          <input type="hidden" name="action" value="export_csv">
          <button class="btn btn-outline-primary">Download CSV</button>
        </form>
      </div>
    </div>
  </div>
<?php endif; ?>

  <div class="text-center text-muted mt-5 mb-4 small">Built with ❤️ using PHP + Bootstrap + MySQL</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
