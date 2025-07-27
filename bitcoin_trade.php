<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
require_once 'db.php';

$user_id = $_SESSION['user_id'];
$user_name = htmlspecialchars($_SESSION['user_name']);

// Fetch user's balance
$balance_stmt = $db->prepare('SELECT balance FROM users WHERE id = ?');
$balance_stmt->bind_param('i', $user_id);
$balance_stmt->execute();
$balance_stmt->bind_result($user_balance);
$balance_stmt->fetch();
$balance_stmt->close();

// Fetch active cryptocurrencies
$cryptocurrencies = [];
$crypto_res = $db->query("SELECT id, crypto_name, crypto_symbol, buy_rate, sell_rate FROM cryptocurrency_rates WHERE status='active' ORDER BY crypto_name ASC");
while ($row = $crypto_res->fetch_assoc()) {
    $cryptocurrencies[] = $row;
}
$crypto_res->close();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_crypto_trade'])) {
        $crypto_id = intval($_POST['crypto_id']);
        $transaction_type = $_POST['transaction_type'];
        $amount = floatval($_POST['amount']);
        $btc_wallet = trim($_POST['btc_wallet']);
        
        // Get cryptocurrency details
        $crypto_stmt = $db->prepare('SELECT crypto_name, crypto_symbol, buy_rate, sell_rate FROM cryptocurrency_rates WHERE id = ?');
        $crypto_stmt->bind_param('i', $crypto_id);
        $crypto_stmt->execute();
        $crypto_stmt->bind_result($crypto_name, $crypto_symbol, $buy_rate, $sell_rate);
        $crypto_stmt->fetch();
        $crypto_stmt->close();
        
        // Calculate estimated payment based on transaction type
        $rate = ($transaction_type === 'buy') ? $buy_rate : $sell_rate;
        
        // For BUY: User pays Naira, receives Crypto (Crypto Amount = Naira Amount / Rate)
        // For SELL: User pays Crypto, receives Naira (Naira Amount = Crypto Amount * Rate)
        if ($transaction_type === 'buy') {
            $estimated_payment = $amount / $rate; // Crypto amount user will receive
        } else {
            $estimated_payment = $amount * $rate; // Naira amount user will receive
        }
        
        // Handle file upload for payment proof
        $payment_proof = '';
        if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/payment_proofs/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION);
            $file_name = 'payment_proof_' . time() . '_' . $user_id . '.' . $file_extension;
            $upload_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $upload_path)) {
                $payment_proof = $upload_path;
            }
        }
        
        // Insert transaction
        $insert_stmt = $db->prepare('INSERT INTO crypto_transactions (user_id, crypto_name, crypto_symbol, transaction_type, amount, rate, estimated_payment, btc_wallet, payment_proof, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "Processing")');
        $insert_stmt->bind_param('isssdddss', $user_id, $crypto_name, $crypto_symbol, $transaction_type, $amount, $rate, $estimated_payment, $btc_wallet, $payment_proof);
        
        if ($insert_stmt->execute()) {
            $_SESSION['success_message'] = "Your cryptocurrency trade has been submitted successfully and is being processed.";
        } else {
            $_SESSION['error_message'] = "Error submitting trade: " . $db->error;
        }
        $insert_stmt->close();
        
        header('Location: bitcoin_trade.php');
        exit();
    }
}

