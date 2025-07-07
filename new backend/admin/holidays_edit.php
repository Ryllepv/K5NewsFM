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

// Fetch holiday data
$stmt = $pdo->prepare("SELECT * FROM philippine_holidays WHERE id = ?");
$stmt->execute([$holidayId]);
$holiday = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$holiday) {
    header('Location: index.php?tab=holidays&error=Holiday not found');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $date = $_POST['date'] ?? '';
    $type = $_POST['type'] ?? 'regular';
    $description = trim($_POST['description'] ?? '');
    $is_recurring = isset($_POST['is_recurring']) ? 1 : 0;
    $month_day = '';
    
    // If recurring, extract month-day format
    if ($is_recurring && $date) {
        $month_day = date('m-d', strtotime($date));
    }

    if ($name && $date) {
        try {
            // Update holiday (preserve source if it exists)
            $updateFields = "name = ?, date = ?, type = ?, description = ?, is_recurring = ?, month_day = ?";
            $params = [$name, $date, $type, $description, $is_recurring, $month_day];
            
            // Add updated_at if column exists
            if (isset($holiday['updated_at'])) {
                $updateFields .= ", updated_at = NOW()";
            }
            
            $stmt = $pdo->prepare("UPDATE philippine_holidays SET $updateFields WHERE id = ?");
            $params[] = $holidayId;
            $stmt->execute($params);
            
            $message = "Holiday updated successfully!";
            
            // Refresh holiday data
            $stmt = $pdo->prepare("SELECT * FROM philippine_holidays WHERE id = ?");
            $stmt->execute([$holidayId]);
            $holiday = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $error = "Error updating holiday: " . $e->getMessage();
        }
    } else {
        if (!$name) $error = "Holiday name is required.";
        elseif (!$date) $error = "Date is required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Holiday - Admin</title>
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
        input[type="text"], input[type="date"], textarea, select { width: 100%; padding: 0.5em; border: 1px solid #ddd; border-radius: 4px; }
        textarea { height: 100px; resize: vertical; }
        button { margin-top: 1em; padding: 0.5em 1.5em; background: #003366; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #1890ff; }
        .msg { color: green; margin-bottom: 1em; padding: 0.5em; background: #f0f8ff; border: 1px solid #91d5ff; border-radius: 4px; }
        .error { color: red; margin-bottom: 1em; padding: 0.5em; background: #fff2f0; border: 1px solid #ffccc7; border-radius: 4px; }
        .form-help { font-size: 0.9em; color: #666; margin-top: 0.3em; }
        .checkbox-group { display: flex; align-items: center; gap: 0.5em; margin-top: 0.5em; }
        .checkbox-group input[type="checkbox"] { width: auto; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1em; }
        .source-info { background: #f0f8ff; border: 1px solid #b3d8fd; border-radius: 6px; padding: 1em; margin: 1em 0; }
        .source-badge { display: inline-block; padding: 0.3em 0.8em; border-radius: 15px; font-size: 0.85em; font-weight: 500; }
        .source-api { background: #e6f7ff; color: #1890ff; }
        .source-prediction { background: #f6ffed; color: #52c41a; }
        .source-manual { background: #f0f0f0; color: #666; }
        .source-nager_api { background: #fff7e6; color: #fa8c16; }
        @media (max-width: 600px) {
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<header>
    <span style="font-size:1.5em;">✏️ Edit Holiday</span>
    <a href="index.php?tab=holidays" style="float:right; color:#fff; text-decoration:none;">← Back to Holidays</a>
</header>
<main>
    <h1>Edit Holiday</h1>
    
    <?php if ($message): ?>
        <div class="msg"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if (isset($holiday['source']) && $holiday['source']): ?>
        <div class="source-info">
            <strong>📍 Holiday Source:</strong> 
            <span class="source-badge source-<?php echo $holiday['source']; ?>">
                <?php 
                switch($holiday['source']) {
                    case 'api': echo '🌐 API'; break;
                    case 'nager_api': echo '🌐 Nager API'; break;
                    case 'prediction': echo '🔮 Prediction'; break;
                    case 'manual': echo '✏️ Manual'; break;
                    default: echo ucfirst(str_replace('_', ' ', $holiday['source']));
                }
                ?>
            </span>
            <?php if (in_array($holiday['source'], ['api', 'nager_api', 'prediction'])): ?>
                <br><small>⚠️ This holiday was automatically added. Changes may be overwritten during next sync.</small>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <label>Holiday Name: *</label>
        <input type="text" name="name" required value="<?php echo htmlspecialchars($holiday['name']); ?>">
        
        <div class="form-row">
            <div>
                <label>Date: *</label>
                <input type="date" name="date" required value="<?php echo htmlspecialchars($holiday['date']); ?>">
            </div>
            <div>
                <label>Type:</label>
                <select name="type">
                    <option value="regular" <?php echo $holiday['type'] === 'regular' ? 'selected' : ''; ?>>Regular Holiday</option>
                    <option value="special_non_working" <?php echo $holiday['type'] === 'special_non_working' ? 'selected' : ''; ?>>Special Non-Working Day</option>
                    <option value="special_working" <?php echo $holiday['type'] === 'special_working' ? 'selected' : ''; ?>>Special Working Holiday</option>
                </select>
                <div class="form-help">Choose the appropriate holiday type</div>
            </div>
        </div>
        
        <label>Description:</label>
        <textarea name="description" placeholder="Describe the holiday (optional)"><?php echo htmlspecialchars($holiday['description'] ?? ''); ?></textarea>
        <div class="form-help">Provide additional information about the holiday</div>
        
        <div class="checkbox-group">
            <input type="checkbox" name="is_recurring" id="is_recurring" value="1" <?php echo $holiday['is_recurring'] ? 'checked' : ''; ?>>
            <label for="is_recurring" style="margin: 0; font-weight: normal;">This holiday recurs every year</label>
        </div>
        <div class="form-help">Check this if the holiday occurs on the same date every year</div>
        
        <br>
        <button type="submit">💾 Update Holiday</button>
        <a href="index.php?tab=holidays" class="btn" style="margin-left: 1em;">Cancel</a>
    </form>

    <?php if (isset($holiday['created_at']) || isset($holiday['updated_at'])): ?>
        <div style="margin-top: 2em; padding: 1em; background: #f9f9f9; border-radius: 4px; font-size: 0.9em; color: #666;">
            <strong>📅 Holiday History:</strong><br>
            <?php if (isset($holiday['created_at'])): ?>
                Created: <?php echo date('M j, Y g:i A', strtotime($holiday['created_at'])); ?><br>
            <?php endif; ?>
            <?php if (isset($holiday['updated_at']) && $holiday['updated_at'] !== $holiday['created_at']): ?>
                Last Updated: <?php echo date('M j, Y g:i A', strtotime($holiday['updated_at'])); ?><br>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div style="margin-top: 2em; padding: 1em; background: #f0f8ff; border-radius: 4px; border-left: 4px solid #91d5ff;">
        <strong>💡 Holiday Types:</strong>
        <ul>
            <li><strong>Regular Holiday:</strong> Official public holidays with pay for workers</li>
            <li><strong>Special Non-Working Day:</strong> Special holidays, usually no work</li>
            <li><strong>Special Working Holiday:</strong> Special holidays but work continues</li>
        </ul>
    </div>
</main>
</body>
</html>
