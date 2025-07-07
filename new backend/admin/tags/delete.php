<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/auth.php';

if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

if (isset($_GET['id'])) {
    $tagId = $_GET['id'];

    // Prepare the SQL statement to delete the tag
    $stmt = $pdo->prepare("DELETE FROM tags WHERE id = :id");
    $stmt->bindParam(':id', $tagId);

    if ($stmt->execute()) {
        // Optionally, you can also delete the associated entries in article_tags
        $stmt = $pdo->prepare("DELETE FROM article_tags WHERE tag_id = :tag_id");
        $stmt->bindParam(':tag_id', $tagId);
        $stmt->execute();

        header('Location: index.php?message=Tag deleted successfully');
    } else {
        header('Location: index.php?error=Failed to delete tag');
    }
} else {
    header('Location: index.php?error=No tag ID provided');
}
?>