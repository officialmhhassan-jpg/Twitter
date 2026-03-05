<?php
session_start();
require "db.php";
if(!isset($_SESSION["user_id"])) exit;

$uid=$_SESSION["user_id"];
$action=$_GET["action"] ?? "";

if($action=="post"){
$content=trim($_POST["content"]);
if($content){
$stmt=$pdo->prepare("INSERT INTO tweets(user_id,content) VALUES(?,?)");
$stmt->execute([$uid,$content]);
}
}

if($action=="like"){
$id=$_GET["id"];
$stmt=$pdo->prepare("INSERT IGNORE INTO likes(user_id,tweet_id) VALUES(?,?)");
$stmt->execute([$uid,$id]);
}

if($action=="comment"){
$id=$_GET["id"];
$content=trim($_POST["content"]);
if($content){
$stmt=$pdo->prepare("INSERT INTO comments(user_id,tweet_id,content) VALUES(?,?,?)");
$stmt->execute([$uid,$id,$content]);
}
}

if($action=="follow"){
$id=$_GET["id"];
$stmt=$pdo->prepare("INSERT IGNORE INTO followers(follower_id,following_id) VALUES(?,?)");
$stmt->execute([$uid,$id]);
}

if($action=="load"){
$stmt=$pdo->prepare("
SELECT tweets.*, users.username 
FROM tweets 
JOIN users ON tweets.user_id=users.id
ORDER BY tweets.created_at DESC
");
$stmt->execute();
$tweets=$stmt->fetchAll();

foreach($tweets as $t){
echo "<div class='tweet'>";
echo "<strong>@".htmlspecialchars($t["username"])."</strong><br>";
echo htmlspecialchars($t["content"])."<br>";
echo "<small>".$t["created_at"]."</small>";

$likeCount=$pdo->prepare("SELECT COUNT(*) FROM likes WHERE tweet_id=?");
$likeCount->execute([$t["id"]]);
$count=$likeCount->fetchColumn();

echo "<div class='actions'>";
echo "<button onclick='likeTweet(".$t["id"].")'>❤️ ".$count."</button>";
echo "</div>";

echo "<div class='comment-box'>";
echo "<input type='text' id='comment_".$t["id"]."' placeholder='Comment'>";
echo "<button onclick='addComment(".$t["id"].")'>Reply</button>";
echo "</div>";

echo "</div>";
}
}
?>
