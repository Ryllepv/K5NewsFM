-- =====================================================
-- Database Verification and Testing Script
-- Run this to verify everything is working correctly
-- =====================================================

USE news_management;

-- =====================================================
-- 1. VERIFY TABLES EXIST
-- =====================================================

SELECT 'Checking if tables exist...' AS Status;

-- Show all tables in the database
SHOW TABLES;

-- Check events table structure
SELECT 'Events table structure:' AS Info;
DESCRIBE events;

-- Check philippine_holidays table structure  
SELECT 'Philippine holidays table structure:' AS Info;
DESCRIBE philippine_holidays;

-- =====================================================
-- 2. CHECK DATA COUNTS
-- =====================================================

SELECT 'Checking data counts...' AS Status;

-- Count records in each table
SELECT 
    (SELECT COUNT(*) FROM events) AS events_count,
    (SELECT COUNT(*) FROM philippine_holidays) AS holidays_count;

-- =====================================================
-- 3. TEST EVENTS FUNCTIONALITY
-- =====================================================

SELECT 'Testing events functionality...' AS Status;

-- Show all events
SELECT 'All events:' AS Info;
SELECT id, title, event_date, event_time, location, status FROM events ORDER BY event_date;

-- Show upcoming events (what the landing page will display)
SELECT 'Upcoming events for landing page:' AS Info;
SELECT id, title, event_date, event_time, location, status 
FROM events 
WHERE event_date >= CURDATE() AND status IN ('upcoming', 'ongoing') 
ORDER BY event_date ASC, event_time ASC 
LIMIT 6;

-- =====================================================
-- 4. TEST HOLIDAYS FUNCTIONALITY
-- =====================================================

SELECT 'Testing holidays functionality...' AS Status;

-- Show current month's holidays (what the landing page will display)
SELECT 'Current month holidays for landing page:' AS Info;
SELECT name, date, type, description 
FROM philippine_holidays 
WHERE MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE()) 
ORDER BY date ASC;

-- Show holidays by type
SELECT 'Holidays by type:' AS Info;
SELECT type, COUNT(*) as count FROM philippine_holidays GROUP BY type;

-- Show next 3 upcoming holidays
SELECT 'Next 3 upcoming holidays:' AS Info;
SELECT name, date, type FROM philippine_holidays 
WHERE date >= CURDATE() 
ORDER BY date 
LIMIT 3;

-- =====================================================
-- 5. TEST ADMIN QUERIES
-- =====================================================

SELECT 'Testing admin dashboard queries...' AS Status;

-- Query that admin events tab uses
SELECT 'Admin events query:' AS Info;
SELECT id, title, event_date, event_time, location, status, created_at 
FROM events 
ORDER BY event_date ASC, event_time ASC;

-- =====================================================
-- 6. INSERT TEST EVENT (if none exist)
-- =====================================================

-- Only insert if no events exist
INSERT INTO events (title, description, event_date, event_time, location, status)
SELECT * FROM (
    SELECT 
        'Test Radio Event' as title,
        'This is a test event to verify the events system is working correctly.' as description,
        DATE_ADD(CURDATE(), INTERVAL 7 DAY) as event_date,
        '15:00:00' as event_time,
        'Radio Station, Olongapo City' as location,
        'upcoming' as status
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM events LIMIT 1);

-- =====================================================
-- 7. FINAL VERIFICATION
-- =====================================================

SELECT 'Final verification...' AS Status;

-- Check if we can select from both tables without errors
SELECT 
    'SUCCESS: Both tables are accessible and functional!' AS Result,
    (SELECT COUNT(*) FROM events) AS total_events,
    (SELECT COUNT(*) FROM philippine_holidays) AS total_holidays,
    NOW() AS verification_time;

-- Show what will appear on the landing page
SELECT 'Landing page preview - Events:' AS Info;
SELECT title, event_date, location FROM events 
WHERE event_date >= CURDATE() AND status IN ('upcoming', 'ongoing') 
ORDER BY event_date LIMIT 3;

SELECT 'Landing page preview - This month holidays:' AS Info;
SELECT name, date, type FROM philippine_holidays 
WHERE MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE()) 
ORDER BY date LIMIT 5;

SELECT '✅ Database setup verification complete!' AS Final_Status;
