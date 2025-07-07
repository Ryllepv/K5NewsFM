<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tag_name = trim($_POST['tag_name']);

    if (!empty($tag_name)) {
        $stmt = $pdo->prepare("INSERT INTO tags (name) VALUES (:name)");
        $stmt->bindParam(':name', $tag_name);

        if ($stmt->execute()) {
            header("Location: index.php?success=Tag created successfully");
            exit;
        } else {
            $error = "Failed to create tag. Please try again.";
        }
    } else {
        $error = "Tag name cannot be empty.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Tag</title>
</head>
<body>
    <h1>Create New Tag</h1>
    <?php if (isset($error)): ?>
        <p style="color:red;"><?php echo $error; ?></p>
    <?php endif; ?>
    <form action="create.php" method="POST">
        <label for="tag_name">Tag Name:</label>
        <input type="text" name="tag_name" id="tag_name" required>
        <button type="submit">Create Tag</button>
    </form>
    <a href="index.php">Back to Tags</a>
</body>
</html>