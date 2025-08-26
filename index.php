<?php
// Include authentication functions
require_once 'auth.php';

// Redirect to login if not authenticated
requireLogin();
include 'db.php';
// Database configuration
// $CONFIG = [
//   'DB_HOST' => 'localhost',
//   'DB_USER' => 'u831088057_finman',
//   'DB_PASS' => 'Anakaya@05',
//   'DB_NAME' => 'u831088057_finman',
//   'APP_NAME' => 'Smart Finance Manager',
// ];

// // ===================== DB CONNECTION ===================== //
// mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
// try {
//     $mysqli = new mysqli($CONFIG['DB_HOST'], $CONFIG['DB_USER'], $CONFIG['DB_PASS'], $CONFIG['DB_NAME']);
//     $mysqli->set_charset('utf8mb4');
// } catch (Exception $e) {
//     die("Database connection failed: " . $e->getMessage());
// }

// ===================== HELPERS ===================== //
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function post($k,$d=null){ return $_POST[$k] ?? $d; }
function getv($k,$d=null){ return $_GET[$k] ?? $d; }
function csrf_token(){ if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(16)); return $_SESSION['csrf']; }
function check_csrf(){ if($_SERVER['REQUEST_METHOD']==='POST' && (post('csrf')!==($_SESSION['csrf']??''))) die('Bad CSRF'); }

$tab = getv('tab','dashboard');
$action = post('action') ?? getv('action');
check_csrf();

// ===================== ACTIONS ===================== //
// User
if ($action==='save_user') {
  $id = (int)post('id',0); $name = trim(post('name')); $email = trim(post('email'));
  if ($id>0) { 
    $stmt=$mysqli->prepare("UPDATE users SET name=?, email=? WHERE id=?"); 
    $stmt->bind_param('ssi',$name,$email,$id); 
  } else { 
    $stmt=$mysqli->prepare("INSERT INTO users (name, email) VALUES (?,?)"); 
    $stmt->bind_param('ss',$name,$email); 
  }
  $stmt->execute(); header('Location: ?tab=users'); exit;
}
if ($action==='delete_user') { 
  $id=(int)post('id'); 
  $stmt=$mysqli->prepare("DELETE FROM users WHERE id=?"); 
  $stmt->bind_param('i',$id); 
  $stmt->execute(); 
  header('Location:?tab=users'); 
  exit; 
}

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
  $id=(int)post('id',0); $dt=post('dt'); $type=post('type'); $category_id=(int)post('category_id')?:NULL; 
  $amount=(float)post('amount'); $account=trim(post('account'))?:NULL; $notes=trim(post('notes'))?:NULL;
  $user_id=(int)post('user_id')?:NULL; // Added user_id
  
  if ($id>0) { 
    $stmt=$mysqli->prepare("UPDATE transactions SET dt=?, type=?, category_id=?, amount=?, account=?, notes=?, user_id=? WHERE id=?"); 
    $stmt->bind_param('ssidssii',$dt,$type,$category_id,$amount,$account,$notes,$user_id,$id); 
  } else { 
    $stmt=$mysqli->prepare("INSERT INTO transactions (dt,type,category_id,amount,account,notes,user_id) VALUES (?,?,?,?,?,?,?)"); 
    $stmt->bind_param('ssidssi',$dt,$type,$category_id,$amount,$account,$notes,$user_id); 
  }
  $stmt->execute(); header('Location:?tab=transactions'); exit;
}
if ($action==='delete_txn') { $id=(int)post('id'); $stmt=$mysqli->prepare("DELETE FROM transactions WHERE id=?"); $stmt->bind_param('i',$id); $stmt->execute(); header('Location:?tab=transactions'); exit; }

