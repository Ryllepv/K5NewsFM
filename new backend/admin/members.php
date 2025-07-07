<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Handle add member form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $program = trim($_POST['program'] ?? '');
    $image_url = trim($_POST['image_url'] ?? '');

    if ($name && $position) {
        $stmt = $pdo->prepare("INSERT INTO station_members (name, position, program, image_url) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $position, $program, $image_url]);
        $message = "Member added!";
    } else {
        $message = "Name and position are required.";
    }
}

// Fetch all members
$stmt = $pdo->query("SELECT * FROM station_members ORDER BY id ASC");
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Station Members</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f6f6f6; }
        main { max-width: 800px; margin: 2em auto; background: #fff; padding: 2em; border-radius: 8px; }
        h1 { color: #003366; }
        .members-grid { display: flex; flex-wrap: wrap; gap: 2em; justify-content: center; }
        .member-card { background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 1em; width: 220px; text-align: center; box-shadow: 0 2px 8px #e0e0e0; }
        .member-card img { width: 100px; height: 100px; object-fit: cover; border-radius: 50%; margin-bottom: 0.5em; border: 2px solid #91d5ff; }
        .member-name { font-weight: bold; font-size: 1.1em; margin-bottom: 0.2em; }
        .member-position { color: #003366; font-size: 0.98em; margin-bottom: 0.2em; }
        .member-program { color: #555; font-size: 0.95em; }
        form { margin-bottom: 2em; }
        label { display: block; margin-top: 1em; }
        input[type="text"] { width: 100%; padding: 0.5em; }
        button { margin-top: 1em; padding: 0.5em 1.5em; background: #003366; color: #fff; border: none; border-radius: 4px; }
        .msg { color: green; margin-bottom: 1em; }
    </style>
</head>
<body>
<main>
    <h1>Manage Station Members</h1>
    <?php if ($message): ?>
        <div class="msg"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <form method="post">
        <label>Name:</label>
        <input type="text" name="name" required>
        <label>Position:</label>
        <input type="text" name="position" required>
        <label>Program (optional):</label>
        <input type="text" name="program">
        <label>Image URL (link to photo):</label>
        <input type="text" name="image_url">
        <button type="submit">Add Member</button>
    </form>
    <div class="members-grid">
        <?php foreach ($members as $member): ?>
            <div class="member-card">
                <img src="<?php echo htmlspecialchars($member['image_url']); ?>" alt="<?php echo htmlspecialchars($member['name']); ?>">
                <div class="member-name"><?php echo htmlspecialchars($member['name']); ?></div>
                <div class="member-position"><?php echo htmlspecialchars($member['position']); ?></div>
                <?php if (!empty($member['program'])): ?>
                    <div class="member-program">Host: <?php echo htmlspecialchars($member['program']); ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</main>
</body>
</html>