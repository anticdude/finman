<?php

// Include authentication functions
require_once 'auth.php';

// Redirect to login if not authenticated
requireLogin();

// Database configuration
$CONFIG = [
  'DB_HOST' => 'localhost',
  'DB_USER' => 'u831088057_finman',
  'DB_PASS' => '',
  'DB_NAME' => 'u831088057_finman',
  'APP_NAME' => 'Smart Finance Manager',
];

// ===================== DB CONNECTION ===================== //
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $mysqli = new mysqli($CONFIG['DB_HOST'], $CONFIG['DB_USER'], $CONFIG['DB_PASS'], $CONFIG['DB_NAME']);
    $mysqli->set_charset('utf8mb4');
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Rest of your existing code continues here...

// ===================== HELPERS ===================== //
// session_start();
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

// Expense breakdown by category
$expenseBreakdown = [];
$expenseStmt = $mysqli->prepare("
    SELECT c.name, SUM(t.amount) as total 
    FROM transactions t 
    JOIN categories c ON t.category_id = c.id 
    WHERE t.dt BETWEEN ? AND ? AND t.type = 'expense' 
    GROUP BY c.name 
    ORDER BY total DESC
");
$expenseStmt->bind_param('ss', $start, $end);
$expenseStmt->execute();
$expenseResult = $expenseStmt->get_result();
while ($row = $expenseResult->fetch_assoc()) {
    $expenseBreakdown[] = $row;
}

// Lists
$catsIncome=$mysqli->query("SELECT * FROM categories WHERE type='income' ORDER BY name");
$catsExpense=$mysqli->query("SELECT * FROM categories WHERE type='expense' ORDER BY name");
$allCats=$mysqli->query("SELECT * FROM categories ORDER BY type,name");
$txns=$mysqli->prepare("SELECT t.*, c.name cat FROM transactions t LEFT JOIN categories c ON t.category_id=c.id WHERE dt BETWEEN ? AND ? ORDER BY dt DESC, id DESC");
$txns->bind_param('ss',$start,$end); $txns->execute(); $txList=$txns->get_result();
$loansList=$mysqli->query("SELECT * FROM loans ORDER BY active DESC, priority ASC, id DESC");
$cardsList=$mysqli->query("SELECT * FROM credit_cards ORDER BY active DESC, name ASC");

// Recent transactions for dashboard
$recentTxns = $mysqli->prepare("
    SELECT t.*, c.name cat 
    FROM transactions t 
    LEFT JOIN categories c ON t.category_id=c.id 
    ORDER BY t.dt DESC, t.id DESC 
    LIMIT 5
");
$recentTxns->execute();
$recentTxnsResult = $recentTxns->get_result();

// Upcoming bills for dashboard
$upcomingBills = $mysqli->prepare("
    SELECT * FROM loans 
    WHERE active = 1 AND start_date <= LAST_DAY(?) 
    AND (end_date IS NULL OR end_date >= ?)
    ORDER BY priority ASC 
    LIMIT 5
");
$upcomingBills->bind_param('ss', $start, $start);
$upcomingBills->execute();
$upcomingBillsResult = $upcomingBills->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?=h($CONFIG['APP_NAME'])?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    :root {
      --primary: #4361ee;
      --secondary: #3f37c9;
      --success: #4cc9f0;
      --danger: #f72585;
      --warning: #f8961e;
      --info: #4895ef;
      --light: #f8f9fa;
      --dark: #212529;
      --sidebar-width: 250px;
      --transition: all 0.3s ease;
    }
    
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f5f7fb;
      color: #333;
      overflow-x: hidden;
    }
    
    /* Sidebar styling */
    #sidebar {
      position: fixed;
      width: var(--sidebar-width);
      height: 100vh;
      background: linear-gradient(180deg, var(--primary) 0%, var(--secondary) 100%);
      color: white;
      transition: var(--transition);
      z-index: 1000;
      box-shadow: 3px 0 10px rgba(0, 0, 0, 0.1);
    }
    
    #sidebar .sidebar-header {
      padding: 20px;
      background: rgba(0, 0, 0, 0.1);
    }
    
    #sidebar ul li a {
      padding: 15px 25px;
      display: block;
      color: rgba(255, 255, 255, 0.9);
      text-decoration: none;
      transition: var(--transition);
      border-left: 4px solid transparent;
    }
    
    #sidebar ul li a:hover,
    #sidebar ul li a.active {
      background: rgba(255, 255, 255, 0.1);
      color: white;
      border-left: 4px solid white;
    }
    
    #sidebar ul li a i {
      margin-right: 10px;
      width: 20px;
      text-align: center;
    }
    
    /* Main content */
    #content {
      margin-left: var(--sidebar-width);
      transition: var(--transition);
      padding: 20px;
    }
    
    /* Card styling */
    .card {
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
      border: none;
      margin-bottom: 24px;
      transition: transform 0.3s, box-shadow 0.3s;
    }
    
    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }
    
    .card-header {
      background-color: white;
      border-bottom: 1px solid #eaeaea;
      padding: 15px 20px;
      border-radius: 10px 10px 0 0 !important;
      font-weight: 600;
    }
    
    /* KPI Cards */
    .kpi-card {
      text-align: center;
      padding: 20px;
    }
    
    .kpi-card .value {
      font-size: 24px;
      font-weight: 700;
      margin: 10px 0;
    }
    
    .kpi-card .title {
      font-size: 14px;
      color: #6c757d;
    }
    
    .kpi-card .icon {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto;
      font-size: 20px;
    }
    
    /* Buttons */
    .btn-primary {
      background-color: var(--primary);
      border-color: var(--primary);
    }
    
    .btn-primary:hover {
      background-color: var(--secondary);
      border-color: var(--secondary);
    }
    
    /* Table styling */
    .table th {
      font-weight: 600;
      color: #495057;
      border-top: none;
    }
    
    /* Chart container */
    .chart-container {
      position: relative;
      height: 350px;
      width: 100%;
    }
    
    /* Tabs */
    .nav-tabs .nav-link {
      border: none;
      color: #6c757d;
      font-weight: 500;
      padding: 10px 20px;
    }
    
    .nav-tabs .nav-link.active {
      color: var(--primary);
      border-bottom: 3px solid var(--primary);
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
      #sidebar {
        margin-left: -var(--sidebar-width);
        width: var(--sidebar-width);
      }
      
      #content {
        margin-left: 0;
      }
      
      #sidebar.active {
        margin-left: 0;
      }
    }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <nav id="sidebar">
    <div class="sidebar-header">
      <h3 class="mb-0">💰 FinManager</h3>
      <p class="text-muted mb-0 small">Smart Finance Control</p>
    </div>
    
    <ul class="list-unstyled">
      <li><a href="?tab=dashboard" class="<?= $tab === 'dashboard' ? 'active' : '' ?>"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
      <li><a href="?tab=transactions" class="<?= $tab === 'transactions' ? 'active' : '' ?>"><i class="bi bi-arrow-left-right"></i> Transactions</a></li>
      <li><a href="?tab=categories" class="<?= $tab === 'categories' ? 'active' : '' ?>"><i class="bi bi-tag"></i> Categories</a></li>
      <li><a href="?tab=loans" class="<?= $tab === 'loans' ? 'active' : '' ?>"><i class="bi bi-currency-exchange"></i> Loans & EMIs</a></li>
      <li><a href="?tab=cards" class="<?= $tab === 'cards' ? 'active' : '' ?>"><i class="bi bi-credit-card"></i> Credit Cards</a></li>
      <li><a href="?tab=import" class="<?= $tab === 'import' ? 'active' : '' ?>"><i class="bi bi-upload"></i> Import/Export</a></li>
      <li class="mt-auto">
  <a href="logout.php" class="text-danger">
    <i class="bi bi-box-arrow-right"></i> Logout (<?php echo htmlspecialchars(getCurrentUserEmail()); ?>)
  </a>
