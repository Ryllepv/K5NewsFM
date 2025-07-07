<?php
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Prepare the SQL statement to delete the live update
    $stmt = $pdo->prepare("DELETE FROM live_updates WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        header('Location: index.php?message=Live update deleted successfully');
        exit();
    } else {
        header('Location: index.php?error=Failed to delete live update');
        exit();
    }
} else {
    header('Location: index.php?error=Invalid request');
    exit();
}
?>