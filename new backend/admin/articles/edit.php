<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

// Initialize variables
$title = '';
$content = '';
$image_path = '';
$tags = [];
$article_id = $_GET['id'] ?? null;

if ($article_id) {
    // Fetch article data
    $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = :id");
    $stmt->execute(['id' => $article_id]);
    $article = $stmt->fetch();

    if ($article) {
        $title = $article['title'];
        $content = $article['content'];
        $image_path = $article['image_path'];

        // Fetch associated tags
        $stmt = $pdo->prepare("SELECT tags.name FROM article_tags JOIN tags ON article_tags.tag_id = tags.id WHERE article_tags.article_id = :article_id");
        $stmt->execute(['article_id' => $article_id]);
        $tags = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } else {
        // Article not found
        header('Location: index.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $tags = $_POST['tags'] ?? [];

    // Handle file upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/';
        $image_path = $upload_dir . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $image_path);
    }

    // Update article in the database
    $stmt = $pdo->prepare("UPDATE articles SET title = :title, content = :content, image_path = :image_path WHERE id = :id");
    $stmt->execute([
        'title' => $title,
        'content' => $content,
        'image_path' => $image_path,
        'id' => $article_id
    ]);

    // Update tags
    $stmt = $pdo->prepare("DELETE FROM article_tags WHERE article_id = :article_id");
    $stmt->execute(['article_id' => $article_id]);

    foreach ($tags as $tag_id) {
        $stmt = $pdo->prepare("INSERT INTO article_tags (article_id, tag_id) VALUES (:article_id, :tag_id)");
        $stmt->execute(['article_id' => $article_id, 'tag_id' => $tag_id]);
    }

    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Article</title>
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
    <h1>Edit Article</h1>
    <form action="" method="post" enctype="multipart/form-data">
        <label for="title">Title:</label>
        <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($title); ?>" required>
        
        <label for="content">Content:</label>
        <textarea name="content" id="content" required><?php echo htmlspecialchars($content); ?></textarea>
        
        <label for="image">Thumbnail Image:</label>
        <input type="file" name="image" id="image">
        
        <label for="tags">Tags:</label>
        <div class="tags-container">
            <?php
            // Fetch all tags for selection
            $stmt = $pdo->query("SELECT * FROM tags");
            $allTags = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($allTags)):
                foreach ($allTags as $tag):
                    $checked = in_array($tag['id'], $tags) ? 'checked' : '';
            ?>
                    <div class="tag-checkbox">
                        <input type="checkbox"
                               name="tags[]"
                               value="<?php echo $tag['id']; ?>"
                               id="tag_<?php echo $tag['id']; ?>"
                               <?php echo $checked; ?>>
                        <label for="tag_<?php echo $tag['id']; ?>"><?php echo htmlspecialchars($tag['name']); ?></label>
                    </div>
            <?php
                endforeach;
            else:
            ?>
                <p class="no-tags">No tags available.</p>
            <?php endif; ?>
        </div>
        
        <button type="submit">Update Article</button>
    </form>
</body>
</html>