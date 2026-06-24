-- SPK SAW update for Seleno Lelang
-- Safe import for phpMyAdmin / XAMPP MySQL or MariaDB

SET @current_database = DATABASE();

SELECT COUNT(*) INTO @has_rating_buyer
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = @current_database
  AND TABLE_NAME = 'users'
  AND COLUMN_NAME = 'rating_buyer';

SET @sql_rating_buyer = IF(
    @has_rating_buyer = 0,
    'ALTER TABLE users ADD COLUMN rating_buyer DECIMAL(3,2) DEFAULT 4.00',
    'SELECT ''users.rating_buyer already exists'' AS message'
);
PREPARE stmt_rating_buyer FROM @sql_rating_buyer;
EXECUTE stmt_rating_buyer;
DEALLOCATE PREPARE stmt_rating_buyer;

SELECT COUNT(*) INTO @has_response_score
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = @current_database
  AND TABLE_NAME = 'users'
  AND COLUMN_NAME = 'response_score';

SET @sql_response_score = IF(
    @has_response_score = 0,
    'ALTER TABLE users ADD COLUMN response_score DECIMAL(3,2) DEFAULT 4.00',
    'SELECT ''users.response_score already exists'' AS message'
);
PREPARE stmt_response_score FROM @sql_response_score;
EXECUTE stmt_response_score;
DEALLOCATE PREPARE stmt_response_score;

SELECT COUNT(*) INTO @has_transaction_history
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = @current_database
  AND TABLE_NAME = 'users'
  AND COLUMN_NAME = 'transaction_history';

SET @sql_transaction_history = IF(
    @has_transaction_history = 0,
    'ALTER TABLE users ADD COLUMN transaction_history INT DEFAULT 1',
    'SELECT ''users.transaction_history already exists'' AS message'
);
PREPARE stmt_transaction_history FROM @sql_transaction_history;
EXECUTE stmt_transaction_history;
DEALLOCATE PREPARE stmt_transaction_history;

CREATE TABLE IF NOT EXISTS spk_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    listing_id INT NOT NULL,
    bid_id INT NOT NULL,
    buyer_id INT NOT NULL,
    normalized_price DECIMAL(10,6) DEFAULT 0,
    normalized_rating DECIMAL(10,6) DEFAULT 0,
    normalized_response DECIMAL(10,6) DEFAULT 0,
    normalized_history DECIMAL(10,6) DEFAULT 0,
    final_score DECIMAL(10,6) DEFAULT 0,
    rank_position INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY unique_spk_bid (bid_id),
    KEY listing_id (listing_id),
    KEY buyer_id (buyer_id)
);

-- Optional direct syntax if your MySQL version supports it:
-- ALTER TABLE users
-- ADD COLUMN IF NOT EXISTS rating_buyer DECIMAL(3,2) DEFAULT 4.00,
-- ADD COLUMN IF NOT EXISTS response_score DECIMAL(3,2) DEFAULT 4.00,
-- ADD COLUMN IF NOT EXISTS transaction_history INT DEFAULT 1;
