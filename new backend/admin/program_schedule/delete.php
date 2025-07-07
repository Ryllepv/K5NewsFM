<?php
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Prepare the SQL statement to delete the program schedule entry
    $stmt = $pdo->prepare("DELETE FROM program_schedule WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        // Redirect to the program schedule index page after deletion
        header('Location: index.php?message=Schedule entry deleted successfully');
        exit();
    } else {
        echo "Error deleting schedule entry.";
    }
} else {
    echo "Invalid request.";
}
?>