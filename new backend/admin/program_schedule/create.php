<?php
require_once '../../config/db.php';

$daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$timeSlots = [
    'Morning (08:00 - 12:00)',
    'Afternoon (12:00 - 16:00)',
    'Evening (16:00 - 20:00)',
    'Night (20:00 - 00:00)'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $program_name = $_POST['program_name'] ?? '';
    $selected_days = $_POST['days_of_week'] ?? [];
    $preset_slot = $_POST['preset_slot'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';

    // Use preset slot if selected, otherwise use custom times
    if ($preset_slot) {
        $time_slot = $preset_slot;
    } else {
        $time_slot = $start_time . ' - ' . $end_time;
    }

    // Insert one row for each selected day
    foreach ($selected_days as $day_of_week) {
        $stmt = $pdo->prepare("INSERT INTO program_schedule (program_name, day_of_week, time_slot) VALUES (?, ?, ?)");
        $stmt->execute([$program_name, $day_of_week, $time_slot]);
    }
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Program Schedule</title>
</head>
<body>
    <h1>Create Program Schedule Entry</h1>
    <form method="post">
        <label>Program Name:</label>
        <input type="text" name="program_name" required><br><br>

        <label>Days of Week:</label><br>
        <?php foreach ($daysOfWeek as $day): ?>
            <input type="checkbox" name="days_of_week[]" value="<?php echo $day; ?>" id="day_<?php echo $day; ?>">
            <label for="day_<?php echo $day; ?>"><?php echo $day; ?></label>
        <?php endforeach; ?>
        <br><br>

        <label>Preset Time Slot:</label><br>
        <?php foreach ($timeSlots as $slot): ?>
            <input type="radio" name="preset_slot" value="<?php echo $slot; ?>" id="slot_<?php echo $slot; ?>">
            <label for="slot_<?php echo $slot; ?>"><?php echo $slot; ?></label><br>
        <?php endforeach; ?>
        <input type="radio" name="preset_slot" value="" id="slot_custom" checked>
        <label for="slot_custom">Custom</label><br><br>

        <div>
            <label>Start Time:</label>
            <input type="time" name="start_time">
            <label>End Time:</label>
            <input type="time" name="end_time">
        </div>
        <br>
        <button type="submit">Create Entry</button>
    </form>
    <a href="index.php">Back to Program Schedule</a>
</body>
</html>