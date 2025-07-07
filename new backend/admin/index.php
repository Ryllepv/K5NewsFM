<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/header.php';

// Check if the user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Tab logic
$tab = $_GET['tab'] ?? 'dashboard';

// Handle member deletion
if (isset($_GET['delete_member'])) {
    $id = intval($_GET['delete_member']);
    $stmt = $pdo->prepare("DELETE FROM station_members WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: index.php?tab=members');
    exit();
}

// Handle member edit
$editMember = null;
if (isset($_GET['edit_member'])) {
    $id = intval($_GET['edit_member']);
    $stmt = $pdo->prepare("SELECT * FROM station_members WHERE id = ?");
    $stmt->execute([$id]);
    $editMember = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle add/edit member form
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['member_form'])) {
    $name = trim($_POST['name'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $program = trim($_POST['program'] ?? '');
    $image_url = trim($_POST['image_url'] ?? '');
    $id = intval($_POST['id'] ?? 0);

    if ($name && $position) {
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE station_members SET name=?, position=?, program=?, image_url=? WHERE id=?");
            $stmt->execute([$name, $position, $program, $image_url, $id]);
            $message = "Member updated!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO station_members (name, position, program, image_url) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $position, $program, $image_url]);
            $message = "Member added!";
        }
    } else {
        $message = "Name and position are required.";
    }
}

// Fetch all members
$stmt = $pdo->query("SELECT * FROM station_members ORDER BY id ASC");
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle featured media add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['media_form'])) {
    $url = trim($_POST['url'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($url) {
        $stmt = $pdo->prepare("INSERT INTO featured_media (url, title, description) VALUES (?, ?, ?)");
        $stmt->execute([$url, $title, $description]);
        $message = "Media added!";
    } else {
        $message = "URL is required.";
    }
}

// Fetch all featured media
$stmt = $pdo->query("SELECT * FROM featured_media ORDER BY id DESC");
$mediaList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper to get thumbnail/iframe
function media_preview($url) {
    // YouTube
    if (preg_match('/youtu\.be\/([^\?&]+)/', $url, $yt) || preg_match('/youtube\.com.*v=([^\?&]+)/', $url, $yt)) {
        $ytId = $yt[1];
        return '<iframe width="100%" height="180" src="https://www.youtube.com/embed/' . htmlspecialchars($ytId) . '" frameborder="0" allowfullscreen></iframe>';
    }
    // Facebook
    if (strpos($url, 'facebook.com') !== false) {
        return '<div class="fb-video" data-href="' . htmlspecialchars($url) . '" data-width="320" data-show-text="false"></div>';
    }
    // TikTok
    if (strpos($url, 'tiktok.com') !== false) {
        return '<blockquote class="tiktok-embed" cite="' . htmlspecialchars($url) . '" style="width:100%;"></blockquote>';
    }
    // Fallback: just link
    return '<a href="' . htmlspecialchars($url) . '" target="_blank">' . htmlspecialchars($url) . '</a>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; background: #f6f6f6; margin: 0; }
        header { background: #003366; color: #fff; padding: 1em 2em; }
        main { max-width: 900px; margin: 2em auto; background: #fff; padding: 2em; border-radius: 8px; }
        h1 { color: #003366; }
        nav { margin-bottom: 2em; background: #eaeaea; padding: 1em 2em; border-radius: 6px; }
        nav a { margin-right: 1em; text-decoration: none; color: #003366; font-weight: bold; }
        nav a.active, nav a:focus, nav a:hover { text-decoration: underline; color: #1890ff; }
        .btn { display: inline-block; padding: 0.5em 1.2em; background: #003366; color: #fff; border-radius: 4px; text-decoration: none; margin-bottom: 1em; }
        .members-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2em; justify-items: center; padding: 1em 0; }
        .member-card {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 1.5em;
            width: 100%;
            max-width: 320px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            position: relative;
            transition: all 0.3s ease;
            overflow: hidden;
        }
        .member-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            border-color: #91d5ff;
        }
        .member-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #003366, #91d5ff);
        }
        .member-card img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            margin: 1em auto 1em auto;
            border: 3px solid #91d5ff;
            display: block;
            transition: transform 0.3s ease;
            background: #f0f8ff;
        }
        .member-card:hover img {
            transform: scale(1.05);
        }
        .member-name {
            font-weight: bold;
            font-size: 1.2em;
            margin-bottom: 0.5em;
            color: #003366;
            line-height: 1.3;
        }
        .member-position {
            color: #1890ff;
            font-size: 1em;
            margin-bottom: 0.5em;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .member-program {
            color: #666;
            font-size: 0.95em;
            font-style: italic;
            background: #f8f9fa;
            padding: 0.5em;
            border-radius: 6px;
            margin: 0.5em 0 1em 0;
        }
        .member-actions {
            display: flex;
            gap: 0.5em;
            justify-content: center;
            margin-top: 1em;
            padding-top: 1em;
            border-top: 1px solid #f0f0f0;
        }
        .edit-btn, .delete-btn {
            padding: 0.5em 1em;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 0.9em;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.3em;
        }
        .edit-btn {
            background: #ffd666;
            color: #333;
        }
        .edit-btn:hover {
            background: #ffcc33;
            transform: translateY(-1px);
        }
        .delete-btn {
            background: #ff4d4f;
            color: #fff;
        }
        .delete-btn:hover {
            background: #ff1f23;
            transform: translateY(-1px);
        }
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 2em;
            margin-top: 2em;
        }
        .event-card {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .event-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .event-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .event-placeholder {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #91d5ff, #003366);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4em;
            color: white;
        }
        .event-content {
            padding: 1.5em;
        }
        .event-title {
            font-weight: bold;
            font-size: 1.2em;
            margin-bottom: 0.5em;
            color: #003366;
        }
        .event-date, .event-location {
            color: #666;
            margin-bottom: 0.5em;
            font-size: 0.95em;
        }
        .event-status {
            display: inline-block;
            padding: 0.3em 0.8em;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 500;
            margin: 0.5em 0;
        }
        .status-upcoming { background: #e6f7ff; color: #1890ff; }
        .status-ongoing { background: #f6ffed; color: #52c41a; }
        .status-completed { background: #f0f0f0; color: #666; }
        .status-cancelled { background: #fff2f0; color: #ff4d4f; }
        .event-actions {
            display: flex;
            gap: 0.5em;
            margin-top: 1em;
            padding-top: 1em;
            border-top: 1px solid #f0f0f0;
        }
        .schedule-container {
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-top: 1em;
        }
        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }
        .schedule-table th {
            background: #003366;
            color: #fff;
            padding: 1em;
            text-align: left;
            font-weight: bold;
        }
        .schedule-table td {
            padding: 0.8em 1em;
            border-bottom: 1px solid #f0f0f0;
        }
        .schedule-table tr:hover {
            background: #f8f9fa;
        }
        .program-name {
            font-weight: bold;
            color: #003366;
        }
        .program-days {
            color: #1890ff;
            font-weight: 500;
            background: #f0f8ff;
            border-radius: 4px;
            padding: 0.3em 0.6em;
            display: inline-block;
            font-size: 0.9em;
        }
        .program-time {
            color: #666;
            font-family: monospace;
        }
        .media-grid { display: flex; flex-wrap: wrap; gap: 1em; }
        .media-item { background: #f0f8ff; border: 1px solid #b3d8fd; border-radius: 6px; padding: 1em; width: 320px; }
        .media-item iframe, .media-item .fb-video, .media-item .tiktok-embed { width: 100%; border-radius: 4px; }
        .media-title { font-weight: bold; margin-top: 0.5em; }
        .media-desc { font-size: 0.95em; color: #555; }
        form { margin-bottom: 2em; }
        label { display: block; margin-top: 1em; }
        input[type="text"], textarea { width: 100%; padding: 0.5em; }
        button { margin-top: 1em; padding: 0.5em 1.5em; background: #003366; color: #fff; border: none; border-radius: 4px; }
        .msg { color: green; margin-bottom: 1em; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 2em; }
        th, td { border: 1px solid #bbb; padding: 8px 12px; text-align: left; }
        th { background: #e6f0fa; }
        tr:nth-child(even) { background: #f9f9f9; }
        ul { padding-left: 1.2em; }
        @media (max-width: 768px) {
            main { padding: 1em; }
            .members-grid { grid-template-columns: 1fr; gap: 1.5em; }
            .member-card { max-width: 100%; }
            .media-grid { grid-template-columns: 1fr; }
            .media-item { width: 100%; }
            nav { padding: 0.5em 1em; }
            nav a { display: block; margin: 0.5em 0; }
        }
        @media (max-width: 480px) {
            main { padding: 0.5em; }
            .member-card { padding: 1em; }
            .member-card img, .member-avatar-fallback { width: 100px; height: 100px; }
            .member-actions { flex-direction: column; }
            .edit-btn, .delete-btn { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>
<header>
    <span style="font-size:1.5em;">🛠️ Admin Dashboard</span>
</header>
<main>
    <nav>
        <a href="?tab=dashboard" class="<?php if($tab=='dashboard') echo 'active'; ?>">Dashboard</a>
        <a href="?tab=members" class="<?php if($tab=='members') echo 'active'; ?>">Station Members</a>
        <a href="?tab=events" class="<?php if($tab=='events') echo 'active'; ?>">Events</a>
        <a href="?tab=holidays" class="<?php if($tab=='holidays') echo 'active'; ?>">Holidays</a>
        <a href="?tab=media" class="<?php if($tab=='media') echo 'active'; ?>">Featured Media</a>
        <a href="logout.php" style="float:right;">Logout</a>
    </nav>
    <?php if ($message): ?>
        <div class="msg"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if ($tab == 'members'): ?>
        <h1>Manage Station Members</h1>
        <form method="post">
            <input type="hidden" name="member_form" value="1">
            <input type="hidden" name="id" value="<?php echo $editMember['id'] ?? 0; ?>">
            <label>Name:</label>
            <input type="text" name="name" required value="<?php echo htmlspecialchars($editMember['name'] ?? ''); ?>">
            <label>Position:</label>
            <input type="text" name="position" required value="<?php echo htmlspecialchars($editMember['position'] ?? ''); ?>">
            <label>Program (optional):</label>
            <input type="text" name="program" value="<?php echo htmlspecialchars($editMember['program'] ?? ''); ?>">
            <label>Image URL (link to photo):</label>
            <input type="text" name="image_url" value="<?php echo htmlspecialchars($editMember['image_url'] ?? ''); ?>">
            <button type="submit"><?php echo isset($editMember) ? 'Update' : 'Add'; ?> Member</button>
            <?php if (isset($editMember)): ?>
                <a href="index.php?tab=members" style="margin-left:1em;">Cancel</a>
            <?php endif; ?>
        </form>
        <div class="members-grid">
            <?php foreach ($members as $member): ?>
                <div class="member-card">
                    <img src="<?php echo htmlspecialchars($member['image_url']); ?>" alt="<?php echo htmlspecialchars($member['name']); ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="member-avatar-fallback" style="display: none; width: 120px; height: 120px; border-radius: 50%; background: linear-gradient(135deg, #91d5ff, #003366); align-items: center; justify-content: center; font-size: 3em; color: white; margin: 1em auto; border: 3px solid #91d5ff;">👤</div>
                    <div class="member-name"><?php echo htmlspecialchars($member['name']); ?></div>
                    <div class="member-position"><?php echo htmlspecialchars($member['position']); ?></div>
                    <?php if (!empty($member['program'])): ?>
                        <div class="member-program">Host: <?php echo htmlspecialchars($member['program']); ?></div>
                    <?php endif; ?>
                    <div class="member-actions">
                        <a href="index.php?tab=members&edit_member=<?php echo $member['id']; ?>" class="edit-btn">
                            ✏️ Edit
                        </a>
                        <a href="index.php?tab=members&delete_member=<?php echo $member['id']; ?>" class="delete-btn" onclick="return confirm('Delete this member?')">
                            🗑️ Delete
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php elseif ($tab == 'events'): ?>
        <h1>Manage Events</h1>
        <a href="events_add.php" class="btn">Add New Event</a>
        <?php
        // Fetch all events
        $stmt = $pdo->query("SELECT * FROM events ORDER BY event_date ASC, event_time ASC");
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <div class="events-grid">
            <?php foreach ($events as $event): ?>
                <div class="event-card">
                    <?php if ($event['image_path'] && file_exists($event['image_path'])): ?>
                        <img src="<?php echo htmlspecialchars($event['image_path']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
                    <?php else: ?>
                        <div class="event-placeholder">📅</div>
                    <?php endif; ?>
                    <div class="event-content">
                        <div class="event-title"><?php echo htmlspecialchars($event['title']); ?></div>
                        <div class="event-date">
                            📅 <?php echo date('M j, Y', strtotime($event['event_date'])); ?>
                            <?php if ($event['event_time']): ?>
                                at <?php echo date('g:i A', strtotime($event['event_time'])); ?>
                            <?php endif; ?>
                        </div>
                        <?php if ($event['location']): ?>
                            <div class="event-location">📍 <?php echo htmlspecialchars($event['location']); ?></div>
                        <?php endif; ?>
                        <div class="event-status status-<?php echo $event['status']; ?>">
                            <?php echo ucfirst($event['status']); ?>
                        </div>
                        <div class="event-actions">
                            <a href="events_edit.php?id=<?php echo $event['id']; ?>" class="edit-btn">✏️ Edit</a>
                            <a href="events_delete.php?id=<?php echo $event['id']; ?>" class="delete-btn" onclick="return confirm('Delete this event?')">🗑️ Delete</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (empty($events)): ?>
            <div style="text-align: center; padding: 2em; color: #666;">
                <h3>No events yet</h3>
                <p>Start by <a href="events_add.php">adding your first event</a>!</p>
            </div>
        <?php endif; ?>
    <?php elseif ($tab == 'holidays'): ?>
        <h1>Manage Philippine Holidays</h1>

        <?php if (isset($_GET['message'])): ?>
            <div style="color: green; margin-bottom: 1em; padding: 0.5em; background: #f0f8ff; border: 1px solid #91d5ff; border-radius: 4px;">
                <?php echo htmlspecialchars($_GET['message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div style="color: red; margin-bottom: 1em; padding: 0.5em; background: #fff2f0; border: 1px solid #ffccc7; border-radius: 4px;">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>
        <div style="margin-bottom: 2em;">
            <a href="../holidays_calendar.php" class="btn" target="_blank">📅 View Calendar</a>
            <a href="../holidays_list.php" class="btn" target="_blank">📋 View All Holidays</a>
            <a href="holidays_add.php" class="btn">➕ Add Holiday</a>
            <a href="holidays_sync.php" class="btn" style="background: #52c41a;">🔄 Auto Sync</a>
        </div>

        <?php
        // Get current year holidays for quick overview
        $currentYear = date('Y');
        $stmt = $pdo->prepare("SELECT * FROM philippine_holidays WHERE YEAR(date) = ? ORDER BY date ASC");
        $stmt->execute([$currentYear]);
        $currentYearHolidays = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get holiday statistics
        $stmt = $pdo->query("SELECT
            COUNT(*) as total,
            COUNT(CASE WHEN type = 'regular' THEN 1 END) as regular,
            COUNT(CASE WHEN type = 'special_non_working' THEN 1 END) as special_non_working,
            COUNT(CASE WHEN type = 'special_working' THEN 1 END) as special_working
            FROM philippine_holidays WHERE YEAR(date) = $currentYear");
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        ?>

        <div class="holidays-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1em; margin-bottom: 2em;">
            <div style="background: #f0f8ff; border: 1px solid #b3d8fd; border-radius: 6px; padding: 1em; text-align: center;">
                <div style="font-size: 2em; font-weight: bold; color: #003366;"><?php echo $stats['total']; ?></div>
                <div style="color: #666;">Total Holidays <?php echo $currentYear; ?></div>
            </div>
            <div style="background: #f6ffed; border: 1px solid #b7eb8f; border-radius: 6px; padding: 1em; text-align: center;">
                <div style="font-size: 2em; font-weight: bold; color: #52c41a;"><?php echo $stats['regular']; ?></div>
                <div style="color: #666;">Regular Holidays</div>
            </div>
            <div style="background: #fff7e6; border: 1px solid #ffd591; border-radius: 6px; padding: 1em; text-align: center;">
                <div style="font-size: 2em; font-weight: bold; color: #fa8c16;"><?php echo $stats['special_non_working']; ?></div>
                <div style="color: #666;">Special Non-Working</div>
            </div>
            <div style="background: #f0f5ff; border: 1px solid #adc6ff; border-radius: 6px; padding: 1em; text-align: center;">
                <div style="font-size: 2em; font-weight: bold; color: #1890ff;"><?php echo $stats['special_working']; ?></div>
                <div style="color: #666;">Special Working</div>
            </div>
        </div>

        <h2>Holidays in <?php echo $currentYear; ?></h2>
        <div class="holidays-grid" style="display: grid; gap: 1em;">
            <?php foreach ($currentYearHolidays as $holiday): ?>
                <div class="holiday-card" style="background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 1em; border-left: 4px solid <?php
                    echo $holiday['type'] === 'regular' ? '#52c41a' :
                        ($holiday['type'] === 'special_non_working' ? '#fa8c16' : '#1890ff');
                ?>;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div style="flex: 1;">
                            <div style="font-weight: bold; font-size: 1.1em; margin-bottom: 0.5em; color: #003366;">
                                <?php echo htmlspecialchars($holiday['name']); ?>
                            </div>
                            <div style="color: #666; margin-bottom: 0.5em;">
                                📅 <?php echo date('l, F j, Y', strtotime($holiday['date'])); ?>
                            </div>
                            <div style="display: inline-block; padding: 0.3em 0.8em; border-radius: 15px; font-size: 0.85em; font-weight: 500; background: <?php
                                echo $holiday['type'] === 'regular' ? '#f6ffed' :
                                    ($holiday['type'] === 'special_non_working' ? '#fff7e6' : '#f0f5ff');
                            ?>; color: <?php
                                echo $holiday['type'] === 'regular' ? '#52c41a' :
                                    ($holiday['type'] === 'special_non_working' ? '#fa8c16' : '#1890ff');
                            ?>;">
                                <?php
                                switch($holiday['type']) {
                                    case 'regular': echo 'Regular Holiday'; break;
                                    case 'special_non_working': echo 'Special Non-Working'; break;
                                    case 'special_working': echo 'Special Working'; break;
                                }
                                ?>
                            </div>
                            <?php if ($holiday['description']): ?>
                                <div style="color: #555; font-size: 0.9em; margin-top: 0.5em; line-height: 1.4;">
                                    <?php echo htmlspecialchars($holiday['description']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div style="display: flex; gap: 0.5em; margin-left: 1em;">
                            <a href="holidays_edit.php?id=<?php echo $holiday['id']; ?>" class="edit-btn" style="padding: 0.3em 0.8em; background: #ffd666; color: #333; border-radius: 4px; text-decoration: none; font-size: 0.9em;">✏️ Edit</a>
                            <a href="holidays_delete.php?id=<?php echo $holiday['id']; ?>" class="delete-btn" style="padding: 0.3em 0.8em; background: #ff4d4f; color: #fff; border-radius: 4px; text-decoration: none; font-size: 0.9em;" onclick="return confirm('Delete this holiday?')">🗑️ Delete</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($currentYearHolidays)): ?>
            <div style="text-align: center; padding: 2em; color: #666;">
                <h3>No holidays found for <?php echo $currentYear; ?></h3>
                <p>Start by <a href="holidays_add.php">adding holidays</a> or check if the database is properly populated.</p>
            </div>
        <?php endif; ?>
    <?php elseif ($tab == 'media'): ?>
        <h1>Manage Featured Media</h1>
        <form method="post">
            <input type="hidden" name="media_form" value="1">
            <label>Media URL (YouTube, Facebook, TikTok, etc.):</label>
            <input type="text" name="url" required>
            <label>Title (optional):</label>
            <input type="text" name="title">
            <label>Description (optional):</label>
            <textarea name="description" rows="2"></textarea>
            <button type="submit">Add Media</button>
        </form>
        <div class="media-grid">
            <?php foreach ($mediaList as $media): ?>
                <div class="media-item">
                    <?php echo media_preview($media['url']); ?>
                    <?php if (!empty($media['title'])): ?>
                        <div class="media-title"><?php echo htmlspecialchars($media['title']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($media['description'])): ?>
                        <div class="media-desc"><?php echo htmlspecialchars($media['description']); ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <h1>Welcome to the Admin Dashboard</h1>
        <p>Use the sections below to manage news, live updates, and program schedule.</p>

        <!-- NEWS ARTICLES MANAGEMENT -->
        <section>
            <h2>News Articles</h2>
            <a href="news_add.php" class="btn">Add News Article</a>
            <?php
            // Example: Fetch and list articles
            $stmt = $pdo->query("SELECT * FROM articles ORDER BY created_at DESC");
            $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <table>
                <tr><th>Title</th><th>Date</th><th>Actions</th></tr>
                <?php foreach ($articles as $article): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($article['title']); ?></td>
                        <td><?php echo htmlspecialchars($article['created_at']); ?></td>
                        <td>
                            <a href="news_edit.php?id=<?php echo $article['id']; ?>">Edit</a> |
                            <a href="news_delete.php?id=<?php echo $article['id']; ?>" onclick="return confirm('Delete this article?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </section>

        <!-- LIVE UPDATES MANAGEMENT -->
        <section>
            <h2>Live Updates</h2>
            <a href="live_add.php" class="btn">Add Live Update</a>
            <?php
            $stmt = $pdo->query("SELECT * FROM live_updates ORDER BY created_at DESC");
            $liveUpdates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <ul>
                <?php foreach ($liveUpdates as $live): ?>
                    <li>
                        <?php echo htmlspecialchars($live['message']); ?>
                        <a href="live_edit.php?id=<?php echo $live['id']; ?>">Edit</a> |
                        <a href="live_delete.php?id=<?php echo $live['id']; ?>" onclick="return confirm('Delete this update?')">Delete</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>

        <!-- SCHEDULE MANAGEMENT -->
        <section>
            <h2>Program Schedule</h2>
            <div style="margin-bottom: 1em;">
                <a href="schedule_edit.php" class="btn">➕ Add New Program</a>
                <a href="schedule_manage.php" class="btn" style="background: #1890ff;">📋 Manage All Programs</a>
            </div>
            <?php
            // Fetch and group program schedule
            $stmt = $pdo->query("SELECT * FROM program_schedule ORDER BY program_name,
                CASE
                    WHEN day_of_week = 'Monday' THEN 1
                    WHEN day_of_week = 'Tuesday' THEN 2
                    WHEN day_of_week = 'Wednesday' THEN 3
                    WHEN day_of_week = 'Thursday' THEN 4
                    WHEN day_of_week = 'Friday' THEN 5
                    WHEN day_of_week = 'Saturday' THEN 6
                    WHEN day_of_week = 'Sunday' THEN 7
                    ELSE 8
                END, time_slot");
            $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Group programs by name and time slot
            $groupedPrograms = [];
            foreach ($schedules as $schedule) {
                $key = $schedule['program_name'] . '|' . $schedule['time_slot'];
                $groupedPrograms[$key]['program_name'] = $schedule['program_name'];
                $groupedPrograms[$key]['time_slot'] = $schedule['time_slot'];
                $groupedPrograms[$key]['days'][] = $schedule['day_of_week'];
                $groupedPrograms[$key]['ids'][] = $schedule['id'];
            }

            // Function to format day ranges
            function formatDayRange($days) {
                $dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                $dayAbbr = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

                // Sort days according to week order
                usort($days, function($a, $b) use ($dayOrder) {
                    return array_search($a, $dayOrder) - array_search($b, $dayOrder);
                });

                // Convert to abbreviations
                $abbrDays = array_map(function($day) use ($dayOrder, $dayAbbr) {
                    return $dayAbbr[array_search($day, $dayOrder)];
                }, $days);

                // Create ranges
                if (count($abbrDays) == 1) {
                    return $abbrDays[0];
                } elseif (count($abbrDays) == 7) {
                    return 'Daily';
                } elseif (array_slice($abbrDays, 0, 5) == ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'] && count($abbrDays) == 5) {
                    return 'Mon-Fri';
                } elseif (array_slice($abbrDays, 5, 2) == ['Sat', 'Sun'] && count($abbrDays) == 2) {
                    return 'Sat-Sun';
                } else {
                    // Check for consecutive ranges
                    $ranges = [];
                    $start = 0;
                    for ($i = 1; $i <= count($abbrDays); $i++) {
                        if ($i == count($abbrDays) || array_search($abbrDays[$i], $dayAbbr) != array_search($abbrDays[$i-1], $dayAbbr) + 1) {
                            if ($i - $start > 2) {
                                $ranges[] = $abbrDays[$start] . '-' . $abbrDays[$i-1];
                            } elseif ($i - $start == 2) {
                                $ranges[] = $abbrDays[$start] . ', ' . $abbrDays[$i-1];
                            } else {
                                $ranges[] = $abbrDays[$start];
                            }
                            $start = $i;
                        }
                    }
                    return implode(', ', $ranges);
                }
            }
            ?>
            <div class="schedule-container">
                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th>Program</th>
                            <th>Days</th>
                            <th>Time Slot</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($groupedPrograms as $program): ?>
                            <tr>
                                <td class="program-name"><?php echo htmlspecialchars($program['program_name']); ?></td>
                                <td><span class="program-days"><?php echo formatDayRange($program['days']); ?></span></td>
                                <td class="program-time"><?php echo htmlspecialchars($program['time_slot']); ?></td>
                                <td>
                                    <a href="schedule_edit.php?program=<?php echo urlencode($program['program_name']); ?>&time=<?php echo urlencode($program['time_slot']); ?>" class="edit-btn">✏️ Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if (empty($groupedPrograms)): ?>
                    <div style="text-align: center; padding: 2em; color: #666;">
                        <h3>No programs scheduled</h3>
                        <p>Start by <a href="schedule_edit.php">adding your first program</a>!</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>
</main>
<!-- Facebook SDK for video embeds -->
<div id="fb-root"></div>
<script async defer crossorigin="anonymous" src="https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v19.0"></script>
<!-- TikTok embed script -->
<script async src="https://www.tiktok.com/embed.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle fallback images for member cards
    document.querySelectorAll('.member-card img').forEach(function(img) {
        // Check if image src is empty or placeholder
        if (!img.src || img.src === '' || img.src.includes('#') || img.src === window.location.href) {
            img.style.display = 'none';
            var fallback = img.nextElementSibling;
            if (fallback && fallback.classList.contains('member-avatar-fallback')) {
                fallback.style.display = 'flex';
            }
        }
    });

    // Add smooth transitions for hover effects
    document.querySelectorAll('.member-card').forEach(function(card) {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});
</script>
</body>
</html>
<?php require_once '../includes/footer.php'; ?>