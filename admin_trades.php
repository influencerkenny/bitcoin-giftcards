<?php
session_start();
require_once 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

// Handle transaction actions (approve/decline/delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['transaction_id'])) {
    $transaction_id = $_POST['transaction_id'];
    $action = $_POST['action'] ?? '';
    $transaction_type = $_POST['transaction_type'] ?? 'giftcard';
    
    try {
        if ($transaction_type === 'giftcard') {
            handleGiftcardTransaction($db, $transaction_id, $action);
        } elseif ($transaction_type === 'crypto') {
            handleCryptocurrencyTransaction($db, $transaction_id, $action);
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error processing transaction: " . $e->getMessage();
    }
    
    header('Location: admin_trades.php');
    exit();
}

// Function to handle giftcard transactions
function handleGiftcardTransaction($db, $transaction_id, $action) {
    $stmt = $db->prepare("SELECT gt.*, u.balance, u.id as user_id FROM giftcard_transactions gt 
                          JOIN users u ON gt.user_id = u.id 
                          WHERE gt.id = ?");
    $stmt->bind_param('i', $transaction_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $transaction = $result->fetch_assoc();
    
    if (!$transaction) {
        throw new Exception("Transaction not found");
    }
    
    if (($action === 'approve' || $action === 'decline') && $transaction['status'] !== 'processing') {
        throw new Exception("Transaction is not in processing status");
    }
    
    if ($action === 'approve') {
        $payout_amount = $transaction['amount'] * $transaction['rate'];
        $new_balance = $transaction['balance'] + $payout_amount;
        
        $stmt = $db->prepare("UPDATE users SET balance = ? WHERE id = ?");
        $stmt->bind_param('di', $new_balance, $transaction['user_id']);
        $stmt->execute();
        
        $stmt = $db->prepare("UPDATE giftcard_transactions SET status = 'approved' WHERE id = ?");
        $stmt->bind_param('i', $transaction_id);
        $stmt->execute();
        
        $_SESSION['success_message'] = "Giftcard trade approved successfully. User balance updated.";
        
    } elseif ($action === 'decline') {
        $stmt = $db->prepare("UPDATE giftcard_transactions SET status = 'declined' WHERE id = ?");
        $stmt->bind_param('i', $transaction_id);
        $stmt->execute();
        
        $_SESSION['success_message'] = "Giftcard trade declined successfully.";
        
    } elseif ($action === 'delete') {
        $stmt = $db->prepare("DELETE FROM giftcard_transactions WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Failed to prepare delete statement: " . $db->error);
        }
        $stmt->bind_param('i', $transaction_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute delete: " . $stmt->error);
        }
        
        if ($stmt->affected_rows > 0) {
            $_SESSION['success_message'] = "Giftcard trade deleted successfully.";
        } else {
            throw new Exception("No transaction was deleted. Transaction ID may not exist.");
        }
    }
}

// Function to handle cryptocurrency transactions
function handleCryptocurrencyTransaction($db, $transaction_id, $action) {
    $stmt = $db->prepare("SELECT ct.*, u.balance, u.id as user_id FROM crypto_transactions ct 
                          JOIN users u ON ct.user_id = u.id 
                          WHERE ct.id = ?");
    $stmt->bind_param('i', $transaction_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $transaction = $result->fetch_assoc();
    
    if (!$transaction) {
        throw new Exception("Transaction not found");
    }
    
    if (($action === 'approve' || $action === 'decline') && $transaction['status'] !== 'Processing') {
        throw new Exception("Transaction is not in processing status");
    }
    
    if ($action === 'approve') {
        $payout_amount = $transaction['estimated_payment'];
        $new_balance = $transaction['balance'] + $payout_amount;
        
        $stmt = $db->prepare("UPDATE users SET balance = ? WHERE id = ?");
        $stmt->bind_param('di', $new_balance, $transaction['user_id']);
        $stmt->execute();
        
        $stmt = $db->prepare("UPDATE crypto_transactions SET status = 'Completed' WHERE id = ?");
        $stmt->bind_param('i', $transaction_id);
        $stmt->execute();
        
        $_SESSION['success_message'] = "Cryptocurrency trade approved successfully. User balance updated.";
        
    } elseif ($action === 'decline') {
        $stmt = $db->prepare("UPDATE crypto_transactions SET status = 'Rejected' WHERE id = ?");
        $stmt->bind_param('i', $transaction_id);
        $stmt->execute();
        
        $_SESSION['success_message'] = "Cryptocurrency trade declined successfully.";
        
    } elseif ($action === 'delete') {
        $stmt = $db->prepare("DELETE FROM crypto_transactions WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Failed to prepare delete statement: " . $db->error);
        }
        $stmt->bind_param('i', $transaction_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute delete: " . $stmt->error);
        }
        
        if ($stmt->affected_rows > 0) {
            $_SESSION['success_message'] = "Cryptocurrency trade deleted successfully.";
        } else {
            throw new Exception("No transaction was deleted. Transaction ID may not exist.");
        }
    }
}

// Fetch all transactions
$giftcard_transactions = fetchGiftcardTransactions($db);
$crypto_transactions = fetchCryptocurrencyTransactions($db);

// Calculate statistics
$total_giftcard_trades = count($giftcard_transactions);
$total_crypto_trades = count($crypto_transactions);
$pending_giftcard_trades = count(array_filter($giftcard_transactions, function($t) { return $t['status'] === 'Processing'; }));
$pending_crypto_trades = count(array_filter($crypto_transactions, function($t) { return $t['status'] === 'Processing'; }));

// Helper functions to fetch transactions
function fetchGiftcardTransactions($db) {
    $transactions = [];
    $query = "
        SELECT 
            gt.id,
            gt.user_id,
            u.name as user_name,
            u.email as user_email,
            gt.card_type,
            gt.amount,
            gt.status,
            gt.date,
            gt.card_image,
            gr.rate
        FROM giftcard_transactions gt
        LEFT JOIN users u ON gt.user_id = u.id
        LEFT JOIN giftcard_rates gr ON gt.card_type = gr.card_name
        ORDER BY gt.date DESC
    ";
    $result = $db->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }
        $result->close();
    }
    return $transactions;
}



