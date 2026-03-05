<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Fetch all tweets with user info and like count
    $query = "SELECT t.*, u.username, u.id as author_id,
              (SELECT COUNT(*) FROM likes WHERE tweet_id = t.id) as like_count,
              (SELECT COUNT(*) FROM likes WHERE tweet_id = t.id AND user_id = ?) as user_liked,
              (SELECT COUNT(*) FROM follows WHERE follower_id = ? AND following_id = u.id) as is_following
              FROM tweets t 
              JOIN users u ON t.user_id = u.id 
              ORDER BY t.created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id, $user_id]);
    $tweets = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage() . ". Make sure you have imported database.sql and your db.php credentials are correct.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home / NeonTweet</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bg: #0a0a0b;
            --card-bg: rgba(255, 255, 255, 0.05);
            --neon-blue: #00f2ff;
            --neon-purple: #bc13fe;
            --neon-pink: #ff00ff;
            --text: #ffffff;
            --glass: rgba(255, 255, 255, 0.08);
            --sidebar-width: 260px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background-color: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
        }

        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
            border-right: 1px solid var(--glass);
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            z-index: 100;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--neon-blue);
            text-shadow: 0 0 10px var(--neon-blue);
            margin-bottom: 30px;
            text-decoration: none;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 20px;
            text-decoration: none;
            color: #ccc;
            border-radius: 12px;
            transition: 0.3s;
            font-size: 1.1rem;
        }

        .nav-item i {
            font-size: 1.3rem;
            width: 25px;
        }

        .nav-item:hover, .nav-item.active {
            color: var(--neon-blue);
            background: var(--glass);
            box-shadow: inset 0 0 10px rgba(0, 242, 255, 0.1);
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 30px;
            max-width: 800px;
            width: 100%;
        }

        .feed-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Tweet Box */
        .tweet-box {
            background: var(--card-bg);
            border: 1px solid var(--glass);
            border-radius: 20px;
            padding: 20px;
            backdrop-filter: blur(5px);
        }

        textarea {
            width: 100%;
            background: transparent;
            border: none;
            color: white;
            font-size: 1.2rem;
            resize: none;
            outline: none;
            min-height: 100px;
            margin-bottom: 10px;
        }

        .tweet-box-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid var(--glass);
            padding-top: 15px;
        }

        #char-count {
            font-size: 0.9rem;
            color: #888;
        }

        #char-count.limit-near { color: #ffad1f; }
        #char-count.limit-reached { color: #ff0000; }

        .btn-tweet {
            background: var(--neon-blue);
            color: #000;
            border: none;
            padding: 8px 25px;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-tweet:hover {
            box-shadow: 0 0 15px var(--neon-blue);
            transform: scale(1.05);
        }

        .btn-tweet:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Tweet Post */
        .tweet-post {
            background: var(--card-bg);
            border: 1px solid var(--glass);
            border-radius: 20px;
            padding: 20px;
            transition: 0.3s;
            position: relative;
        }

        .tweet-post:hover {
            border-color: rgba(0, 242, 255, 0.3);
            background: rgba(255, 255, 255, 0.07);
        }

        .post-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .author-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .username {
            font-weight: 600;
            color: var(--neon-blue);
            text-decoration: none;
        }

        .timestamp {
            font-size: 0.8rem;
            color: #888;
        }

        .post-content {
            font-size: 1.05rem;
            line-height: 1.5;
            margin-bottom: 15px;
            word-wrap: break-word;
        }

        .post-actions {
            display: flex;
            gap: 30px;
            border-top: 1px solid var(--glass);
            padding-top: 12px;
        }

        .action-btn {
            background: none;
            border: none;
            color: #888;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
            font-size: 0.95rem;
            text-decoration: none;
        }

        .action-btn:hover { color: var(--neon-blue); }
        .action-btn.liked { color: var(--neon-pink); }
        .action-btn.liked i { filter: drop-shadow(0 0 5px var(--neon-pink)); }

        .btn-follow {
            background: transparent;
            border: 1px solid var(--neon-purple);
            color: var(--neon-purple);
            padding: 4px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
        }

        .btn-follow:hover {
            background: var(--neon-purple);
            color: white;
            box-shadow: 0 0 10px var(--neon-purple);
        }

        .btn-unfollow {
            border-color: #ff4d4d;
            color: #ff4d4d;
        }

        .btn-unfollow:hover {
            background: #ff4d4d;
        }

        .delete-btn {
            color: #ff4d4d;
            opacity: 0.5;
        }

        .delete-btn:hover {
            opacity: 1;
            color: #ff0000;
        }

        /* Responsiveness */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                padding: 20px 10px;
                align-items: center;
            }

            .logo, .nav-item span {
                display: none;
            }

            .main-content {
                margin-left: 70px;
                padding: 15px;
            }

            .sidebar i {
                margin: 0;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                margin-left: 0;
                margin-bottom: 70px;
            }

            .sidebar {
                width: 100%;
                height: 60px;
                flex-direction: row;
                justify-content: space-around;
                top: auto;
                bottom: 0;
                padding: 0;
                border-right: none;
                border-top: 1px solid var(--glass);
            }
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <a href="home.php" class="logo">NT</a>
        <a href="home.php" class="nav-item active"><i class="fas fa-home"></i> <span>Home</span></a>
        <a href="profile.php" class="nav-item"><i class="fas fa-user"></i> <span>Profile</span></a>
        <a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </div>

    <div class="main-content">
        <div class="feed-container">
            <!-- Tweet Box -->
            <div class="tweet-box">
                <form action="tweet.php" method="POST">
                    <input type="hidden" name="action" value="create">
                    <textarea name="content" id="tweet-input" placeholder="What's happening?" maxlength="280"></textarea>
                    <div class="tweet-box-footer">
                        <span id="char-count">0/280</span>
                        <button type="submit" class="btn-tweet" id="tweet-btn" disabled>Tweet</button>
                    </div>
                </form>
            </div>

            <!-- Feed -->
            <?php foreach ($tweets as $tweet): ?>
                <div class="tweet-post">
                    <div class="post-header">
                        <div class="author-info">
                            <a href="profile.php?username=<?php echo urlencode($tweet['username']); ?>" class="username">
                                @<?php echo htmlspecialchars($tweet['username']); ?>
                            </a>
                            <span class="timestamp"><?php echo date('M d, H:i', strtotime($tweet['created_at'])); ?></span>
                            
                            <?php if ($tweet['author_id'] != $user_id): ?>
                                <a href="follow.php?id=<?php echo $tweet['author_id']; ?>&redirect=home" 
                                   class="btn-follow <?php echo $tweet['is_following'] ? 'btn-unfollow' : ''; ?>">
                                    <?php echo $tweet['is_following'] ? 'Unfollow' : 'Follow'; ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($tweet['author_id'] == $user_id): ?>
                            <a href="tweet.php?action=delete&id=<?php echo $tweet['id']; ?>" class="action-btn delete-btn" onclick="return confirm('Delete this tweet?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="post-content">
                        <?php echo nl2br(htmlspecialchars($tweet['content'])); ?>
                    </div>
                    
                    <div class="post-actions">
                        <a href="like.php?tweet_id=<?php echo $tweet['id']; ?>&redirect=home" class="action-btn <?php echo $tweet['user_liked'] ? 'liked' : ''; ?>">
                            <i class="fa<?php echo $tweet['user_liked'] ? 's' : 'r'; ?> fa-heart"></i>
                            <span><?php echo $tweet['like_count']; ?></span>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        const tweetInput = document.getElementById('tweet-input');
        const charCount = document.getElementById('char-count');
        const tweetBtn = document.getElementById('tweet-btn');

        tweetInput.addEventListener('input', () => {
            const length = tweetInput.value.length;
            charCount.textContent = `${length}/280`;
            
            if (length > 0 && length <= 280) {
                tweetBtn.disabled = false;
            } else {
                tweetBtn.disabled = true;
            }

            if (length >= 260 && length < 280) {
                charCount.className = 'limit-near';
            } else if (length >= 280) {
                charCount.className = 'limit-reached';
            } else {
                charCount.className = '';
            }
        });
    </script>
</body>
</html>
