<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $message = trim($_POST['message']);
    
    if (!empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO live_updates (message, created_at) VALUES (:message, NOW())");
        $stmt->bindParam(':message', $message);
        
        if ($stmt->execute()) {
            header("Location: index.php?success=Live update created successfully.");
            exit();
        } else {
            $error = "Failed to create live update.";
        }
    } else {
        $error = "Message cannot be empty.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Live Update</title>
</head>
<body>
    <h1>Create Live Update</h1>
    <?php if (isset($error)): ?>
        <p style="color:red;"><?php echo $error; ?></p>
    <?php endif; ?>
    <form action="create.php" method="POST">
        <label for="message">Message:</label><br>
        <textarea name="message" id="message" rows="4" required></textarea><br>
        <button type="submit">Create Live Update</button>
    </form>
    <a href="index.php">Back to Live Updates</a>
</body>
</html>