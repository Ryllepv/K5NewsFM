<?php
// This file contains various utility functions used throughout the application

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function uploadImage($file) {
    $targetDir = "../uploads/";
    $targetFile = $targetDir . basename($file["name"]);
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    $check = getimagesize($file["tmp_name"]);

    if ($check === false) {
        return false; // Not an image
    }

    if (file_exists($targetFile)) {
        return false; // File already exists
    }

    if ($file["size"] > 500000) {
        return false; // File too large
    }

    if (!in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif'])) {
        return false; // Invalid file type
    }

    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        return $targetFile; // Return the path of the uploaded file
    } else {
        return false; // Upload failed
    }
}

function fetchAllArticles($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM articles ORDER BY created_at DESC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchTags($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM tags");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchLiveUpdates($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM live_updates ORDER BY created_at DESC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchProgramSchedule($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM program_schedule ORDER BY day_of_week, time_slot");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>