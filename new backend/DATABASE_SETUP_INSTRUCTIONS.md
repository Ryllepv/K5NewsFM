# 🚀 Database Setup Instructions

## Quick Fix for Events Table Error

Follow these steps **in order** to resolve the database error and set up the Events and Holidays system:

---

## 📋 **Step 1: Access Your Database**

1. Open **phpMyAdmin** (usually at `http://localhost/phpmyadmin`)
2. Or use **MySQL Command Line**
3. Select your `news_management` database

---

## 🗄️ **Step 2: Create Tables**

**Copy and paste this entire script into your SQL tab and run it:**

```sql
USE news_management;

-- Create Events Table
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

-- Create Philippine Holidays Table
CREATE TABLE IF NOT EXISTS philippine_holidays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    type ENUM('regular', 'special_non_working', 'special_working') DEFAULT 'regular',
    description TEXT,
    is_recurring BOOLEAN DEFAULT FALSE,
    month_day VARCHAR(10),
    INDEX idx_holiday_date (date),
    INDEX idx_holiday_type (type),
    INDEX idx_month_day (month_day)
);

-- Insert a test event
INSERT INTO events (title, description, event_date, event_time, location, status) VALUES 
('Radio Station Open House', 'Join us for a special open house event!', '2024-12-31', '14:00:00', 'Radio Station Main Building, Olongapo City', 'upcoming');

SELECT 'Tables created successfully!' AS Status;
```

---

## 🇵🇭 **Step 3: Add Philippine Holidays Data**

**Run this script to populate holidays:**

```sql
USE news_management;

-- Insert Philippine Holidays for 2024-2025
INSERT INTO philippine_holidays (name, date, type, description, is_recurring, month_day) VALUES
('New Year\'s Day', '2024-01-01', 'regular', 'First day of the year', TRUE, '01-01'),
('Chinese New Year', '2024-02-10', 'special_non_working', 'Lunar New Year celebration', FALSE, NULL),
('EDSA People Power Revolution Anniversary', '2024-02-25', 'special_non_working', 'Anniversary of the 1986 EDSA Revolution', TRUE, '02-25'),
('Maundy Thursday', '2024-03-28', 'regular', 'Thursday before Easter Sunday', FALSE, NULL),
('Good Friday', '2024-03-29', 'regular', 'Friday before Easter Sunday', FALSE, NULL),
('Black Saturday', '2024-03-30', 'special_non_working', 'Saturday before Easter Sunday', FALSE, NULL),
('Araw ng Kagitingan (Day of Valor)', '2024-04-09', 'regular', 'Commemoration of the fall of Bataan', TRUE, '04-09'),
('Eid al-Fitr', '2024-04-10', 'special_non_working', 'End of Ramadan', FALSE, NULL),
('Labor Day', '2024-05-01', 'regular', 'International Workers\' Day', TRUE, '05-01'),
('Independence Day', '2024-06-12', 'regular', 'Philippine Independence Day', TRUE, '06-12'),
('Ninoy Aquino Day', '2024-08-21', 'special_non_working', 'Death anniversary of Benigno Aquino Jr.', TRUE, '08-21'),
('National Heroes Day', '2024-08-26', 'regular', 'Last Monday of August', FALSE, NULL),
('All Saints\' Day', '2024-11-01', 'special_non_working', 'Christian holiday honoring all saints', TRUE, '11-01'),
('Bonifacio Day', '2024-11-30', 'regular', 'Birth anniversary of Andres Bonifacio', TRUE, '11-30'),
('Feast of the Immaculate Conception', '2024-12-08', 'special_non_working', 'Catholic feast day', TRUE, '12-08'),
('Christmas Eve', '2024-12-24', 'special_non_working', 'Day before Christmas', TRUE, '12-24'),
('Christmas Day', '2024-12-25', 'regular', 'Celebration of the birth of Jesus Christ', TRUE, '12-25'),
('Rizal Day', '2024-12-30', 'regular', 'Death anniversary of Dr. Jose Rizal', TRUE, '12-30'),
('New Year\'s Eve', '2024-12-31', 'special_non_working', 'Last day of the year', TRUE, '12-31');

SELECT 'Holidays data inserted successfully!' AS Status;
SELECT COUNT(*) AS 'Total Holidays' FROM philippine_holidays;
```

---

## 📁 **Step 4: Create Upload Directory**

1. Navigate to your project folder: `C:\xampp\htdocs\new backend\`
2. Create these directories:
   ```
   uploads/
   └── events/
   ```
3. Make sure the `uploads` folder has write permissions

---

## ✅ **Step 5: Verify Setup**

**Run this verification script:**

```sql
USE news_management;

-- Check tables exist
SHOW TABLES;

-- Check data counts
SELECT 
    (SELECT COUNT(*) FROM events) AS events_count,
    (SELECT COUNT(*) FROM philippine_holidays) AS holidays_count;

-- Test the queries used by the website
SELECT 'Upcoming events:' AS test;
SELECT title, event_date, location FROM events 
WHERE event_date >= CURDATE() AND status IN ('upcoming', 'ongoing') 
ORDER BY event_date LIMIT 3;

SELECT 'Current month holidays:' AS test;
SELECT name, date, type FROM philippine_holidays 
WHERE MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE()) 
ORDER BY date;

SELECT '✅ Setup verification complete!' AS Status;
```

---

## 🧪 **Step 6: Test the Website**

1. **Go to Admin Dashboard**: `http://localhost/new backend/admin/`
2. **Login** with your admin credentials
3. **Click the "Events" tab** - it should now work without errors!
4. **Try adding a test event** to make sure everything works
5. **Visit the main page**: `http://localhost/new backend/` 
6. **Scroll down** to see the Events and Holidays sections

---

## 🔧 **Troubleshooting**

### If you still get errors:

1. **Check database name**: Make sure you're using the correct database name in your `config/db.php`
2. **Check permissions**: Ensure your database user has CREATE and INSERT permissions
3. **Check PHP errors**: Look at your PHP error log for more details

### Common Issues:

- **"Table doesn't exist"**: Run Step 2 again
- **"No holidays showing"**: Run Step 3 again  
- **"Can't upload images"**: Create the uploads/events/ directory (Step 4)

---

## 🎉 **Success!**

After completing these steps, you should have:
- ✅ Working Events management in admin
- ✅ Events section on landing page
- ✅ Philippine holidays section showing current month
- ✅ Full CRUD functionality for events
- ✅ Image upload capability

The error should be completely resolved and your Events system should be fully functional!
