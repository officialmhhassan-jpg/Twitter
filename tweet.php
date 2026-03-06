<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_REQUEST['action'] ?? '';

if ($action == 'create' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $content = trim($_POST['content']);
    if (!empty($content) && strlen($content) <= 280) {
        $stmt = $pdo->prepare("INSERT INTO tweets (user_id, content) VALUES (?, ?)");
        $stmt->execute([$user_id, $content]);
    }
} elseif ($action == 'delete' && isset($_GET['id'])) {
    $tweet_id = (int)$_GET['id'];
    // Ensure user owns the tweet
    $stmt = $pdo->prepare("DELETE FROM tweets WHERE id = ? AND user_id = ?");
    $stmt->execute([$tweet_id, $user_id]);
}

header("Location: home.php");
exit;
?>
