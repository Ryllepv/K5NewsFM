<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Handle form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    <title>Admin - Featured Media</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f6f6f6; }
        main { max-width: 800px; margin: 2em auto; background: #fff; padding: 2em; border-radius: 8px; }
        h1 { color: #003366; }
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
    </style>
</head>
<body>
<main>
    <h1>Manage Featured Media</h1>
    <?php if ($message): ?>
        <div class="msg"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <form method="post">
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
</main>
<!-- Facebook SDK for video embeds -->
<div id="fb-root"></div>
<script async defer crossorigin="anonymous" src="https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v19.0"></script>
<!-- TikTok embed script -->
<script async src="https://www.tiktok.com/embed.js"></script>
</body>
</html>