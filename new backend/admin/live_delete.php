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
$liveUpdate = null;

// Get live update ID from URL
$updateId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($updateId <= 0) {
    header('Location: index.php?error=Invalid update ID');
    exit();
}

// Fetch live update data for confirmation
$stmt = $pdo->prepare("SELECT * FROM live_updates WHERE id = ?");
$stmt->execute([$updateId]);
$liveUpdate = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$liveUpdate) {
    header('Location: index.php?error=Live update not found');
    exit();
}

// Handle deletion confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM live_updates WHERE id = ?");
        $stmt->execute([$updateId]);
        
        // Redirect with success message
        header('Location: index.php?message=Live update deleted successfully');
        exit();
        
    } catch (Exception $e) {
        $error = "Error deleting live update: " . $e->getMessage();
    }
}

// Handle cancellation
if (isset($_GET['cancel'])) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete Live Update - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; background: #f6f6f6; margin: 0; }
        header { background: #003366; color: #fff; padding: 1em 2em; }
        main { max-width: 600px; margin: 2em auto; background: #fff; padding: 2em; border-radius: 8px; }
        h1 { color: #d32f2f; }
        .btn { display: inline-block; padding: 0.5em 1.2em; border-radius: 4px; text-decoration: none; margin: 0.5em 0.5em 0.5em 0; cursor: pointer; border: none; }
        .btn-danger { background: #d32f2f; color: #fff; }
        .btn-danger:hover { background: #b71c1c; }
        .btn-secondary { background: #666; color: #fff; }
        .btn-secondary:hover { background: #555; }
        .update-preview { background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; padding: 1em; margin: 1em 0; }
        .update-message { margin-bottom: 0.5em; }
        .update-meta { font-size: 0.9em; color: #888; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 1em; border-radius: 4px; margin: 1em 0; }
        .error { color: red; margin-bottom: 1em; padding: 0.5em; background: #fff2f0; border: 1px solid #ffccc7; border-radius: 4px; }
        .confirmation-form { margin-top: 2em; }
    </style>
</head>
<body>
<header>
    <span style="font-size:1.5em;">🗑️ Delete Live Update</span>
    <a href="index.php" style="float:right; color:#fff; text-decoration:none;">← Back to Dashboard</a>
</header>
<main>
    <h1>Delete Live Update</h1>
    
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="warning">
        <strong>⚠️ Warning:</strong> This action cannot be undone. The live update will be permanently deleted.
    </div>

    <div class="update-preview">
        <div class="update-message"><?php echo htmlspecialchars($liveUpdate['message']); ?></div>
        <div class="update-meta">
            Created: <?php echo htmlspecialchars($liveUpdate['created_at']); ?>
        </div>
    </div>

    <div class="confirmation-form">
        <p><strong>Are you sure you want to delete this live update?</strong></p>
        
        <form method="post" style="display: inline;">
            <input type="hidden" name="confirm_delete" value="1">
            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure? This cannot be undone!')">
                🗑️ Yes, Delete Update
            </button>
        </form>
        
        <a href="index.php" class="btn btn-secondary">
            ← Cancel
        </a>
    </div>
</main>
</body>
</html>
