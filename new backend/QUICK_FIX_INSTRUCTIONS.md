# 🚨 QUICK FIX for Events Table Error

## Your Database: `news_management` (MariaDB)

Based on your existing schema, here's the **exact fix** for the Events table error:

---

## 🔧 **STEP 1: Run This SQL Script**

1. **Open phpMyAdmin** → Go to `news_management` database
2. **Click "SQL" tab**
3. **Copy and paste this ENTIRE script:**

```sql
USE `news_management`;

-- Create Events Table (matches your existing schema style)
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

-- Create Philippine Holidays Table
CREATE TABLE `philippine_holidays` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `type` enum('regular','special_non_working','special_working') DEFAULT 'regular',
  `description` text DEFAULT NULL,
  `is_recurring` tinyint(1) DEFAULT 0,
  `month_day` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_holiday_date` (`date`),
  KEY `idx_holiday_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert test event
INSERT INTO `events` (`title`, `description`, `event_date`, `event_time`, `location`, `status`) VALUES
('K5 News FM Open House', 'Join us at our station!', '2024-12-31', '14:00:00', '3rd Floor Macariola Building, Olongapo City', 'upcoming');

-- Insert some holidays for December 2024
INSERT INTO `philippine_holidays` (`name`, `date`, `type`, `description`, `is_recurring`, `month_day`) VALUES
('Christmas Eve', '2024-12-24', 'special_non_working', 'Day before Christmas', 1, '12-24'),
('Christmas Day', '2024-12-25', 'regular', 'Celebration of the birth of Jesus Christ', 1, '12-25'),
('Rizal Day', '2024-12-30', 'regular', 'Death anniversary of Dr. Jose Rizal', 1, '12-30'),
('New Year\'s Eve', '2024-12-31', 'special_non_working', 'Last day of the year', 1, '12-31');

-- Verify everything worked
SELECT 'Tables created successfully!' AS status;
SELECT COUNT(*) AS events_count FROM `events`;
SELECT COUNT(*) AS holidays_count FROM `philippine_holidays`;
```

4. **Click "Go" to execute**

---

## 📁 **STEP 2: Create Upload Directory**

1. Go to: `C:\xampp\htdocs\new backend\`
2. Create folder: `uploads`
3. Inside `uploads`, create folder: `events`

Final structure:
```
C:\xampp\htdocs\new backend\
├── uploads/
│   └── events/
```

---

## ✅ **STEP 3: Test the Fix**

1. **Refresh your admin page**: `http://localhost/new backend/admin/`
2. **Click "Events" tab** - Should work now! ✅
3. **Try adding a test event**
4. **Check main page**: `http://localhost/new backend/` - Scroll down to see Events and Holidays sections

---

## 🎯 **What This Does:**

✅ **Fixes the "Table doesn't exist" error**  
✅ **Creates events table matching your existing schema style**  
✅ **Creates holidays table with Philippine holidays**  
✅ **Adds sample data so you can see it working immediately**  
✅ **Uses same charset/collation as your existing tables**

---

## 🔍 **If You Want More Holidays:**

After the basic fix works, you can run the complete `add_events_and_holidays_tables.sql` file to get all 2024-2025 Philippine holidays.

---

## 🆘 **Still Having Issues?**

If you get any errors:

1. **Check the error message** - copy/paste it to me
2. **Verify database name** - make sure you're in `news_management` 
3. **Check permissions** - your MySQL user needs CREATE TABLE rights

---

**This should completely fix your Events table error!** 🎉
