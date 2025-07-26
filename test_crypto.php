<?php
require_once 'db.php';

echo "<h1>Cryptocurrency System Test</h1>";

// Test 1: Check if cryptocurrency_rates table exists
echo "<h2>Test 1: Database Tables</h2>";
$result = $db->query("SHOW TABLES LIKE 'cryptocurrency_rates'");
if ($result->num_rows > 0) {
    echo "✅ cryptocurrency_rates table exists<br>";
} else {
    echo "❌ cryptocurrency_rates table does not exist<br>";
}

$result = $db->query("SHOW TABLES LIKE 'crypto_transactions'");
if ($result->num_rows > 0) {
    echo "✅ crypto_transactions table exists<br>";
} else {
    echo "❌ crypto_transactions table does not exist<br>";
}

// Test 2: Check if default cryptocurrencies are inserted
echo "<h2>Test 2: Default Cryptocurrencies</h2>";
$result = $db->query("SELECT * FROM cryptocurrency_rates WHERE status='active'");
if ($result->num_rows > 0) {
    echo "✅ Found " . $result->num_rows . " active cryptocurrencies:<br>";
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['crypto_name']} ({$row['crypto_symbol']}): Buy ₦" . number_format($row['buy_rate'], 2) . ", Sell ₦" . number_format($row['sell_rate'], 2) . "<br>";
    }
} else {
    echo "❌ No active cryptocurrencies found<br>";
}

// Test 3: Check if users table has balance column
echo "<h2>Test 3: User Balance</h2>";
$result = $db->query("SHOW COLUMNS FROM users LIKE 'balance'");
if ($result->num_rows > 0) {
    echo "✅ Users table has balance column<br>";
} else {
    echo "❌ Users table does not have balance column<br>";
}

// Test 4: Check if rates table exists
echo "<h2>Test 4: Rates Table</h2>";
$result = $db->query("SHOW TABLES LIKE 'rates'");
if ($result->num_rows > 0) {
    echo "✅ rates table exists<br>";
    $rates_result = $db->query("SELECT * FROM rates");
    if ($rates_result->num_rows > 0) {
        echo "✅ Found " . $rates_result->num_rows . " rate entries:<br>";
        while ($row = $rates_result->fetch_assoc()) {
            echo "- {$row['type']}: {$row['label']} = ₦" . number_format($row['value'], 2) . "<br>";
        }
    }
} else {
    echo "❌ rates table does not exist<br>";
}

echo "<h2>Test Complete!</h2>";
echo "<p>If all tests show ✅, the cryptocurrency system is ready to use.</p>";
echo "<p><a href='bitcoin_trade.php'>Go to Bitcoin Trade Page</a></p>";
echo "<p><a href='admin_cryptocurrencies.php'>Go to Admin Cryptocurrencies</a></p>";
?> 