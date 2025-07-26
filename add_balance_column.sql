-- Add balance column to users table if it doesn't exist
USE giftcard_trading;

-- Check if balance column exists, if not add it
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'giftcard_trading' 
     AND TABLE_NAME = 'users' 
     AND COLUMN_NAME = 'balance') = 0,
    'ALTER TABLE users ADD COLUMN balance DECIMAL(15,2) DEFAULT 0.00',
    'SELECT "Balance column already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update existing users to have 0 balance if they don't have one
UPDATE users SET balance = 0.00 WHERE balance IS NULL; 