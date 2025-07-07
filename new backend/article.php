<?php
require_once 'config/db.php';

// Get article ID from URL
$articleId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($articleId <= 0) {
    header('Location: index.php');
    exit();
}

// Fetch article with tags
$stmt = $pdo->prepare("
    SELECT a.*, GROUP_CONCAT(t.name) AS tags 
    FROM articles a 
    LEFT JOIN article_tags at ON a.id = at.article_id 
    LEFT JOIN tags t ON at.tag_id = t.id 
    WHERE a.id = ? 
    GROUP BY a.id
");
$stmt->execute([$articleId]);
$article = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$article) {
    header('Location: index.php');
    exit();
}

// Fetch related articles (same tags)
$stmt = $pdo->prepare("
    SELECT DISTINCT a.*, GROUP_CONCAT(t.name) AS tags 
    FROM articles a 
    LEFT JOIN article_tags at ON a.id = at.article_id 
    LEFT JOIN tags t ON at.tag_id = t.id 
    WHERE a.id != ? AND at.tag_id IN (
        SELECT tag_id FROM article_tags WHERE article_id = ?
    )
    GROUP BY a.id 
    ORDER BY a.created_at DESC 
    LIMIT 3
");
$stmt->execute([$articleId, $articleId]);
$relatedArticles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($article['title']); ?> - News Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; background: #f6f6f6; margin: 0; line-height: 1.6; }
        header { background: #003366; color: #fff; padding: 1em 2em; }
        .container { max-width: 800px; margin: 2em auto; background: #fff; padding: 2em; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .back-link { display: inline-block; margin-bottom: 2em; color: #003366; text-decoration: none; }
        .back-link:hover { color: #1890ff; }
        .article-header { margin-bottom: 2em; }
        .article-title { color: #003366; margin: 0 0 1em 0; font-size: 2em; line-height: 1.2; }
        .article-meta { display: flex; flex-wrap: wrap; gap: 1em; margin-bottom: 1.5em; font-size: 0.9em; color: #666; }
        .article-date { color: #1890ff; }
        .article-tags { color: #52c41a; }
        .article-image { width: 100%; margin-bottom: 2em; text-align: center; }
        .article-image img { max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .article-content { font-size: 1.1em; line-height: 1.7; color: #333; }
        .article-content p { margin-bottom: 1.5em; }
        .related-articles { margin-top: 3em; padding-top: 2em; border-top: 1px solid #eee; }
        .related-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1em; margin-top: 1em; }
        .related-card { background: #f9f9f9; padding: 1em; border-radius: 6px; }
        .related-card h4 { margin: 0 0 0.5em 0; color: #003366; }
        .related-card a { color: #003366; text-decoration: none; }
        .related-card a:hover { color: #1890ff; }
        .related-meta { font-size: 0.8em; color: #666; margin-bottom: 0.5em; }
    </style>
</head>
<body>
<header>
    <span style="font-size:1.5em;">📰 News Portal</span>
    <a href="index.php" style="float:right; color:#fff; text-decoration:none;">← Back to Home</a>
</header>

<div class="container">
    <a href="index.php#news" class="back-link">← Back to News</a>
    
    <article>
        <div class="article-header">
            <h1 class="article-title"><?php echo htmlspecialchars($article['title']); ?></h1>
            <div class="article-meta">
                <span class="article-date">📅 <?php echo date('F j, Y \a\t g:i A', strtotime($article['created_at'])); ?></span>
                <?php if ($article['tags']): ?>
                    <span class="article-tags">🏷️ <?php echo htmlspecialchars($article['tags']); ?></span>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($article['image_path'] && file_exists($article['image_path'])): ?>
            <div class="article-image">
                <img src="<?php echo htmlspecialchars($article['image_path']); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>">
            </div>
        <?php endif; ?>
        
        <div class="article-content">
            <?php echo nl2br(htmlspecialchars($article['content'])); ?>
        </div>
    </article>
    
    <?php if (!empty($relatedArticles)): ?>
        <div class="related-articles">
            <h3>Related Articles</h3>
            <div class="related-grid">
                <?php foreach ($relatedArticles as $related): ?>
                    <div class="related-card">
                        <div class="related-meta">
                            <?php echo date('M j, Y', strtotime($related['created_at'])); ?>
                        </div>
                        <h4>
                            <a href="article.php?id=<?php echo $related['id']; ?>">
                                <?php echo htmlspecialchars($related['title']); ?>
                            </a>
                        </h4>
                        <?php if ($related['tags']): ?>
                            <div class="related-meta">
                                🏷️ <?php echo htmlspecialchars($related['tags']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
