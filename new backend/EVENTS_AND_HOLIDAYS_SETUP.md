# Events and Holidays Setup Guide

## Database Setup

### 1. Run the Updated Schema
Execute the updated `init.sql` file to create the new tables:
- `events` - Stores event information
- `philippine_holidays` - Stores Philippine holiday data

### 2. Populate Holidays Data
Run the `philippine_holidays.sql` file to populate the holidays table with Philippine holidays for 2024-2025.

```sql
-- Run these commands in your MySQL database
SOURCE init.sql;
SOURCE philippine_holidays.sql;
```

## New Features Added

### 📅 Events Management System

#### Admin Features:
- **Events Tab** in admin dashboard (`admin/index.php?tab=events`)
- **Add Events** (`admin/events_add.php`)
- **Edit Events** (`admin/events_edit.php`)
- **Delete Events** (`admin/events_delete.php`)

#### Event Fields:
- Title (required)
- Description
- Event Date (required)
- Event Time (optional)
- Location
- Status (upcoming, ongoing, completed, cancelled)
- Image upload

#### Landing Page Display:
- Shows upcoming events in attractive cards
- Displays event images or placeholder icons
- Shows date, time, location, and description preview
- Responsive grid layout

### 🇵🇭 Philippine Holidays Section

#### Features:
- Automatically displays current month's holidays
- Shows holiday types (Regular, Special Non-Working, Special Working)
- Color-coded holiday types
- Includes holiday descriptions

#### Holiday Types:
- **Regular Holiday** (Green) - Official public holidays
- **Special Non-Working** (Orange) - Special non-working days
- **Special Working** (Blue) - Special working holidays

## File Structure

### New Admin Files:
```
admin/
├── events_add.php      # Add new events
├── events_edit.php     # Edit existing events
├── events_delete.php   # Delete events
└── index.php          # Updated with events tab
```

### Updated Files:
```
index.php              # Added events and holidays sections
init.sql               # Added events and holidays tables
```

### New Database Files:
```
philippine_holidays.sql # Holiday data for Philippines
```

## Usage Instructions

### For Administrators:

1. **Adding Events:**
   - Go to Admin Dashboard → Events tab
   - Click "Add New Event"
   - Fill in event details
   - Upload an image (optional)
   - Set event status

2. **Managing Events:**
   - View all events in the Events tab
   - Edit events by clicking the "Edit" button
   - Delete events with confirmation dialog
   - Events are sorted by date

### For Visitors:

1. **Viewing Events:**
   - Events appear on the main landing page
   - Only upcoming and ongoing events are shown
   - Click navigation to jump to Events section

2. **Viewing Holidays:**
   - Current month's Philippine holidays are displayed
   - Holidays are color-coded by type
   - Includes official descriptions

## Styling Features

### Events Cards:
- Responsive grid layout
- Hover animations
- Image placeholders for events without photos
- Status indicators
- Clean, modern design

### Holidays Display:
- Grid layout for easy scanning
- Color-coded holiday types
- Clear date formatting
- Professional appearance

## Technical Notes

### Database Tables:

**events table:**
- `id` - Primary key
- `title` - Event title
- `description` - Event description
- `event_date` - Event date
- `event_time` - Event time (optional)
- `location` - Event location
- `image_path` - Path to uploaded image
- `status` - Event status
- `created_at` - Creation timestamp

**philippine_holidays table:**
- `id` - Primary key
- `name` - Holiday name
- `date` - Holiday date
- `type` - Holiday type
- `description` - Holiday description
- `is_recurring` - Whether holiday recurs yearly
- `month_day` - For recurring holidays (MM-DD format)

### Image Upload:
- Events images are stored in `uploads/events/` directory
- Automatic directory creation
- Unique filename generation
- Image cleanup on event deletion

### Security:
- All inputs are sanitized
- File upload validation
- SQL injection protection with prepared statements
- Admin authentication required

## Maintenance

### Adding New Holidays:
1. Insert new records into `philippine_holidays` table
2. Set appropriate type and description
3. Use `is_recurring` for yearly holidays

### Event Status Management:
- Regularly update event statuses
- Completed events won't show on landing page
- Use status filters in admin for better organization

This system provides a complete events and holidays management solution for the radio station website!
