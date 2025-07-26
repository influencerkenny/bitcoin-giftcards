<?php
require_once 'db.php';

echo "Running database migration...\n";

// Check if balance column exists
$check_column = $db->query("SHOW COLUMNS FROM users LIKE 'balance'");
if ($check_column->num_rows == 0) {
    // Add balance column
    $add_column = $db->query("ALTER TABLE users ADD COLUMN balance DECIMAL(15,2) DEFAULT 0.00");
    if ($add_column) {
        echo "✓ Balance column added successfully\n";
    } else {
        echo "✗ Error adding balance column: " . $db->error . "\n";
    }
} else {
    echo "✓ Balance column already exists\n";
}

// Update existing users to have 0 balance if they don't have one
$update_balance = $db->query("UPDATE users SET balance = 0.00 WHERE balance IS NULL");
if ($update_balance) {
    echo "✓ Updated existing users with default balance\n";
} else {
    echo "✗ Error updating user balances: " . $db->error . "\n";
}

echo "Migration completed!\n";
?> 