-- =====================================================
-- ADD EVENTS AND HOLIDAYS TABLES TO EXISTING DATABASE
-- Compatible with your existing news_management schema
-- =====================================================

USE `news_management`;

-- =====================================================
-- 1. CREATE EVENTS TABLE
-- =====================================================

CREATE TABLE `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` date NOT NULL,
  `event_time` time DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('upcoming','ongoing','completed','cancelled') DEFAULT 'upcoming',
  PRIMARY KEY (`id`),
  KEY `idx_event_date` (`event_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 2. CREATE PHILIPPINE HOLIDAYS TABLE
-- =====================================================

CREATE TABLE `philippine_holidays` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `type` enum('regular','special_non_working','special_working') DEFAULT 'regular',
  `description` text DEFAULT NULL,
  `is_recurring` tinyint(1) DEFAULT 0,
  `month_day` varchar(10) DEFAULT NULL,
  `source` varchar(50) DEFAULT NULL COMMENT 'api, prediction, manual, nager_api',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_holiday_date` (`date`),
  KEY `idx_holiday_type` (`type`),
  KEY `idx_month_day` (`month_day`),
  KEY `idx_source` (`source`),
  UNIQUE KEY `unique_holiday_date` (`name`, `date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 3. INSERT SAMPLE EVENT FOR TESTING
-- =====================================================

INSERT INTO `events` (`title`, `description`, `event_date`, `event_time`, `location`, `status`) VALUES
('K5 News FM Station Open House', 'Join us for a special open house event at K5 News FM 88.7! Tour our facilities, meet our DJs including Rogie Pangilinan, and learn about our programs. Located at 3rd Floor Macariola Building Rizal Avenue in front of Olongapo City Hall.', '2024-12-31', '14:00:00', '3rd Floor Macariola Building, Rizal Avenue, Olongapo City', 'upcoming');

-- =====================================================
-- 4. INSERT PHILIPPINE HOLIDAYS DATA
-- =====================================================

INSERT INTO `philippine_holidays` (`name`, `date`, `type`, `description`, `is_recurring`, `month_day`) VALUES
-- 2024 Holidays
('New Year\'s Day', '2024-01-01', 'regular', 'First day of the year', 1, '01-01'),
('Chinese New Year', '2024-02-10', 'special_non_working', 'Lunar New Year celebration', 0, NULL),
('EDSA People Power Revolution Anniversary', '2024-02-25', 'special_non_working', 'Anniversary of the 1986 EDSA Revolution', 1, '02-25'),
('Maundy Thursday', '2024-03-28', 'regular', 'Thursday before Easter Sunday', 0, NULL),
('Good Friday', '2024-03-29', 'regular', 'Friday before Easter Sunday', 0, NULL),
('Black Saturday', '2024-03-30', 'special_non_working', 'Saturday before Easter Sunday', 0, NULL),
('Araw ng Kagitingan (Day of Valor)', '2024-04-09', 'regular', 'Commemoration of the fall of Bataan', 1, '04-09'),
('Eid al-Fitr', '2024-04-10', 'special_non_working', 'End of Ramadan', 0, NULL),
('Labor Day', '2024-05-01', 'regular', 'International Workers\' Day', 1, '05-01'),
('Independence Day', '2024-06-12', 'regular', 'Philippine Independence Day', 1, '06-12'),
('Ninoy Aquino Day', '2024-08-21', 'special_non_working', 'Death anniversary of Benigno Aquino Jr.', 1, '08-21'),
('National Heroes Day', '2024-08-26', 'regular', 'Last Monday of August', 0, NULL),
('All Saints\' Day', '2024-11-01', 'special_non_working', 'Christian holiday honoring all saints', 1, '11-01'),
('Bonifacio Day', '2024-11-30', 'regular', 'Birth anniversary of Andres Bonifacio', 1, '11-30'),
('Feast of the Immaculate Conception', '2024-12-08', 'special_non_working', 'Catholic feast day', 1, '12-08'),
('Christmas Eve', '2024-12-24', 'special_non_working', 'Day before Christmas', 1, '12-24'),
('Christmas Day', '2024-12-25', 'regular', 'Celebration of the birth of Jesus Christ', 1, '12-25'),
('Rizal Day', '2024-12-30', 'regular', 'Death anniversary of Dr. Jose Rizal', 1, '12-30'),
('New Year\'s Eve', '2024-12-31', 'special_non_working', 'Last day of the year', 1, '12-31'),

-- 2025 Holidays
('New Year\'s Day', '2025-01-01', 'regular', 'First day of the year', 1, '01-01'),
('Chinese New Year', '2025-01-29', 'special_non_working', 'Lunar New Year celebration', 0, NULL),
('EDSA People Power Revolution Anniversary', '2025-02-25', 'special_non_working', 'Anniversary of the 1986 EDSA Revolution', 1, '02-25'),
('Araw ng Kagitingan (Day of Valor)', '2025-04-09', 'regular', 'Commemoration of the fall of Bataan', 1, '04-09'),
('Maundy Thursday', '2025-04-17', 'regular', 'Thursday before Easter Sunday', 0, NULL),
('Good Friday', '2025-04-18', 'regular', 'Friday before Easter Sunday', 0, NULL),
('Black Saturday', '2025-04-19', 'special_non_working', 'Saturday before Easter Sunday', 0, NULL),
('Labor Day', '2025-05-01', 'regular', 'International Workers\' Day', 1, '05-01'),
('Independence Day', '2025-06-12', 'regular', 'Philippine Independence Day', 1, '06-12'),
('Ninoy Aquino Day', '2025-08-21', 'special_non_working', 'Death anniversary of Benigno Aquino Jr.', 1, '08-21'),
('National Heroes Day', '2025-08-25', 'regular', 'Last Monday of August', 0, NULL),
('All Saints\' Day', '2025-11-01', 'special_non_working', 'Christian holiday honoring all saints', 1, '11-01'),
('Bonifacio Day', '2025-11-30', 'regular', 'Birth anniversary of Andres Bonifacio', 1, '11-30'),
('Feast of the Immaculate Conception', '2025-12-08', 'special_non_working', 'Catholic feast day', 1, '12-08'),
('Christmas Eve', '2025-12-24', 'special_non_working', 'Day before Christmas', 1, '12-24'),
('Christmas Day', '2025-12-25', 'regular', 'Celebration of the birth of Jesus Christ', 1, '12-25'),
('Rizal Day', '2025-12-30', 'regular', 'Death anniversary of Dr. Jose Rizal', 1, '12-30'),
('New Year\'s Eve', '2025-12-31', 'special_non_working', 'Last day of the year', 1, '12-31');

-- =====================================================
-- 5. VERIFICATION QUERIES
-- =====================================================

-- Show tables to confirm creation
SHOW TABLES;

-- Show structure of new tables
DESCRIBE `events`;
DESCRIBE `philippine_holidays`;

-- Show data counts
SELECT 
    (SELECT COUNT(*) FROM `events`) AS events_count,
    (SELECT COUNT(*) FROM `philippine_holidays`) AS holidays_count;

-- Test the queries that will be used by the website
SELECT 'Testing upcoming events query:' AS test_info;
SELECT `title`, `event_date`, `location`, `status` FROM `events` 
WHERE `event_date` >= CURDATE() AND `status` IN ('upcoming', 'ongoing') 
ORDER BY `event_date` ASC, `event_time` ASC 
LIMIT 6;

SELECT 'Testing current month holidays query:' AS test_info;
SELECT `name`, `date`, `type` FROM `philippine_holidays` 
WHERE MONTH(`date`) = MONTH(CURDATE()) AND YEAR(`date`) = YEAR(CURDATE()) 
ORDER BY `date` ASC;

-- =====================================================
-- 6. ADD MORE COMPREHENSIVE HOLIDAY DATA
-- =====================================================

-- Add more holidays for better calendar coverage
INSERT INTO `philippine_holidays` (`name`, `date`, `type`, `description`, `is_recurring`, `month_day`) VALUES
-- Additional 2024 holidays
('Valentine\'s Day', '2024-02-14', 'special_working', 'Day of love and affection', 1, '02-14'),
('Lent Season Begins (Ash Wednesday)', '2024-02-14', 'special_working', 'Beginning of Lent season', 0, NULL),
('Mother\'s Day', '2024-05-12', 'special_working', 'Day to honor mothers', 0, NULL),
('Father\'s Day', '2024-06-16', 'special_working', 'Day to honor fathers', 0, NULL),
('Halloween', '2024-10-31', 'special_working', 'All Hallows\' Eve', 1, '10-31'),

-- Additional 2025 holidays
('Valentine\'s Day', '2025-02-14', 'special_working', 'Day of love and affection', 1, '02-14'),
('Mother\'s Day', '2025-05-11', 'special_working', 'Day to honor mothers', 0, NULL),
('Father\'s Day', '2025-06-15', 'special_working', 'Day to honor fathers', 0, NULL),
('Halloween', '2025-10-31', 'special_working', 'All Hallows\' Eve', 1, '10-31');

-- =====================================================
-- 7. CREATE USEFUL VIEWS FOR CALENDAR FUNCTIONALITY
-- =====================================================

-- Create a view for current year holidays
CREATE OR REPLACE VIEW current_year_holidays AS
SELECT * FROM philippine_holidays
WHERE YEAR(date) = YEAR(CURDATE())
ORDER BY date;

-- Create a view for upcoming holidays
CREATE OR REPLACE VIEW upcoming_holidays AS
SELECT * FROM philippine_holidays
WHERE date >= CURDATE()
ORDER BY date
LIMIT 10;

-- =====================================================
-- 8. FINAL VERIFICATION AND CALENDAR TESTING
-- =====================================================

-- Test calendar functionality
SELECT 'Testing calendar queries:' AS test_info;

-- Test current month holidays (for landing page)
SELECT 'Current month holidays:' AS info;
SELECT name, date, type FROM philippine_holidays
WHERE MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())
ORDER BY date;

-- Test holiday calendar view (for calendar page)
SELECT 'Holiday calendar data:' AS info;
SELECT
    DAY(date) as day,
    name,
    type,
    description
FROM philippine_holidays
WHERE YEAR(date) = YEAR(CURDATE()) AND MONTH(date) = MONTH(CURDATE())
ORDER BY DAY(date);

-- Show final counts
SELECT
    'Final Statistics:' AS info,
    (SELECT COUNT(*) FROM events) AS total_events,
    (SELECT COUNT(*) FROM philippine_holidays) AS total_holidays,
    (SELECT COUNT(*) FROM philippine_holidays WHERE YEAR(date) = YEAR(CURDATE())) AS current_year_holidays;

SELECT '✅ Events and Holidays system with Calendar functionality added successfully!' AS final_status;
SELECT '📅 You can now access:' AS access_info;
SELECT '   • holidays_calendar.php - Visual calendar with holiday markers' AS calendar_page;
SELECT '   • holidays_list.php - Complete holidays listing with filters' AS list_page;
SELECT '   • Admin Dashboard → Holidays tab - Holiday management' AS admin_page;
