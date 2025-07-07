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
$article = null;
$articleTags = [];

// Get article ID from URL
$articleId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($articleId <= 0) {
    header('Location: index.php?error=Invalid article ID');
    exit();
}

// Fetch article data
$stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
$stmt->execute([$articleId]);
$article = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$article) {
    header('Location: index.php?error=Article not found');
    exit();
}

// Fetch article tags
$stmt = $pdo->prepare("SELECT tag_id FROM article_tags WHERE article_id = ?");
$stmt->execute([$articleId]);
$articleTags = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch all available tags
$stmt = $pdo->query("SELECT * FROM tags ORDER BY name ASC");
$allTags = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $selectedTags = $_POST['tags'] ?? [];
    $imagePath = $article['image_path']; // Keep existing image by default

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/news/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Validate file
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($_FILES['image']['type'], $allowedTypes)) {
            $error = "Invalid file type. Please upload JPG, PNG, GIF, or WebP images only.";
        } elseif ($_FILES['image']['size'] > $maxSize) {
            $error = "File too large. Maximum size is 5MB.";
        } else {
            // Generate safe filename
            $fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $fileName = uniqid() . '_' . time() . '.' . strtolower($fileExtension);
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                // Delete old image if it exists
                if ($article['image_path'] && file_exists('../' . $article['image_path'])) {
                    unlink('../' . $article['image_path']);
                }
                // Store relative path for database (without ../)
                $imagePath = 'uploads/news/' . $fileName;
            } else {
                $error = "Failed to upload image. Please check directory permissions.";
            }
        }
    }

    if ($title && $content && !$error) {
        try {
            // Update article
            $stmt = $pdo->prepare("UPDATE articles SET title = ?, content = ?, image_path = ? WHERE id = ?");
            $stmt->execute([$title, $content, $imagePath, $articleId]);

            // Update tags - first delete existing associations
            $stmt = $pdo->prepare("DELETE FROM article_tags WHERE article_id = ?");
            $stmt->execute([$articleId]);

            // Insert new tag associations
            foreach ($selectedTags as $tagId) {
                $stmt = $pdo->prepare("INSERT INTO article_tags (article_id, tag_id) VALUES (?, ?)");
                $stmt->execute([$articleId, $tagId]);
            }

            $message = "Article updated successfully!";
            
            // Refresh article data
            $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
            $stmt->execute([$articleId]);
            $article = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $pdo->prepare("SELECT tag_id FROM article_tags WHERE article_id = ?");
            $stmt->execute([$articleId]);
            $articleTags = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
        } catch (Exception $e) {
            $error = "Error updating article: " . $e->getMessage();
        }
    } else {
        if (!$title) $error = "Title is required.";
        elseif (!$content) $error = "Content is required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Article - Admin</title>
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
        input[type="text"], textarea, select { width: 100%; padding: 0.5em; border: 1px solid #ddd; border-radius: 4px; }
        textarea { height: 200px; resize: vertical; }
        select[multiple] { height: 120px; }
        button { margin-top: 1em; padding: 0.5em 1.5em; background: #003366; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #1890ff; }
        .msg { color: green; margin-bottom: 1em; padding: 0.5em; background: #f0f8ff; border: 1px solid #91d5ff; border-radius: 4px; }
        .error { color: red; margin-bottom: 1em; padding: 0.5em; background: #fff2f0; border: 1px solid #ffccc7; border-radius: 4px; }
        .current-image { margin: 1em 0; }
        .current-image img { max-width: 200px; height: auto; border: 1px solid #ddd; border-radius: 4px; }
        .tags-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.5em;
            margin: 0.5em 0;
            padding: 1em;
            background: #f9f9f9;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .tag-checkbox {
            display: flex;
            align-items: center;
            gap: 0.5em;
            padding: 0.3em;
            border-radius: 3px;
            transition: background-color 0.2s;
        }
        .tag-checkbox:hover { background: #f0f0f0; }
        .tag-checkbox input[type="checkbox"] {
            margin: 0;
            transform: scale(1.1);
        }
        .tag-checkbox label {
            margin: 0;
            cursor: pointer;
            font-weight: normal;
            font-size: 0.95em;
        }
        .no-tags {
            color: #666;
            font-style: italic;
            text-align: center;
            padding: 1em;
        }
    </style>
</head>
<body>
<header>
    <span style="font-size:1.5em;">✏️ Edit Article</span>
    <a href="index.php" style="float:right; color:#fff; text-decoration:none;">← Back to Dashboard</a>
</header>
<main>
    <h1>Edit Article</h1>
    
    <?php if ($message): ?>
        <div class="msg"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <label>Title:</label>
        <input type="text" name="title" required value="<?php echo htmlspecialchars($article['title']); ?>">
        
        <label>Content:</label>
        <textarea name="content" required><?php echo htmlspecialchars($article['content']); ?></textarea>
        
        <label>Current Image:</label>
        <?php if ($article['image_path'] && file_exists('../' . $article['image_path'])): ?>
            <div class="current-image">
                <img src="../<?php echo htmlspecialchars($article['image_path']); ?>" alt="Current image" style="max-width: 300px; height: auto; border-radius: 4px; border: 1px solid #ddd;">
                <br><small>Current image will be replaced if you upload a new one</small>
            </div>
        <?php else: ?>
            <div class="current-image">
                <small>No image currently uploaded</small>
            </div>
        <?php endif; ?>
        
        <label>Upload New Image (optional):</label>
        <input type="file" name="image" accept="image/*">
        
        <label>Tags:</label>
        <div class="tags-container">
            <?php if (!empty($allTags)): ?>
                <?php foreach ($allTags as $tag): ?>
                    <div class="tag-checkbox">
                        <input type="checkbox"
                               name="tags[]"
                               value="<?php echo $tag['id']; ?>"
                               id="tag_<?php echo $tag['id']; ?>"
                               <?php echo in_array($tag['id'], $articleTags) ? 'checked' : ''; ?>>
                        <label for="tag_<?php echo $tag['id']; ?>"><?php echo htmlspecialchars($tag['name']); ?></label>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-tags">No tags available. <a href="tags/create.php">Create some tags first</a>.</p>
            <?php endif; ?>
        </div>
        <small>Select one or more tags to categorize this article</small>
        
        <br>
        <button type="submit">Update Article</button>
        <a href="index.php" class="btn" style="margin-left: 1em;">Cancel</a>
    </form>
</main>
</body>
</html>
