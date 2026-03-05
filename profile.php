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

$logged_in_id = $_SESSION['user_id'];
$requested_username = $_GET['username'] ?? $_SESSION['username'];

try {
    // Fetch user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$requested_username]);
    $profile_user = $stmt->fetch();

    if (!$profile_user) {
        die("User not found.");
    }

    $profile_user_id = $profile_user['id'];

    // Counts
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE following_id = ?");
    $stmt->execute([$profile_user_id]);
    $followers_count = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ?");
    $stmt->execute([$profile_user_id]);
    $following_count = $stmt->fetchColumn();

    // Is following?
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ? AND following_id = ?");
    $stmt->execute([$logged_in_id, $profile_user_id]);
    $is_following = $stmt->fetchColumn() > 0;

    // User's tweets
    $query = "SELECT t.*, u.username,
              (SELECT COUNT(*) FROM likes WHERE tweet_id = t.id) as like_count,
              (SELECT COUNT(*) FROM likes WHERE tweet_id = t.id AND user_id = ?) as user_liked
              FROM tweets t 
              JOIN users u ON t.user_id = u.id 
              WHERE t.user_id = ?
              ORDER BY t.created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$logged_in_id, $profile_user_id]);
    $tweets = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// Update bio (own profile)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $logged_in_id == $profile_user_id && isset($_POST['bio'])) {
    $new_bio = trim($_POST['bio']);
    $stmt = $pdo->prepare("UPDATE users SET bio = ? WHERE id = ?");
    $stmt->execute([$new_bio, $logged_in_id]);
    header("Location: profile.php?username=" . urlencode($requested_username));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@<?php echo htmlspecialchars($profile_user['username']); ?> / NeonTweet</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bg: #0a0a0b;
            --card-bg: rgba(255, 255, 255, 0.05);
            --neon-blue: #00f2ff;
            --neon-purple: #bc13fe;
            --text: #ffffff;
            --glass: rgba(255, 255, 255, 0.08);
            --sidebar-width: 260px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background-color: var(--bg); color: var(--text); min-height: 100vh; display: flex; }

        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            left: 0; top: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
            border-right: 1px solid var(--glass);
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            z-index: 100;
        }

        .logo { font-size: 1.8rem; font-weight: 700; color: var(--neon-blue); text-shadow: 0 0 10px var(--neon-blue); margin-bottom: 30px; text-decoration: none; }
        .nav-item { display: flex; align-items: center; gap: 15px; padding: 12px 20px; text-decoration: none; color: #ccc; border-radius: 12px; transition: 0.3s; font-size: 1.1rem; }
        .nav-item i { font-size: 1.3rem; width: 25px; }
        .nav-item:hover, .nav-item.active { color: var(--neon-blue); background: var(--glass); }

        .main-content { margin-left: var(--sidebar-width); flex: 1; padding: 30px; max-width: 800px; }

        .profile-header {
            background: var(--card-bg);
            border: 1px solid var(--glass);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .profile-header::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 5px;
            background: linear-gradient(90deg, var(--neon-blue), var(--neon-purple));
        }

        .profile-info h1 {
            font-size: 2rem;
            color: var(--neon-blue);
            margin-bottom: 10px;
        }

        .bio {
            color: #ccc;
            line-height: 1.6;
            margin-bottom: 20px;
            font-style: italic;
        }

        .stats {
            display: flex;
            gap: 30px;
            margin-bottom: 20px;
        }

        .stat-item {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .stat-count {
            font-weight: 700;
            color: white;
        }

        .stat-label {
            color: #888;
            font-size: 0.9rem;
        }

        .profile-actions {
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 10px 25px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            border: none;
        }

        .btn-follow {
            background: var(--neon-blue);
            color: #000;
        }

        .btn-unfollow {
            background: transparent;
            border: 1px solid #ff4d4d;
            color: #ff4d4d;
        }

        .btn-edit {
            background: var(--glass);
            color: white;
            border: 1px solid var(--glass);
        }

        .btn-edit:hover { background: rgba(255, 255, 255, 0.15); }

        /* Tweets in Profile */
        .tweet-post {
            background: var(--card-bg);
            border: 1px solid var(--glass);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
            transition: 0.3s;
        }

        .post-content { line-height: 1.5; margin: 10px 0; }
        .post-footer { display: flex; gap: 20px; color: #888; border-top: 1px solid var(--glass); padding-top: 10px; }
        .liked { color: #ff00ff; }

        /* Bio Edit Modal Style */
        .bio-form { margin-top: 20px; display: none; }
        .bio-form textarea {
            width: 100%;
            background: rgba(0,0,0,0.3);
            border: 1px solid var(--glass);
            color: white;
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 10px;
        }

        @media (max-width: 768px) {
            .sidebar { width: 70px; align-items: center; }
            .logo, .nav-item span { display: none; }
            .main-content { margin-left: 70px; width: calc(100% - 70px); }
        }

        @media (max-width: 480px) {
            .main-content { margin-left: 0; margin-bottom: 70px; width: 100%; }
            .sidebar { width: 100%; height: 60px; flex-direction: row; top: auto; bottom: 0; border: none; border-top: 1px solid var(--glass); }
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <a href="home.php" class="logo">NT</a>
        <a href="home.php" class="nav-item"><i class="fas fa-home"></i> <span>Home</span></a>
        <a href="profile.php" class="nav-item <?php echo $requested_username == $_SESSION['username'] ? 'active' : ''; ?>"><i class="fas fa-user"></i> <span>Profile</span></a>
        <a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </div>

    <div class="main-content">
        <div class="profile-header">
            <div class="profile-info">
                <h1>@<?php echo htmlspecialchars($profile_user['username']); ?></h1>
                <p class="bio" id="bio-text"><?php echo htmlspecialchars($profile_user['bio'] ?: 'No bio yet...'); ?></p>
                
                <div class="stats">
                    <div class="stat-item">
                        <span class="stat-count"><?php echo $following_count; ?></span>
                        <span class="stat-label">Following</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-count"><?php echo $followers_count; ?></span>
                        <span class="stat-label">Followers</span>
                    </div>
                </div>

                <div class="profile-actions">
                    <?php if ($logged_in_id == $profile_user_id): ?>
                        <button class="btn btn-edit" onclick="toggleBioForm()">Edit Bio</button>
                    <?php else: ?>
                        <a href="follow.php?id=<?php echo $profile_user_id; ?>&redirect=profile&username=<?php echo urlencode($profile_user['username']); ?>" 
                           class="btn <?php echo $is_following ? 'btn-unfollow' : 'btn-follow'; ?>">
                            <?php echo $is_following ? 'Unfollow' : 'Follow'; ?>
                        </a>
                    <?php endif; ?>
                </div>

                <?php if ($logged_in_id == $profile_user_id): ?>
                <form class="bio-form" id="bio-form" method="POST">
                    <textarea name="bio" rows="3"><?php echo htmlspecialchars($profile_user['bio']); ?></textarea>
                    <button type="submit" class="btn btn-follow">Save</button>
                    <button type="button" class="btn btn-edit" onclick="toggleBioForm()">Cancel</button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <h2 style="margin-bottom: 20px; color: var(--neon-purple);">Tweets</h2>
        
        <div class="feed">
            <?php foreach ($tweets as $tweet): ?>
                <div class="tweet-post">
                    <div class="post-content"><?php echo nl2br(htmlspecialchars($tweet['content'])); ?></div>
                    <div class="post-footer">
                        <span><i class="far fa-calendar"></i> <?php echo date('M d, Y', strtotime($tweet['created_at'])); ?></span>
                        <span class="<?php echo $tweet['user_liked'] ? 'liked' : ''; ?>">
                            <i class="fa<?php echo $tweet['user_liked'] ? 's' : 'r'; ?> fa-heart"></i> <?php echo $tweet['like_count']; ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; if (empty($tweets)) echo "<p style='color: #888;'>No tweets yet.</p>"; ?>
        </div>
    </div>

    <script>
        function toggleBioForm() {
            const form = document.getElementById('bio-form');
            const bioText = document.getElementById('bio-text');
            if (form.style.display === 'block') {
                form.style.display = 'none';
                bioText.style.display = 'block';
            } else {
                form.style.display = 'block';
                bioText.style.display = 'none';
            }
        }
    </script>
</body>
</html>
