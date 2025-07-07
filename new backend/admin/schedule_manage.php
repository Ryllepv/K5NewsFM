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

// Handle program deletion
if (isset($_GET['delete']) && isset($_GET['program']) && isset($_GET['time'])) {
    $programName = $_GET['program'];
    $timeSlot = $_GET['time'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM program_schedule WHERE program_name = ? AND time_slot = ?");
        $stmt->execute([$programName, $timeSlot]);
        $message = "Program deleted successfully!";
    } catch (Exception $e) {
        $error = "Error deleting program: " . $e->getMessage();
    }
}

// Fetch and group program schedule
$stmt = $pdo->query("SELECT * FROM program_schedule ORDER BY program_name, 
    CASE 
        WHEN day_of_week = 'Monday' THEN 1
        WHEN day_of_week = 'Tuesday' THEN 2
        WHEN day_of_week = 'Wednesday' THEN 3
        WHEN day_of_week = 'Thursday' THEN 4
        WHEN day_of_week = 'Friday' THEN 5
        WHEN day_of_week = 'Saturday' THEN 6
        WHEN day_of_week = 'Sunday' THEN 7
        ELSE 8
    END, time_slot");
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group programs by name and time slot
$groupedPrograms = [];
foreach ($schedules as $schedule) {
    $key = $schedule['program_name'] . '|' . $schedule['time_slot'];
    $groupedPrograms[$key]['program_name'] = $schedule['program_name'];
    $groupedPrograms[$key]['time_slot'] = $schedule['time_slot'];
    $groupedPrograms[$key]['days'][] = $schedule['day_of_week'];
    $groupedPrograms[$key]['ids'][] = $schedule['id'];
}

// Function to format day ranges
function formatDayRange($days) {
    $dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    $dayAbbr = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    
    // Sort days according to week order
    usort($days, function($a, $b) use ($dayOrder) {
        return array_search($a, $dayOrder) - array_search($b, $dayOrder);
    });
    
    // Convert to abbreviations
    $abbrDays = array_map(function($day) use ($dayOrder, $dayAbbr) {
        return $dayAbbr[array_search($day, $dayOrder)];
    }, $days);
    
    // Create ranges
    if (count($abbrDays) == 1) {
        return $abbrDays[0];
    } elseif (count($abbrDays) == 7) {
        return 'Daily';
    } elseif (array_slice($abbrDays, 0, 5) == ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'] && count($abbrDays) == 5) {
        return 'Mon-Fri';
    } elseif (array_slice($abbrDays, 5, 2) == ['Sat', 'Sun'] && count($abbrDays) == 2) {
        return 'Sat-Sun';
    } else {
        // Check for consecutive ranges
        $ranges = [];
        $start = 0;
        for ($i = 1; $i <= count($abbrDays); $i++) {
            if ($i == count($abbrDays) || array_search($abbrDays[$i], $dayAbbr) != array_search($abbrDays[$i-1], $dayAbbr) + 1) {
                if ($i - $start > 2) {
                    $ranges[] = $abbrDays[$start] . '-' . $abbrDays[$i-1];
                } elseif ($i - $start == 2) {
                    $ranges[] = $abbrDays[$start] . ', ' . $abbrDays[$i-1];
                } else {
                    $ranges[] = $abbrDays[$start];
                }
                $start = $i;
            }
        }
        return implode(', ', $ranges);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Program Schedule - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; background: #f6f6f6; margin: 0; }
        header { background: #003366; color: #fff; padding: 1em 2em; }
        main { max-width: 1200px; margin: 2em auto; background: #fff; padding: 2em; border-radius: 8px; }
        h1 { color: #003366; }
        .btn { display: inline-block; padding: 0.5em 1.2em; background: #003366; color: #fff; border-radius: 4px; text-decoration: none; margin-bottom: 1em; }
        .btn:hover { background: #1890ff; }
        .btn-success { background: #52c41a; }
        .btn-success:hover { background: #73d13d; }
        .btn-danger { background: #ff4d4f; }
        .btn-danger:hover { background: #ff7875; }
        .msg { color: green; margin-bottom: 1em; padding: 0.5em; background: #f0f8ff; border: 1px solid #91d5ff; border-radius: 4px; }
        .error { color: red; margin-bottom: 1em; padding: 0.5em; background: #fff2f0; border: 1px solid #ffccc7; border-radius: 4px; }
        .schedule-container { 
            background: #fff; 
            border-radius: 8px; 
            overflow: hidden; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.1); 
            margin-top: 1em; 
        }
        .schedule-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 0; 
        }
        .schedule-table th { 
            background: #003366; 
            color: #fff; 
            padding: 1em; 
            text-align: left; 
            font-weight: bold; 
        }
        .schedule-table td { 
            padding: 0.8em 1em; 
            border-bottom: 1px solid #f0f0f0; 
        }
        .schedule-table tr:hover { 
            background: #f8f9fa; 
        }
        .program-name { 
            font-weight: bold; 
            color: #003366; 
        }
        .program-days { 
            color: #1890ff; 
            font-weight: 500; 
            background: #f0f8ff; 
            border-radius: 4px; 
            padding: 0.3em 0.6em; 
            display: inline-block; 
            font-size: 0.9em; 
        }
        .program-time { 
            color: #666; 
            font-family: monospace; 
        }
        .actions { 
            display: flex; 
            gap: 0.5em; 
        }
        .edit-btn { 
            padding: 0.3em 0.8em; 
            background: #ffd666; 
            color: #333; 
            border-radius: 4px; 
            text-decoration: none; 
            font-size: 0.9em; 
        }
        .edit-btn:hover { 
            background: #ffc53d; 
        }
        .delete-btn { 
            padding: 0.3em 0.8em; 
            background: #ff4d4f; 
            color: #fff; 
            border-radius: 4px; 
            text-decoration: none; 
            font-size: 0.9em; 
        }
        .delete-btn:hover { 
            background: #ff7875; 
        }
        .stats { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 1em; 
            margin-bottom: 2em; 
        }
        .stat-card { 
            background: #f0f8ff; 
            border: 1px solid #b3d8fd; 
            border-radius: 6px; 
            padding: 1em; 
            text-align: center; 
        }
        .stat-number { 
            font-size: 2em; 
            font-weight: bold; 
            color: #003366; 
        }
        .stat-label { 
            color: #666; 
            font-size: 0.9em; 
        }
    </style>
</head>
<body>
<header>
    <span style="font-size:1.5em;">📋 Manage Program Schedule</span>
    <a href="index.php" style="float:right; color:#fff; text-decoration:none;">← Back to Dashboard</a>
</header>
<main>
    <h1>Program Schedule Management</h1>
    
    <?php if ($message): ?>
        <div class="msg"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div style="margin-bottom: 2em;">
        <a href="schedule_edit.php" class="btn btn-success">➕ Add New Program</a>
        <a href="../index.php#schedule" class="btn" target="_blank">👁️ View Public Schedule</a>
    </div>

    <div class="stats">
        <div class="stat-card">
            <div class="stat-number"><?php echo count($groupedPrograms); ?></div>
            <div class="stat-label">Total Programs</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo count($schedules); ?></div>
            <div class="stat-label">Schedule Entries</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">
                <?php 
                $uniqueDays = [];
                foreach ($schedules as $schedule) {
                    $uniqueDays[$schedule['day_of_week']] = true;
                }
                echo count($uniqueDays);
                ?>
            </div>
            <div class="stat-label">Days Covered</div>
        </div>
    </div>

    <div class="schedule-container">
        <table class="schedule-table">
            <thead>
                <tr>
                    <th>Program Name</th>
                    <th>Days</th>
                    <th>Time Slot</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($groupedPrograms)): ?>
                    <?php foreach ($groupedPrograms as $program): ?>
                        <tr>
                            <td class="program-name"><?php echo htmlspecialchars($program['program_name']); ?></td>
                            <td><span class="program-days"><?php echo formatDayRange($program['days']); ?></span></td>
                            <td class="program-time"><?php echo htmlspecialchars($program['time_slot']); ?></td>
                            <td class="actions">
                                <a href="schedule_edit.php?program=<?php echo urlencode($program['program_name']); ?>&time=<?php echo urlencode($program['time_slot']); ?>" class="edit-btn">✏️ Edit</a>
                                <a href="?delete=1&program=<?php echo urlencode($program['program_name']); ?>&time=<?php echo urlencode($program['time_slot']); ?>" class="delete-btn" onclick="return confirm('Delete program \'<?php echo addslashes($program['program_name']); ?>\'?\n\nThis will remove all schedule entries for this program.')">🗑️ Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 2em; color: #666; font-style: italic;">
                            No programs scheduled yet.<br>
                            <a href="schedule_edit.php" style="color: #003366;">Add your first program</a> to get started!
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div style="margin-top: 2em; padding: 1em; background: #f0f8ff; border-radius: 4px; border-left: 4px solid #91d5ff;">
        <h3>💡 Schedule Management Tips</h3>
        <ul>
            <li><strong>Consistent Timing:</strong> Use consistent time formats (e.g., "6:00 AM - 9:00 AM")</li>
            <li><strong>Clear Names:</strong> Use descriptive program names that reflect content</li>
            <li><strong>Day Ranges:</strong> The system automatically groups consecutive days (Mon-Fri, Sat-Sun)</li>
            <li><strong>Public View:</strong> Changes appear immediately on the public website</li>
            <li><strong>Editing:</strong> When editing, you can change program name, time, or days</li>
        </ul>
    </div>
</main>
</body>
</html>
