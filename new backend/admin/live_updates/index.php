<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

// Fetch live updates from the database
$stmt = $pdo->prepare("SELECT * FROM live_updates ORDER BY created_at DESC");
$stmt->execute();
$live_updates = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
?>

<h1>Live Updates Management</h1>

<a href="create.php">Add New Live Update</a>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Message</th>
            <th>Date Posted</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($live_updates as $update): ?>
            <tr>
                <td><?php echo htmlspecialchars($update['id']); ?></td>
                <td><?php echo htmlspecialchars($update['message']); ?></td>
                <td><?php echo htmlspecialchars($update['created_at']); ?></td>
                <td>
                    <a href="edit.php?id=<?php echo htmlspecialchars($update['id']); ?>">Edit</a>
                    <a href="delete.php?id=<?php echo htmlspecialchars($update['id']); ?>">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php include '../../includes/footer.php'; ?>