</li>
    </ul>
    
    <div class="mt-auto p-3 text-center">
      <div class="btn-group" role="group">
        <button type="button" class="btn btn-outline-light btn-sm">
          <i class="bi bi-moon"></i>
        </button>
        <button type="button" class="btn btn-outline-light btn-sm">
          <i class="bi bi-box-arrow-right"></i>
        </button>
      </div>
    </div>
  </nav>

  <!-- Content -->
  <div id="content">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-0"><?= ucfirst($tab) ?></h2>
        <p class="text-muted">
          <?php if ($tab === 'dashboard'): ?>
            Welcome back, here's your financial overview
          <?php elseif ($tab === 'transactions'): ?>
            Manage your income and expenses
          <?php elseif ($tab === 'categories'): ?>
            Organize your transaction categories
          <?php elseif ($tab === 'loans'): ?>
            Track your loans and EMIs
          <?php elseif ($tab === 'cards'): ?>
            Manage your credit cards
          <?php elseif ($tab === 'import'): ?>
            Import and export your financial data
          <?php endif; ?>
        </p>
      </div>
      <div class="d-flex">
        <div class="me-2">
          <form method="get">
            <input type="hidden" name="tab" value="<?= h($tab) ?>">
            <input type="month" class="form-select" name="ym" value="<?= h($ym) ?>" onchange="this.form.submit()">
          </form>
        </div>
        <?php if ($tab === 'transactions'): ?>
          <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#transactionModal">
            <i class="bi bi-plus-circle"></i> Add Transaction
          </button>
        <?php endif; ?>
      </div>
    </div>
    
    <?php if($tab==='dashboard'): ?>
      <!-- KPI Cards -->
      <div class="row">
        <div class="col-md-3">
          <div class="card kpi-card">
            <div class="icon bg-success bg-opacity-10 text-success">
              <i class="bi bi-arrow-down-left"></i>
            </div>
            <div class="value text-success">₹<?= number_format($income, 2) ?></div>
            <div class="title">Total Income</div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card kpi-card">
            <div class="icon bg-danger bg-opacity-10 text-danger">
              <i class="bi bi-arrow-up-right"></i>
            </div>
            <div class="value text-danger">₹<?= number_format($expense, 2) ?></div>
            <div class="title">Total Expenses</div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card kpi-card">
            <div class="icon bg-info bg-opacity-10 text-info">
              <i class="bi bi-graph-up"></i>
            </div>
            <div class="value text-info">₹<?= number_format($net, 2) ?></div>
            <div class="title">Net Savings</div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card kpi-card">
            <div class="icon bg-warning bg-opacity-10 text-warning">
              <i class="bi bi-calendar-check"></i>
            </div>
            <div class="value text-warning">₹<?= number_format($sumUpcomingEmi, 2) ?></div>
            <div class="title">Upcoming EMIs</div>
          </div>
        </div>
      </div>
      
      <!-- Charts & Overview -->
      <div class="row">
        <div class="col-lg-8">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <span>Financial Overview</span>
              <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-secondary active">Monthly</button>
                <button type="button" class="btn btn-outline-secondary">Quarterly</button>
                <button type="button" class="btn btn-outline-secondary">Yearly</button>
              </div>
            </div>
            <div class="card-body">
              <div class="chart-container">
                <canvas id="financialChart"></canvas>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-lg-4">
          <div class="card">
            <div class="card-header">Expense Breakdown</div>
            <div class="card-body">
              <div class="chart-container">
                <canvas id="expenseChart"></canvas>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Recent Transactions & Upcoming Bills -->
      <div class="row">
        <div class="col-lg-6">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <span>Recent Transactions</span>
              <a href="?tab=transactions" class="btn btn-sm btn-link">View All</a>
            </div>
            <div class="card-body p-0">
              <div class="list-group list-group-flush">
                <?php while($t = $recentTxnsResult->fetch_assoc()): ?>
                <div class="list-group-item d-flex justify-content-between align-items-center">
                  <div>
                    <div class="fw-bold"><?= h($t['cat'] ?? 'Uncategorized') ?></div>
                    <small class="text-muted"><?= h($t['dt']) ?> • <?= h($t['account'] ?? 'No Account') ?></small>
                  </div>
                  <span class="<?= $t['type'] === 'income' ? 'text-success' : 'text-danger' ?>">
                    <?= $t['type'] === 'income' ? '+' : '-' ?>₹<?= number_format($t['amount'], 2) ?>
                  </span>
                </div>
                <?php endwhile; ?>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-lg-6">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <span>Upcoming Bills & EMIs</span>
              <a href="?tab=loans" class="btn btn-sm btn-link">View All</a>
            </div>
            <div class="card-body p-0">
              <div class="list-group list-group-flush">
                <?php while($ln = $upcomingBillsResult->fetch_assoc()): ?>
                <div class="list-group-item d-flex justify-content-between align-items-center">
                  <div>
                    <div class="fw-bold"><?= h($ln['name']) ?></div>
                    <small class="text-muted">Due on <?= h(date('M j, Y', strtotime($ln['start_date']))) ?></small>
                  </div>
                  <span class="text-warning">₹<?= number_format($ln['monthly_emi'], 2) ?></span>
                </div>
                <?php endwhile; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <?php if($tab==='transactions'): ?>
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <span>Transactions — <?= h(date('M Y', strtotime($start))) ?></span>
              <div>
                <form method="post" class="d-inline">
                  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                  <input type="hidden" name="action" value="export_csv">
                  <button class="btn btn-sm btn-outline-primary">Export CSV</button>
                </form>
                <button class="btn btn-sm btn-primary ms-2" data-bs-toggle="modal" data-bs-target="#transactionModal">
                  <i class="bi bi-plus"></i> Add New
                </button>
              </div>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-sm">
                  <thead>
                    <tr>
                      <th>Date</th>
                      <th>Type</th>
                      <th>Category</th>
                      <th>Account</th>
                      <th class="text-end">Amount</th>
                      <th>Notes</th>
                      <th class="text-end">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php while($t = $txList->fetch_assoc()): ?>
                    <tr>
                      <td><?= h($t['dt']) ?></td>
                      <td>
                        <span class="badge <?= $t['type'] === 'income' ? 'bg-success' : 'bg-danger' ?>">
                          <?= h($t['type']) ?>
                        </span>
                      </td>
                      <td><?= h($t['cat'] ?? '—') ?></td>
                      <td><?= h($t['account'] ?? '') ?></td>
                      <td class="text-end">₹<?= number_format($t['amount'], 2) ?></td>
                      <td><?= h($t['notes'] ?? '') ?></td>
                      <td class="text-end">
                        <form method="post" onsubmit="return confirm('Delete transaction?')" class="d-inline">
                          <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                          <input type="hidden" name="action" value="delete_txn">
                          <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
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
      </div>
      
      <!-- Transaction Modal -->
      <div class="modal fade" id="transactionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Add Transaction</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
              <div class="modal-body">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="save_txn">
                <div class="mb-3">
                  <label class="form-label">Date</label>
                  <input type="date" class="form-control" name="dt" value="<?= h(date('Y-m-d')) ?>" required>
                </div>
                <div class="mb-3">
                  <label class="form-label">Type</label>
                  <select name="type" class="form-select" required>
                    <option value="income">Income</option>
                    <option value="expense" selected>Expense</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label">Category</label>
                  <select name="category_id" class="form-select">
                    <optgroup label="Income">
                      <?php while($c = $catsIncome->fetch_assoc()): ?>
                        <option value="<?= (int)$c['id'] ?>"><?= h($c['name']) ?></option>
                      <?php endwhile; ?>
                    </optgroup>
                    <optgroup label="Expense">
                      <?php while($c = $catsExpense->fetch_assoc()): ?>
                        <option value="<?= (int)$c['id'] ?>"><?= h($c['name']) ?></option>
                      <?php endwhile; ?>
                    </optgroup>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label">Amount (₹)</label>
                  <input type="number" step="0.01" class="form-control" name="amount" required>
                </div>
                <div class="mb-3">
                  <label class="form-label">Account (optional)</label>
                  <input type="text" class="form-control" name="account" placeholder="e.g., HDFC, OneCard, Cash">
                </div>
                <div class="mb-3">
                  <label class="form-label">Notes</label>
                  <input type="text" class="form-control" name="notes" maxlength="255">
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Transaction</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <?php if($tab==='categories'): ?>
      <div class="row">
        <div class="col-md-4">
          <div class="card">
            <div class="card-header">Add Category</div>
            <div class="card-body">
              <form method="post">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="save_category">
                <div class="mb-3">
                  <label class="form-label">Name</label>
                  <input class="form-control" name="name" required>
                </div>
                <div class="mb-3">
                  <label class="form-label">Type</label>
                  <select class="form-select" name="type" required>
                    <option value="income">Income</option>
                    <option value="expense" selected>Expense</option>
                  </select>
                </div>
                <button class="btn btn-primary w-100">Save Category</button>
              </form>
            </div>
          </div>
        </div>
        <div class="col-md-8">
          <div class="card">
            <div class="card-header">All Categories</div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-sm align-middle">
                  <thead>
                    <tr>
                      <th>Name</th>
                      <th>Type</th>
                      <th class="text-end">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php while($c = $allCats->fetch_assoc()): ?>
                    <tr>
                      <td><?= h($c['name']) ?></td>
                      <td>
                        <span class="badge <?= $c['type'] === 'income' ? 'bg-success' : 'bg-danger' ?>">
                          <?= h($c['type']) ?>
                        </span>
                      </td>
                      <td class="text-end">
                        <form method="post" onsubmit="return confirm('Delete category?')" class="d-inline">
                          <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                          <input type="hidden" name="action" value="delete_category">
                          <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
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
      </div>
    <?php endif; ?>

    <?php if($tab==='loans'): ?>
      <div class="row">
        <div class="col-md-4">
          <div class="card">
            <div class="card-header">Add Loan / EMI</div>
            <div class="card-body">
              <form method="post">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="save_loan">
                <div class="mb-3">
                  <label class="form-label">Name</label>
                  <input class="form-control" name="name" required placeholder="e.g., Home Loan">
                </div>
                <div class="mb-3">
                  <label class="form-label">Monthly EMI (₹)</label>
                  <input type="number" step="0.01" class="form-control" name="monthly_emi" required>
                </div>
                <div class="mb-3">
                  <label class="form-label">Start Date</label>
                  <input type="date" class="form-control" name="start_date" required>
                </div>
                <div class="mb-3">
                  <label class="form-label">End Date (optional)</label>
                  <input type="date" class="form-control" name="end_date">
                </div>
                <div class="mb-3">
                  <label class="form-label">Interest % (optional)</label>
                  <input type="number" step="0.01" class="form-control" name="interest_rate">
                </div>
                <div class="mb-3">
                  <label class="form-label">Paydown Priority (1=highest)</label>
                  <input type="number" class="form-control" name="priority" value="5">
                </div>
                <div class="mb-3">
                  <label class="form-label">Active</label>
                  <select class="form-select" name="active">
                    <option value="1" selected>Yes</option>
                    <option value="0">No</option>
                  </select>
                </div>
                <button class="btn btn-primary w-100">Save Loan</button>
              </form>
            </div>
          </div>
        </div>
        <div class="col-md-8">
          <div class="card">
            <div class="card-header">Loans / EMIs</div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-sm">
                  <thead>
                    <tr>
                      <th>Name</th>
                      <th class="text-end">EMI</th>
                      <th>Start</th>
                      <th>End</th>
                      <th>Rate%</th>
                      <th>Priority</th>
                      <th>Active</th>
                      <th class="text-end">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php while($l = $loansList->fetch_assoc()): ?>
                    <tr>
                      <td><?= h($l['name']) ?></td>
                      <td class="text-end">₹<?= number_format($l['monthly_emi'], 2) ?></td>
                      <td><?= h($l['start_date']) ?></td>
                      <td><?= h($l['end_date'] ?? '') ?></td>
                      <td><?= h($l['interest_rate'] ?? '') ?></td>
                      <td><?= h($l['priority']) ?></td>
                      <td>
                        <?= $l['active'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' ?>
                      </td>
                      <td class="text-end">
                        <form method="post" onsubmit="return confirm('Delete loan?')" class="d-inline">
                          <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                          <input type="hidden" name="action" value="delete_loan">
                          <input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
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
      </div>
    <?php endif; ?>

    <?php if($tab==='cards'): ?>
      <div class="row">
        <div class="col-md-4">
          <div class="card">
            <div class="card-header">Add Credit Card</div>
            <div class="card-body">
              <form method="post">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="save_cc">
                <div class="mb-3">
                  <label class="form-label">Name</label>
                  <input class="form-control" name="name" required placeholder="e.g., OneCard">
                </div>
                <div class="mb-3">
                  <label class="form-label">Limit (₹)</label>
                  <input type="number" step="0.01" class="form-control" name="credit_limit">
                </div>
                <div class="mb-3">
                  <label class="form-label">Interest %</label>
                  <input type="number" step="0.01" class="form-control" name="interest_rate">
                </div>
                <div class="mb-3">
                  <label class="form-label">Billing Cycle Closing Day</label>
                  <input type="number" class="form-control" name="closing_day" placeholder="e.g., 20">
                </div>
                <div class="mb-3">
                  <label class="form-label">Payment Due Day</label>
                  <input type="number" class="form-control" name="due_day" placeholder="e.g., 27">
                </div>
                <div class="mb-3">
                  <label class="form-label">Active</label>
                  <select class="form-select" name="active">
                    <option value="1" selected>Yes</option>
                    <option value="0">No</option>
                  </select>
                </div>
                <button class="btn btn-primary w-100">Save Card</button>
              </form>
            </div>
          </div>
        </div>
        <div class="col-md-8">
          <div class="card">
            <div class="card-header">Credit Cards</div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-sm">
                  <thead>
                    <tr>
                      <th>Name</th>
                      <th>Limit</th>
                      <th>Rate%</th>
                      <th>Closing</th>
                      <th>Due</th>
                      <th>Active</th>
                      <th class="text-end">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php while($cc = $cardsList->fetch_assoc()): ?>
                    <tr>
                      <td><?= h($cc['name']) ?></td>
                      <td><?= $cc['credit_limit'] !== NULL ? '₹' . number_format($cc['credit_limit'], 2) : '—' ?></td>
                      <td><?= h($cc['interest_rate'] ?? '—') ?></td>
                      <td><?= h($cc['closing_day'] ?? '—') ?></td>
                      <td><?= h($cc['due_day'] ?? '—') ?></td>
                      <td>
                        <?= $cc['active'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' ?>
                      </td>
                      <td class="text-end">
                        <form method="post" onsubmit="return confirm('Delete card?')" class="d-inline">
                          <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                          <input type="hidden" name="action" value="delete_cc">
                          <input type="hidden" name="id" value="<?= (int)$cc['id'] ?>">
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
      </div>
    <?php endif; ?>

    <?php if($tab==='import'): ?>
      <div class="row">
        <div class="col-md-6">
          <div class="card">
            <div class="card-header">Import Transactions (CSV)</div>
            <div class="card-body">
              <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="import_csv">
                <div class="mb-3">
                  <input class="form-control" type="file" name="csv" accept=".csv" required>
                </div>
                <button class="btn btn-primary">Import</button>
              </form>
              <div class="small text-muted mt-3">
                CSV columns accepted: <code>date,type,category,amount,account,notes</code>. Extra columns are ignored.
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card">
            <div class="card-header">Export Transactions</div>
            <div class="card-body">
              <form method="post">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="export_csv">
                <button class="btn btn-outline-primary">Download CSV</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <div class="text-center text-muted mt-5 mb-4 small">
      Built with ❤️ using PHP + Bootstrap + MySQL
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Initialize charts
    document.addEventListener('DOMContentLoaded', function() {
      <?php if($tab === 'dashboard'): ?>
        // Financial Overview Chart
        const financialCtx = document.getElementById('financialChart').getContext('2d');
        const financialChart = new Chart(financialCtx, {
          type: 'bar',
          data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [
              {
                label: 'Income',
                data: <?= json_encode(array_slice($series, 0, count($labels))) ?>,
                backgroundColor: 'rgba(67, 97, 238, 0.7)',
                borderColor: 'rgba(67, 97, 238, 1)',
                borderWidth: 1
              },
              {
                label: 'Expenses',
                data: <?= json_encode(array_slice($series, 0, count($labels))) ?>,
                backgroundColor: 'rgba(247, 37, 133, 0.7)',
                borderColor: 'rgba(247, 37, 133, 1)',
                borderWidth: 1
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              y: {
                beginAtZero: true,
                grid: {
                  drawBorder: false
                }
              },
              x: {
                grid: {
                  display: false
                }
              }
            },
            plugins: {
              legend: {
                position: 'top',
              }
            }
          }
        });
        
        // Expense Breakdown Chart
        const expenseCtx = document.getElementById('expenseChart').getContext('2d');
        const expenseChart = new Chart(expenseCtx, {
          type: 'doughnut',
          data: {
            labels: <?= json_encode(array_column($expenseBreakdown, 'name')) ?>,
            datasets: [{
              data: <?= json_encode(array_column($expenseBreakdown, 'total')) ?>,
              backgroundColor: [
                '#4361ee',
                '#4cc9f0',
                '#f72585',
                '#f8961e',
                '#4895ef',
                '#7209b7'
              ],
              borderWidth: 0
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: 'bottom'
              }
            },
            cutout: '70%'
          }
        });
      <?php endif; ?>
    });
  </script>
</body>
</html>