function fetchCryptocurrencyTransactions($db) {
    $transactions = [];
    $query = "
        SELECT 
            ct.id,
            ct.user_id,
            u.name as user_name,
            u.email as user_email,
            ct.crypto_name,
            ct.crypto_symbol,
            ct.transaction_type,
            ct.amount,
            ct.rate,
            ct.estimated_payment,
            ct.btc_wallet,
            ct.payment_proof,
            ct.status,
            ct.created_at,
            ct.admin_notes
        FROM crypto_transactions ct
        LEFT JOIN users u ON ct.user_id = u.id
        ORDER BY ct.created_at DESC
    ";
    $result = $db->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }
        $result->close();
    }
    return $transactions;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trade Management | Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Professional Admin Panel Styles */
        :root {
            --primary-color: #1a938a;
            --secondary-color: #19376d;
            --accent-color: #ffbf3f;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-bg: #f8fafd;
            --border-color: #e9ecef;
            --text-muted: #6c757d;
        }

        body {
            background: linear-gradient(135deg, var(--light-bg) 0%, #e6f4ea 100%);
            color: var(--secondary-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Header Styles */
        .admin-header {
            background: #fff;
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 2rem;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1030;
            box-shadow: 0 2px 10px rgba(26, 147, 138, 0.1);
        }

        .admin-logo {
            font-weight: 700;
            font-size: 1.4rem;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Sidebar Styles */
        .admin-sidebar {
            background: linear-gradient(180deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: #fff;
            min-height: 100vh;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1020;
            padding: 0;
            box-shadow: 2px 0 20px rgba(26, 147, 138, 0.15);
            transition: transform 0.3s ease;
        }

        .sidebar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .sidebar-logo i {
            font-size: 1.5rem;
        }

        .sidebar-close-btn {
            padding: 0.25rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        .sidebar-close-btn:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .admin-sidebar .nav {
            padding: 1rem;
        }

        .admin-sidebar .nav-link {
            color: #fff;
            font-weight: 500;
            border-radius: 0.75rem;
            margin-bottom: 0.5rem;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .admin-sidebar .nav-link:hover,
        .admin-sidebar .nav-link.active {
            background: var(--accent-color);
            color: var(--secondary-color);
            transform: translateX(5px);
        }

        /* Mobile touch improvements */
        @media (max-width: 991px) {
            .admin-sidebar .nav-link {
                min-height: 48px; /* Minimum touch target size */
                display: flex;
                align-items: center;
                justify-content: flex-start;
            }
            
            .admin-sidebar .nav-link:active {
                background: rgba(255, 255, 255, 0.2);
                transform: scale(0.98);
            }
        }

        /* Sidebar Overlay */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1015;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            backdrop-filter: blur(2px);
        }

        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* Prevent body scroll when sidebar is open */
        body.sidebar-open {
            overflow: hidden;
        }

        /* Main Content */
        .admin-main-content {
            margin-left: 20%;
            margin-top: 0%;
            padding: 2rem;
            min-height: calc(100vh - 80px);
            max-width: 100%;
            width: 90%;
        }

        /* Container for better content organization - matching admin_bank_accounts.php */
        .admin-container {
            max-width: 1000px;
            margin: 0 auto;
            width: 90%;
        }

        /* Full-width container for large screens - matching admin_bank_accounts.php */
        .admin-container-fluid {
            width: 100%;
            max-width: none;
        }

        /* Widgets container styling - 90% width */
        .container-fluid.widgets-container {
            margin-top: 6rem !important;
            width: 90% !important;
            max-width: 90% !important;
            margin-left: auto !important;
            margin-right: auto !important;
            padding: 0 1rem;
        }

        .dashboard-card {
            width: 100% !important;
            max-width: 100% !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            margin-top: 0 !important;
        }

        /* Dashboard Cards */
        .dashboard-card {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(26, 147, 138, 0.08);
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .dashboard-card:hover {
            box-shadow: 0 8px 30px rgba(26, 147, 138, 0.12);
            transform: translateY(-2px);
        }

        /* Statistics Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            border-radius: 1rem;
            padding: 1.5rem;
            text-align: center;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(26, 147, 138, 0.15);
        }

        .stat-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--secondary-color);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-muted);
            font-weight: 500;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Trade Sections */
        .trade-section {
            background: #fff;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(26, 147, 138, 0.08);
            border: 1px solid var(--border-color);
            margin-bottom: 2rem;
        }

        /* Table responsive improvements */
        .table-responsive {
            overflow-x: auto;
            border-radius: 0 0 1rem 1rem;
        }

        .table-responsive::-webkit-scrollbar {
            height: 8px;
        }

        .table-responsive::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .table-responsive::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 4px;
        }

        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: var(--secondary-color);
        }

        .trade-section-header {
            background: linear-gradient(90deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: #fff;
            padding: 1.5rem 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .trade-section-header h3 {
            margin: 0;
            font-weight: 600;
            font-size: 1.25rem;
        }

        .trade-section-header .badge {
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            font-size: 0.85rem;
            padding: 0.5rem 1rem;
        }

        /* Table Styles */
        .table {
            margin: 0;
            font-size: 0.9rem;
            width: 100%;
        }

        .table th {
            background: #f8f9fa;
            border: none;
            padding: 1rem 1.5rem;
            font-weight: 600;
            color: var(--secondary-color);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.8rem;
            white-space: nowrap;
        }

        .table td {
            border: none;
            padding: 1rem 1.5rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
        }

        .table tbody tr:hover {
            background: #f8f9fa;
            transition: background 0.2s ease;
        }

        /* Table column widths for better space utilization - full width */
        .table th:nth-child(1), .table td:nth-child(1) { /* User */
            width: 18%;
            min-width: 200px;
        }
        
        .table th:nth-child(2), .table td:nth-child(2) { /* Giftcard/Crypto */
            width: 15%;
            min-width: 140px;
        }
        
        .table th:nth-child(3), .table td:nth-child(3) { /* Card Image */
            width: 12%;
            min-width: 100px;
        }
        
        .table th:nth-child(4), .table td:nth-child(4) { /* Amount */
            width: 12%;
            min-width: 120px;
        }
        
        .table th:nth-child(5), .table td:nth-child(5) { /* Status */
            width: 10%;
            min-width: 100px;
        }
        
        .table th:nth-child(6), .table td:nth-child(6) { /* Date */
            width: 12%;
            min-width: 130px;
        }
        
        .table th:nth-child(7), .table td:nth-child(7) { /* Actions */
            width: 15%;
            min-width: 150px;
        }

        /* Crypto table specific column widths - full width */
        .crypto-table th:nth-child(1), .crypto-table td:nth-child(1) { /* User */
            width: 15%;
            min-width: 180px;
        }
        
        .crypto-table th:nth-child(2), .crypto-table td:nth-child(2) { /* Cryptocurrency */
            width: 12%;
            min-width: 120px;
        }
        
        .crypto-table th:nth-child(3), .crypto-table td:nth-child(3) { /* Type */
            width: 8%;
            min-width: 80px;
        }
        
        .crypto-table th:nth-child(4), .crypto-table td:nth-child(4) { /* Amount */
            width: 12%;
            min-width: 130px;
        }
        
        .crypto-table th:nth-child(5), .crypto-table td:nth-child(5) { /* Rate */
            width: 10%;
            min-width: 100px;
        }
        
        .crypto-table th:nth-child(6), .crypto-table td:nth-child(6) { /* Est. Payment */
            width: 12%;
            min-width: 130px;
        }
        
        .crypto-table th:nth-child(7), .crypto-table td:nth-child(7) { /* Status */
            width: 8%;
            min-width: 80px;
        }
        
        .crypto-table th:nth-child(8), .crypto-table td:nth-child(8) { /* Date */
            width: 10%;
            min-width: 110px;
        }
        
        .crypto-table th:nth-child(9), .crypto-table td:nth-child(9) { /* Actions */
            width: 12%;
            min-width: 130px;
        }

        /* Status Badges */
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-processing {
            background: #fff3cd;
            color: #856404;
        }

        .status-completed, .status-approved {
            background: #d1edff;
            color: #0c5460;
        }

        .status-rejected, .status-declined {
            background: #f8d7da;
            color: #721c24;
        }

        /* Action Buttons */
        .action-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.5rem;
            font-size: 0.8rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .action-btn-success {
            background: var(--success-color);
            color: #fff;
        }

        .action-btn-success:hover {
            background: #218838;
            color: #fff;
            transform: translateY(-1px);
        }

        .action-btn-danger {
            background: var(--danger-color);
            color: #fff;
        }

        .action-btn-danger:hover {
            background: #c82333;
            color: #fff;
            transform: translateY(-1px);
        }

        .action-btn-warning {
            background: var(--warning-color);
            color: #212529;
        }

        .action-btn-warning:hover {
            background: #e0a800;
            color: #212529;
            transform: translateY(-1px);
        }

        .action-btn-info {
            background: var(--info-color);
            color: #fff;
        }

        .action-btn-info:hover {
            background: #138496;
            color: #fff;
            transform: translateY(-1px);
        }

        /* User Info */
        .user-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .user-name {
            font-weight: 600;
            color: var(--secondary-color);
        }

        .user-email {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        /* Amount Display */
        .amount-display {
            font-weight: 600;
            color: var(--primary-color);
        }

        .date-display {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        /* Responsive Design */
        @media (max-width: 1400px) {
            .admin-main-content {
                padding: 1.5rem;
            }
            
            .dashboard-card {
                padding: 1.5rem;
            }
            
            .trade-section-header {
                padding: 1.25rem 1.5rem;
            }
            
            .container-fluid.widgets-container {
                width: 92% !important;
                max-width: 92% !important;
                padding: 0 0.5rem;
            }
        }

        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                gap: 1.25rem;
            }
            
            .admin-main-content {
                padding: 1.25rem;
            }
        }

        @media (max-width: 991px) {
            .admin-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                width: 280px;
            }

            .admin-sidebar.open {
                transform: translateX(0);
            }

            .admin-main-content {
                margin-left: 0;
                padding: 1rem;
                width: 100%;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 1rem;
            }
            
            .dashboard-card {
                padding: 1.25rem;
            }
        }

        @media (min-width: 769px) {
            .table-responsive {
                display: block !important;
            }

            .mobile-cards {
                display: none !important;
            }
        }

        @media (max-width: 768px) {
            .admin-main-content {
                padding: 0.75rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }

            .table-responsive {
                display: none !important;
            }

            .mobile-cards {
                display: block !important;
            }
            
            .dashboard-card {
                padding: 1rem;
                margin-bottom: 1rem;
            }
            
            .trade-section-header {
                padding: 1rem;
            }
            
            /* Mobile sidebar improvements */
            .admin-sidebar {
                width: 300px;
            }
            
            .sidebar-header {
                padding: 1rem;
            }
            
            .admin-sidebar .nav-link {
                padding: 1rem;
                font-size: 1rem;
                margin-bottom: 0.25rem;
            }
            
            .admin-sidebar .nav-link i {
                font-size: 1.2rem;
                width: 1.5rem;
            }
            
            /* Mobile card image sizing for tablets and mobile */
            .trade-card-image img {
                width: 45px;
                height: 32px;
                object-fit: cover;
                border-radius: 0.5rem;
                border: 1px solid var(--border-color);
                cursor: pointer;
                transition: all 0.3s ease;
            }
            
            .trade-card-image img:hover {
                transform: scale(1.05);
                border-color: var(--primary-color);
            }
            
            .container-fluid.widgets-container {
                width: 95% !important;
                max-width: 95% !important;
                padding: 0 0.25rem;
            }
        }

        @media (max-width: 576px) {
            .admin-main-content {
                padding: 0.5rem;
            }
            
            .dashboard-card {
                padding: 0.75rem;
                border-radius: 0.75rem;
            }
            
            .trade-section {
                border-radius: 0.75rem;
            }
            
            .trade-card {
                padding: 1rem;
                border-radius: 0.75rem;
            }
            
            .trade-card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .trade-card-label {
                font-size: 0.7rem;
            }
            
            .trade-card-value {
                font-size: 0.9rem;
            }
            
            /* Small mobile sidebar improvements */
            .admin-sidebar {
                width: 280px;
            }
            
            .sidebar-header {
                padding: 0.75rem;
            }
            
            .sidebar-logo {
                font-size: 1rem;
            }
            
            .sidebar-logo i {
                font-size: 1.3rem;
            }
            
            .admin-sidebar .nav-link {
                padding: 0.75rem;
                font-size: 0.9rem;
            }
            
            .admin-sidebar .nav-link i {
                font-size: 1.1rem;
                width: 1.3rem;
            }
            
            /* Mobile card image sizing */
            .trade-card-image img {
                width: 40px;
                height: 28px;
                object-fit: cover;
                border-radius: 0.4rem;
                border: 1px solid var(--border-color);
                cursor: pointer;
                transition: all 0.3s ease;
            }
            
            .trade-card-image img:hover {
                transform: scale(1.05);
                border-color: var(--primary-color);
            }
            
            .container-fluid.widgets-container {
                width: 98% !important;
                max-width: 98% !important;
                padding: 0 0.1rem;
            }
            
            footer {
                padding: 1rem;
                text-align: center;
            }
            
            footer .d-flex {
                flex-direction: column;
                gap: 0.5rem;
            }
        }

        /* Mobile Cards */
        .mobile-cards {
            display: block;
            padding: 1rem;
        }

        .trade-card {
            background: #fff;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(26, 147, 138, 0.08);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .trade-card:hover {
            box-shadow: 0 4px 20px rgba(26, 147, 138, 0.15);
            transform: translateY(-2px);
        }

        .trade-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .trade-card-body {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        @media (min-width: 480px) {
            .trade-card-body {
                grid-template-columns: 1fr 1fr;
            }
        }

        .trade-card-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .trade-card-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .trade-card-value {
            font-weight: 600;
            color: var(--secondary-color);
        }

        .trade-card-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
            flex-wrap: wrap;
        }

        @media (max-width: 480px) {
            .trade-card-actions {
                justify-content: center;
                gap: 0.75rem;
            }
            
            .trade-card-actions .action-btn {
                flex: 1;
                min-width: 120px;
            }
        }

        /* Modal Styles */
        .modal-content {
            border-radius: 1rem;
            border: none;
            box-shadow: 0 10px 40px rgba(26, 147, 138, 0.2);
        }

        .modal-header {
            background: linear-gradient(90deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: #fff;
            border-radius: 1rem 1rem 0 0;
            border: none;
        }

        .modal-footer {
            border-top: 1px solid var(--border-color);
            padding: 1.5rem;
        }

        /* Card Image Thumbnail */
        .card-image-thumbnail {
            width: 50px;
            height: 35px;
            object-fit: cover;
            border-radius: 0.5rem;
            cursor: pointer;
            border: 2px solid var(--border-color);
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .card-image-thumbnail:hover {
            transform: scale(1.1);
            border-color: var(--primary-color);
            box-shadow: 0 4px 12px rgba(26, 147, 138, 0.2);
        }

        /* Footer Styles */
        footer {
            background: #fff;
            border-top: 1px solid var(--border-color);
            color: var(--text-muted);
            font-size: 0.9rem;
            text-align: center;
            padding: 1.5rem 2rem;
            margin-top: auto;
            flex-shrink: 0;
            width: 100%;
        }

        /* Ensure footer stays at bottom */
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .admin-main-content {
            flex: 1;
        }

        /* Utility Classes */
        .text-primary-custom {
            color: var(--primary-color) !important;
        }

        .bg-primary-custom {
            background-color: var(--primary-color) !important;
        }

        .border-primary-custom {
            border-color: var(--primary-color) !important;
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <header class="admin-header">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-outline-primary d-lg-none" id="mobileSidebarBtn">
                    <i class="bi bi-list"></i>
                </button>
                <div class="admin-logo">
                    <i class="bi bi-currency-bitcoin"></i>
                    <span>Admin Panel</span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="dropdown">
                    <button class="btn btn-link text-decoration-none dropdown-toggle" type="button" id="notificationsDropdown" data-bs-toggle="dropdown">
                        <i class="bi bi-bell fs-5"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#">No new notifications</a></li>
                    </ul>
                </div>
                <div class="dropdown">
                    <button class="btn btn-link text-decoration-none dropdown-toggle d-flex align-items-center gap-2" type="button" id="userDropdown" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle fs-4"></i>
                        <span class="d-none d-md-inline">Admin</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="admin_dashboard.php"><i class="bi bi-house me-2"></i>Dashboard</a></li>
                        <li><a class="dropdown-item" href="admin_stats.php"><i class="bi bi-graph-up me-2"></i>Statistics</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="admin_logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <!-- Admin Sidebar -->
    <nav class="admin-sidebar" id="adminSidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <i class="bi bi-currency-bitcoin"></i>
                <span>Admin Panel</span>
            </div>
            <button class="btn btn-link text-white d-lg-none sidebar-close-btn" id="sidebarCloseBtn">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="admin_dashboard.php">
                    <i class="bi bi-house"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="admin_stats.php">
                    <i class="bi bi-graph-up"></i>
                    <span>Statistics</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="admin_trades.php">
                    <i class="bi bi-currency-exchange"></i>
                    <span>Trades</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="admin_giftcard_types.php">
                    <i class="bi bi-gift"></i>
                    <span>Giftcard Types</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="admin_cryptocurrencies.php">
                    <i class="bi bi-currency-bitcoin"></i>
                    <span>Cryptocurrencies</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="admin_bank_accounts.php">
                    <i class="bi bi-bank"></i>
                    <span>Bank Accounts</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="adminSidebarOverlay"></div>

    <!-- Main Content -->
    <main class="admin-main-content" id="adminMainContent" style="top: 10px;">
        <div class="container-fluid widgets-container" style="margin-top: 6rem; width: 90%; max-width: 90%; margin-left: auto; margin-right: auto;">
            <div class="row">
                <div class="col-12">
                    <!-- Success/Error Messages -->
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle me-2"></i>
                            <?php echo htmlspecialchars($_SESSION['success_message']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['success_message']); ?>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <?php echo htmlspecialchars($_SESSION['error_message']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['error_message']); ?>
                    <?php endif; ?>

                    <!-- Page Header -->
                    <div class="dashboard-card" style="box-shadow: 0 8px 32px rgba(26,147,138,0.13); border-radius: 1.5rem; padding: 2.5rem 2rem 2rem 2rem;">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h2 mb-1 text-primary-custom">Trade Management</h1>
                        <p class="text-muted mb-0">Comprehensive overview of all transaction types</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary" onclick="exportTrades()">
                            <i class="bi bi-download me-2"></i>Export
                        </button>
                        <button class="btn btn-primary" onclick="refreshPage()">
                            <i class="bi bi-arrow-clockwise me-2"></i>Refresh
                        </button>
                    </div>
                </div>

                <!-- Statistics Overview -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-gift"></i>
                        </div>
                        <div class="stat-number"><?php echo $total_giftcard_trades; ?></div>
                        <div class="stat-label">Giftcard Trades</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-currency-exchange"></i>
                        </div>
                        <div class="stat-number"><?php echo $total_crypto_trades; ?></div>
                        <div class="stat-label">Crypto Trades</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-clock"></i>
                        </div>
                        <div class="stat-number"><?php echo $pending_giftcard_trades + $pending_crypto_trades; ?></div>
                        <div class="stat-label">Pending Trades</div>
                    </div>
                </div>
            </div>

            <!-- Giftcard Trades Section -->
            <div class="trade-section">
                <div class="trade-section-header">
                    <i class="bi bi-gift"></i>
                    <h3>Giftcard Trades</h3>
                    <span class="badge"><?php echo $total_giftcard_trades; ?> trades</span>
                </div>
                
                <!-- Desktop Table View -->
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><i class="bi bi-person me-2"></i>User</th>
                                <th><i class="bi bi-gift me-2"></i>Giftcard</th>
                                <th><i class="bi bi-image me-2"></i>Card Image</th>
                                <th><i class="bi bi-currency-dollar me-2"></i>Amount (USD)</th>
                                <th><i class="bi bi-gear me-2"></i>Status</th>
                                <th><i class="bi bi-calendar me-2"></i>Date</th>
                                <th><i class="bi bi-three-dots me-2"></i>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($giftcard_transactions)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-3 text-muted"></i>
                                        <h5 class="text-muted">No giftcard trades found</h5>
                                        <p class="text-muted mb-0">Giftcard transactions will appear here when users submit trades.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($giftcard_transactions as $trade): ?>
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-name"><?php echo htmlspecialchars($trade['user_name']); ?></div>
                                            <div class="user-email"><?php echo htmlspecialchars($trade['user_email']); ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary px-3 py-2"><?php echo htmlspecialchars($trade['card_type']); ?></span>
                                    </td>
                                    <td>
                                        <?php if (!empty($trade['card_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($trade['card_image']); ?>" 
                                                 alt="Card Image" 
                                                 class="card-image-thumbnail"
                                                 onclick="openImageModal('<?php echo htmlspecialchars($trade['card_image']); ?>')"
                                                 title="Click to view larger image">
                                        <?php else: ?>
                                            <span class="text-muted small"><i class="bi bi-image"></i> No image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="amount-display">$<?php echo number_format($trade['amount'], 2); ?></div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($trade['status']); ?>">
                                            <?php echo ucfirst($trade['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="date-display">
                                            <?php echo date('M j, Y', strtotime($trade['date'])); ?><br>
                                            <small><?php echo date('g:i A', strtotime($trade['date'])); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button class="action-btn action-btn-info" 
                                                    onclick='showGiftcardTradeModal(<?php echo json_encode([
                                                        "id" => $trade["id"],
                                                        "user_name" => $trade["user_name"],
                                                        "user_email" => $trade["user_email"],
                                                        "card_type" => $trade["card_type"],
                                                        "card_image" => $trade["card_image"],
                                                        "amount" => $trade["amount"],
                                                        "rate" => $trade["rate"],
                                                        "status" => $trade["status"],
                                                        "date" => $trade["date"]
                                                    ]); ?>)'
                                                    title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <?php if ($trade['status'] === 'Processing'): ?>
                                                <button class="action-btn action-btn-success" 
                                                        onclick="approveGiftcardTrade(<?php echo $trade['id']; ?>)"
                                                        title="Approve Trade">
                                                    <i class="bi bi-check"></i>
                                                </button>
                                                <button class="action-btn action-btn-danger" 
                                                        onclick="declineGiftcardTrade(<?php echo $trade['id']; ?>)"
                                                        title="Decline Trade">
                                                    <i class="bi bi-x"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button class="action-btn action-btn-warning" 
                                                    onclick="deleteGiftcardTrade(<?php echo $trade['id']; ?>)"
                                                    title="Delete Trade">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                    <!-- Modal for viewing/approving/declining giftcard trade -->
                    <div class="modal fade" id="giftcardTradeModal" tabindex="-1" aria-labelledby="giftcardTradeModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <form method="POST" id="giftcardTradeActionForm" autocomplete="off">
                                    <input type="hidden" name="transaction_id" id="modal_giftcard_trade_id">
                                    <input type="hidden" name="action" id="modal_giftcard_action">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="giftcardTradeModalLabel">Giftcard Trade Details</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row mb-3">
                                            <div class="col-md-6 mb-2 mb-md-0">
                                                <div class="fw-bold text-secondary mb-1">User</div>
                                                <div id="modal_giftcard_user"></div>
                                                <div id="modal_giftcard_user_email"></div>
                                            </div>
                                                                 <div class="col-md-6 text-center">
                                             <div class="fw-bold text-secondary mb-1">Card Image</div>
                                             <img id="modal_giftcard_image" src="" alt="Card Image" style="width:140px;height:auto;border-radius:0.5rem;box-shadow:0 2px 12px rgba(0,0,0,0.10);border:2px solid #e9ecef;max-width:100%;margin-bottom:0.5rem;cursor:pointer;" onclick="openImageModal(this.src)">
                                           </div>
                                        </div>
                                        <hr>
                                        <div class="row mb-2">
                                            <div class="col-md-6 mb-2 mb-md-0">
                                                <div class="fw-bold text-secondary mb-1">Giftcard</div>
                                                <div id="modal_giftcard_type"></div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="fw-bold text-secondary mb-1">Amount</div>
                                                <div>$<span id="modal_giftcard_amount"></span></div>
                                            </div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-md-6 mb-2 mb-md-0">
                                                <div class="fw-bold text-secondary mb-1">Estimated Payout</div>
                                                <div class="text-success fw-bold">₦<span id="modal_giftcard_payout"></span></div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="fw-bold text-secondary mb-1">Date</div>
                                                <div id="modal_giftcard_date" style="display:flex;align-items:center;gap:0.4em;font-size:1.01em;"><span class="bi bi-calendar-event"></span> <span id="modal_giftcard_date_value"></span></div>
                                            </div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-md-6">
                                                <div class="fw-bold text-secondary mb-1">Status</div>
                                                <span id="modal_giftcard_status_badge"></span>
                                            </div>
                                        </div>
                                    </div>
                                                                 <div class="modal-footer" id="modal_giftcard_action_buttons">
                                     <!-- Approve/Decline buttons will be inserted here by JS if status is Processing -->
                                   </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mobile Card View -->
                    <div class="mobile-cards">
                        <?php if (empty($giftcard_transactions)): ?>
                            <div class="text-center py-4 text-muted">
                                <span class="bi bi-inbox" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></span>
                                No giftcard trades found
                            </div>
                        <?php else: ?>
                            <?php foreach ($giftcard_transactions as $trade): ?>
                                <div class="trade-card">
                                    <div class="trade-card-header">
                                        <div class="trade-card-user">
                                            <div class="trade-card-user-name"><?php echo htmlspecialchars($trade['user_name']); ?></div>
                                            <div class="trade-card-user-email"><?php echo htmlspecialchars($trade['user_email']); ?></div>
                                        </div>
                                        <div class="trade-card-status">
                                            <span class="status-badge status-<?php echo strtolower($trade['status']); ?>">
                                                <?php echo htmlspecialchars($trade['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="trade-card-body">
                                        <div class="trade-card-item">
                                            <div class="trade-card-label">Amount</div>
                                            <div class="trade-card-value trade-card-amount">$<?php echo number_format($trade['amount'], 2); ?></div>
                                        </div>
                                        <div class="trade-card-item">
                                            <div class="trade-card-label">Card Type</div>
                                            <div class="trade-card-value">
                                                <span class="badge bg-primary" style="font-size: 0.8rem; padding: 0.4rem 0.6rem;">
                                                    <?php echo htmlspecialchars($trade['card_type']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="trade-card-item">
                                            <div class="trade-card-label">Rate (₦/USD)</div>
                                            <div class="trade-card-value">
                                                ₦<?php echo number_format($trade['rate'] ?? 0, 2); ?>
                                            </div>
                                        </div>
                                        <div class="trade-card-item">
                                            <div class="trade-card-label">Estimated Payout</div>
                                            <div class="trade-card-value trade-card-payout">
                                                ₦<?php echo number_format($trade['amount'] * ($trade['rate'] ?? 0), 2); ?>
                                            </div>
                                        </div>
                                        <div class="trade-card-item">
                                            <div class="trade-card-label">Transaction ID</div>
                                            <div class="trade-card-value">
                                                <code class="text-muted" style="font-size: 0.75rem; word-break: break-all;">
                                                    #<?php echo $trade['id']; ?>
                                                </code>
                                            </div>
                                        </div>
                                        <div class="trade-card-item">
                                            <div class="trade-card-label">User ID</div>
                                            <div class="trade-card-value">
                                                <code class="trade-card-user-id" style="font-size: 0.75rem;">
                                                    #<?php echo $trade['user_id']; ?>
                                                </code>
                                            </div>
                                        </div>
                                        <div class="trade-card-item">
                                            <div class="trade-card-label">Card Image</div>
                                            <div class="trade-card-value trade-card-image">
                                                <?php if (!empty($trade['card_image'])): ?>
                                                    <img src="<?php echo htmlspecialchars($trade['card_image']); ?>" 
                                                         alt="Card Image" 
                                                         onclick="openImageModal('<?php echo htmlspecialchars($trade['card_image']); ?>')" 
                                                         title="Click to view larger image">
                                                <?php else: ?>
                                                    <span class="text-muted" style="font-size: 0.8rem;">
                                                        <i class="bi bi-image"></i> No image
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="trade-card-item">
                                            <div class="trade-card-label">Date</div>
                                            <div class="trade-card-value">
                                                <?php echo date('M j, Y', strtotime($trade['date'])); ?><br>
                                                <small><?php echo date('g:i A', strtotime($trade['date'])); ?></small>
                                            </div>
                                        </div>
                                        <div class="trade-card-item">
                                            <div class="trade-card-label">Processing Time</div>
                                            <div class="trade-card-value trade-card-processing-time">
                                                <?php 
                                                $processing_time = time() - strtotime($trade['date']);
                                                $hours = floor($processing_time / 3600);
                                                $minutes = floor(($processing_time % 3600) / 60);
                                                if ($hours > 0) {
                                                    echo $hours . 'h ' . $minutes . 'm ago';
                                                } else {
                                                    echo $minutes . 'm ago';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="trade-card-actions">
                                        <div class="trade-card-date">
                                            <div class="d-flex gap-1">
                                                <?php if ($trade['status'] === 'Processing'): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="transaction_id" value="<?php echo $trade['id']; ?>">
                                                        <input type="hidden" name="action" value="approve">
                                                        <button type="submit" name="update_giftcard_status" class="action-btn action-btn-success" 
                                                                title="Approve trade and add ₦<?php echo number_format($trade['amount'] * ($trade['rate'] ?? 0), 2); ?> to user balance">
                                                            <span class="bi bi-check"></span> Approve
                                                        </button>
                                                    </form>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="transaction_id" value="<?php echo $trade['id']; ?>">
                                                        <input type="hidden" name="action" value="decline">
                                                        <button type="submit" name="update_giftcard_status" class="action-btn action-btn-danger" title="Reject Trade">
                                                            <span class="bi bi-x"></span> Reject
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="transaction_id" value="<?php echo $trade['id']; ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <button type="submit" name="update_giftcard_status" class="action-btn action-btn-warning" 
                                                            title="Delete transaction permanently" 
                                                            onclick="return confirm('Are you sure you want to delete this transaction? This action cannot be undone.')">
                                                        <span class="bi bi-trash"></span> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>


            <!-- Cryptocurrency Trades Section -->
            <div class="trade-section">
                <div class="trade-section-header">
                    <i class="bi bi-currency-bitcoin"></i>
                    <h3>Cryptocurrency Trades</h3>
                    <span class="badge"><?php echo count($crypto_transactions); ?> trades</span>
                </div>
                
                <!-- Desktop Table View -->
                <div class="table-responsive">
                    <table class="table crypto-table">
                        <thead>
                            <tr>
                                <th><i class="bi bi-person me-2"></i>User</th>
                                <th><i class="bi bi-currency-bitcoin me-2"></i>Cryptocurrency</th>
                                <th><i class="bi bi-arrow-left-right me-2"></i>Type</th>
                                <th><i class="bi bi-currency-exchange me-2"></i>Amount</th>
                                <th><i class="bi bi-graph-up me-2"></i>Rate (₦)</th>
                                <th><i class="bi bi-cash me-2"></i>Est. Payment</th>
                                <th><i class="bi bi-gear me-2"></i>Status</th>
                                <th><i class="bi bi-calendar me-2"></i>Date</th>
                                <th><i class="bi bi-three-dots me-2"></i>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($crypto_transactions)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-3 text-muted"></i>
                                        <h5 class="text-muted">No cryptocurrency trades found</h5>
                                        <p class="text-muted mb-0">Cryptocurrency transactions will appear here when users submit trades.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($crypto_transactions as $trade): ?>
                                    <tr>
                                        <td>
                                            <div class="user-info">
                                                <div class="user-name"><?php echo htmlspecialchars($trade['user_name']); ?></div>
                                                <div class="user-email"><?php echo htmlspecialchars($trade['user_email']); ?></div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="crypto-info">
                                                <div class="crypto-name"><?php echo htmlspecialchars($trade['crypto_name']); ?></div>
                                                <div class="crypto-symbol"><?php echo htmlspecialchars($trade['crypto_symbol']); ?></div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $trade['transaction_type'] === 'buy' ? 'success' : 'warning'; ?> px-3 py-2">
                                                <?php echo ucfirst($trade['transaction_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="amount-display"><?php echo number_format($trade['amount'], 8); ?> <?php echo $trade['crypto_symbol']; ?></div>
                                        </td>
                                        <td>
                                            <div class="rate-display">₦<?php echo number_format($trade['rate'], 2); ?></div>
                                        </td>
                                        <td>
                                            <div class="payment-display">₦<?php echo number_format($trade['estimated_payment'], 2); ?></div>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($trade['status']); ?>">
                                                <?php echo htmlspecialchars($trade['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="date-display">
                                                <?php echo date('M j, Y', strtotime($trade['created_at'])); ?><br>
                                                <small><?php echo date('g:i A', strtotime($trade['created_at'])); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <button class="action-btn action-btn-info" 
                                                        onclick="showCryptoTradeModal(<?php echo htmlspecialchars(json_encode($trade)); ?>)"
                                                        title="View Details">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <?php if ($trade['status'] === 'Processing'): ?>
                                                    <button class="action-btn action-btn-success" 
                                                            onclick="approveCryptoTrade(<?php echo $trade['id']; ?>)"
                                                            title="Approve Trade">
                                                        <i class="bi bi-check"></i>
                                                    </button>
                                                    <button class="action-btn action-btn-danger" 
                                                            onclick="declineCryptoTrade(<?php echo $trade['id']; ?>)"
                                                            title="Decline Trade">
                                                        <i class="bi bi-x"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button class="action-btn action-btn-warning" 
                                                        onclick="deleteCryptoTrade(<?php echo $trade['id']; ?>)"
                                                        title="Delete Trade">
                                                    <i class="bi bi-trash"></i>
                                                </button>
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
                        <?php if (empty($crypto_transactions)): ?>
                            <div class="text-center py-4 text-muted">
                                <span class="bi bi-inbox" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></span>
                                No cryptocurrency trades found
                            </div>
                        <?php else: ?>
                            <?php foreach ($crypto_transactions as $trade): ?>
                                <div class="trade-card">
                                    <div class="trade-card-header">
                                        <div class="trade-card-user">
                                            <div class="trade-card-user-name"><?php echo htmlspecialchars($trade['user_name']); ?></div>
                                            <div class="trade-card-user-email"><?php echo htmlspecialchars($trade['user_email']); ?></div>
                                        </div>
                                        <div class="trade-card-status">
                                            <span class="status-badge status-<?php echo strtolower($trade['status']); ?>">
                                                <?php echo htmlspecialchars($trade['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="trade-card-body">
                                        <div class="trade-card-item">
                                            <div class="trade-card-label">Cryptocurrency</div>
                                            <div class="trade-card-value">
                                                <?php echo htmlspecialchars($trade['crypto_name']); ?> (<?php echo htmlspecialchars($trade['crypto_symbol']); ?>)
                                            </div>
                                        </div>
                                        <div class="trade-card-item">
                                            <div class="trade-card-label">Type</div>
                                            <div class="trade-card-value">
                                                <span class="badge bg-<?php echo $trade['transaction_type'] === 'buy' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst($trade['transaction_type']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="trade-card-item">
                                            <div class="trade-card-label">Amount</div>
                                            <div class="trade-card-value trade-card-amount"><?php echo number_format($trade['amount'], 8); ?> <?php echo $trade['crypto_symbol']; ?></div>
                                        </div>
                                        <div class="trade-card-item">
                                            <div class="trade-card-label">Rate (₦)</div>
                                            <div class="trade-card-value">₦<?php echo number_format($trade['rate'], 2); ?></div>
                                        </div>
                                        <div class="trade-card-item">
                                            <div class="trade-card-label">Estimated Payment</div>
                                            <div class="trade-card-value trade-card-payout">₦<?php echo number_format($trade['estimated_payment'], 2); ?></div>
                                        </div>
                                        <div class="trade-card-item">
                                            <div class="trade-card-label">Wallet Address</div>
                                            <div class="trade-card-value">
                                                <code class="text-muted" style="font-size: 0.75rem; word-break: break-all;">
                                                    <?php echo htmlspecialchars($trade['btc_wallet'] ?? 'N/A'); ?>
                                                </code>
                                            </div>
                                        </div>
                                        <div class="trade-card-item">
                                            <div class="trade-card-label">Payment Proof</div>
                                            <div class="trade-card-value">
                                                <?php if ($trade['payment_proof']): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            onclick="openImageModal('<?php echo htmlspecialchars($trade['payment_proof']); ?>')">
                                                        View Proof
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted">No proof uploaded</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="trade-card-item">
                                            <div class="trade-card-label">Date</div>
                                            <div class="trade-card-value">
                                                <?php echo date('M j, Y', strtotime($trade['created_at'])); ?><br>
                                                <small><?php echo date('g:i A', strtotime($trade['created_at'])); ?></small>
                                            </div>
                                        </div>
                                        <div class="trade-card-actions">
                                            <?php if ($trade['status'] === 'Processing'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="transaction_id" value="<?php echo $trade['id']; ?>">
                                                    <input type="hidden" name="transaction_type" value="crypto">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="action-btn action-btn-success" 
                                                            title="Approve trade and add ₦<?php echo number_format($trade['estimated_payment'], 2); ?> to user balance">
                                                        <span class="bi bi-check"></span> Approve
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="transaction_id" value="<?php echo $trade['id']; ?>">
                                                    <input type="hidden" name="transaction_type" value="crypto">
                                                    <input type="hidden" name="action" value="decline">
                                                    <button type="submit" class="action-btn action-btn-danger" title="Reject Trade">
                                                        <span class="bi bi-x"></span> Reject
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted">No actions available</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
            </div>
        </div>
    </main>

    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">Card Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" alt="Card Image" style="max-width:100%;height:auto;border-radius:0.5rem;">
                </div>
            </div>
        </div>
    </div>

    <!-- Cryptocurrency Trade Modal -->
    <div class="modal fade" id="cryptoTradeModal" tabindex="-1" aria-labelledby="cryptoTradeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cryptoTradeModalLabel">Cryptocurrency Trade Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="fw-bold text-secondary mb-1">User Information</div>
                                <div id="modal_crypto_user"></div>
                                <div id="modal_crypto_user_email"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="fw-bold text-secondary mb-1">Trade Information</div>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="fw-bold text-secondary mb-1">Cryptocurrency</div>
                                        <div id="modal_crypto_name"></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="fw-bold text-secondary mb-1">Type</div>
                                        <div id="modal_crypto_type"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="fw-bold text-secondary mb-1">Amount</div>
                                <div id="modal_crypto_amount"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="fw-bold text-secondary mb-1">Rate (₦)</div>
                                <div>₦<span id="modal_crypto_rate"></span></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="fw-bold text-secondary mb-1">Estimated Payment</div>
                                <div class="text-success fw-bold">₦<span id="modal_crypto_payment"></span></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="fw-bold text-secondary mb-1">Date</div>
                                <div id="modal_crypto_date" style="display:flex;align-items:center;gap:0.4em;font-size:1.01em;">
                                    <span class="bi bi-calendar-event"></span> <span id="modal_crypto_date_value"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="fw-bold text-secondary mb-1">Wallet Address</div>
                        <div>
                            <code class="text-muted" style="font-size: 0.9rem; word-break: break-all;">
                                <span id="modal_crypto_wallet"></span>
                            </code>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="fw-bold text-secondary mb-1">Payment Proof</div>
                        <div id="modal_crypto_proof"></div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="fw-bold text-secondary mb-1">Status</div>
                        <span id="modal_crypto_status_badge"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar toggle functionality
        const adminSidebar = document.getElementById('adminSidebar');
        const mobileSidebarBtn = document.getElementById('mobileSidebarBtn');
        const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');
        const adminSidebarOverlay = document.getElementById('adminSidebarOverlay');

        // Mobile sidebar toggle
        mobileSidebarBtn.addEventListener('click', function() {
            adminSidebar.classList.add('open');
            adminSidebarOverlay.classList.add('active');
            document.body.classList.add('sidebar-open');
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        });

        // Close sidebar with close button
        sidebarCloseBtn.addEventListener('click', function() {
            adminSidebar.classList.remove('open');
            adminSidebarOverlay.classList.remove('active');
            document.body.classList.remove('sidebar-open');
            document.body.style.overflow = ''; // Restore scrolling
        });

        // Close sidebar with overlay click
        adminSidebarOverlay.addEventListener('click', function() {
            adminSidebar.classList.remove('open');
            adminSidebarOverlay.classList.remove('active');
            document.body.classList.remove('sidebar-open');
            document.body.style.overflow = ''; // Restore scrolling
        });

        // Close sidebar with escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && adminSidebar.classList.contains('open')) {
                adminSidebar.classList.remove('open');
                adminSidebarOverlay.classList.remove('active');
                document.body.classList.remove('sidebar-open');
                document.body.style.overflow = ''; // Restore scrolling
            }
        });

        // Auto-close sidebar when clicking on a link (mobile)
        const sidebarLinks = adminSidebar.querySelectorAll('.nav-link');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 991) {
                    adminSidebar.classList.remove('open');
                    adminSidebarOverlay.classList.remove('active');
                    document.body.classList.remove('sidebar-open');
                    document.body.style.overflow = ''; // Restore scrolling
                }
            });
        });

        // Handle window resize to close sidebar on desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth > 991 && adminSidebar.classList.contains('open')) {
                adminSidebar.classList.remove('open');
                adminSidebarOverlay.classList.remove('active');
                document.body.classList.remove('sidebar-open');
                document.body.style.overflow = '';
            }
        });

        // Utility functions
        function refreshPage() {
            location.reload();
        }

        function exportTrades() {
            // Implement export functionality
            alert('Export functionality will be implemented here');
        }

        // Giftcard trade action functions
        function approveGiftcardTrade(tradeId) {
            if (confirm('Are you sure you want to approve this giftcard trade?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="transaction_id" value="${tradeId}">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="transaction_type" value="giftcard">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function declineGiftcardTrade(tradeId) {
            if (confirm('Are you sure you want to decline this giftcard trade?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="transaction_id" value="${tradeId}">
                    <input type="hidden" name="action" value="decline">
                    <input type="hidden" name="transaction_type" value="giftcard">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function deleteGiftcardTrade(tradeId) {
            if (confirm('Are you sure you want to delete this giftcard trade? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="transaction_id" value="${tradeId}">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="transaction_type" value="giftcard">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Cryptocurrency trade action functions
        function approveCryptoTrade(tradeId) {
            if (confirm('Are you sure you want to approve this cryptocurrency trade?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="transaction_id" value="${tradeId}">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="transaction_type" value="crypto">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function declineCryptoTrade(tradeId) {
            if (confirm('Are you sure you want to decline this cryptocurrency trade?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="transaction_id" value="${tradeId}">
                    <input type="hidden" name="action" value="decline">
                    <input type="hidden" name="transaction_type" value="crypto">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function deleteCryptoTrade(tradeId) {
            if (confirm('Are you sure you want to delete this cryptocurrency trade? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="transaction_id" value="${tradeId}">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="transaction_type" value="crypto">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Function to open image modal for viewing uploaded card images
        function openImageModal(imageSrc) {
            document.getElementById('modalImage').src = imageSrc;
            var imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
            imageModal.show();
        }

        // Auto-refresh every 30 seconds
        setInterval(function() {
            // You can implement AJAX refresh here instead of full page reload
        }, 30000);
        
        // Function to update modal status after action
        function updateModalStatus(newStatus) {
            const statusBadge = document.getElementById('modal_giftcard_status_badge');
            if (statusBadge) {
                let badge = '<span class="badge px-3 py-2 fw-bold" style="font-size:1em;';
                if (newStatus === 'approved') badge += 'background:#d4edda;color:#155724;';
                else if (newStatus === 'declined') badge += 'background:#f8d7da;color:#721c24;';
                else badge += 'background:#e9ecef;color:#888;';
                badge += '">' + newStatus.charAt(0).toUpperCase() + newStatus.slice(1) + '</span>';
                statusBadge.innerHTML = badge;
            }
            
            // Update action buttons
            const actionButtons = document.getElementById('modal_giftcard_action_buttons');
            if (actionButtons) {
                actionButtons.innerHTML = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
            }
        }

        let giftcardModalSubmitting = false;
        function showGiftcardTradeModal(trade) {
            document.getElementById('modal_giftcard_trade_id').value = trade.id;
            document.getElementById('modal_giftcard_user').textContent = trade.user_name;
            document.getElementById('modal_giftcard_user_email').innerHTML = `<a href="mailto:${trade.user_email}">${trade.user_email}</a>`;
            document.getElementById('modal_giftcard_type').textContent = trade.card_type;
            document.getElementById('modal_giftcard_image').src = trade.card_image || '';
            document.getElementById('modal_giftcard_amount').textContent = Number(trade.amount).toLocaleString('en-US', {minimumFractionDigits:2});
            var payout = (Number(trade.amount) * Number(trade.rate || 0));
            document.getElementById('modal_giftcard_payout').textContent = payout.toLocaleString('en-NG', {maximumFractionDigits:2});
            document.getElementById('modal_giftcard_date_value').textContent = trade.date ? new Date(trade.date).toLocaleString('en-US', {dateStyle:'medium', timeStyle:'short'}) : 'N/A';
            // Status badge
            var status = trade.status;
            var badge = '<span class="badge px-3 py-2 fw-bold" style="font-size:1em;';
            if (status === 'Processing') badge += 'background:#fff3cd;color:#856404;';
            else if (status === 'Completed' || status === 'Paid Out') badge += 'background:#d4edda;color:#155724;';
            else if (status === 'Declined' || status === 'Rejected') badge += 'background:#f8d7da;color:#721c24;';
            else badge += 'background:#e9ecef;color:#888;';
            badge += '">' + status + '</span>';
            document.getElementById('modal_giftcard_status_badge').innerHTML = badge;
            // Action buttons
            var actionBtns = document.getElementById('modal_giftcard_action_buttons');
            actionBtns.innerHTML = '';
            
            // Always show delete button
            var deleteBtn = `
                <button type="button" class="btn btn-warning" id="modalDeleteBtn" onclick="submitGiftcardDelete()" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete transaction permanently" style="cursor: pointer;">
                    <span class="bi bi-trash"></span> <span id="modalDeleteText">Delete</span>
                    <span id="modalDeleteSpinner" class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                </button>
            `;
            
            console.log('Delete button HTML:', deleteBtn); // Debug log
            
            if (trade.status === 'Processing') {
                actionBtns.innerHTML = `
                    <button type="button" class="btn btn-success" id="modalApproveBtn" onclick="submitGiftcardApprove()" data-bs-toggle="tooltip" data-bs-placement="top" title="Approve trade and credit user">
                        <span class="bi bi-check"></span> <span id="modalApproveText">Approve</span>
                        <span id="modalApproveSpinner" class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                    </button>
                    <button type="button" class="btn btn-danger" id="modalDeclineBtn" onclick="submitGiftcardDecline()" data-bs-toggle="tooltip" data-bs-placement="top" title="Decline trade">
                        <span class="bi bi-x"></span> <span id="modalDeclineText">Decline</span>
                        <span id="modalDeclineSpinner" class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                    </button>
                    ${deleteBtn}
                `;
            } else {
                // For non-processing statuses, only show delete button
                actionBtns.innerHTML = deleteBtn;
            }
            
            setTimeout(() => {
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (tooltipTriggerEl) { return new bootstrap.Tooltip(tooltipTriggerEl); });
                
                // Add event listener to delete button as backup
                var deleteBtn = document.getElementById('modalDeleteBtn');
                if (deleteBtn) {
                    console.log('Delete button found, adding event listener'); // Debug log
                    deleteBtn.addEventListener('click', function(e) {
                        console.log('Delete button clicked via event listener'); // Debug log
                        submitGiftcardDelete();
                    });
                } else {
                    console.log('Delete button not found'); // Debug log
                }
            }, 200);
            var modal = new bootstrap.Modal(document.getElementById('giftcardTradeModal'));
            modal.show();
        }
        function submitGiftcardDecline() {
            if (giftcardModalSubmitting) return;
            giftcardModalSubmitting = true;
            
            // Set action to decline
            document.getElementById('modal_giftcard_action').value = 'decline';
            
            // Disable buttons and show spinner
            document.getElementById('modalDeclineBtn').disabled = true;
            document.getElementById('modalDeclineSpinner').classList.remove('d-none');
            document.getElementById('modalDeclineText').textContent = 'Declining...';
            
            // Submit the form
            document.getElementById('giftcardTradeActionForm').submit();
        }
        function submitGiftcardApprove() {
            if (giftcardModalSubmitting) return;
            giftcardModalSubmitting = true;
            
            // Set action to approve
            document.getElementById('modal_giftcard_action').value = 'approve';
            
            var approveBtn = document.getElementById('modalApproveBtn');
            if (approveBtn) {
                approveBtn.disabled = true;
                document.getElementById('modalApproveSpinner').classList.remove('d-none');
                document.getElementById('modalApproveText').textContent = 'Approving...';
            }
            
            // Submit the form
            document.getElementById('giftcardTradeActionForm').submit();
        }
        
        function submitGiftcardDelete() {
            console.log('submitGiftcardDelete function called'); // Debug log
            
            if (giftcardModalSubmitting) {
                console.log('Already submitting, returning'); // Debug log
                return;
            }
            
            // Show confirmation dialog
            if (!confirm('Are you sure you want to delete this transaction? This action cannot be undone.')) {
                console.log('User cancelled deletion'); // Debug log
                return;
            }
            
            console.log('User confirmed deletion'); // Debug log
            giftcardModalSubmitting = true;
            
            // Set action to delete
            var actionField = document.getElementById('modal_giftcard_action');
            var transactionIdField = document.getElementById('modal_giftcard_trade_id');
            
            if (!actionField || !transactionIdField) {
                // Debug: Print the form's innerHTML for troubleshooting
                var form = document.getElementById('giftcardTradeActionForm');
                if (form) {
                    console.error('Form innerHTML:', form.innerHTML);
                } else {
                    console.error('Form not found when trying to print innerHTML');
                }
                console.error('Required form fields not found!'); // Debug log
                alert('Error: Form fields not found. Please try again.');
                giftcardModalSubmitting = false;
                return;
            }
            
            actionField.value = 'delete';
            
            // Debug: Log the form data
            console.log('Submitting delete form with transaction ID:', transactionIdField.value);
            console.log('Action set to:', actionField.value);
            
            // Disable buttons and show spinner
            var deleteBtn = document.getElementById('modalDeleteBtn');
            var deleteSpinner = document.getElementById('modalDeleteSpinner');
            var deleteText = document.getElementById('modalDeleteText');
            
            if (deleteBtn) deleteBtn.disabled = true;
            if (deleteSpinner) deleteSpinner.classList.remove('d-none');
            if (deleteText) deleteText.textContent = 'Deleting...';
            
            // Submit the form
            var form = document.getElementById('giftcardTradeActionForm');
            if (form) {
                console.log('Submitting form...'); // Debug log
                form.submit();
            } else {
                console.error('Form not found!'); // Debug log
                alert('Error: Form not found. Please try again.');
                giftcardModalSubmitting = false;
            }
        }

        // Cryptocurrency trade modal function
        function showCryptoTradeModal(trade) {
            document.getElementById('modal_crypto_user').textContent = trade.user_name;
            document.getElementById('modal_crypto_user_email').innerHTML = `<a href="mailto:${trade.user_email}">${trade.user_email}</a>`;
            document.getElementById('modal_crypto_name').textContent = `${trade.crypto_name} (${trade.crypto_symbol})`;
            document.getElementById('modal_crypto_type').innerHTML = `<span class="badge bg-${trade.transaction_type === 'buy' ? 'success' : 'warning'}">${trade.transaction_type.charAt(0).toUpperCase() + trade.transaction_type.slice(1)}</span>`;
            document.getElementById('modal_crypto_amount').textContent = `${Number(trade.amount).toLocaleString('en-US', {minimumFractionDigits:8})} ${trade.crypto_symbol}`;
            document.getElementById('modal_crypto_rate').textContent = Number(trade.rate).toLocaleString('en-NG', {maximumFractionDigits:2});
            document.getElementById('modal_crypto_payment').textContent = Number(trade.estimated_payment).toLocaleString('en-NG', {maximumFractionDigits:2});
            document.getElementById('modal_crypto_date_value').textContent = trade.created_at ? new Date(trade.created_at).toLocaleString('en-US', {dateStyle:'medium', timeStyle:'short'}) : 'N/A';
            document.getElementById('modal_crypto_wallet').textContent = trade.btc_wallet || 'N/A';
            
            // Payment proof
            if (trade.payment_proof) {
                document.getElementById('modal_crypto_proof').innerHTML = `
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="openImageModal('${trade.payment_proof}')">
                        <i class="bi bi-eye"></i> View Payment Proof
                    </button>
                `;
            } else {
                document.getElementById('modal_crypto_proof').innerHTML = '<span class="text-muted">No payment proof uploaded</span>';
            }
            
            // Status badge
            var status = trade.status;
            var badge = '<span class="badge px-3 py-2 fw-bold" style="font-size:1em;';
            if (status === 'Processing') badge += 'background:#fff3cd;color:#856404;';
            else if (status === 'Completed') badge += 'background:#d4edda;color:#155724;';
            else if (status === 'Rejected') badge += 'background:#f8d7da;color:#721c24;';
            else badge += 'background:#e9ecef;color:#888;';
            badge += '">' + status + '</span>';
            document.getElementById('modal_crypto_status_badge').innerHTML = badge;
            
            var modal = new bootstrap.Modal(document.getElementById('cryptoTradeModal'));
            modal.show();
        }
    </script>

    <!-- Footer -->
    <footer>
        <div class="d-flex justify-content-between align-items-center">
            <div>
                &copy; <?php echo date('Y'); ?> Bitcoin Giftcards Admin Panel. All Rights Reserved.
            </div>
            <div class="d-flex gap-3">
                <span class="text-muted">Version 1.0</span>
                <span class="text-muted">•</span>
                <span class="text-muted">Admin Dashboard</span>
            </div>
        </div>
    </footer>
</body>
</html> 