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

// Get article ID from URL
$articleId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($articleId <= 0) {
    header('Location: index.php?error=Invalid article ID');
    exit();
}

// Fetch article data for confirmation
$stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
$stmt->execute([$articleId]);
$article = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$article) {
    header('Location: index.php?error=Article not found');
    exit();
}

// Handle deletion confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Delete article tags first (foreign key constraint)
        $stmt = $pdo->prepare("DELETE FROM article_tags WHERE article_id = ?");
        $stmt->execute([$articleId]);
        
        // Delete the article
        $stmt = $pdo->prepare("DELETE FROM articles WHERE id = ?");
        $stmt->execute([$articleId]);
        
        // Delete associated image file if it exists
        if ($article['image_path'] && file_exists($article['image_path'])) {
            unlink($article['image_path']);
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Redirect with success message
        header('Location: index.php?message=Article deleted successfully');
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollback();
        $error = "Error deleting article: " . $e->getMessage();
    }
}

// Handle cancellation
if (isset($_GET['cancel'])) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete Article - Admin</title>
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
        .article-preview { background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; padding: 1em; margin: 1em 0; }
        .article-title { font-weight: bold; font-size: 1.1em; margin-bottom: 0.5em; }
        .article-content { color: #666; margin-bottom: 0.5em; }
        .article-meta { font-size: 0.9em; color: #888; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 1em; border-radius: 4px; margin: 1em 0; }
        .error { color: red; margin-bottom: 1em; padding: 0.5em; background: #fff2f0; border: 1px solid #ffccc7; border-radius: 4px; }
        .confirmation-form { margin-top: 2em; }
    </style>
</head>
<body>
<header>
    <span style="font-size:1.5em;">🗑️ Delete Article</span>
    <a href="index.php" style="float:right; color:#fff; text-decoration:none;">← Back to Dashboard</a>
</header>
<main>
    <h1>Delete Article</h1>
    
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="warning">
        <strong>⚠️ Warning:</strong> This action cannot be undone. The article and all its associated data will be permanently deleted.
    </div>

    <div class="article-preview">
        <div class="article-title"><?php echo htmlspecialchars($article['title']); ?></div>
        <div class="article-content">
            <?php 
            $preview = substr(strip_tags($article['content']), 0, 200);
            echo htmlspecialchars($preview);
            if (strlen($article['content']) > 200) echo '...';
            ?>
        </div>
        <div class="article-meta">
            Created: <?php echo htmlspecialchars($article['created_at']); ?>
            <?php if ($article['image_path']): ?>
                | Has image attachment
            <?php endif; ?>
        </div>
    </div>

    <div class="confirmation-form">
        <p><strong>Are you sure you want to delete this article?</strong></p>
        
        <form method="post" style="display: inline;">
            <input type="hidden" name="confirm_delete" value="1">
            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you absolutely sure? This cannot be undone!')">
                🗑️ Yes, Delete Article
            </button>
        </form>
        
        <a href="index.php" class="btn btn-secondary">
            ← Cancel
        </a>
    </div>

    <script>
    // Additional confirmation for extra safety
    document.querySelector('form').addEventListener('submit', function(e) {
        if (!confirm('FINAL CONFIRMATION: Delete article "<?php echo addslashes($article['title']); ?>"?\n\nThis action is permanent and cannot be undone.')) {
            e.preventDefault();
        }
    });
    </script>
</main>
</body>
</html>
