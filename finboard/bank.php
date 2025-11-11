<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Statement Analyzer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Add SheetJS library for Excel parsing -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar-brand {
            font-weight: 700;
            color: #2c3e50 !important;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            border: none;
        }
        .card-header {
            background-color: #2c3e50;
            color: white;
            border-radius: 10px 10px 0 0 !important;
            font-weight: 600;
        }
        .summary-card {
            text-align: center;
            padding: 15px;
            transition: transform 0.3s;
        }
        .summary-card:hover {
            transform: translateY(-5px);
        }
        .income {
            border-bottom: 4px solid #28a745;
        }
        .expense {
            border-bottom: 4px solid #dc3545;
        }
        .balance {
            border-bottom: 4px solid #007bff;
        }
        .transaction-table {
            font-size: 0.9rem;
        }
        .transaction-table th {
            background-color: #2c3e50;
            color: white;
        }
        .positive-amount {
            color: #28a745;
            font-weight: 600;
        }
        .negative-amount {
            color: #dc3545;
            font-weight: 600;
        }
        .upload-area {
            border: 2px dashed #6c757d;
            border-radius: 10px;
            padding: 25px;
            text-align: center;
            background-color: #f8f9fa;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .upload-area:hover {
            background-color: #e9ecef;
        }
        .category-badge {
            font-size: 0.75rem;
            padding: 0.35em 0.65em;
        }
        .analysis-section {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        #file-name {
            font-weight: 600;
            color: #2c3e50;
        }
        .pagination .page-item.active .page-link {
            background-color: #2c3e50;
            border-color: #2c3e50;
        }
        .pagination .page-link {
            color: #2c3e50;
        }
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }
        .period-selector {
            background-color: #e9ecef;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
   <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-chart-pie me-2"></i>Bank Statement Analyzer
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#"><i class="fas fa-home me-1"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-history me-1"></i> History</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-cog me-1"></i> Settings</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

 <div class="container mt-4">
        <!-- Period Selector -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-calendar me-2"></i>Select Period
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="period-selector">
                            <form id="period-form">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="month-select" class="form-label">Month</label>
                                        <select class="form-select" id="month-select">
                                            <option value="1">January</option>
                                            <option value="2">February</option>
                                            <option value="3">March</option>
                                            <option value="4">April</option>
                                            <option value="5">May</option>
                                            <option value="6">June</option>
                                            <option value="7">July</option>
                                            <option value="8">August</option>
                                            <option value="9">September</option>
                                            <option value="10">October</option>
                                            <option value="11">November</option>
                                            <option value="12">December</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="year-select" class="form-label">Year</label>
                                        <select class="form-select" id="year-select">
                                            <?php
                                            $currentYear = date('Y');
                                            for ($year = $currentYear; $year >= $currentYear - 5; $year--) {
                                                echo "<option value='$year'>$year</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary mt-3">
                                    <i class="fas fa-search me-1"></i> Load Statement
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div id="period-status">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <span id="status-message">Select a month and year to view or upload a statement</span>
                            </div>
                            <div id="upload-prompt" class="d-none">
                                <p>No statement found for the selected period.</p>
                                <button class="btn btn-success" id="upload-for-period">
                                    <i class="fas fa-upload me-1"></i> Upload Statement
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upload Section -->
        <div class="card mb-4" id="upload-section">
            <div class="card-header">
                <i class="fas fa-file-upload me-2"></i>Upload Bank Statement
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="upload-area" id="drop-zone">
                            <i class="fas fa-cloud-upload-alt fa-3x text-secondary mb-3"></i>
                            <h5>Drag & Drop your statement file</h5>
                            <p class="text-muted">Supported formats: XLS, XLSX, CSV</p>
                            <input type="file" class="d-none" id="file-input" accept=".xls,.xlsx,.csv">
                            <button class="btn btn-primary mt-2" onclick="document.getElementById('file-input').click()">
                                <i class="fas fa-file me-1"></i> Browse Files
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle me-2"></i>Instructions</h6>
                            <ul class="mb-0 ps-3">
                                <li>Upload your bank statement in Excel or CSV format</li>
                                <li>Ensure it contains Date, Description, Withdrawal, Deposit, and Balance columns</li>
                                <li>Your data is processed and stored for future reference</li>
                            </ul>
                        </div>
                        <div id="file-info" class="d-none">
                            <p>File: <span id="file-name">No file selected</span></p>
                            <p>Period: <span id="selected-period">Not specified</span></p>
                            <button class="btn btn-success" id="process-btn">
                                <i class="fas fa-cog me-1"></i> Process & Save Statement
                            </button>
                        </div>
                    </div>
                </div>
                <div class="loading mt-3" id="loading-indicator">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Processing and saving your statement...</p>
                </div>
            </div>
        </div>


        <!-- Summary Cards -->
         <div class="row" id="summary-cards" style="display: none;">
            <div class="col-md-4">
                <div class="card summary-card income">
                    <div class="card-body">
                        <h6 class="card-title text-muted">TOTAL INCOME</h6>
                        <h3 id="total-income" class="positive-amount">₹0.00</h3>
                        <p id="income-count" class="card-text">From 0 deposits</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card summary-card expense">
                    <div class="card-body">
                        <h6 class="card-title text-muted">TOTAL EXPENSES</h6>
                        <h3 id="total-expense" class="negative-amount">₹0.00</h3>
                        <p id="expense-count" class="card-text">From 0 transactions</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card summary-card balance">
                    <div class="card-body">
                        <h6 class="card-title text-muted">CLOSING BALANCE</h6>
                        <h3 id="closing-balance">₹0.00</h3>
                        <p id="opening-balance" class="card-text">From ₹0.00 opening</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts and Analysis -->
        <div class="row mt-4" id="analysis-section" style="display: none;">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-pie me-2"></i>Expense Categories
                    </div>
                    <div class="card-body">
                        <canvas id="categoryChart" height="250"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-line me-2"></i>Balance Trend
                    </div>
                    <div class="card-body">
                        <canvas id="balanceChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaction Table -->
        <div class="card mt-4" id="transaction-table" style="display: none;">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-table me-2"></i>Transaction History
                </div>
                <div>
                    <input type="text" class="form-control form-control-sm" placeholder="Search transactions..." id="search-input">
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0 transaction-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Reference</th>
                                <th class="text-end">Withdrawal</th>
                                <th class="text-end">Deposit</th>
                                <th class="text-end">Balance</th>
                                <th>Category</th>
                            </tr>
                        </thead>
                        <tbody id="transaction-body">
                            <!-- Transactions will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <div id="showing-info">Showing 0 of 0 transactions</div>
                    <ul class="pagination mb-0" id="pagination">
                        <!-- Pagination will be generated by JavaScript -->
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white text-center py-3 mt-5">
        <p class="mb-0">Bank Statement Analyzer &copy; 2025. All rights reserved.</p>
    </footer>

     <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Global variables
        let allTransactions = [];
        let filteredTransactions = [];
        let currentPage = 1;
        const transactionsPerPage = 10;
        let categoryChart = null;
        let balanceChart = null;
        let selectedMonth = new Date().getMonth() + 1;
        let selectedYear = new Date().getFullYear();

        document.addEventListener('DOMContentLoaded', function() {
            // Set current month and year in selectors
            document.getElementById('month-select').value = selectedMonth;
            document.getElementById('year-select').value = selectedYear;
            
            // Check if statement exists for current period
            checkStatementExists(selectedMonth, selectedYear);
            
            // Initialize the drop zone
            const dropZone = document.getElementById('drop-zone');
            const fileInput = document.getElementById('file-input');
            const processBtn = document.getElementById('process-btn');
            const periodForm = document.getElementById('period-form');
            const uploadForPeriodBtn = document.getElementById('upload-for-period');
            
            // Event listeners
            periodForm.addEventListener('submit', function(e) {
                e.preventDefault();
                selectedMonth = document.getElementById('month-select').value;
                selectedYear = document.getElementById('year-select').value;
                checkStatementExists(selectedMonth, selectedYear);
            });
            
            uploadForPeriodBtn.addEventListener('click', function() {
                document.getElementById('upload-section').scrollIntoView();
                updateSelectedPeriodText();
            });
            
            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropZone.style.backgroundColor = '#e9ecef';
            });
            
            dropZone.addEventListener('dragleave', () => {
                dropZone.style.backgroundColor = '#f8f9fa';
            });
            
            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropZone.style.backgroundColor = '#f8f9fa';
                if (e.dataTransfer.files.length) {
                    fileInput.files = e.dataTransfer.files;
                    handleFileSelection();
                }
            });
            
            fileInput.addEventListener('change', handleFileSelection);
            
            processBtn.addEventListener('click', function() {
                processFile(fileInput.files[0]);
            });
            
            function handleFileSelection() {
                if (fileInput.files.length) {
                    const file = fileInput.files[0];
                    document.getElementById('file-name').textContent = file.name;
                    updateSelectedPeriodText();
                    document.getElementById('file-info').classList.remove('d-none');
                }
            }
            
            function updateSelectedPeriodText() {
                const monthNames = ["January", "February", "March", "April", "May", "June",
                    "July", "August", "September", "October", "November", "December"];
                document.getElementById('selected-period').textContent = 
                    `${monthNames[selectedMonth - 1]} ${selectedYear}`;
            }
        });

        function checkStatementExists(month, year) {
            // In a real implementation, this would make an AJAX call to the server
            // For this example, we'll simulate checking with the server
            
            // Show loading state
            document.getElementById('status-message').innerHTML = 
                '<i class="fas fa-spinner fa-spin me-2"></i>Checking for statement...';
            
            // Simulate server request with timeout
            setTimeout(() => {
                // This would be replaced with actual AJAX call
                // For demo purposes, we'll randomly return exists or not
                const exists = Math.random() > 0.5;
                
                if (exists) {
                    document.getElementById('status-message').textContent = 
                        `Statement found for ${getMonthName(month)} ${year}. Loading...`;
                    loadStatementFromServer(month, year);
                    document.getElementById('upload-prompt').classList.add('d-none');
                } else {
                    document.getElementById('status-message').textContent = 
                        `No statement found for ${getMonthName(month)} ${year}.`;
                    document.getElementById('upload-prompt').classList.remove('d-none');
                    // Hide analysis sections if shown
                    document.getElementById('summary-cards').style.display = 'none';
                    document.getElementById('analysis-section').style.display = 'none';
                    document.getElementById('transaction-table').style.display = 'none';
                }
            }, 1000);
        }

        function getMonthName(monthNumber) {
            const months = ["January", "February", "March", "April", "May", "June",
                "July", "August", "September", "October", "November", "December"];
            return months[monthNumber - 1];
        }

        function loadStatementFromServer(month, year) {
            // Show loading indicator
            document.getElementById('loading-indicator').style.display = 'block';
            
            // In a real implementation, this would make an AJAX call to retrieve the saved data
            setTimeout(() => {
                // Simulate server response with sample data
                // In reality, this would come from your database
                const sampleData = {
                    success: true,
                    statement: {
                        month: month,
                        year: year,
                        totalIncome: 25430.50,
                        totalExpense: 18765.25,
                        openingBalance: 54321.67,
                        closingBalance: 61012.92,
                        transactions: [
                            // Sample transactions would be here
                        ]
                    }
                };
                
                if (sampleData.success) {
                    // Update UI with the retrieved data
                    updateSummaryCards(
                        sampleData.statement.totalIncome,
                        sampleData.statement.totalExpense,
                        sampleData.statement.openingBalance,
                        sampleData.statement.closingBalance
                    );
                    
                    // For this demo, we'll just show the summary with sample data
                    // In reality, you would process the transactions and render charts
                    
                    document.getElementById('loading-indicator').style.display = 'none';
                    document.getElementById('summary-cards').style.display = 'flex';
                    document.getElementById('analysis-section').style.display = 'none';
                    document.getElementById('transaction-table').style.display = 'none';
                    
                    document.getElementById('status-message').textContent = 
                        `Statement for ${getMonthName(month)} ${year} loaded successfully.`;
                } else {
                    document.getElementById('status-message').textContent = 
                        'Error loading statement. Please try again.';
                    document.getElementById('loading-indicator').style.display = 'none';
                }
            }, 1500);
        }


        function processFile(file) {
            if (!file) return;
            
            // Show loading indicator
            document.getElementById('loading-indicator').style.display = 'block';
            
            const reader = new FileReader();
            
            reader.onload = function(e) {
                try {
                    const data = e.target.result;
                    let workbook;
                    
                    // Check file extension to determine how to process
                    if (file.name.endsWith('.xlsx') || file.name.endsWith('.xls')) {
                        workbook = XLSX.read(data, { type: 'binary' });
                    } else if (file.name.endsWith('.csv')) {
                        // For CSV files, we'll convert to workbook format
                        const csvData = XLSX.read(data, { type: 'binary', cellDates: true, cellNF: false, cellText: false });
                        workbook = csvData;
                    } else {
                        throw new Error('Unsupported file format');
                    }
                    
                    // Process the workbook
                    processWorkbook(workbook, file);
                    
                } catch (error) {
                    console.error('Error processing file:', error);
                    alert('Error processing file: ' + error.message);
                    document.getElementById('loading-indicator').style.display = 'none';
                }
            };
            
            reader.onerror = function() {
                alert('Error reading file');
                document.getElementById('loading-indicator').style.display = 'none';
            };
            
            reader.readAsBinaryString(file);
        }

        function processWorkbook(workbook, file) {
            // Get the first worksheet
            const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
            
            // Convert to JSON
            const jsonData = XLSX.utils.sheet_to_json(firstSheet, { header: 1 });
            
            // Process the data based on HDFC format (we'll need to adjust for other banks)
            processHDFCData(jsonData, file);
        }

        function processHDFCData(data, file) {
            // Find the transaction data in the HDFC format
            let transactions = [];
            let startRow = -1;
            
            // Look for the transaction header row
            for (let i = 0; i < data.length; i++) {
                if (Array.isArray(data[i]) && data[i].length >= 7) {
                    if (data[i][0] === 'Date' && data[i][1] === 'Narration') {
                        startRow = i + 2; // Skip the header and the line of asterisks
                        break;
                    }
                }
            }
            
            if (startRow === -1) {
                alert('Could not find transaction data in the expected format.');
                document.getElementById('loading-indicator').style.display = 'none';
                return;
            }
            
            // Extract transactions until we hit the end marker
            for (let i = startRow; i < data.length; i++) {
                if (!Array.isArray(data[i]) || data[i].length < 7) continue;
                
                // Check for end of transactions (line of asterisks)
                if (typeof data[i][0] === 'string' && data[i][0].startsWith('********')) break;
                
                const row = data[i];
                
                // Create transaction object
                const transaction = {
                    date: formatDate(row[0]),
                    description: row[1] || '',
                    reference: row[2] || '',
                    valueDate: formatDate(row[3]),
                    withdrawal: row[4] ? parseFloat(row[4]) : 0,
                    deposit: row[5] ? parseFloat(row[5]) : 0,
                    balance: row[6] ? parseFloat(String(row[6]).replace(/,/g, '')) : 0,
                    category: categorizeTransaction(row[1] || '')
                };
                
                transactions.push(transaction);
            }
            
            // Find opening and closing balances from the summary
            let openingBalance = 0;
            let closingBalance = 0;
            let totalWithdrawal = 0;
            let totalDeposit = 0;
            
            for (let i = data.length - 1; i >= 0; i--) {
                if (Array.isArray(data[i]) && data[i].length >= 3) {
                    if (data[i][0] === 'Opening Balance') {
                        openingBalance = parseFloat(String(data[i+1][0]).replace(/,/g, ''));
                        totalWithdrawal = parseFloat(String(data[i+1][4]).replace(/,/g, ''));
                        totalDeposit = parseFloat(String(data[i+1][5]).replace(/,/g, ''));
                        closingBalance = parseFloat(String(data[i+1][6]).replace(/,/g, ''));
                        break;
                    }
                }
            }
            
            // Set the global transactions array
            allTransactions = transactions.reverse(); // Reverse to show latest first
            filteredTransactions = [...allTransactions];
            
            // Save to server
            saveToServer(file, allTransactions, totalDeposit, totalWithdrawal, openingBalance, closingBalance);
        }

        function saveToServer(file, transactions, totalIncome, totalExpense, openingBalance, closingBalance) {
            // In a real implementation, this would send data to the server via AJAX
            // For this example, we'll simulate the server request
            
            // Create form data to send to server
            const formData = new FormData();
            formData.append('month', selectedMonth);
            formData.append('year', selectedYear);
            formData.append('file', file);
            formData.append('transactions', JSON.stringify(transactions));
            formData.append('totalIncome', totalIncome);
            formData.append('totalExpense', totalExpense);
            formData.append('openingBalance', openingBalance);
            formData.append('closingBalance', closingBalance);
            
            // Simulate AJAX call with timeout
            setTimeout(() => {
                // This would be replaced with actual AJAX call
                // For demo purposes, we'll assume it was successful
                const success = true;
                
                if (success) {
                    // Update UI with the processed data
                    updateSummaryCards(totalIncome, totalExpense, openingBalance, closingBalance);
                    initCharts();
                    renderTransactions();
                    setupPagination();
                    setupSearch();
                    
                    // Hide loading indicator and show results
                    document.getElementById('loading-indicator').style.display = 'none';
                    document.getElementById('summary-cards').style.display = 'flex';
                    document.getElementById('analysis-section').style.display = 'flex';
                    document.getElementById('transaction-table').style.display = 'block';
                    
                    // Update status message
                    document.getElementById('status-message').textContent = 
                        `Statement for ${getMonthName(selectedMonth)} ${selectedYear} saved successfully.`;
                    document.getElementById('upload-prompt').classList.add('d-none');
                } else {
                    alert('Error saving statement to server. Please try again.');
                    document.getElementById('loading-indicator').style.display = 'none';
                }
            }, 2000);
        }

        function formatDate(dateValue) {
            if (!dateValue) return '';
            
            // If it's already a string, return as is
            if (typeof dateValue === 'string') return dateValue;
            
            // If it's a date object, format it
            if (dateValue instanceof Date) {
                return dateValue.toLocaleDateString('en-GB'); // DD/MM/YYYY format
            }
            
            return String(dateValue);
        }

        function categorizeTransaction(description) {
            description = description.toLowerCase();
            
            if (description.includes('loan') || description.includes('emi')) return 'Loan';
            if (description.includes('food') || description.includes('sweet') || description.includes('restaurant')) return 'Food';
            if (description.includes('shopping') || description.includes('store') || description.includes('market')) return 'Shopping';
            if (description.includes('fuel') || description.includes('petrol') || description.includes('diesel')) return 'Transport';
            if (description.includes('electricity') || description.includes('water') || description.includes('bill')) return 'Utilities';
            if (description.includes('movie') || description.includes('entertainment')) return 'Entertainment';
            if (description.includes('medical') || description.includes('pharmacy')) return 'Healthcare';
            if (description.includes('transfer') || description.includes('upi')) return 'Transfer';
            if (description.includes('salary') || description.includes('income')) return 'Income';
            
            return 'Other';
        }

        function updateSummaryCards(totalIncome, totalExpense, openingBalance, closingBalance) {
            // Count income and expense transactions
            const incomeCount = allTransactions.filter(t => t.deposit > 0).length;
            const expenseCount = allTransactions.filter(t => t.withdrawal > 0).length;
            
            // Update the summary cards
            document.getElementById('total-income').textContent = '₹' + totalIncome.toLocaleString('en-IN', { maximumFractionDigits: 2 });
            document.getElementById('income-count').textContent = `From ${incomeCount} deposits`;
            
            document.getElementById('total-expense').textContent = '₹' + totalExpense.toLocaleString('en-IN', { maximumFractionDigits: 2 });
            document.getElementById('expense-count').textContent = `From ${expenseCount} transactions`;
            
            document.getElementById('closing-balance').textContent = '₹' + closingBalance.toLocaleString('en-IN', { maximumFractionDigits: 2 });
            document.getElementById('opening-balance').textContent = `From ₹${openingBalance.toLocaleString('en-IN', { maximumFractionDigits: 2 })} opening`;
        }

        function initCharts() {
            // Destroy existing charts if they exist
            if (categoryChart) {
                categoryChart.destroy();
            }
            if (balanceChart) {
                balanceChart.destroy();
            }
            
            // Prepare data for category chart
            const categoryData = calculateCategoryData();
            
            // Expense Categories Chart
            const categoryCtx = document.getElementById('categoryChart').getContext('2d');
            categoryChart = new Chart(categoryCtx, {
                type: 'pie',
                data: {
                    labels: Object.keys(categoryData),
                    datasets: [{
                        data: Object.values(categoryData),
                        backgroundColor: [
                            '#ff6384', '#36a2eb', '#ffce56', '#4bc0c0', '#9966ff', '#ff9f40', '#8ac249', '#f67019', '#bd7ebe', '#2c3e50'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ₹${value.toLocaleString('en-IN')} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });

            // Prepare data for balance trend chart
            const balanceData = calculateBalanceTrendData();
            
            // Balance Trend Chart
            const balanceCtx = document.getElementById('balanceChart').getContext('2d');
            balanceChart = new Chart(balanceCtx, {
                type: 'line',
                data: {
                    labels: balanceData.dates,
                    datasets: [{
                        label: 'Account Balance',
                        data: balanceData.balances,
                        borderColor: '#2c3e50',
                        backgroundColor: 'rgba(44, 62, 80, 0.1)',
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: false,
                            ticks: {
                                callback: function(value) {
                                    return '₹' + value.toLocaleString('en-IN');
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Balance: ₹' + context.raw.toLocaleString('en-IN');
                                }
                            }
                        }
                    }
                }
            });
        }

        function calculateCategoryData() {
            const categories = {};
            
            allTransactions.forEach(transaction => {
                if (transaction.withdrawal > 0) { // Only expenses
                    const category = transaction.category;
                    if (!categories[category]) {
                        categories[category] = 0;
                    }
                    categories[category] += transaction.withdrawal;
                }
            });
            
            return categories;
        }

        function calculateBalanceTrendData() {
            // We'll sample up to 10 balance points to show the trend
            const sampleSize = Math.min(10, allTransactions.length);
            const step = Math.floor(allTransactions.length / sampleSize);
            
            const dates = [];
            const balances = [];
            
            for (let i = 0; i < allTransactions.length; i += step) {
                if (dates.length >= sampleSize) break;
                
                dates.push(allTransactions[i].date);
                balances.push(allTransactions[i].balance);
            }
            
            // Make sure we include the last transaction
            if (allTransactions.length > 0 && dates[dates.length-1] !== allTransactions[allTransactions.length-1].date) {
                dates.push(allTransactions[allTransactions.length-1].date);
                balances.push(allTransactions[allTransactions.length-1].balance);
            }
            
            return { dates, balances };
        }

        function renderTransactions() {
            const transactionBody = document.getElementById('transaction-body');
            transactionBody.innerHTML = '';
            
            const startIndex = (currentPage - 1) * transactionsPerPage;
            const endIndex = Math.min(startIndex + transactionsPerPage, filteredTransactions.length);
            
            for (let i = startIndex; i < endIndex; i++) {
                const transaction = filteredTransactions[i];
                const row = document.createElement('tr');
                
                row.innerHTML = `
                    <td>${transaction.date}</td>
                    <td>${transaction.description}</td>
                    <td>${transaction.reference}</td>
                    <td class="text-end ${transaction.withdrawal ? 'negative-amount' : ''}">${transaction.withdrawal ? '₹' + transaction.withdrawal.toLocaleString('en-IN') : ''}</td>
                    <td class="text-end ${transaction.deposit ? 'positive-amount' : ''}">${transaction.deposit ? '₹' + transaction.deposit.toLocaleString('en-IN') : ''}</td>
                    <td class="text-end">₹${transaction.balance.toLocaleString('en-IN')}</td>
                    <td><span class="badge bg-secondary category-badge">${transaction.category}</span></td>
                `;
                
                transactionBody.appendChild(row);
            }
            
            // Update showing info
            document.getElementById('showing-info').textContent = 
                `Showing ${startIndex + 1} to ${endIndex} of ${filteredTransactions.length} transactions`;
        }

        function setupPagination() {
            const paginationElement = document.getElementById('pagination');
            const pageCount = Math.ceil(filteredTransactions.length / transactionsPerPage);
            
            paginationElement.innerHTML = '';
            
            // Previous button
            const prevLi = document.createElement('li');
            prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
            prevLi.innerHTML = `<a class="page-link" href="#">Previous</a>`;
            prevLi.addEventListener('click', (e) => {
                e.preventDefault();
                if (currentPage > 1) {
                    currentPage--;
                    renderTransactions();
                    setupPagination();
                }
            });
            paginationElement.appendChild(prevLi);
            
            // Page buttons
            for (let i = 1; i <= pageCount; i++) {
                const pageLi = document.createElement('li');
                pageLi.className = `page-item ${currentPage === i ? 'active' : ''}`;
                pageLi.innerHTML = `<a class="page-link" href="#">${i}</a>`;
                pageLi.addEventListener('click', (e) => {
                    e.preventDefault();
                    currentPage = i;
                    renderTransactions();
                    setupPagination();
                });
                paginationElement.appendChild(pageLi);
            }
            
            // Next button
            const nextLi = document.createElement('li');
            nextLi.className = `page-item ${currentPage === pageCount ? 'disabled' : ''}`;
            nextLi.innerHTML = `<a class="page-link" href="#">Next</a>`;
            nextLi.addEventListener('click', (e) => {
                e.preventDefault();
                if (currentPage < pageCount) {
                    currentPage++;
                    renderTransactions();
                    setupPagination();
                }
            });
            paginationElement.appendChild(nextLi);
        }

        function setupSearch() {
            const searchInput = document.getElementById('search-input');
            
            searchInput.addEventListener('input', () => {
                const searchTerm = searchInput.value.toLowerCase();
                
                if (searchTerm === '') {
                    filteredTransactions = [...allTransactions];
                } else {
                    filteredTransactions = allTransactions.filter(transaction => 
                        transaction.description.toLowerCase().includes(searchTerm) ||
                        transaction.reference.toLowerCase().includes(searchTerm) ||
                        transaction.category.toLowerCase().includes(searchTerm)
                    );
                }
                
                currentPage = 1;
                renderTransactions();
                setupPagination();
            });
        }
    </script>
</body>
</html>