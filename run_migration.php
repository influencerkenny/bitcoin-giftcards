<?php
require_once 'db.php';

// Create cryptocurrency rates table
$db->query("
CREATE TABLE IF NOT EXISTS cryptocurrency_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    crypto_name VARCHAR(50) NOT NULL,
    crypto_symbol VARCHAR(10) NOT NULL,
    buy_rate DECIMAL(15,2) NOT NULL,
    sell_rate DECIMAL(15,2) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Create cryptocurrency transactions table
$db->query("
CREATE TABLE IF NOT EXISTS crypto_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    crypto_name VARCHAR(50) NOT NULL,
    crypto_symbol VARCHAR(10) NOT NULL,
    transaction_type ENUM('buy', 'sell') NOT NULL,
    amount DECIMAL(15,8) NOT NULL,
    rate DECIMAL(15,2) NOT NULL,
    estimated_payment DECIMAL(20,8) NOT NULL,
    btc_wallet VARCHAR(255),
    payment_proof VARCHAR(255),
    status ENUM('Processing', 'Completed', 'Rejected') DEFAULT 'Processing',
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

// Create rates table if it doesn't exist
$db->query("
CREATE TABLE IF NOT EXISTS rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL UNIQUE,
    label VARCHAR(100) NOT NULL,
    value DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Insert default cryptocurrency rates
$db->query("
INSERT IGNORE INTO cryptocurrency_rates (crypto_name, crypto_symbol, buy_rate, sell_rate) VALUES
('Bitcoin', 'BTC', 45000000.00, 44000000.00),
('Ethereum', 'ETH', 2800000.00, 2700000.00),
('Litecoin', 'LTC', 85000.00, 82000.00),
('Bitcoin Cash', 'BCH', 180000.00, 175000.00),
('Dogecoin', 'DOGE', 45.00, 42.00)
");

// Insert default rates
$db->query("
INSERT IGNORE INTO rates (type, label, value) VALUES
('btc_buy', 'Bitcoin Buy Rate', 45000000.00),
('btc_sell', 'Bitcoin Sell Rate', 44000000.00)
");

echo "Migration completed successfully!";
?> 