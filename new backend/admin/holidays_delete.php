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
$holiday = null;

// Get holiday ID from URL
$holidayId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($holidayId <= 0) {
    header('Location: index.php?tab=holidays&error=Invalid holiday ID');
    exit();
}

// Fetch holiday data for confirmation
$stmt = $pdo->prepare("SELECT * FROM philippine_holidays WHERE id = ?");
$stmt->execute([$holidayId]);
$holiday = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$holiday) {
    header('Location: index.php?tab=holidays&error=Holiday not found');
    exit();
}

// Handle deletion confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    try {
        // Delete the holiday
        $stmt = $pdo->prepare("DELETE FROM philippine_holidays WHERE id = ?");
        $stmt->execute([$holidayId]);
        
        // Redirect with success message
        header('Location: index.php?tab=holidays&message=Holiday deleted successfully');
        exit();
        
    } catch (Exception $e) {
        $error = "Error deleting holiday: " . $e->getMessage();
    }
}

// Handle cancellation
if (isset($_GET['cancel'])) {
    header('Location: index.php?tab=holidays');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete Holiday - Admin</title>
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
        .holiday-preview { background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; padding: 1em; margin: 1em 0; }
        .holiday-name { font-weight: bold; font-size: 1.1em; margin-bottom: 0.5em; }
        .holiday-details { color: #666; margin-bottom: 0.5em; }
        .holiday-meta { font-size: 0.9em; color: #888; }
        .holiday-type { display: inline-block; padding: 0.3em 0.8em; border-radius: 15px; font-size: 0.85em; font-weight: 500; margin: 0.5em 0; }
        .type-regular { background: #f6ffed; color: #52c41a; }
        .type-special_non_working { background: #fff7e6; color: #fa8c16; }
        .type-special_working { background: #f0f5ff; color: #1890ff; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 1em; border-radius: 4px; margin: 1em 0; }
        .error { color: red; margin-bottom: 1em; padding: 0.5em; background: #fff2f0; border: 1px solid #ffccc7; border-radius: 4px; }
        .confirmation-form { margin-top: 2em; }
        .source-badge { display: inline-block; padding: 0.2em 0.5em; border-radius: 10px; font-size: 0.8em; font-weight: 500; margin-left: 0.5em; }
        .source-api { background: #e6f7ff; color: #1890ff; }
        .source-prediction { background: #f6ffed; color: #52c41a; }
        .source-manual { background: #f0f0f0; color: #666; }
        .source-nager_api { background: #fff7e6; color: #fa8c16; }
    </style>
</head>
<body>
<header>
    <span style="font-size:1.5em;">🗑️ Delete Holiday</span>
    <a href="index.php?tab=holidays" style="float:right; color:#fff; text-decoration:none;">← Back to Holidays</a>
</header>
<main>
    <h1>Delete Holiday</h1>
    
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="warning">
        <strong>⚠️ Warning:</strong> This action cannot be undone. The holiday will be permanently deleted from the database.
    </div>

    <div class="holiday-preview">
        <div class="holiday-name">
            <?php echo htmlspecialchars($holiday['name']); ?>
            <?php if (isset($holiday['source']) && $holiday['source']): ?>
                <span class="source-badge source-<?php echo $holiday['source']; ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $holiday['source'])); ?>
                </span>
            <?php endif; ?>
        </div>
        
        <div class="holiday-details">
            <strong>Date:</strong> <?php echo date('l, F j, Y', strtotime($holiday['date'])); ?>
        </div>
        
        <div class="holiday-type type-<?php echo $holiday['type']; ?>">
            <?php 
            switch($holiday['type']) {
                case 'regular': echo 'Regular Holiday'; break;
                case 'special_non_working': echo 'Special Non-Working Day'; break;
                case 'special_working': echo 'Special Working Holiday'; break;
                default: echo ucfirst($holiday['type']);
            }
            ?>
        </div>
        
        <?php if ($holiday['description']): ?>
            <div class="holiday-details">
                <strong>Description:</strong> <?php echo htmlspecialchars($holiday['description']); ?>
            </div>
        <?php endif; ?>
        
        <div class="holiday-meta">
            <?php if ($holiday['is_recurring']): ?>
                <span>🔄 Recurring Holiday</span> | 
            <?php endif; ?>
            
            <?php if (isset($holiday['created_at'])): ?>
                Created: <?php echo date('M j, Y', strtotime($holiday['created_at'])); ?>
            <?php endif; ?>
            
            <?php if (isset($holiday['source']) && $holiday['source']): ?>
                | Source: <?php echo ucfirst(str_replace('_', ' ', $holiday['source'])); ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($holiday['source']) && in_array($holiday['source'], ['api', 'nager_api', 'prediction'])): ?>
        <div style="background: #fff7e6; border: 1px solid #ffd591; color: #d46b08; padding: 1em; border-radius: 4px; margin: 1em 0;">
            <strong>⚠️ Note:</strong> This holiday was automatically added from <?php echo $holiday['source'] === 'nager_api' ? 'Nager API' : ($holiday['source'] === 'prediction' ? 'prediction algorithm' : 'API'); ?>. 
            It may be re-added during the next automatic sync unless you disable auto-sync for this holiday type.
        </div>
    <?php endif; ?>

    <div class="confirmation-form">
        <p><strong>Are you sure you want to delete this holiday?</strong></p>
        
        <form method="post" style="display: inline;">
            <input type="hidden" name="confirm_delete" value="1">
            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you absolutely sure? This cannot be undone!')">
                🗑️ Yes, Delete Holiday
            </button>
        </form>
        
        <a href="index.php?tab=holidays" class="btn btn-secondary">
            ← Cancel
        </a>
    </div>

    <script>
    // Additional confirmation for extra safety
    document.querySelector('form').addEventListener('submit', function(e) {
        if (!confirm('FINAL CONFIRMATION: Delete holiday "<?php echo addslashes($holiday['name']); ?>"?\n\nThis action is permanent and cannot be undone.')) {
            e.preventDefault();
        }
    });
    </script>
</main>
</body>
</html>
