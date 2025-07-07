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

// Fetch event data
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$eventId]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    header('Location: index.php?tab=events&error=Event not found');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $event_date = $_POST['event_date'] ?? '';
    $event_time = $_POST['event_time'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $status = $_POST['status'] ?? 'upcoming';
    $imagePath = $event['image_path']; // Keep existing image by default

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/events/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileName = uniqid() . '_' . basename($_FILES['image']['name']);
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            // Delete old image if it exists
            if ($event['image_path'] && file_exists($event['image_path'])) {
                unlink($event['image_path']);
            }
            $imagePath = $targetPath;
        } else {
            $error = "Failed to upload image.";
        }
    }

    if ($title && $event_date && !$error) {
        try {
            $stmt = $pdo->prepare("UPDATE events SET title = ?, description = ?, event_date = ?, event_time = ?, location = ?, image_path = ?, status = ? WHERE id = ?");
            $stmt->execute([$title, $description, $event_date, $event_time ?: null, $location, $imagePath, $status, $eventId]);
            $message = "Event updated successfully!";
            
            // Refresh event data
            $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
            $stmt->execute([$eventId]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $error = "Error updating event: " . $e->getMessage();
        }
    } else {
        if (!$title) $error = "Title is required.";
        elseif (!$event_date) $error = "Event date is required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Event - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; background: #f6f6f6; margin: 0; }
        header { background: #003366; color: #fff; padding: 1em 2em; }
        main { max-width: 800px; margin: 2em auto; background: #fff; padding: 2em; border-radius: 8px; }
        h1 { color: #003366; }
        .btn { display: inline-block; padding: 0.5em 1.2em; background: #003366; color: #fff; border-radius: 4px; text-decoration: none; margin-bottom: 1em; }
        .btn:hover { background: #1890ff; }
        form { margin-bottom: 2em; }
        label { display: block; margin-top: 1em; font-weight: bold; }
        input[type="text"], input[type="date"], input[type="time"], textarea, select { width: 100%; padding: 0.5em; border: 1px solid #ddd; border-radius: 4px; }
        textarea { height: 120px; resize: vertical; }
        button { margin-top: 1em; padding: 0.5em 1.5em; background: #003366; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #1890ff; }
        .msg { color: green; margin-bottom: 1em; padding: 0.5em; background: #f0f8ff; border: 1px solid #91d5ff; border-radius: 4px; }
        .error { color: red; margin-bottom: 1em; padding: 0.5em; background: #fff2f0; border: 1px solid #ffccc7; border-radius: 4px; }
        .form-help { font-size: 0.9em; color: #666; margin-top: 0.3em; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1em; }
        .current-image { margin: 1em 0; }
        .current-image img { max-width: 200px; height: auto; border: 1px solid #ddd; border-radius: 4px; }
        @media (max-width: 600px) {
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<header>
    <span style="font-size:1.5em;">✏️ Edit Event</span>
    <a href="index.php?tab=events" style="float:right; color:#fff; text-decoration:none;">← Back to Events</a>
</header>
<main>
    <h1>Edit Event</h1>
    
    <?php if ($message): ?>
        <div class="msg"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <label>Event Title: *</label>
        <input type="text" name="title" required value="<?php echo htmlspecialchars($event['title']); ?>">
        
        <label>Description:</label>
        <textarea name="description" placeholder="Describe the event..."><?php echo htmlspecialchars($event['description']); ?></textarea>
        
        <div class="form-row">
            <div>
                <label>Event Date: *</label>
                <input type="date" name="event_date" required value="<?php echo htmlspecialchars($event['event_date']); ?>">
            </div>
            <div>
                <label>Event Time:</label>
                <input type="time" name="event_time" value="<?php echo htmlspecialchars($event['event_time']); ?>">
            </div>
        </div>
        
        <label>Location:</label>
        <input type="text" name="location" value="<?php echo htmlspecialchars($event['location']); ?>">
        
        <label>Status:</label>
        <select name="status">
            <option value="upcoming" <?php echo $event['status'] === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
            <option value="ongoing" <?php echo $event['status'] === 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
            <option value="completed" <?php echo $event['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
            <option value="cancelled" <?php echo $event['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
        </select>
        
        <label>Current Image:</label>
        <?php if ($event['image_path'] && file_exists($event['image_path'])): ?>
            <div class="current-image">
                <img src="<?php echo htmlspecialchars($event['image_path']); ?>" alt="Current image">
                <br><small>Current image will be replaced if you upload a new one</small>
            </div>
        <?php else: ?>
            <div class="current-image">
                <small>No image currently uploaded</small>
            </div>
        <?php endif; ?>
        
        <label>Upload New Image (optional):</label>
        <input type="file" name="image" accept="image/*">
        
        <br>
        <button type="submit">💾 Update Event</button>
        <a href="index.php?tab=events" class="btn" style="margin-left: 1em;">Cancel</a>
    </form>
</main>
</body>
</html>
