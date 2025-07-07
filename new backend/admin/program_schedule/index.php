<?php
session_start();
include '../../config/db.php';
include '../../includes/auth.php';

if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

require_once '../../config/db.php';

// Fetch all program schedules, grouped by program_name
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

// Group by program_name
$programs = [];
foreach ($schedules as $row) {
    $programs[$row['program_name']][] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Program Schedule</title>
</head>
<body>
    <h1>Program Schedule</h1>
    <a href="create.php">Add New Program</a>
    <table>
        <thead>
            <tr>
                <th>Program Name</th>
                <th>Day of Week</th>
                <th>Time Slot</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($programs as $program_name => $entries): ?>
            <?php $rowspan = count($entries); $first = true; ?>
            <?php foreach ($entries as $entry): ?>
                <tr>
                    <?php if ($first): ?>
                        <td rowspan="<?php echo $rowspan; ?>"><?php echo htmlspecialchars($program_name); ?></td>
                        <?php $first = false; ?>
                    <?php endif; ?>
                    <td><?php echo htmlspecialchars($entry['day_of_week']); ?></td>
                    <td><?php echo htmlspecialchars($entry['time_slot']); ?></td>
                    <td>
                        <a href="edit.php?id=<?php echo $entry['id']; ?>">Edit</a> | 
                        <a href="delete.php?id=<?php echo $entry['id']; ?>" onclick="return confirm('Delete this entry?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
        </tbody>
    </table>
    <a href="../index.php">Back to Admin Dashboard</a>
</body>
</html></td>