<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: home.php");
    exit;
}

$follower_id = $_SESSION['user_id'];
$following_id = (int)$_GET['id'];
$redirect = $_GET['redirect'] ?? 'home';
$username = $_GET['username'] ?? '';

if ($follower_id != $following_id) {
    // Check if already following
    $stmt = $pdo->prepare("SELECT id FROM follows WHERE follower_id = ? AND following_id = ?");
    $stmt->execute([$follower_id, $following_id]);
    $follow = $stmt->fetch();

    if ($follow) {
        // Unfollow
        $stmt = $pdo->prepare("DELETE FROM follows WHERE id = ?");
        $stmt->execute([$follow['id']]);
    } else {
        // Follow
        $stmt = $pdo->prepare("INSERT INTO follows (follower_id, following_id) VALUES (?, ?)");
        $stmt->execute([$follower_id, $following_id]);
    }
}

$url = $redirect == 'home' ? 'home.php' : "profile.php?username=$username";
header("Location: $url");
exit;
?>
