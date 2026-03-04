<?php
require_once 'config.php';

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$rest_id = $_GET['id'] ?? 0;

// Check if already favorite
$stmt = $conn->prepare("SELECT id FROM favorites WHERE user_id = ? AND restaurant_id = ?");
$stmt->bind_param("ii", $user_id, $rest_id);
$stmt->execute();
$fav = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($fav) {
    // Remove
    $stmt = $conn->prepare("DELETE FROM favorites WHERE id = ?");
    $stmt->bind_param("i", $fav['id']);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['status' => 'removed']);
} else {
    // Add
    $stmt = $conn->prepare("INSERT INTO favorites (user_id, restaurant_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $rest_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['status' => 'added']);
}
