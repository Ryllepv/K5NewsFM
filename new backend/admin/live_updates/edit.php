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
    $message = $_POST['message'];

    if (!empty($message)) {
        $stmt = $pdo->prepare("UPDATE live_updates SET message = :message WHERE id = :id");
        $stmt->execute(['message' => $message, 'id' => $id]);
        header('Location: index.php');
        exit();
    } else {
        $error = "Message cannot be empty.";
    }
} else {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM live_updates WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $live_update = $stmt->fetch();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Live Update</title>
</head>
<body>
    <h1>Edit Live Update</h1>
    <?php if (isset($error)): ?>
        <p style="color:red;"><?php echo $error; ?></p>
    <?php endif; ?>
    <form action="edit.php" method="POST">
        <input type="hidden" name="id" value="<?php echo $live_update['id']; ?>">
        <textarea name="message" rows="4" cols="50"><?php echo htmlspecialchars($live_update['message']); ?></textarea><br>
        <button type="submit">Update Live Update</button>
    </form>
    <a href="index.php">Back to Live Updates</a>
</body>
</html>