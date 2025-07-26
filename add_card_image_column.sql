-- Add card_image column to giftcard_transactions table if it doesn't exist
USE giftcard_trading;

-- Check if card_image column exists, if not add it
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'giftcard_trading' 
     AND TABLE_NAME = 'giftcard_transactions' 
     AND COLUMN_NAME = 'card_image') = 0,
    'ALTER TABLE giftcard_transactions ADD COLUMN card_image VARCHAR(255) DEFAULT NULL',
    'SELECT "Card image column already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt; 