// Fetch user's recent crypto transactions
$recent_transactions = [];
$transactions_stmt = $db->prepare('SELECT crypto_name, crypto_symbol, transaction_type, amount, rate, estimated_payment, status, created_at FROM crypto_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10');
$transactions_stmt->bind_param('i', $user_id);
$transactions_stmt->execute();
$result = $transactions_stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_transactions[] = $row;
}
$transactions_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buy/Sell Cryptocurrency | Bitcoin Giftcards</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: linear-gradient(252deg, #1a938a 0%, rgba(26, 147, 138, 0) 100.44%);
            min-height: 100vh;
            color: #19376d;
            display: flex;
            flex-direction: column;
        }
        .dashboard-header {
            background: #fff;
            border-bottom: 1px solid #e9ecef;
            padding: 0.7rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            z-index: 110;
            min-height: 64px;
        }
        .dashboard-logo {
            font-weight: 700;
            font-size: 1.3rem;
            color: #19376d;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .sidebar {
            background: #1a938a;
            color: #fff;
            min-height: 100vh;
            padding: 2rem 0.5rem 2rem 0.5rem;
            width: 220px;
            position: fixed;
            top: 64px;
            left: 0;
            z-index: 10;
            transition: width 0.2s;
        }
        .sidebar.collapsed { width: 60px; }
        .sidebar .nav-link {
            color: #fff;
            padding: 0.8rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 0.3rem;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            text-decoration: none;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,0.15);
            color: #fff;
        }
        .sidebar .nav-link span:first-child {
            font-size: 1.2rem;
            min-width: 20px;
        }
        .sidebar-label {
            transition: opacity 0.2s;
        }
        .sidebar.collapsed .sidebar-label {
            opacity: 0;
            width: 0;
            overflow: hidden;
        }
        .sidebar-toggler {
            position: absolute;
            top: 1rem;
            right: -12px;
            background: #fff;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1a938a;
            font-size: 0.8rem;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            z-index: 1002;
        }
        
        /* Mobile sidebar improvements */
        @media (max-width: 991px) {
            .sidebar-toggler {
                display: none; /* Hide desktop toggle on mobile */
            }
            
            .sidebar {
                top: 64px; /* Ensure it starts below header */
                height: calc(100vh - 64px);
                overflow-y: auto;
            }
            
            .sidebar .nav-link {
                padding: 1rem;
                font-size: 1rem;
            }
            
            .sidebar .nav-link span:first-child {
                font-size: 1.3rem;
            }
            
            /* Mobile sidebar button improvements */
            #mobileSidebarBtn {
                transition: all 0.2s ease;
            }
            
            #mobileSidebarBtn:hover {
                transform: scale(1.1);
                background-color: #1a938a;
                border-color: #1a938a;
                color: white;
            }
            
            #mobileSidebarBtn:active {
                transform: scale(0.95);
            }
        }
        .main-content {
            margin-left: 220px;
            margin-top: 64px;
            padding: 2rem;
            transition: margin-left 0.2s;
            flex: 1;
        }
        .main-content.collapsed { margin-left: 60px; }
        .dashboard-card {
            background: #fff;
            border-radius: 1.1rem;
            box-shadow: 0 2px 16px rgba(26,147,138,0.10);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid #f1f3f4;
        }
        .balance-card {
            background: linear-gradient(135deg, #1a938a, #19376d);
            color: white;
            border-radius: 1.1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(26,147,138,0.15);
        }
        .btn-crypto {
            padding: 1rem 2rem;
            border-radius: 1rem;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-buy {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        .btn-buy:hover {
            background: linear-gradient(135deg, #218838, #1ea085);
            color: white;
            transform: translateY(-2px);
        }
        .btn-sell {
            background: linear-gradient(135deg, #dc3545, #fd7e14);
            color: white;
        }
        .btn-sell:hover {
            background: linear-gradient(135deg, #c82333, #e55a00);
            color: white;
            transform: translateY(-2px);
        }
        .crypto-rate {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 1rem;
            padding: 1.2rem;
            margin-bottom: 1rem;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }
        .crypto-rate:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(26,147,138,0.1);
        }
        .modal-content {
            border-radius: 1.1rem;
            border: none;
            box-shadow: 0 20px 60px rgba(26, 147, 138, 0.2);
        }
        
        /* Enhanced modal styles */
        .modal-header {
            background: linear-gradient(135deg, #1a938a, #19376d);
            color: white;
            border-radius: 1.1rem 1.1rem 0 0;
            border-bottom: none;
        }
        
        .modal-header .btn-close {
            filter: invert(1);
        }
        
        .modal-body {
            padding: 2rem;
        }
        
        .modal-footer {
            border-top: 1px solid #e9ecef;
            padding: 1.5rem 2rem;
        }
        
        /* Quick amount buttons */
        .btn-outline-primary {
            border-color: #1a938a;
            color: #1a938a;
        }
        
        .btn-outline-primary:hover {
            background-color: #1a938a;
            border-color: #1a938a;
            color: white;
        }
        
        /* Enhanced form controls */
        .form-control-lg, .form-select-lg {
            padding: 1rem 1.2rem;
            font-size: 1.1rem;
        }
        
        .input-group-text {
            background-color: #f8f9fa;
            border-color: #e9ecef;
            font-weight: 600;
            color: #1a938a;
        }
        
        /* Card enhancements */
        .card.border-primary {
            border-color: #1a938a !important;
        }
        
        .card.border-success {
            border-color: #28a745 !important;
        }
        
        .card-header.bg-primary {
            background-color: #1a938a !important;
        }
        .form-control, .form-select {
            border-radius: 0.8rem;
            border: 2px solid #e9ecef;
            padding: 0.8rem 1rem;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #1a938a;
            box-shadow: 0 0 0 0.2rem rgba(26, 147, 138, 0.25);
        }
        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 2rem;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-processing { background: #fff3cd; color: #856404; }
        .status-completed { background: #d1edff; color: #0c5460; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        
        /* Table improvements */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }
        .table {
            width: 100%;
            min-width: 900px;
            margin-bottom: 0;
        }
        .table th {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #495057;
            padding: 1rem 0.75rem;
        }
        .table td {
            padding: 1rem 0.75rem;
            vertical-align: middle;
        }
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        /* Mobile responsive styles */
        @media (max-width: 991px) {
            .sidebar { 
                left: -220px; 
                transition: left 0.3s ease; 
                z-index: 1000;
            }
            .sidebar.show { 
                left: 0; 
                box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            }
            .main-content { 
                margin-left: 0; 
                flex: 1; 
                padding: 1rem;
            }
            .main-content.collapsed { 
                margin-left: 0; 
            }
            
            /* Ensure header stays on top */
            .dashboard-header {
                z-index: 1001;
            }
            
            /* Improve overlay */
            #sidebarOverlay {
                z-index: 999;
                background: rgba(0,0,0,0.5);
            }
            
            /* Hide desktop tables on mobile */
            .table-responsive {
                display: none;
            }
            
            /* Show mobile cards */
            .mobile-cards {
                display: block;
            }
            
            /* Mobile card styles */
            .crypto-card {
                background: #fff;
                border-radius: 1rem;
                padding: 1.2rem;
                margin-bottom: 1rem;
                box-shadow: 0 2px 12px rgba(26,147,138,0.08);
                border: 1px solid #f1f3f4;
                transition: transform 0.2s, box-shadow 0.2s;
            }
            
            .crypto-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 20px rgba(26,147,138,0.12);
            }
            
            .crypto-card-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin-bottom: 1rem;
                padding-bottom: 0.8rem;
                border-bottom: 1px solid #f1f3f4;
            }
            
            .crypto-card-body {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 1rem;
                margin-bottom: 1rem;
            }
            
            .crypto-card-item {
                display: flex;
                flex-direction: column;
                gap: 0.3rem;
            }
            
            .crypto-card-label {
                font-size: 0.8rem;
                color: #666;
                font-weight: 500;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            .crypto-card-value {
                font-weight: 600;
                color: #19376d;
                font-size: 0.95rem;
            }
            
            .crypto-card-amount {
                color: #1a938a;
                font-size: 1.1rem;
            }
            
            .crypto-card-payout {
                font-weight: 700;
                color: #28a745;
                font-size: 1.1rem;
            }
        }
        
        @media (min-width: 992px) {
            .mobile-cards {
                display: none;
            }
        }
        
        footer {
            background: #fff;
            border-top: 1px solid #e9ecef;
            padding: 1rem 2rem;
            text-align: center;
            color: #666;
            font-size: 0.9rem;
            margin-top: auto;
            width: 100%;
            position: relative;
            bottom: 0;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <button class="sidebar-toggler" id="sidebarToggler" title="Toggle Sidebar">
            <span class="bi bi-list"></span>
        </button>
        <ul class="nav flex-column">
            <li><a class="nav-link" href="dashboard.php"><span class="bi bi-house"></span> <span class="sidebar-label">Dashboard</span></a></li>
            <li><a class="nav-link" href="bank_account.php"><span class="bi bi-bank"></span> <span class="sidebar-label">Bank Account</span></a></li>
            <li><a class="nav-link" href="giftcard_trade.php"><span class="bi bi-gift"></span> <span class="sidebar-label">Sell Giftcard</span></a></li>
            <li><a class="nav-link active" href="bitcoin_trade.php"><span class="bi bi-currency-bitcoin"></span> <span class="sidebar-label">Buy/Sell Bitcoin</span></a></li>
            <li><a class="nav-link" href="support.php"><span class="bi bi-life-preserver"></span> <span class="sidebar-label">Support</span></a></li>
            <li><a class="nav-link" href="account.php"><span class="bi bi-person"></span> <span class="sidebar-label">Account</span></a></li>
            <li><a class="nav-link" href="security.php"><span class="bi bi-shield-lock"></span> <span class="sidebar-label">Security</span></a></li>
            <li><a class="nav-link" href="logout.php"><span class="bi bi-box-arrow-right"></span> <span class="sidebar-label">Logout</span></a></li>
        </ul>
    </nav>
    
    <div id="sidebarOverlay" style="display:none;position:fixed;inset:0;z-index:99;background:rgba(10,23,78,0.35);transition:opacity 0.2s;"></div>
    
    <!-- Header -->
    <header class="dashboard-header">
        <div class="d-flex align-items-center gap-3 flex-grow-1">
            <button class="btn btn-outline-primary d-lg-none me-2" id="mobileSidebarBtn" style="font-size:1.5rem; transition: all 0.2s ease;">
                <span class="bi bi-list"></span>
            </button>
            <div class="dashboard-logo flex-grow-1">
                <img src="images/logo.png" alt="Logo" style="height:32px;"> Giftcard & Bitcoin
            </div>
            <span class="bi bi-bell" style="font-size:1.3rem;cursor:pointer;" title="Notifications"></span>
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="bi bi-person-circle" style="font-size:1.7rem;color:#19376d;"></span>
                    <span class="ms-2 d-none d-md-inline" style="color:#19376d;font-weight:600;">Hi, <?php echo $user_name; ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li><a class="dropdown-item" href="account.php">Account</a></li>
                    <li><a class="dropdown-item" href="security.php">Security</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <div class="container-fluid">
            <!-- Messages -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['success_message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['error_message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <!-- Balance Card -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="balance-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Available Balance</h6>
                                <h3 class="mb-0">₦<?php echo number_format($user_balance, 2); ?></h3>
                            </div>
                            <div class="text-end">
                                <i class="bi bi-wallet2 fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trading Section -->
            <div class="dashboard-card">
                <div class="text-center mb-4">
                    <h2 class="mb-3">
                        <i class="bi bi-currency-bitcoin text-warning"></i>
                        Cryptocurrency Trading
                    </h2>
                    <p class="text-muted">Buy and sell cryptocurrencies at competitive rates</p>
                </div>

                <!-- Trading Buttons -->
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <button class="btn btn-crypto btn-buy w-100" onclick="openTradeModal('buy')">
                            <i class="bi bi-arrow-up-circle"></i>
                            Buy Crypto
                        </button>
                    </div>
                    <div class="col-md-6">
                        <button class="btn btn-crypto btn-sell w-100" onclick="openTradeModal('sell')">
                            <i class="bi bi-arrow-down-circle"></i>
                            Sell Crypto
                        </button>
                    </div>
                </div>

                <!-- Available Cryptocurrencies -->
                <div class="mb-4">
                    <h5 class="mb-3">
                        <i class="bi bi-list-ul"></i>
                        Available Cryptocurrencies
                    </h5>
                    <div class="row g-3">
                        <?php if (empty($cryptocurrencies)): ?>
                            <div class="col-12">
                                <div class="text-center text-muted py-4">
                                    <i class="bi bi-exclamation-circle fs-1"></i>
                                    <p class="mt-2">No cryptocurrencies available at the moment.</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($cryptocurrencies as $crypto): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="crypto-rate">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="mb-0"><?php echo htmlspecialchars($crypto['crypto_name']); ?></h6>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($crypto['crypto_symbol']); ?></span>
                                        </div>
                                        <div class="row text-center">
                                            <div class="col-6">
                                                <small class="text-muted">Buy Rate</small>
                                                <div class="fw-bold text-success">₦<?php echo number_format($crypto['buy_rate'], 2); ?></div>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">Sell Rate</small>
                                                <div class="fw-bold text-danger">₦<?php echo number_format($crypto['sell_rate'], 2); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Transactions Section -->
            <div class="dashboard-card">
                <h5 class="mb-3">
                    <i class="bi bi-clock-history"></i>
                    Recent Transactions
                </h5>
                
                <!-- Desktop Table View -->
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Cryptocurrency</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Rate (₦)</th>
                                <th>Est. Payment (₦)</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_transactions)): ?>
                                <tr><td colspan="7" class="text-center py-4 text-muted"><span class="bi bi-inbox" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></span>No transactions yet</td></tr>
                            <?php else: ?>
                                <?php foreach ($recent_transactions as $transaction): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="bi bi-currency-bitcoin me-2 text-warning"></span>
                                                <div>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($transaction['crypto_name']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($transaction['crypto_symbol']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-<?php echo $transaction['transaction_type'] === 'buy' ? 'success' : 'warning'; ?>"><?php echo ucfirst($transaction['transaction_type']); ?></span></td>
                                        <td>
                                            <div class="fw-bold">
                                                <?php if ($transaction['transaction_type'] === 'buy'): ?>
                                                    ₦<?php echo number_format($transaction['amount'], 2); ?>
                                                <?php else: ?>
                                                    <?php echo number_format($transaction['amount'], 8); ?> <?php echo $transaction['crypto_symbol']; ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><div class="text-muted">₦<?php echo number_format($transaction['rate'], 2); ?></div></td>
                                        <td>
                                            <div class="fw-bold text-success">
                                                <?php if ($transaction['transaction_type'] === 'buy'): ?>
                                                    <?php echo number_format($transaction['estimated_payment'], 8); ?> <?php echo $transaction['crypto_symbol']; ?>
                                                <?php else: ?>
                                                    ₦<?php echo number_format($transaction['estimated_payment'], 2); ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><span class="status-badge status-<?php echo strtolower($transaction['status']); ?>"><?php echo htmlspecialchars($transaction['status']); ?></span></td>
                                        <td>
                                            <div class="text-muted">
                                                <div><?php echo date('M j, Y', strtotime($transaction['created_at'])); ?></div>
                                                <small><?php echo date('g:i A', strtotime($transaction['created_at'])); ?></small>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Mobile Card View -->
                <div class="mobile-cards">
                    <?php if (empty($recent_transactions)): ?>
                        <div class="text-center py-4 text-muted">
                            <span class="bi bi-inbox" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></span>
                            No transactions yet
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_transactions as $transaction): ?>
                            <div class="crypto-card">
                                <div class="crypto-card-header">
                                    <div class="d-flex align-items-center">
                                        <span class="bi bi-currency-bitcoin me-2 text-warning"></span>
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($transaction['crypto_name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($transaction['crypto_symbol']); ?></small>
                                        </div>
                                    </div>
                                    <span class="status-badge status-<?php echo strtolower($transaction['status']); ?>"><?php echo htmlspecialchars($transaction['status']); ?></span>
                                </div>
                                <div class="crypto-card-body">
                                    <div class="crypto-card-item">
                                        <div class="crypto-card-label">Type</div>
                                        <div class="crypto-card-value">
                                            <span class="badge bg-<?php echo $transaction['transaction_type'] === 'buy' ? 'success' : 'warning'; ?>"><?php echo ucfirst($transaction['transaction_type']); ?></span>
                                        </div>
                                    </div>
                                    <div class="crypto-card-item">
                                        <div class="crypto-card-label">Amount</div>
                                        <div class="crypto-card-value crypto-card-amount">
                                            <?php if ($transaction['transaction_type'] === 'buy'): ?>
                                                ₦<?php echo number_format($transaction['amount'], 2); ?>
                                            <?php else: ?>
                                                <?php echo number_format($transaction['amount'], 8); ?> <?php echo $transaction['crypto_symbol']; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="crypto-card-item">
                                        <div class="crypto-card-label">Rate (₦)</div>
                                        <div class="crypto-card-value">₦<?php echo number_format($transaction['rate'], 2); ?></div>
                                    </div>
                                    <div class="crypto-card-item">
                                        <div class="crypto-card-label">Est. Payment</div>
                                        <div class="crypto-card-value crypto-card-payout">
                                            <?php if ($transaction['transaction_type'] === 'buy'): ?>
                                                <?php echo number_format($transaction['estimated_payment'], 8); ?> <?php echo $transaction['crypto_symbol']; ?>
                                            <?php else: ?>
                                                ₦<?php echo number_format($transaction['estimated_payment'], 2); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="crypto-card-item">
                                        <div class="crypto-card-label">Date</div>
                                        <div class="crypto-card-value">
                                            <?php echo date('M j, Y', strtotime($transaction['created_at'])); ?><br>
                                            <small><?php echo date('g:i A', strtotime($transaction['created_at'])); ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        &copy; <?php echo date('Y'); ?> Giftcard & Bitcoin Trading. All Rights Reserved. &middot;
        <a href="#" class="text-decoration-none text-primary">Privacy Policy</a> &middot;
        <a href="#" class="text-decoration-none text-primary">Terms of Service</a> &middot;
        <a href="#" class="text-decoration-none text-primary">Contact</a>
    </footer>

    <!-- Trade Modal -->
    <div class="modal fade" id="tradeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tradeModalTitle">
                        <i class="bi bi-currency-bitcoin"></i>
                        <span id="tradeTypeText">Trade Cryptocurrency</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data" id="tradeForm">
                    <div class="modal-body">
                        <input type="hidden" name="transaction_type" id="transactionType">
                        
                        <!-- Transaction Type Indicator -->
                        <div class="mb-4">
                            <div class="alert alert-info d-flex align-items-center" role="alert">
                                <i class="bi bi-info-circle me-2"></i>
                                <div>
                                    <strong id="transactionTypeInfo">Buying Cryptocurrency</strong><br>
                                    <small id="transactionTypeDescription">You'll pay in Naira and receive cryptocurrency</small>
                                </div>
                            </div>
                        </div>

                        <!-- Cryptocurrency Selection -->
                        <div class="mb-4">
                            <label for="crypto_id" class="form-label fw-bold">
                                <i class="bi bi-currency-bitcoin me-2"></i>
                                Select Cryptocurrency
                            </label>
                            <select class="form-select form-select-lg" id="crypto_id" name="crypto_id" required onchange="updateRates()">
                                <option value="">Choose a cryptocurrency...</option>
                                <?php foreach ($cryptocurrencies as $crypto): ?>
                                    <option value="<?php echo $crypto['id']; ?>" 
                                            data-buy-rate="<?php echo $crypto['buy_rate']; ?>"
                                            data-sell-rate="<?php echo $crypto['sell_rate']; ?>"
                                            data-symbol="<?php echo htmlspecialchars($crypto['crypto_symbol']); ?>"
                                            data-name="<?php echo htmlspecialchars($crypto['crypto_name']); ?>">
                                        <?php echo htmlspecialchars($crypto['crypto_name']); ?> (<?php echo htmlspecialchars($crypto['crypto_symbol']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Rate Display -->
                        <div class="mb-4" id="rateDisplay" style="display: none;">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0">
                                        <i class="bi bi-graph-up me-2"></i>
                                        Current Rates
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <div class="border-end">
                                                <div class="text-muted small">Buy Rate</div>
                                                <div class="h5 text-success mb-0" id="buyRateDisplay">₦0.00</div>
                                                <small class="text-muted">per crypto unit</small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted small">Sell Rate</div>
                                            <div class="h5 text-danger mb-0" id="sellRateDisplay">₦0.00</div>
                                            <small class="text-muted">per crypto unit</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Amount Input Section -->
                        <div class="mb-4">
                            <label for="amount" class="form-label fw-bold">
                                <i class="bi bi-cash-coin me-2"></i>
                                Amount to <span id="amountAction">Pay</span> (<span id="amountLabel">₦</span>)
                            </label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text" id="amountSymbol">₦</span>
                                <input type="number" 
                                       step="0.01" 
                                       min="1000" 
                                       class="form-control" 
                                       id="amount" 
                                       name="amount" 
                                       required 
                                       oninput="calculatePayment()"
                                       placeholder="Enter amount...">
                            </div>
                            <div class="form-text">
                                <i class="bi bi-info-circle me-1"></i>
                                Minimum amount: ₦1,000
                            </div>
                        </div>

                        <!-- You Get Section -->
                        <div class="mb-4" id="youGetSection" style="display: none;">
                            <label class="form-label fw-bold">
                                <i class="bi bi-arrow-down-circle me-2 text-success"></i>
                                You Will Receive
                            </label>
                            <div class="card border-success bg-light">
                                <div class="card-body text-center">
                                    <div class="h3 text-success mb-1" id="estimatedPayment">0.00000000</div>
                                    <div class="text-muted" id="cryptoNameDisplay">CRYPTO</div>
                                    <small class="text-muted">Estimated amount based on current rates</small>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Amount Buttons -->
                        <div class="mb-4" id="quickAmounts" style="display: none;">
                            <label class="form-label fw-bold">
                                <i class="bi bi-lightning me-2"></i>
                                Quick Amounts
                            </label>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-outline-primary" onclick="setAmount(5000)">₦5,000</button>
                                <button type="button" class="btn btn-outline-primary" onclick="setAmount(10000)">₦10,000</button>
                                <button type="button" class="btn btn-outline-primary" onclick="setAmount(25000)">₦25,000</button>
                                <button type="button" class="btn btn-outline-primary" onclick="setAmount(50000)">₦50,000</button>
                                <button type="button" class="btn btn-outline-primary" onclick="setAmount(100000)">₦100,000</button>
                            </div>
                        </div>

                        <!-- Destination Wallet Address -->
                        <div class="mb-4">
                            <label for="btc_wallet" class="form-label fw-bold">
                                <i class="bi bi-wallet2 me-2"></i>
                                Destination Wallet Address
                            </label>
                            <input type="text" 
                                   class="form-control form-control-lg" 
                                   id="btc_wallet" 
                                   name="btc_wallet" 
                                   required
                                   placeholder="Enter your cryptocurrency wallet address">
                            <div class="form-text">
                                <i class="bi bi-shield-check me-1"></i>
                                Double-check your wallet address. Incorrect addresses may result in permanent loss of funds.
                            </div>
                        </div>

                        <!-- Proof of Payment -->
                        <div class="mb-4">
                            <label for="payment_proof" class="form-label fw-bold">
                                <i class="bi bi-file-earmark-image me-2"></i>
                                Proof of Payment
                            </label>
                            <input type="file" 
                                   class="form-control" 
                                   id="payment_proof" 
                                   name="payment_proof" 
                                   accept="image/*,.pdf" 
                                   required>
                            <div class="form-text">
                                <i class="bi bi-upload me-1"></i>
                                Upload screenshot or PDF of your payment transaction (Bank transfer, card payment, etc.)
                            </div>
                        </div>

                        <!-- Terms and Conditions -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="termsCheck" required>
                                <label class="form-check-label" for="termsCheck">
                                    I agree to the <a href="#" class="text-decoration-none">terms and conditions</a> and confirm that all information provided is accurate.
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-2"></i>
                            Cancel
                        </button>
                        <button type="submit" name="submit_crypto_trade" class="btn btn-primary btn-lg" id="submitBtn" disabled>
                            <i class="bi bi-check-circle me-2"></i>
                            <span id="submitBtnText">Submit Trade</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar toggle
        const sidebar = document.getElementById('sidebar');
        const sidebarToggler = document.getElementById('sidebarToggler');
        const mainContent = document.getElementById('mainContent');
        const mobileSidebarBtn = document.getElementById('mobileSidebarBtn');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        sidebarToggler.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('collapsed');
        });

        mobileSidebarBtn.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            sidebarOverlay.style.display = sidebar.classList.contains('show') ? 'block' : 'none';
            
            // Add visual feedback
            if (sidebar.classList.contains('show')) {
                document.body.style.overflow = 'hidden'; // Prevent background scrolling
            } else {
                document.body.style.overflow = ''; // Restore scrolling
            }
        });

        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            sidebarOverlay.style.display = 'none';
            document.body.style.overflow = ''; // Restore scrolling
        });

        // Close sidebar when clicking on navigation links (mobile)
        document.querySelectorAll('.sidebar .nav-link').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 992) {
                    sidebar.classList.remove('show');
                    sidebarOverlay.style.display = 'none';
                    document.body.style.overflow = ''; // Restore scrolling
                }
            });
        });

        // Close sidebar when clicking outside (on main content)
        mainContent.addEventListener('click', function() {
            if (window.innerWidth < 992 && sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
                sidebarOverlay.style.display = 'none';
                document.body.style.overflow = ''; // Restore scrolling
            }
        });

        // Close sidebar on window resize if switching to desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 992) {
                sidebar.classList.remove('show');
                sidebarOverlay.style.display = 'none';
                document.body.style.overflow = ''; // Restore scrolling
            }
        });

        // Trade modal functionality
        let currentTransactionType = '';
        let currentBuyRate = 0;
        let currentSellRate = 0;
        let selectedCryptoName = '';
        let selectedCryptoSymbol = '';

        function openTradeModal(type) {
            currentTransactionType = type;
            document.getElementById('transactionType').value = type;
            document.getElementById('tradeTypeText').textContent = type === 'buy' ? 'Buy Cryptocurrency' : 'Sell Cryptocurrency';
            
            // Update transaction type info
            const transactionTypeInfo = document.getElementById('transactionTypeInfo');
            const transactionTypeDescription = document.getElementById('transactionTypeDescription');
            
            if (type === 'buy') {
                transactionTypeInfo.textContent = 'Buying Cryptocurrency';
                transactionTypeDescription.textContent = 'You\'ll pay in Naira and receive cryptocurrency';
            } else {
                transactionTypeInfo.textContent = 'Selling Cryptocurrency';
                transactionTypeDescription.textContent = 'You\'ll receive Naira for your cryptocurrency';
            }
            
            // Update amount field based on transaction type
            const amountInput = document.getElementById('amount');
            const amountLabel = document.getElementById('amountLabel');
            const amountAction = document.getElementById('amountAction');
            const amountSymbol = document.getElementById('amountSymbol');
            const youGetSection = document.getElementById('youGetSection');
            const quickAmounts = document.getElementById('quickAmounts');
            const submitBtnText = document.getElementById('submitBtnText');
            const submitBtn = document.getElementById('submitBtn');

            if (type === 'buy') {
                amountLabel.textContent = '₦';
                amountInput.step = '0.01';
                amountInput.min = '1000';
                amountAction.textContent = 'Pay';
                amountSymbol.textContent = '₦';
                youGetSection.style.display = 'block';
                quickAmounts.style.display = 'block';
                submitBtnText.textContent = 'Buy Cryptocurrency';
            } else {
                amountLabel.textContent = 'CRYPTO';
                amountInput.step = '0.00000001';
                amountInput.min = '0.00000001';
                amountAction.textContent = 'Sell';
                amountSymbol.textContent = 'CRYPTO';
                youGetSection.style.display = 'block';
                quickAmounts.style.display = 'none';
                submitBtnText.textContent = 'Sell Cryptocurrency';
            }
            
            // Reset form and UI
            document.getElementById('tradeForm').reset();
            document.getElementById('rateDisplay').style.display = 'none';
            document.getElementById('youGetSection').style.display = 'none';
            document.getElementById('quickAmounts').style.display = 'none';
            document.getElementById('estimatedPayment').textContent = '0.00000000';
            document.getElementById('cryptoNameDisplay').textContent = 'CRYPTO';
            document.getElementById('submitBtn').disabled = true;
            
            // Reset crypto selection
            selectedCryptoName = '';
            selectedCryptoSymbol = '';
            
            new bootstrap.Modal(document.getElementById('tradeModal')).show();
        }

        function updateRates() {
            const cryptoSelect = document.getElementById('crypto_id');
            const selectedOption = cryptoSelect.options[cryptoSelect.selectedIndex];
            
            if (selectedOption.value) {
                currentBuyRate = parseFloat(selectedOption.dataset.buyRate);
                currentSellRate = parseFloat(selectedOption.dataset.sellRate);
                selectedCryptoName = selectedOption.dataset.name;
                selectedCryptoSymbol = selectedOption.dataset.symbol;
                
                document.getElementById('cryptoNameDisplay').textContent = selectedCryptoName;
                document.getElementById('buyRateDisplay').textContent = '₦' + currentBuyRate.toLocaleString();
                document.getElementById('sellRateDisplay').textContent = '₦' + currentSellRate.toLocaleString();
                document.getElementById('rateDisplay').style.display = 'block';
                
                // Show quick amounts for buy transactions
                if (currentTransactionType === 'buy') {
                    document.getElementById('quickAmounts').style.display = 'block';
                }
                
                calculatePayment();
            } else {
                document.getElementById('rateDisplay').style.display = 'none';
                document.getElementById('youGetSection').style.display = 'none';
                document.getElementById('quickAmounts').style.display = 'none';
                document.getElementById('estimatedPayment').textContent = '0.00000000';
                document.getElementById('cryptoNameDisplay').textContent = 'CRYPTO';
                document.getElementById('submitBtn').disabled = true;
                
                selectedCryptoName = '';
                selectedCryptoSymbol = '';
            }
        }

        function calculatePayment() {
            const amount = parseFloat(document.getElementById('amount').value) || 0;
            const rate = currentTransactionType === 'buy' ? currentBuyRate : currentSellRate;
            const youGetSection = document.getElementById('youGetSection');
            const submitBtn = document.getElementById('submitBtn');
            
            // Only calculate if we have a valid amount and rate
            if (amount > 0 && rate > 0 && selectedCryptoName) {
                youGetSection.style.display = 'block';
                
                if (currentTransactionType === 'buy') {
                    // For buying: amount in ₦, result in crypto
                    const cryptoAmount = amount / rate;
                    document.getElementById('estimatedPayment').textContent = cryptoAmount.toFixed(8);
                    document.getElementById('cryptoNameDisplay').textContent = selectedCryptoName;
                } else {
                    // For selling: amount in crypto, result in ₦
                    const estimatedPayment = amount * rate;
                    document.getElementById('estimatedPayment').textContent = '₦' + estimatedPayment.toLocaleString();
                    document.getElementById('cryptoNameDisplay').textContent = 'Naira';
                }
                
                // Enable submit button if all required fields are filled
                const walletAddress = document.getElementById('btc_wallet').value.trim();
                const paymentProof = document.getElementById('payment_proof').files.length > 0;
                const termsAccepted = document.getElementById('termsCheck').checked;
                
                if (walletAddress && paymentProof && termsAccepted) {
                    submitBtn.disabled = false;
                } else {
                    submitBtn.disabled = true;
                }
            } else {
                youGetSection.style.display = 'none';
                submitBtn.disabled = true;
            }
        }

        function setAmount(amount) {
            document.getElementById('amount').value = amount;
            calculatePayment();
        }

        // Add event listeners for form validation
        document.addEventListener('DOMContentLoaded', function() {
            const walletInput = document.getElementById('btc_wallet');
            const paymentProofInput = document.getElementById('payment_proof');
            const termsCheck = document.getElementById('termsCheck');
            
            // Add event listeners for real-time validation
            walletInput.addEventListener('input', validateForm);
            paymentProofInput.addEventListener('change', validateForm);
            termsCheck.addEventListener('change', validateForm);
        });

        function validateForm() {
            const amount = parseFloat(document.getElementById('amount').value) || 0;
            const walletAddress = document.getElementById('btc_wallet').value.trim();
            const paymentProof = document.getElementById('payment_proof').files.length > 0;
            const termsAccepted = document.getElementById('termsCheck').checked;
            const submitBtn = document.getElementById('submitBtn');
            
            // Enable submit button only if all required fields are filled and crypto is selected
            if (amount > 0 && selectedCryptoName && walletAddress && paymentProof && termsAccepted) {
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
            }
        }
    </script>
</body>
</html> 
