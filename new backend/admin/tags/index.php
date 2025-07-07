<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Fetch tags from the database
$stmt = $pdo->prepare("SELECT * FROM tags");
$stmt->execute();
$tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Tags</title>
</head>
<body>
    <h1>Manage Tags</h1>
    <a href="create.php">Add New Tag</a>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tags as $tag): ?>
                <tr>
                    <td><?php echo htmlspecialchars($tag['id']); ?></td>
                    <td><?php echo htmlspecialchars($tag['name']); ?></td>
                    <td>
                        <a href="edit.php?id=<?php echo htmlspecialchars($tag['id']); ?>">Edit</a>
                        <a href="delete.php?id=<?php echo htmlspecialchars($tag['id']); ?>">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>