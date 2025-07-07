-- =====================================================
-- UPDATE EXISTING HOLIDAYS TABLE FOR API INTEGRATION
-- Run this to add missing columns to your existing table
-- =====================================================

USE `news_management`;

-- =====================================================
-- 1. ADD MISSING COLUMNS TO EXISTING TABLE
-- =====================================================

-- Add source column to track where holidays come from
ALTER TABLE `philippine_holidays` 
ADD COLUMN `source` varchar(50) DEFAULT NULL COMMENT 'api, prediction, manual, nager_api' AFTER `month_day`;

-- Add created_at column if it doesn't exist
ALTER TABLE `philippine_holidays` 
ADD COLUMN `created_at` datetime NOT NULL DEFAULT current_timestamp() AFTER `source`;

-- Add updated_at column if it doesn't exist
ALTER TABLE `philippine_holidays` 
ADD COLUMN `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER `created_at`;

-- Add index for source column
ALTER TABLE `philippine_holidays` 
ADD KEY `idx_source` (`source`);

-- Add unique constraint to prevent duplicate holidays
ALTER TABLE `philippine_holidays` 
ADD UNIQUE KEY `unique_holiday_date` (`name`, `date`);

-- =====================================================
-- 2. UPDATE EXISTING HOLIDAYS WITH SOURCE INFO
-- =====================================================

-- Mark existing holidays as manually added
UPDATE `philippine_holidays` 
SET `source` = 'manual' 
WHERE `source` IS NULL OR `source` = '';

-- =====================================================
-- 3. CREATE SYNC LOG TABLE FOR AUTOMATION
-- =====================================================

CREATE TABLE IF NOT EXISTS `holiday_sync_log` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `sync_date` datetime NOT NULL DEFAULT current_timestamp(),
    `holidays_synced` int(11) DEFAULT 0,
    `errors_count` int(11) DEFAULT 0,
    `status` enum('success','partial','failed') DEFAULT 'success',
    `details` text DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_sync_date` (`sync_date`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 4. VERIFICATION QUERIES
-- =====================================================

-- Show updated table structure
DESCRIBE `philippine_holidays`;

-- Show current holidays with source info
SELECT `name`, `date`, `type`, `source`, `created_at` 
FROM `philippine_holidays` 
ORDER BY `date` 
LIMIT 10;

-- Show statistics by source
SELECT 
    `source`,
    COUNT(*) as count,
    MIN(`date`) as earliest_date,
    MAX(`date`) as latest_date
FROM `philippine_holidays` 
GROUP BY `source`;

-- Verify sync log table
DESCRIBE `holiday_sync_log`;

SELECT '✅ Holiday table updated successfully for API integration!' AS status;
SELECT 'You can now use the Holiday API Sync feature!' AS next_step;
