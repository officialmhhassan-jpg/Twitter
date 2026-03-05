<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['tweet_id'])) {
    header("Location: home.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$tweet_id = (int)$_GET['tweet_id'];
$redirect = $_GET['redirect'] ?? 'home';

// Check if already liked
$stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND tweet_id = ?");
$stmt->execute([$user_id, $tweet_id]);
$like = $stmt->fetch();

if ($like) {
    // Unlike
    $stmt = $pdo->prepare("DELETE FROM likes WHERE id = ?");
    $stmt->execute([$like['id']]);
} else {
    // Like
    try {
        $stmt = $pdo->prepare("INSERT INTO likes (user_id, tweet_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $tweet_id]);
    } catch (PDOException $e) {
        // Already liked (duplicate prevention via unique key)
    }
}

$url = $redirect == 'home' ? 'home.php' : "profile.php";
header("Location: $url");
exit;
?>
