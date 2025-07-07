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
$event = null;

// Get event ID from URL
$eventId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($eventId <= 0) {
    header('Location: index.php?tab=events&error=Invalid event ID');
    exit();
}

// Fetch event data for confirmation
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$eventId]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    header('Location: index.php?tab=events&error=Event not found');
    exit();
}

// Handle deletion confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    try {
        // Delete the event
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$eventId]);
        
        // Delete associated image file if it exists
        if ($event['image_path'] && file_exists($event['image_path'])) {
            unlink($event['image_path']);
        }
        
        // Redirect with success message
        header('Location: index.php?tab=events&message=Event deleted successfully');
        exit();
        
    } catch (Exception $e) {
        $error = "Error deleting event: " . $e->getMessage();
    }
}

// Handle cancellation
if (isset($_GET['cancel'])) {
    header('Location: index.php?tab=events');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete Event - Admin</title>
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
        .event-preview { background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; padding: 1em; margin: 1em 0; }
        .event-title { font-weight: bold; font-size: 1.1em; margin-bottom: 0.5em; }
        .event-details { color: #666; margin-bottom: 0.5em; }
        .event-meta { font-size: 0.9em; color: #888; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 1em; border-radius: 4px; margin: 1em 0; }
        .error { color: red; margin-bottom: 1em; padding: 0.5em; background: #fff2f0; border: 1px solid #ffccc7; border-radius: 4px; }
        .confirmation-form { margin-top: 2em; }
        .event-image { max-width: 200px; height: auto; border-radius: 4px; margin: 0.5em 0; }
    </style>
</head>
<body>
<header>
    <span style="font-size:1.5em;">🗑️ Delete Event</span>
    <a href="index.php?tab=events" style="float:right; color:#fff; text-decoration:none;">← Back to Events</a>
</header>
<main>
    <h1>Delete Event</h1>
    
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="warning">
        <strong>⚠️ Warning:</strong> This action cannot be undone. The event and all its associated data will be permanently deleted.
    </div>

    <div class="event-preview">
        <div class="event-title"><?php echo htmlspecialchars($event['title']); ?></div>
        
        <?php if ($event['image_path'] && file_exists($event['image_path'])): ?>
            <img src="<?php echo htmlspecialchars($event['image_path']); ?>" alt="Event image" class="event-image">
        <?php endif; ?>
        
        <div class="event-details">
            <strong>Date:</strong> <?php echo date('M j, Y', strtotime($event['event_date'])); ?>
            <?php if ($event['event_time']): ?>
                at <?php echo date('g:i A', strtotime($event['event_time'])); ?>
            <?php endif; ?>
        </div>
        
        <?php if ($event['location']): ?>
            <div class="event-details">
                <strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($event['description']): ?>
            <div class="event-details">
                <strong>Description:</strong> 
                <?php 
                $preview = substr(strip_tags($event['description']), 0, 200);
                echo htmlspecialchars($preview);
                if (strlen($event['description']) > 200) echo '...';
                ?>
            </div>
        <?php endif; ?>
        
        <div class="event-meta">
            Status: <?php echo ucfirst($event['status']); ?> | 
            Created: <?php echo htmlspecialchars($event['created_at']); ?>
        </div>
    </div>

    <div class="confirmation-form">
        <p><strong>Are you sure you want to delete this event?</strong></p>
        
        <form method="post" style="display: inline;">
            <input type="hidden" name="confirm_delete" value="1">
            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you absolutely sure? This cannot be undone!')">
                🗑️ Yes, Delete Event
            </button>
        </form>
        
        <a href="index.php?tab=events" class="btn btn-secondary">
            ← Cancel
        </a>
    </div>

    <script>
    // Additional confirmation for extra safety
    document.querySelector('form').addEventListener('submit', function(e) {
        if (!confirm('FINAL CONFIRMATION: Delete event "<?php echo addslashes($event['title']); ?>"?\n\nThis action is permanent and cannot be undone.')) {
            e.preventDefault();
        }
    });
    </script>
</main>
</body>
</html>
