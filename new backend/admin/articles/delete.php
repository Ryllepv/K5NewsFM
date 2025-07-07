<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/auth.php';

if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

if (isset($_GET['id'])) {
    $articleId = $_GET['id'];

    // Prepare the SQL statement to delete the article
    $stmt = $pdo->prepare("DELETE FROM articles WHERE id = :id");
    $stmt->bindParam(':id', $articleId, PDO::PARAM_INT);

    if ($stmt->execute()) {
        // Optionally, you can add logic to delete the associated image file from the uploads directory
        header('Location: ../index.php?message=Article deleted successfully');
        exit();
    } else {
        header('Location: ../index.php?error=Failed to delete article');
        exit();
    }
} else {
    header('Location: ../index.php?error=Invalid article ID');
    exit();
}
?>