<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/auth.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tag_id = $_POST['tag_id'];
    $tag_name = trim($_POST['tag_name']);

    if (!empty($tag_name)) {
        $stmt = $pdo->prepare("UPDATE tags SET name = :name WHERE id = :id");
        $stmt->execute(['name' => $tag_name, 'id' => $tag_id]);
        header('Location: index.php?message=Tag updated successfully');
        exit();
    } else {
        $error = "Tag name cannot be empty.";
    }
} else {
    $tag_id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM tags WHERE id = :id");
    $stmt->execute(['id' => $tag_id]);
    $tag = $stmt->fetch();

    if (!$tag) {
        header('Location: index.php?error=Tag not found');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Tag</title>
</head>
<body>
    <h1>Edit Tag</h1>
    <?php if (isset($error)): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>
    <form action="edit.php" method="POST">
        <input type="hidden" name="tag_id" value="<?php echo $tag['id']; ?>">
        <label for="tag_name">Tag Name:</label>
        <input type="text" name="tag_name" id="tag_name" value="<?php echo htmlspecialchars($tag['name']); ?>" required>
        <button type="submit">Update Tag</button>
    </form>
    <a href="index.php">Back to Tags</a>
</body>
</html>