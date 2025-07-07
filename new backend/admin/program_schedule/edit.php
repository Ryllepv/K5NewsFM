<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/auth.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $program_name = $_POST['program_name'];
    $day_of_week = $_POST['day_of_week'];
    $time_slot = $_POST['time_slot'];

    $stmt = $pdo->prepare("UPDATE program_schedule SET program_name = ?, day_of_week = ?, time_slot = ? WHERE id = ?");
    $stmt->execute([$program_name, $day_of_week, $time_slot, $id]);

    header('Location: index.php');
    exit();
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM program_schedule WHERE id = ?");
$stmt->execute([$id]);
$schedule = $stmt->fetch();

if (!$schedule) {
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Program Schedule</title>
</head>
<body>
    <h1>Edit Program Schedule</h1>
    <form action="edit.php" method="POST">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($schedule['id']); ?>">
        <label for="program_name">Program Name:</label>
        <input type="text" name="program_name" id="program_name" value="<?php echo htmlspecialchars($schedule['program_name']); ?>" required>
        <br>
        <label for="day_of_week">Day of Week:</label>
        <input type="text" name="day_of_week" id="day_of_week" value="<?php echo htmlspecialchars($schedule['day_of_week']); ?>" required>
        <br>
        <label for="time_slot">Time Slot:</label>
        <input type="text" name="time_slot" id="time_slot" value="<?php echo htmlspecialchars($schedule['time_slot']); ?>" required>
        <br>
        <button type="submit">Update Schedule</button>
    </form>
    <a href="index.php">Cancel</a>
</body>
</html>