<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if the user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$message = '';
$error = '';
$editMode = false;
$programData = [];

// Check if editing existing program
if (isset($_GET['program']) && isset($_GET['time'])) {
    $editMode = true;
    $programName = $_GET['program'];
    $timeSlot = $_GET['time'];
    
    // Fetch existing program data
    $stmt = $pdo->prepare("SELECT * FROM program_schedule WHERE program_name = ? AND time_slot = ? ORDER BY 
        CASE 
            WHEN day_of_week = 'Monday' THEN 1
            WHEN day_of_week = 'Tuesday' THEN 2
            WHEN day_of_week = 'Wednesday' THEN 3
            WHEN day_of_week = 'Thursday' THEN 4
            WHEN day_of_week = 'Friday' THEN 5
            WHEN day_of_week = 'Saturday' THEN 6
            WHEN day_of_week = 'Sunday' THEN 7
        END");
    $stmt->execute([$programName, $timeSlot]);
    $programData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($programData)) {
        $error = "Program not found.";
        $editMode = false;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $programName = trim($_POST['program_name'] ?? '');
    $timeSlot = trim($_POST['time_slot'] ?? '');
    $selectedDays = $_POST['days'] ?? [];
    $oldProgramName = $_POST['old_program_name'] ?? '';
    $oldTimeSlot = $_POST['old_time_slot'] ?? '';

    if ($programName && $timeSlot && !empty($selectedDays)) {
        try {
            // If editing, delete old entries first
            if ($editMode && $oldProgramName && $oldTimeSlot) {
                $stmt = $pdo->prepare("DELETE FROM program_schedule WHERE program_name = ? AND time_slot = ?");
                $stmt->execute([$oldProgramName, $oldTimeSlot]);
            }

            // Insert new/updated entries
            $stmt = $pdo->prepare("INSERT INTO program_schedule (program_name, day_of_week, time_slot) VALUES (?, ?, ?)");
            
            foreach ($selectedDays as $day) {
                $stmt->execute([$programName, $day, $timeSlot]);
            }

            $message = $editMode ? "Program updated successfully!" : "Program added successfully!";
            
            // Clear form data after successful submission
            $programName = '';
            $timeSlot = '';
            $selectedDays = [];
            $editMode = false;
            
        } catch (Exception $e) {
            $error = "Error saving program: " . $e->getMessage();
        }
    } else {
        if (!$programName) $error = "Program name is required.";
        elseif (!$timeSlot) $error = "Time slot is required.";
        elseif (empty($selectedDays)) $error = "At least one day must be selected.";
    }
}

// Get current selected days for edit mode
$selectedDays = [];
if ($editMode && !empty($programData)) {
    $selectedDays = array_column($programData, 'day_of_week');
    $programName = $programData[0]['program_name'];
    $timeSlot = $programData[0]['time_slot'];
}

$daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $editMode ? 'Edit' : 'Add'; ?> Program Schedule - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; background: #f6f6f6; margin: 0; }
        header { background: #003366; color: #fff; padding: 1em 2em; }
        main { max-width: 800px; margin: 2em auto; background: #fff; padding: 2em; border-radius: 8px; }
        h1 { color: #003366; }
        .btn { display: inline-block; padding: 0.5em 1.2em; background: #003366; color: #fff; border-radius: 4px; text-decoration: none; margin-bottom: 1em; }
        .btn:hover { background: #1890ff; }
        form { margin-bottom: 2em; }
        label { display: block; margin-top: 1em; font-weight: bold; }
        input[type="text"], input[type="time"], textarea { width: 100%; padding: 0.5em; border: 1px solid #ddd; border-radius: 4px; }
        button { margin-top: 1em; padding: 0.5em 1.5em; background: #003366; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #1890ff; }
        .msg { color: green; margin-bottom: 1em; padding: 0.5em; background: #f0f8ff; border: 1px solid #91d5ff; border-radius: 4px; }
        .error { color: red; margin-bottom: 1em; padding: 0.5em; background: #fff2f0; border: 1px solid #ffccc7; border-radius: 4px; }
        .form-help { font-size: 0.9em; color: #666; margin-top: 0.3em; }
        .days-container { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); 
            gap: 0.5em; 
            margin: 0.5em 0; 
            padding: 1em; 
            background: #f9f9f9; 
            border-radius: 4px; 
            border: 1px solid #ddd; 
        }
        .day-checkbox { 
            display: flex; 
            align-items: center; 
            gap: 0.5em; 
            padding: 0.5em; 
            border-radius: 3px; 
            transition: background-color 0.2s; 
        }
        .day-checkbox:hover { background: #f0f0f0; }
        .day-checkbox input[type="checkbox"] { 
            margin: 0; 
            transform: scale(1.2); 
        }
        .day-checkbox label { 
            margin: 0; 
            cursor: pointer; 
            font-weight: normal; 
            font-size: 0.95em; 
        }
        .time-inputs { display: grid; grid-template-columns: 1fr auto 1fr; gap: 1em; align-items: center; }
        .quick-select { margin: 1em 0; }
        .quick-btn { padding: 0.3em 0.8em; background: #f0f8ff; color: #003366; border: 1px solid #b3d8fd; border-radius: 4px; margin: 0.2em; cursor: pointer; font-size: 0.9em; }
        .quick-btn:hover { background: #e6f7ff; }
    </style>
</head>
<body>
<header>
    <span style="font-size:1.5em;">📻 <?php echo $editMode ? 'Edit' : 'Add'; ?> Program Schedule</span>
    <a href="index.php" style="float:right; color:#fff; text-decoration:none;">← Back to Dashboard</a>
</header>
<main>
    <h1><?php echo $editMode ? 'Edit' : 'Add'; ?> Program Schedule</h1>
    
    <?php if ($message): ?>
        <div class="msg"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post">
        <?php if ($editMode): ?>
            <input type="hidden" name="old_program_name" value="<?php echo htmlspecialchars($programData[0]['program_name']); ?>">
            <input type="hidden" name="old_time_slot" value="<?php echo htmlspecialchars($programData[0]['time_slot']); ?>">
        <?php endif; ?>
        
        <label>Program Name: *</label>
        <input type="text" name="program_name" required value="<?php echo htmlspecialchars($programName ?? ''); ?>" placeholder="Enter program name">
        
        <label>Time Slot: *</label>
        <input type="text" name="time_slot" required value="<?php echo htmlspecialchars($timeSlot ?? ''); ?>" placeholder="e.g., 6:00 AM - 9:00 AM">
        <div class="form-help">Enter the time range for this program (e.g., "6:00 AM - 9:00 AM" or "18:00 - 21:00")</div>
        
        <label>Days: *</label>
        <div class="days-container">
            <?php foreach ($daysOfWeek as $day): ?>
                <div class="day-checkbox">
                    <input type="checkbox" 
                           name="days[]" 
                           value="<?php echo $day; ?>" 
                           id="day_<?php echo strtolower($day); ?>"
                           <?php echo in_array($day, $selectedDays) ? 'checked' : ''; ?>>
                    <label for="day_<?php echo strtolower($day); ?>"><?php echo $day; ?></label>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="form-help">Select the days when this program airs</div>
        
        <div class="quick-select">
            <strong>Quick Select:</strong><br>
            <button type="button" class="quick-btn" onclick="selectDays(['Monday','Tuesday','Wednesday','Thursday','Friday'])">Weekdays (Mon-Fri)</button>
            <button type="button" class="quick-btn" onclick="selectDays(['Saturday','Sunday'])">Weekends (Sat-Sun)</button>
            <button type="button" class="quick-btn" onclick="selectDays(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'])">Daily (All Days)</button>
            <button type="button" class="quick-btn" onclick="clearDays()">Clear All</button>
        </div>
        
        <br>
        <button type="submit">📻 <?php echo $editMode ? 'Update' : 'Add'; ?> Program</button>
        <a href="index.php" class="btn" style="margin-left: 1em;">Cancel</a>
    </form>

    <div style="margin-top: 2em; padding: 1em; background: #f0f8ff; border-radius: 4px; border-left: 4px solid #91d5ff;">
        <strong>💡 Tips:</strong>
        <ul>
            <li>Use clear, descriptive program names</li>
            <li>Include AM/PM in time slots for clarity</li>
            <li>Use consistent time format across all programs</li>
            <li>Consider your audience when scheduling programs</li>
        </ul>
    </div>

    <script>
    function selectDays(days) {
        // Clear all checkboxes first
        const checkboxes = document.querySelectorAll('input[name="days[]"]');
        checkboxes.forEach(cb => cb.checked = false);
        
        // Check selected days
        days.forEach(day => {
            const checkbox = document.getElementById('day_' + day.toLowerCase());
            if (checkbox) checkbox.checked = true;
        });
    }
    
    function clearDays() {
        const checkboxes = document.querySelectorAll('input[name="days[]"]');
        checkboxes.forEach(cb => cb.checked = false);
    }
    </script>
</main>
</body>
</html>
