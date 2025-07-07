<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/auth.php';

if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $tags = $_POST['tags'] ?? [];
    $imagePath = '';

    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $targetDir = '../../uploads/';
        $imageName = basename($_FILES['image']['name']);
        $imagePath = $targetDir . uniqid() . '_' . $imageName;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
            $imagePath = '';
        }
    }

    if ($title && $content) {
        $stmt = $pdo->prepare("INSERT INTO articles (title, content, created_at, image_path) VALUES (?, ?, NOW(), ?)");
        $stmt->execute([$title, $content, $imagePath]);
        $articleId = $pdo->lastInsertId();

        foreach ($tags as $tagId) {
            $stmt = $pdo->prepare("INSERT INTO article_tags (article_id, tag_id) VALUES (?, ?)");
            $stmt->execute([$articleId, $tagId]);
        }

        header('Location: ../index.php');
        exit();
    }
}

$stmt = $pdo->query("SELECT * FROM tags");
$tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Article</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f6f6f6; margin: 0; padding: 2em; }
        h1 { color: #003366; }
        form { max-width: 600px; background: #fff; padding: 2em; border-radius: 8px; }
        label { display: block; margin-top: 1em; font-weight: bold; }
        input[type="text"], textarea { width: 100%; padding: 0.5em; border: 1px solid #ddd; border-radius: 4px; }
        textarea { height: 150px; resize: vertical; }
        button { margin-top: 1em; padding: 0.5em 1.5em; background: #003366; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #1890ff; }
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
    <h1>Create New Article</h1>
    <form action="" method="POST" enctype="multipart/form-data">
        <label for="title">Title:</label>
        <input type="text" name="title" required>
        <br>
        <label for="content">Content:</label>
        <textarea name="content" required></textarea>
        <br>
        <label for="image">Thumbnail Image:</label>
        <input type="file" name="image" accept="image/*">
        <br>
        <label for="tags">Tags:</label>
        <div class="tags-container">
            <?php if (!empty($tags)): ?>
                <?php foreach ($tags as $tag): ?>
                    <div class="tag-checkbox">
                        <input type="checkbox"
                               name="tags[]"
                               value="<?= $tag['id'] ?>"
                               id="tag_<?= $tag['id'] ?>">
                        <label for="tag_<?= $tag['id'] ?>"><?= htmlspecialchars($tag['name']) ?></label>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-tags">No tags available.</p>
            <?php endif; ?>
        </div>
        <br>
        <button type="submit">Create Article</button>
    </form>
    <a href="../index.php">Back to Dashboard</a>
</body>
</html>