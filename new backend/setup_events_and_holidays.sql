-- =====================================================
-- Events and Holidays Database Setup Script
-- Run this script in your news_management database
-- =====================================================

USE news_management;

-- =====================================================
-- 1. CREATE EVENTS TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    event_time TIME,
    location VARCHAR(255),
    image_path VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('upcoming', 'ongoing', 'completed', 'cancelled') DEFAULT 'upcoming',
    INDEX idx_event_date (event_date),
    INDEX idx_status (status)
);

-- =====================================================
-- 2. CREATE PHILIPPINE HOLIDAYS TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS philippine_holidays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    type ENUM('regular', 'special_non_working', 'special_working') DEFAULT 'regular',
    description TEXT,
    is_recurring BOOLEAN DEFAULT FALSE,
    month_day VARCHAR(10), -- For recurring holidays like 01-01 for New Year
    INDEX idx_holiday_date (date),
    INDEX idx_holiday_type (type),
    INDEX idx_month_day (month_day)
);

-- =====================================================
-- 3. CREATE UPLOADS DIRECTORY (Note: This needs to be done manually)
-- =====================================================
-- You need to manually create the directory: uploads/events/
-- Make sure it has proper write permissions

-- =====================================================
-- 4. VERIFY TABLES WERE CREATED
-- =====================================================

-- Check if events table exists and show structure
DESCRIBE events;

-- Check if philippine_holidays table exists and show structure  
DESCRIBE philippine_holidays;

-- Show all tables in the database
SHOW TABLES;

-- =====================================================
-- 5. INSERT SAMPLE EVENT (Optional - for testing)
-- =====================================================

INSERT INTO events (title, description, event_date, event_time, location, status) VALUES 
(
    'Radio Station Open House', 
    'Join us for a special open house event where you can tour our facilities, meet our DJs, and learn about our programs. Free refreshments will be provided!', 
    '2024-12-31', 
    '14:00:00', 
    'Radio Station Main Building, Olongapo City', 
    'upcoming'
);

-- =====================================================
-- SUCCESS MESSAGE
-- =====================================================

SELECT 'Events and Holidays tables created successfully!' AS Status;
SELECT COUNT(*) AS 'Events Count' FROM events;
SELECT COUNT(*) AS 'Holidays Count' FROM philippine_holidays;
