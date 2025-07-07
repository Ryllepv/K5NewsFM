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
            $stmt = $pdo->prepare("INSERT INTO philippine_holidays (name, date, type, description, is_recurring, month_day) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $date, $type, $description, $is_recurring, $month_day]);
            $message = "Holiday added successfully!";
            
            // Clear form data after successful submission
            $name = '';
            $date = '';
            $type = 'regular';
            $description = '';
            $is_recurring = 0;
            
        } catch (Exception $e) {
            $error = "Error adding holiday: " . $e->getMessage();
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
    <title>Add Holiday - Admin</title>
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
        @media (max-width: 600px) {
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<header>
    <span style="font-size:1.5em;">🇵🇭 Add Philippine Holiday</span>
    <a href="index.php?tab=holidays" style="float:right; color:#fff; text-decoration:none;">← Back to Holidays</a>
</header>
<main>
    <h1>Add New Holiday</h1>
    
    <?php if ($message): ?>
        <div class="msg"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post">
        <label>Holiday Name: *</label>
        <input type="text" name="name" required value="<?php echo htmlspecialchars($name ?? ''); ?>" placeholder="Enter holiday name">
        
        <div class="form-row">
            <div>
                <label>Date: *</label>
                <input type="date" name="date" required value="<?php echo htmlspecialchars($date ?? ''); ?>">
            </div>
            <div>
                <label>Type:</label>
                <select name="type">
                    <option value="regular" <?php echo (isset($type) && $type === 'regular') ? 'selected' : ''; ?>>Regular Holiday</option>
                    <option value="special_non_working" <?php echo (isset($type) && $type === 'special_non_working') ? 'selected' : ''; ?>>Special Non-Working Day</option>
                    <option value="special_working" <?php echo (isset($type) && $type === 'special_working') ? 'selected' : ''; ?>>Special Working Holiday</option>
                </select>
                <div class="form-help">Choose the appropriate holiday type</div>
            </div>
        </div>
        
        <label>Description:</label>
        <textarea name="description" placeholder="Describe the holiday (optional)"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
        <div class="form-help">Provide additional information about the holiday</div>
        
        <div class="checkbox-group">
            <input type="checkbox" name="is_recurring" id="is_recurring" value="1" <?php echo (isset($is_recurring) && $is_recurring) ? 'checked' : ''; ?>>
            <label for="is_recurring" style="margin: 0; font-weight: normal;">This holiday recurs every year</label>
        </div>
        <div class="form-help">Check this if the holiday occurs on the same date every year (like Christmas, Independence Day, etc.)</div>
        
        <br>
        <button type="submit">🇵🇭 Add Holiday</button>
        <a href="index.php?tab=holidays" class="btn" style="margin-left: 1em;">Cancel</a>
    </form>

    <div style="margin-top: 2em; padding: 1em; background: #f0f8ff; border-radius: 4px; border-left: 4px solid #91d5ff;">
        <strong>💡 Holiday Types:</strong>
        <ul>
            <li><strong>Regular Holiday:</strong> Official public holidays with pay for workers</li>
            <li><strong>Special Non-Working Day:</strong> Special holidays, usually no work</li>
            <li><strong>Special Working Holiday:</strong> Special holidays but work continues</li>
        </ul>
        <strong>📅 Recurring Holidays:</strong>
        <p>Check "recurring" for holidays that happen every year on the same date (like New Year's Day, Christmas, Independence Day). Leave unchecked for holidays that change dates each year (like Easter, Chinese New Year).</p>
    </div>
</main>
</body>
</html>
