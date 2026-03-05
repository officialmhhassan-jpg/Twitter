<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Mark all as read
$stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
$stmt->execute([$user_id]);

// Fetch notifications
$query = "SELECT n.*, u.username as actor_name, t.content as tweet_preview
          FROM notifications n
          JOIN users u ON n.actor_id = u.id
          LEFT JOIN tweets t ON n.tweet_id = t.id
          WHERE n.user_id = ?
          ORDER BY n.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications / NeonTweet</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&family=Inter:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bg: #050505;
            --card-bg: rgba(20, 20, 20, 0.6);
            --neon-blue: #00f2ff;
            --neon-purple: #bc13fe;
            --text: #ffffff;
            --glass: rgba(255, 255, 255, 0.05);
            --sidebar-width: 280px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background-color: var(--bg); color: var(--text); min-height: 100vh; display: flex; 
               background-image: radial-gradient(circle at 0% 0%, rgba(188, 19, 254, 0.1) 0%, transparent 50%),
                                 radial-gradient(circle at 100% 100%, rgba(0, 242, 255, 0.1) 0%, transparent 50%);
        }

        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            left: 0; top: 0;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(20px);
            border-right: 1px solid var(--glass);
            padding: 40px 25px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            z-index: 100;
        }

        .logo { font-size: 2rem; font-weight: 800; color: var(--neon-blue); text-shadow: 0 0 15px var(--neon-blue); margin-bottom: 40px; text-decoration: none; display: flex; align-items: center; gap: 10px; }
        .nav-item { display: flex; align-items: center; gap: 18px; padding: 14px 22px; text-decoration: none; color: #999; border-radius: 15px; transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); font-size: 1.1rem; font-weight: 500; }
        .nav-item i { font-size: 1.4rem; }
        .nav-item:hover, .nav-item.active { color: #fff; background: var(--glass); box-shadow: 0 4px 15px rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); }
        .nav-item.active { color: var(--neon-blue); border-color: rgba(0, 242, 255, 0.3); }

        .main-content { margin-left: var(--sidebar-width); flex: 1; padding: 40px; max-width: 800px; width: 100%; }

        .page-header { margin-bottom: 30px; }
        .page-header h1 { font-size: 2.2rem; background: linear-gradient(90deg, #fff, #888); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }

        .notification-list { display: flex; flex-direction: column; gap: 15px; }
        .notification-item {
            background: var(--card-bg);
            border: 1px solid var(--glass);
            border-radius: 18px;
            padding: 20px;
            display: flex;
            gap: 20px;
            align-items: center;
            transition: 0.3s;
            backdrop-filter: blur(10px);
        }

        .notification-item:hover {
            transform: translateX(5px);
            border-color: rgba(255, 255, 255, 0.2);
            background: rgba(30, 30, 30, 0.8);
        }

        .icon-box {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .icon-like { background: rgba(255, 0, 255, 0.1); color: var(--neon-purple); }
        .icon-follow { background: rgba(0, 242, 255, 0.1); color: var(--neon-blue); }

        .notif-content { flex: 1; }
        .notif-title { font-weight: 600; margin-bottom: 5px; color: #fff; }
        .notif-title span { color: var(--neon-blue); cursor: pointer; }
        .notif-time { font-size: 0.85rem; color: #666; }
        .tweet-snippet { font-size: 0.95rem; color: #888; margin-top: 8px; font-style: italic; border-left: 2px solid var(--neon-purple); padding-left: 10px; }

        @media (max-width: 768px) {
            .sidebar { width: 80px; padding: 30px 10px; align-items: center; }
            .logo span, .nav-item span { display: none; }
            .main-content { margin-left: 80px; padding: 20px; }
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <a href="home.php" class="logo">NT<span>.</span></a>
        <a href="home.php" class="nav-item"><i class="fas fa-home"></i> <span>Home</span></a>
        <a href="dashboard.php" class="nav-item"><i class="fas fa-chart-line"></i> <span>Dashboard</span></a>
        <a href="notifications.php" class="nav-item active"><i class="fas fa-bell"></i> <span>Notifications</span></a>
        <a href="profile.php" class="nav-item"><i class="fas fa-user"></i> <span>Profile</span></a>
        <a href="logout.php" class="nav-item" style="margin-top: auto;"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </div>

    <div class="main-content">
        <div class="page-header">
            <h1>Notifications</h1>
        </div>

        <div class="notification-list">
            <?php foreach ($notifications as $n): ?>
                <div class="notification-item">
                    <div class="icon-box <?php echo $n['type'] == 'like' ? 'icon-like' : 'icon-follow'; ?>">
                        <i class="fas <?php echo $n['type'] == 'like' ? 'fa-heart' : 'fa-user-plus'; ?>"></i>
                    </div>
                    <div class="notif-content">
                        <p class="notif-title">
                            <span>@<?php echo htmlspecialchars($n['actor_name']); ?></span> 
                            <?php echo $n['type'] == 'like' ? 'liked your tweet' : 'started following you'; ?>
                        </p>
                        <p class="notif-time"><?php echo date('M d, H:i', strtotime($n['created_at'])); ?></p>
                        <?php if ($n['type'] == 'like' && $n['tweet_preview']): ?>
                            <p class="tweet-snippet">"<?php echo htmlspecialchars(substr($n['tweet_preview'], 0, 50)) . '...'; ?>"</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; if (empty($notifications)) echo "<p style='color: #666; text-align: center; margin-top: 50px;'>No notifications yet.</p>"; ?>
        </div>
    </div>

</body>
</html>