// Loans
// Replace the existing save_loan action with this:
if ($action==='save_loan') {
  $id=(int)post('id',0); $name=trim(post('name')); $emi=(float)post('monthly_emi'); 
  $start=post('start_date'); $due=post('due_date')?:NULL; $end=post('end_date')?:NULL; 
  $rate=post('interest_rate')!==''?(float)post('interest_rate'):NULL; 
  $priority=(int)post('priority'); $notes=trim(post('notes'))?:NULL;
  $status=post('payment_status') ?: 'pending'; $active=(int)post('active',1);
  
  if ($id>0) { 
    $stmt=$mysqli->prepare("UPDATE loans SET name=?, monthly_emi=?, start_date=?, due_date=?, end_date=?, interest_rate=?, priority=?, notes=?, payment_status=?, active=? WHERE id=?"); 
    $stmt->bind_param('sdssssdissii',$name,$emi,$start,$due,$end,$rate,$priority,$notes,$status,$active,$id); 
  } else { 
    $stmt=$mysqli->prepare("INSERT INTO loans (name,monthly_emi,start_date,due_date,end_date,interest_rate,priority,notes,payment_status,active) VALUES (?,?,?,?,?,?,?,?,?,?)"); 
    $stmt->bind_param('sdssssdissi',$name,$emi,$start,$due,$end,$rate,$priority,$notes,$status,$active); 
  }
  $stmt->execute(); 
  
  // If it's a new loan and active, create a transaction for the current month
  if ($id === 0 && $active) {
    $currentMonth = date('Y-m-01');
    $loanName = "EMI: " . $name;
    
    // Check if this loan's EMI already exists for current month
    $checkStmt = $mysqli->prepare("SELECT id FROM transactions WHERE notes = ? AND dt BETWEEN ? AND LAST_DAY(?)");
    $checkStmt->bind_param('sss', $loanName, $currentMonth, $currentMonth);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows === 0) {
      // Create expense transaction for the EMI
      $txnStmt = $mysqli->prepare("INSERT INTO transactions (dt, type, amount, account, notes, created_at) VALUES (?, 'expense', ?, 'Loan', ?, NOW())");
      $txnStmt->bind_param('sds', $due, $emi, $loanName);
      $txnStmt->execute();
    }
  }
  
  header('Location:?tab=loans'); exit;
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
  fputcsv($out,['id','date','type','category','amount','account','notes','user']);
  $res=$mysqli->query("SELECT t.id,t.dt,t.type,c.name cat,t.amount,t.account,t.notes,u.name user FROM transactions t LEFT JOIN categories c ON t.category_id=c.id LEFT JOIN users u ON t.user_id=u.id ORDER BY t.dt DESC, t.id DESC");
  while($row=$res->fetch_assoc()){ fputcsv($out,$row); }
  fclose($out); exit;
}

