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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $liveMessage = trim($_POST['message'] ?? '');

    if ($liveMessage) {
        try {
            $stmt = $pdo->prepare("INSERT INTO live_updates (message, created_at) VALUES (?, NOW())");
            $stmt->execute([$liveMessage]);
            $message = "Live update added successfully!";
            $liveMessage = ''; // Clear form
        } catch (Exception $e) {
            $error = "Error adding live update: " . $e->getMessage();
        }
    } else {
        $error = "Message is required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Live Update - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; background: #f6f6f6; margin: 0; }
        header { background: #003366; color: #fff; padding: 1em 2em; }
        main { max-width: 600px; margin: 2em auto; background: #fff; padding: 2em; border-radius: 8px; }
        h1 { color: #003366; }
        .btn { display: inline-block; padding: 0.5em 1.2em; background: #003366; color: #fff; border-radius: 4px; text-decoration: none; margin-bottom: 1em; }
        .btn:hover { background: #1890ff; }
        form { margin-bottom: 2em; }
        label { display: block; margin-top: 1em; font-weight: bold; }
        textarea { width: 100%; padding: 0.5em; border: 1px solid #ddd; border-radius: 4px; height: 100px; resize: vertical; }
        button { margin-top: 1em; padding: 0.5em 1.5em; background: #003366; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #1890ff; }
        .msg { color: green; margin-bottom: 1em; padding: 0.5em; background: #f0f8ff; border: 1px solid #91d5ff; border-radius: 4px; }
        .error { color: red; margin-bottom: 1em; padding: 0.5em; background: #fff2f0; border: 1px solid #ffccc7; border-radius: 4px; }
        .form-help { font-size: 0.9em; color: #666; margin-top: 0.3em; }
    </style>
</head>
<body>
<header>
    <span style="font-size:1.5em;">📢 Add Live Update</span>
    <a href="index.php" style="float:right; color:#fff; text-decoration:none;">← Back to Dashboard</a>
</header>
<main>
    <h1>Add Live Update</h1>
    
    <?php if ($message): ?>
        <div class="msg"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post">
        <label>Live Update Message: *</label>
        <textarea name="message" required placeholder="Enter your live update message here..."><?php echo htmlspecialchars($liveMessage ?? ''); ?></textarea>
        <div class="form-help">Keep it short and informative. This will appear on the main page.</div>
        
        <br>
        <button type="submit">📢 Add Live Update</button>
        <a href="index.php" class="btn" style="margin-left: 1em;">Cancel</a>
    </form>

    <div style="margin-top: 2em; padding: 1em; background: #f0f8ff; border-radius: 4px; border-left: 4px solid #91d5ff;">
        <strong>💡 Tips for Live Updates:</strong>
        <ul>
            <li>Keep messages concise and to the point</li>
            <li>Use present tense for current events</li>
            <li>Include time-sensitive information</li>
            <li>Consider your audience when writing</li>
        </ul>
    </div>
</main>
</body>
</html>
