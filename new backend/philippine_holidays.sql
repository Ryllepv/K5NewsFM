-- =====================================================
-- Philippine Holidays Data Population Script
-- Run this AFTER running setup_events_and_holidays.sql
-- =====================================================

USE news_management;

-- Clear existing holiday data (if any)
DELETE FROM philippine_holidays;

-- Reset auto increment
ALTER TABLE philippine_holidays AUTO_INCREMENT = 1;

-- Insert Philippine Holidays Data
INSERT INTO philippine_holidays (name, date, type, description, is_recurring, month_day) VALUES
-- Regular Holidays (Fixed dates)
('New Year\'s Day', '2024-01-01', 'regular', 'First day of the year', TRUE, '01-01'),
('Maundy Thursday', '2024-03-28', 'regular', 'Thursday before Easter Sunday', FALSE, NULL),
('Good Friday', '2024-03-29', 'regular', 'Friday before Easter Sunday', FALSE, NULL),
('Araw ng Kagitingan (Day of Valor)', '2024-04-09', 'regular', 'Commemoration of the fall of Bataan', TRUE, '04-09'),
('Labor Day', '2024-05-01', 'regular', 'International Workers\' Day', TRUE, '05-01'),
('Independence Day', '2024-06-12', 'regular', 'Philippine Independence Day', TRUE, '06-12'),
('National Heroes Day', '2024-08-26', 'regular', 'Last Monday of August', FALSE, NULL),
('Bonifacio Day', '2024-11-30', 'regular', 'Birth anniversary of Andres Bonifacio', TRUE, '11-30'),
('Christmas Day', '2024-12-25', 'regular', 'Celebration of the birth of Jesus Christ', TRUE, '12-25'),
('Rizal Day', '2024-12-30', 'regular', 'Death anniversary of Dr. Jose Rizal', TRUE, '12-30'),

-- Special Non-Working Holidays
('Chinese New Year', '2024-02-10', 'special_non_working', 'Lunar New Year celebration', FALSE, NULL),
('EDSA People Power Revolution Anniversary', '2024-02-25', 'special_non_working', 'Anniversary of the 1986 EDSA Revolution', TRUE, '02-25'),
('Black Saturday', '2024-03-30', 'special_non_working', 'Saturday before Easter Sunday', FALSE, NULL),
('Eid al-Fitr', '2024-04-10', 'special_non_working', 'End of Ramadan', FALSE, NULL),
('Ninoy Aquino Day', '2024-08-21', 'special_non_working', 'Death anniversary of Benigno Aquino Jr.', TRUE, '08-21'),
('All Saints\' Day', '2024-11-01', 'special_non_working', 'Christian holiday honoring all saints', TRUE, '11-01'),
('Feast of the Immaculate Conception', '2024-12-08', 'special_non_working', 'Catholic feast day', TRUE, '12-08'),
('Christmas Eve', '2024-12-24', 'special_non_working', 'Day before Christmas', TRUE, '12-24'),
('New Year\'s Eve', '2024-12-31', 'special_non_working', 'Last day of the year', TRUE, '12-31'),

-- 2025 Holidays (for next year)
('New Year\'s Day', '2025-01-01', 'regular', 'First day of the year', TRUE, '01-01'),
('Chinese New Year', '2025-01-29', 'special_non_working', 'Lunar New Year celebration', FALSE, NULL),
('EDSA People Power Revolution Anniversary', '2025-02-25', 'special_non_working', 'Anniversary of the 1986 EDSA Revolution', TRUE, '02-25'),
('Araw ng Kagitingan (Day of Valor)', '2025-04-09', 'regular', 'Commemoration of the fall of Bataan', TRUE, '04-09'),
('Maundy Thursday', '2025-04-17', 'regular', 'Thursday before Easter Sunday', FALSE, NULL),
('Good Friday', '2025-04-18', 'regular', 'Friday before Easter Sunday', FALSE, NULL),
('Black Saturday', '2025-04-19', 'special_non_working', 'Saturday before Easter Sunday', FALSE, NULL),
('Labor Day', '2025-05-01', 'regular', 'International Workers\' Day', TRUE, '05-01'),
('Independence Day', '2025-06-12', 'regular', 'Philippine Independence Day', TRUE, '06-12'),
('Ninoy Aquino Day', '2025-08-21', 'special_non_working', 'Death anniversary of Benigno Aquino Jr.', TRUE, '08-21'),
('National Heroes Day', '2025-08-25', 'regular', 'Last Monday of August', FALSE, NULL),
('All Saints\' Day', '2025-11-01', 'special_non_working', 'Christian holiday honoring all saints', TRUE, '11-01'),
('Bonifacio Day', '2025-11-30', 'regular', 'Birth anniversary of Andres Bonifacio', TRUE, '11-30'),
('Feast of the Immaculate Conception', '2025-12-08', 'special_non_working', 'Catholic feast day', TRUE, '12-08'),
('Christmas Eve', '2025-12-24', 'special_non_working', 'Day before Christmas', TRUE, '12-24'),
('Christmas Day', '2025-12-25', 'regular', 'Celebration of the birth of Jesus Christ', TRUE, '12-25'),
('Rizal Day', '2025-12-30', 'regular', 'Death anniversary of Dr. Jose Rizal', TRUE, '12-30'),
('New Year\'s Eve', '2025-12-31', 'special_non_working', 'Last day of the year', TRUE, '12-31');

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================

-- Show total count of holidays inserted
SELECT COUNT(*) AS 'Total Holidays Inserted' FROM philippine_holidays;

-- Show holidays by type
SELECT type, COUNT(*) AS count FROM philippine_holidays GROUP BY type;

-- Show current month's holidays (for testing)
SELECT name, date, type FROM philippine_holidays
WHERE MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())
ORDER BY date;

-- Show next 5 upcoming holidays
SELECT name, date, type, description FROM philippine_holidays
WHERE date >= CURDATE()
ORDER BY date
LIMIT 5;

SELECT 'Philippine holidays data populated successfully!' AS Status;