// Import CSV
if ($action==='import_csv' && isset($_FILES['csv']) && is_uploaded_file($_FILES['csv']['tmp_name'])) {
  $f=fopen($_FILES['csv']['tmp_name'],'r'); $header=fgetcsv($f);
  $stmt=$mysqli->prepare("INSERT INTO transactions (dt,type,category_id,amount,account,notes,user_id) VALUES (?,?,?,?,?,?,?)");
  while(($row=fgetcsv($f))!==false){
    $date=$row[1] ?? $row[0]; $type=$row[2] ?? $row[1]; $catName=$row[3] ?? $row[2]; $amount=(float)($row[4] ?? $row[3]); $account=$row[5] ?? ($row[4] ?? NULL); $notes=$row[6] ?? ($row[5] ?? NULL); $userName=$row[7] ?? ($row[6] ?? NULL);
    $catId=NULL; if ($catName) { $find=$mysqli->prepare("SELECT id,type FROM categories WHERE name=?"); $find->bind_param('s',$catName); $find->execute(); $r=$find->get_result()->fetch_assoc(); if($r){$catId=(int)$r['id'];} else { $tguess=in_array(strtolower($catName),['salary','rent','other income'])?'income':'expense'; $ins=$mysqli->prepare("INSERT INTO categories (name,type) VALUES (?,?)"); $ins->bind_param('ss',$catName,$tguess); $ins->execute(); $catId=$ins->insert_id; }}
    
    $userId=NULL;
    if ($userName) {
      $findUser=$mysqli->prepare("SELECT id FROM users WHERE name=?"); 
      $findUser->bind_param('s',$userName); 
      $findUser->execute(); 
      $rUser=$findUser->get_result()->fetch_assoc(); 
      if($rUser){
        $userId=(int)$rUser['id'];
      } else {
        $insUser=$mysqli->prepare("INSERT INTO users (name) VALUES (?)"); 
        $insUser->bind_param('s',$userName); 
        $insUser->execute(); 
        $userId=$insUser->insert_id;
      }
    }
    
    $stmt->bind_param('ssiddsi',$date,$type,$catId,$amount,$account,$notes,$userId); 
    $stmt->execute();
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
$usersList=$mysqli->query("SELECT * FROM users ORDER BY name"); // Added users list
$txns=$mysqli->prepare("SELECT t.*, c.name cat, u.name user FROM transactions t LEFT JOIN categories c ON t.category_id=c.id LEFT JOIN users u ON t.user_id=u.id WHERE dt BETWEEN ? AND ? ORDER BY dt DESC, id DESC");
$txns->bind_param('ss',$start,$end); $txns->execute(); $txList=$txns->get_result();
$loansList=$mysqli->query("SELECT * FROM loans ORDER BY active DESC, priority ASC, id DESC");
$cardsList=$mysqli->query("SELECT * FROM credit_cards ORDER BY active DESC, name ASC");

// Recent transactions for dashboard
$recentTxns = $mysqli->prepare("
    SELECT t.*, c.name cat, u.name user 
    FROM transactions t 
    LEFT JOIN categories c ON t.category_id=c.id 
    LEFT JOIN users u ON t.user_id=u.id
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
    <title>Fixed Responsive Navbar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
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
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fb;
            color: #333;
            overflow-x: hidden;
            padding-top: 70px; /* Space for fixed navbar */
        }
        
        /* Navbar styling */
        .navbar {
            background: linear-gradient(180deg, var(--primary) 0%, var(--secondary) 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 0.5rem 1rem;
        }
        
        .navbar-brand {
            color: white !important;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .navbar-brand i {
            margin-right: 10px;
        }
        
        .navbar-nav .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            padding: 0.5rem 1rem;
            transition: var(--transition);
            border-radius: 5px;
            margin: 0 2px;
            display: flex;
            align-items: center;
        }
        
        .navbar-nav .nav-link i {
            margin-right: 8px;
            width: 20px;
            text-align: center;
        }
        
        .navbar-nav .nav-link:hover,
        .navbar-nav .nav-link.active {
            background: rgba(255, 255, 255, 0.1);
            color: white !important;
        }
        
        .navbar-toggler {
            border: none;
            color: white !important;
            font-size: 1.25rem;
        }
        
        .navbar-toggler:focus {
            box-shadow: none;
        }
        
        /* Dropdown menu for smaller screens */
        @media (max-width: 991.98px) {
            .navbar-collapse {
                background: linear-gradient(180deg, var(--primary) 0%, var(--secondary) 100%);
                padding: 10px;
                border-radius: 0 0 10px 10px;
                margin-top: 5px;
            }
            
            .navbar-nav .nav-link {
                padding: 10px 15px;
            }
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
        
        /* Responsive adjustments */
        @media (max-width: 767.98px) {
            body {
                padding-top: 60px;
            }
            
            /* Adjust KPI cards for mobile */
            .kpi-card .value {
                font-size: 20px;
            }
            
            .kpi-card .icon {
                width: 40px;
                height: 40px;
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
 <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-wallet2"></i>💰 FinManager
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" href="?tab=dashboard"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="?tab=transactions"><i class="bi bi-arrow-left-right"></i> Transactions</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="?tab=categories"><i class="bi bi-tag"></i> Categories</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="?tab=users"><i class="bi bi-people"></i> Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="?tab=loans"><i class="bi bi-currency-exchange"></i> Loans & EMIs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="?tab=cards"><i class="bi bi-credit-card"></i> Credit Cards</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="?tab=import"><i class="bi bi-upload"></i> Import/Export</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="#"><i class="bi bi-box-arrow-right"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>


<!-- Content -->
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 mt-3">
            <div>
                <h2 class="mb-0"><?= ucfirst($tab) ?></h2>
                <p class="text-muted">
                    <?php if ($tab === 'dashboard'): ?>
                        Welcome back, here's your financial overview
                    <?php elseif ($tab === 'transactions'): ?>
                        Manage your income and expenses
                    <?php elseif ($tab === 'categories'): ?>
                        Organize your transaction categories
                    <?php elseif ($tab === 'users'): ?>
                        Manage users for tracking expenses
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
                <div class="col-md-3 col-6">
                    <div class="card kpi-card">
                        <div class="icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-arrow-down-left"></i>
                        </div>
                        <div class="value text-success">₹<?= number_format($income, 2) ?></div>
                        <div class="title">Total Income</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card kpi-card">
                        <div class="icon bg-danger bg-opacity-10 text-danger">
                            <i class="bi bi-arrow-up-right"></i>
                        </div>
                        <div class="value text-danger">₹<?= number_format($expense, 2) ?></div>
                        <div class="title">Total Expenses</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card kpi-card">
                        <div class="icon bg-info bg-opacity-10 text-info">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <div class="value text-info">₹<?= number_format($net, 2) ?></div>
                        <div class="title">Net Savings</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
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
                                        <small class="text-muted"><?= h($t['dt']) ?> • <?= h($t['account'] ?? 'No Account') ?> • <?= h($t['user'] ?? 'No User') ?></small>
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
                                        <?php if ($ln['payment_status'] === 'overdue'): ?>
                                            <span class="badge bg-danger mt-1">Overdue</span>
                                        <?php endif; ?>
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
              <span>All Transactions</span>
              <div>
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#transactionModal">
                  <i class="bi bi-plus-circle"></i> Add New
                </button>
              </div>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>Date</th>
                      <th>Type</th>
                      <th>Category</th>
                      <th>Amount</th>
                      <th>Account</th>
                      <th>User</th>
                      <th>Notes</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php while($t = $txList->fetch_assoc()): ?>
                    <tr>
                      <td><?= h($t['dt']) ?></td>
                      <td><span class="badge bg-<?= $t['type']==='income' ? 'success' : 'danger' ?>"><?= ucfirst($t['type']) ?></span></td>
                      <td><?= h($t['cat'] ?? 'Uncategorized') ?></td>
                      <td class="fw-bold <?= $t['type']==='income' ? 'text-success' : 'text-danger' ?>">₹<?= number_format($t['amount'], 2) ?></td>
                      <td><?= h($t['account'] ?? '') ?></td>
                      <td><?= h($t['user'] ?? '') ?></td>
                      <td><?= h($t['notes'] ?? '') ?></td>
                      <td>
                        <div class="btn-group btn-group-sm">
                          <button class="btn btn-outline-primary" onclick="editTransaction(<?= $t['id'] ?>, '<?= h($t['dt']) ?>', '<?= h($t['type']) ?>', <?= $t['category_id'] ?? 'null' ?>, <?= $t['amount'] ?>, '<?= h($t['account'] ?? '') ?>', '<?= h($t['notes'] ?? '') ?>', <?= $t['user_id'] ?? 'null' ?>)">
                            <i class="bi bi-pencil"></i>
                          </button>
                          <form method="post" onsubmit="return confirm('Delete this transaction?')">
                            <input type="hidden" name="action" value="delete_txn">
                            <input type="hidden" name="id" value="<?= $t['id'] ?>">
                            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                            <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                          </form>
                        </div>
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

    <?php if($tab==='categories'): ?>
      <div class="row">
        <div class="col-md-6">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <span>Income Categories</span>
              <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#categoryModal" data-type="income">
                <i class="bi bi-plus-circle"></i> Add New
              </button>
            </div>
            <div class="card-body p-0">
              <div class="list-group list-group-flush">
                <?php while($c = $catsIncome->fetch_assoc()): ?>
                <div class="list-group-item d-flex justify-content-between align-items-center">
                  <span><?= h($c['name']) ?></span>
                  <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" onclick="editCategory(<?= $c['id'] ?>, '<?= h($c['name']) ?>', '<?= h($c['type']) ?>')">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <form method="post" onsubmit="return confirm('Delete this category?')">
                      <input type="hidden" name="action" value="delete_category">
                      <input type="hidden" name="id" value="<?= $c['id'] ?>">
                      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                      <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                  </div>
                </div>
                <?php endwhile; ?>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-md-6">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <span>Expense Categories</span>
              <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#categoryModal" data-type="expense">
                <i class="bi bi-plus-circle"></i> Add New
              </button>
            </div>
            <div class="card-body p-0">
              <div class="list-group list-group-flush">
                <?php while($c = $catsExpense->fetch_assoc()): ?>
                <div class="list-group-item d-flex justify-content-between align-items-center">
                  <span><?= h($c['name']) ?></span>
                  <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" onclick="editCategory(<?= $c['id'] ?>, '<?= h($c['name']) ?>', '<?= h($c['type']) ?>')">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <form method="post" onsubmit="return confirm('Delete this category?')">
                      <input type="hidden" name="action" value="delete_category">
                      <input type="hidden" name="id" value="<?= $c['id'] ?>">
                      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                      <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                  </div>
                </div>
                <?php endwhile; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <?php if($tab==='users'): ?>
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <span>Users</span>
              <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#userModal">
                <i class="bi bi-plus-circle"></i> Add New
              </button>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>ID</th>
                      <th>Name</th>
                      <th>Email</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php while($u = $usersList->fetch_assoc()): ?>
                    <tr>
                      <td><?= h($u['id']) ?></td>
                      <td><?= h($u['name']) ?></td>
                      <td><?= h($u['email']) ?></td>
                      <td>
                        <div class="btn-group btn-group-sm">
                          <button class="btn btn-outline-primary" onclick="editUser(<?= $u['id'] ?>, '<?= h($u['name']) ?>', '<?= h($u['email']) ?>')">
                            <i class="bi bi-pencil"></i>
                          </button>
                          <form method="post" onsubmit="return confirm('Delete this user?')">
                            <input type="hidden" name="action" value="delete_user">
                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                            <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                          </form>
                        </div>
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
        <div class="col-12">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <span>Loans & EMIs</span>
              <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#loanModal">
                <i class="bi bi-plus-circle"></i> Add New
              </button>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>Name</th>
                      <th>EMI Amount</th>
                      <th>Start Date</th>
                      <th>Due Date</th>
                      <th>End Date</th>
                      <th>Interest Rate</th>
                      <th>Priority</th>
                      <th>Status</th>
                      <th>Active</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php while($ln = $loansList->fetch_assoc()): ?>
                    <tr>
                      <td><?= h($ln['name']) ?></td>
                      <td>₹<?= number_format($ln['monthly_emi'], 2) ?></td>
                      <td><?= h($ln['start_date']) ?></td>
                      <td><?= h($ln['due_date'] ?? '-') ?></td>
                      <td><?= h($ln['end_date'] ?? '-') ?></td>
                      <td><?= $ln['interest_rate'] ? h($ln['interest_rate']).'%' : '-' ?></td>
                      <td><?= h($ln['priority']) ?></td>
                      <td>
                        <span class="badge bg-<?= $ln['payment_status'] === 'paid' ? 'success' : ($ln['payment_status'] === 'overdue' ? 'danger' : 'warning') ?>">
                          <?= ucfirst($ln['payment_status']) ?>
                        </span>
                      </td>
                      <td>
                        <span class="badge bg-<?= $ln['active'] ? 'success' : 'secondary' ?>">
                          <?= $ln['active'] ? 'Yes' : 'No' ?>
                        </span>
                      </td>
                      <td>
                        <div class="btn-group btn-group-sm">
                          <button class="btn btn-outline-primary" onclick="editLoan(<?= $ln['id'] ?>, '<?= h($ln['name']) ?>', <?= $ln['monthly_emi'] ?>, '<?= h($ln['start_date']) ?>', '<?= h($ln['due_date'] ?? '') ?>', '<?= h($ln['end_date'] ?? '') ?>', '<?= h($ln['interest_rate'] ?? '') ?>', <?= $ln['priority'] ?>, '<?= h($ln['notes'] ?? '') ?>', '<?= h($ln['payment_status']) ?>', <?= $ln['active'] ?>)">
                            <i class="bi bi-pencil"></i>
                          </button>
                          <form method="post" onsubmit="return confirm('Delete this loan?')">
                            <input type="hidden" name="action" value="delete_loan">
                            <input type="hidden" name="id" value="<?= $ln['id'] ?>">
                            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                            <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                          </form>
                        </div>
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
        <div class="col-12">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <span>Credit Cards</span>
              <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#cardModal">
                <i class="bi bi-plus-circle"></i> Add New
              </button>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>Name</th>
                      <th>Credit Limit</th>
                      <th>Interest Rate</th>
                      <th>Closing Day</th>
                      <th>Due Day</th>
                      <th>Active</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php while($cc = $cardsList->fetch_assoc()): ?>
                    <tr>
                      <td><?= h($cc['name']) ?></td>
                      <td>₹<?= $cc['credit_limit'] ? number_format($cc['credit_limit'], 2) : '-' ?></td>
                      <td><?= $cc['interest_rate'] ? h($cc['interest_rate']).'%' : '-' ?></td>
                      <td><?= $cc['closing_day'] ? h($cc['closing_day']) : '-' ?></td>
                      <td><?= $cc['due_day'] ? h($cc['due_day']) : '-' ?></td>
                      <td>
                        <span class="badge bg-<?= $cc['active'] ? 'success' : 'secondary' ?>">
                          <?= $cc['active'] ? 'Yes' : 'No' ?>
                        </span>
                      </td>
                      <td>
                        <div class="btn-group btn-group-sm">
                          <button class="btn btn-outline-primary" onclick="editCard(<?= $cc['id'] ?>, '<?= h($cc['name']) ?>', '<?= h($cc['credit_limit'] ?? '') ?>', '<?= h($cc['interest_rate'] ?? '') ?>', '<?= h($cc['closing_day'] ?? '') ?>', '<?= h($cc['due_day'] ?? '') ?>', <?= $cc['active'] ?>)">
                            <i class="bi bi-pencil"></i>
                          </button>
                          <form method="post" onsubmit="return confirm('Delete this card?')">
                            <input type="hidden" name="action" value="delete_cc">
                            <input type="hidden" name="id" value="<?= $cc['id'] ?>">
                            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                            <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                          </form>
                        </div>
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
            <div class="card-header">Export Data</div>
            <div class="card-body">
              <p>Export all transactions to a CSV file for backup or analysis.</p>
              <form method="post">
                <input type="hidden" name="action" value="export_csv">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <button type="submit" class="btn btn-primary">
                  <i class="bi bi-download"></i> Download CSV
                </button>
              </form>
            </div>
          </div>
        </div>
        
        <div class="col-md-6">
          <div class="card">
            <div class="card-header">Import Data</div>
            <div class="card-body">
              <p>Import transactions from a CSV file. The CSV should have columns: date, type, category, amount, account, notes, user.</p>
              <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="import_csv">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <div class="mb-3">
                  <input type="file" class="form-control" name="csv" accept=".csv">
                </div>
                <button type="submit" class="btn btn-primary">
                  <i class="bi bi-upload"></i> Upload CSV
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Transaction Modal -->
  <div class="modal fade" id="transactionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="transactionModalLabel">Add Transaction</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="post">
          <input type="hidden" name="action" value="save_txn">
          <input type="hidden" name="id" id="txn_id" value="0">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <div class="modal-body">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Date</label>
                <input type="date" class="form-control" name="dt" value="<?= date('Y-m-d') ?>" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Type</label>
                <select class="form-select" name="type" required>
                  <option value="income">Income</option>
                  <option value="expense">Expense</option>
                </select>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Category</label>
                <select class="form-select" name="category_id" id="category_select" required>
                  <option value="">-- Select Category --</option>
                  <optgroup label="Income">
                    <?php $catsIncome->data_seek(0); while($c = $catsIncome->fetch_assoc()): ?>
                      <option value="<?= $c['id'] ?>"><?= h($c['name']) ?></option>
                    <?php endwhile; ?>
                  </optgroup>
                  <optgroup label="Expense">
                    <?php $catsExpense->data_seek(0); while($c = $catsExpense->fetch_assoc()): ?>
                      <option value="<?= $c['id'] ?>"><?= h($c['name']) ?></option>
                    <?php endwhile; ?>
                  </optgroup>
                </select>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Amount (₹)</label>
                <input type="number" step="0.01" class="form-control" name="amount" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Account</label>
                <input type="text" class="form-control" name="account">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">User</label>
                <select class="form-select" name="user_id">
                  <option value="">-- Select User --</option>
                  <?php $usersList->data_seek(0); while($u = $usersList->fetch_assoc()): ?>
                    <option value="<?= $u['id'] ?>"><?= h($u['name']) ?></option>
                  <?php endwhile; ?>
                </select>
              </div>
              <div class="col-12 mb-3">
                <label class="form-label">Notes</label>
                <textarea class="form-control" name="notes" rows="2"></textarea>
              </div>
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

  <!-- Category Modal -->
  <div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="categoryModalLabel">Add Category</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="post">
          <input type="hidden" name="action" value="save_category">
          <input type="hidden" name="id" id="cat_id" value="0">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Name</label>
              <input type="text" class="form-control" name="name" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Type</label>
              <select class="form-select" name="type" required>
                <option value="income">Income</option>
                <option value="expense">Expense</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Save Category</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- User Modal -->
  <div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="userModalLabel">Add User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="post">
          <input type="hidden" name="action" value="save_user">
          <input type="hidden" name="id" id="user_id" value="0">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Name</label>
              <input type="text" class="form-control" name="name" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" name="email">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Save User</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Loan Modal -->
  <div class="modal fade" id="loanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="loanModalLabel">Add Loan/EMI</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="post">
          <input type="hidden" name="action" value="save_loan">
          <input type="hidden" name="id" id="loan_id" value="0">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <div class="modal-body">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Name</label>
                <input type="text" class="form-control" name="name" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Monthly EMI (₹)</label>
                <input type="number" step="0.01" class="form-control" name="monthly_emi" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Start Date</label>
                <input type="date" class="form-control" name="start_date" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Due Date (each month)</label>
                <input type="date" class="form-control" name="due_date">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">End Date (if any)</label>
                <input type="date" class="form-control" name="end_date">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Interest Rate (%)</label>
                <input type="number" step="0.01" class="form-control" name="interest_rate">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Priority (1-10)</label>
                <input type="number" min="1" max="10" class="form-control" name="priority" value="5">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Payment Status</label>
                <select class="form-select" name="payment_status">
                  <option value="pending">Pending</option>
                  <option value="paid">Paid</option>
                  <option value="overdue">Overdue</option>
                </select>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Active</label>
                <select class="form-select" name="active">
                  <option value="1">Yes</option>
                  <option value="0">No</option>
                </select>
              </div>
              <div class="col-12 mb-3">
                <label class="form-label">Notes</label>
                <textarea class="form-control" name="notes" rows="2"></textarea>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Save Loan</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Credit Card Modal -->
  <div class="modal fade" id="cardModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="cardModalLabel">Add Credit Card</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="post">
          <input type="hidden" name="action" value="save_cc">
          <input type="hidden" name="id" id="card_id" value="0">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <div class="modal-body">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Card Name</label>
                <input type="text" class="form-control" name="name" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Credit Limit (₹)</label>
                <input type="number" step="0.01" class="form-control" name="credit_limit">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Interest Rate (%)</label>
                <input type="number" step="0.01" class="form-control" name="interest_rate">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Closing Day</label>
                <input type="number" min="1" max="31" class="form-control" name="closing_day">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Due Day</label>
                <input type="number" min="1" max="31" class="form-control" name="due_day">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Active</label>
                <select class="form-select" name="active">
                  <option value="1">Yes</option>
                  <option value="0">No</option>
                </select>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Save Card</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Sidebar toggle functionality
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const closeSidebar = document.getElementById('closeSidebar');
    
    function toggleSidebar() {
      sidebar.classList.toggle('active');
      overlay.classList.toggle('active');
    }
    
    sidebarToggle.addEventListener('click', toggleSidebar);
    closeSidebar.addEventListener('click', toggleSidebar);
    overlay.addEventListener('click', toggleSidebar);
    
    // Edit functions
    function editTransaction(id, dt, type, categoryId, amount, account, notes, userId) {
      document.getElementById('txn_id').value = id;
      document.querySelector('input[name="dt"]').value = dt;
      document.querySelector('select[name="type"]').value = type;
      document.querySelector('select[name="category_id"]').value = categoryId || '';
      document.querySelector('input[name="amount"]').value = amount;
      document.querySelector('input[name="account"]').value = account || '';
      document.querySelector('select[name="user_id"]').value = userId || '';
      document.querySelector('textarea[name="notes"]').value = notes || '';
      
      const modal = new bootstrap.Modal(document.getElementById('transactionModal'));
      modal.show();
    }
    
    function editCategory(id, name, type) {
      document.getElementById('cat_id').value = id;
      document.querySelector('#categoryModal input[name="name"]').value = name;
      document.querySelector('#categoryModal select[name="type"]').value = type;
      
      const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
      modal.show();
    }
    
    function editUser(id, name, email) {
      document.getElementById('user_id').value = id;
      document.querySelector('#userModal input[name="name"]').value = name;
      document.querySelector('#userModal input[name="email"]').value = email || '';
      
      const modal = new bootstrap.Modal(document.getElementById('userModal'));
      modal.show();
    }
    
    function editLoan(id, name, emi, start, due, end, rate, priority, notes, status, active) {
      document.getElementById('loan_id').value = id;
      document.querySelector('#loanModal input[name="name"]').value = name;
      document.querySelector('#loanModal input[name="monthly_emi"]').value = emi;
      document.querySelector('#loanModal input[name="start_date"]').value = start;
      document.querySelector('#loanModal input[name="due_date"]').value = due || '';
      document.querySelector('#loanModal input[name="end_date"]').value = end || '';
      document.querySelector('#loanModal input[name="interest_rate"]').value = rate || '';
      document.querySelector('#loanModal input[name="priority"]').value = priority;
      document.querySelector('#loanModal textarea[name="notes"]').value = notes || '';
      document.querySelector('#loanModal select[name="payment_status"]').value = status;
      document.querySelector('#loanModal select[name="active"]').value = active;
      
      const modal = new bootstrap.Modal(document.getElementById('loanModal'));
      modal.show();
    }
    
    function editCard(id, name, limit, rate, closing, due, active) {
      document.getElementById('card_id').value = id;
      document.querySelector('#cardModal input[name="name"]').value = name;
      document.querySelector('#cardModal input[name="credit_limit"]').value = limit || '';
      document.querySelector('#cardModal input[name="interest_rate"]').value = rate || '';
      document.querySelector('#cardModal input[name="closing_day"]').value = closing || '';
      document.querySelector('#cardModal input[name="due_day"]').value = due || '';
      document.querySelector('#cardModal select[name="active"]').value = active;
      
      const modal = new bootstrap.Modal(document.getElementById('cardModal'));
      modal.show();
    }
    
    // Initialize modals with type data
    document.addEventListener('DOMContentLoaded', function() {
      const categoryModal = document.getElementById('categoryModal');
      if (categoryModal) {
        categoryModal.addEventListener('show.bs.modal', function (event) {
          const button = event.relatedTarget;
          const type = button.getAttribute('data-type');
          if (type) {
            categoryModal.querySelector('select[name="type"]').value = type;
          }
        });
      }
    });
    
    // Charts
    <?php if($tab==='dashboard'): ?>
    document.addEventListener('DOMContentLoaded', function() {
      // Financial Overview Chart
      const financialCtx = document.getElementById('financialChart').getContext('2d');
      const financialChart = new Chart(financialCtx, {
        type: 'line',
        data: {
          labels: <?= json_encode($labels) ?>,
          datasets: [{
            label: 'Net Savings',
            data: <?= json_encode($series) ?>,
            borderColor: '#4361ee',
            backgroundColor: 'rgba(67, 97, 238, 0.1)',
            fill: true,
            tension: 0.3
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            },
            tooltip: {
              mode: 'index',
              intersect: false
            }
          },
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
              '#4361ee', '#3f37c9', '#4cc9f0', '#4895ef', '#560bad',
              '#7209b7', '#b5179e', '#f72585', '#f8961e', '#4cc9f0'
            ]
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom'
            }
          }
        }
      });
    });
    <?php endif; ?>
  </script>
</body>
</html>