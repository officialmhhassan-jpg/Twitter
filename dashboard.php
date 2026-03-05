<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get counts
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tweets WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_tweets = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE following_id = ?");
$stmt->execute([$user_id]);
$followers = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ?");
$stmt->execute([$user_id]);
$following = $stmt->fetchColumn();

// Total likes received
$stmt = $pdo->prepare("SELECT COUNT(*) FROM likes l JOIN tweets t ON l.tweet_id = t.id WHERE t.user_id = ?");
$stmt->execute([$user_id]);
$total_likes = $stmt->fetchColumn();

// Engagement rate (simplified)
$engagement = $total_tweets > 0 ? round(($total_likes / $total_tweets), 2) : 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard / NeonTweet</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bg: #050505;
            --card-bg: rgba(20, 20, 20, 0.7);
            --neon-blue: #00f2ff;
            --neon-purple: #bc13fe;
            --text: #ffffff;
            --glass: rgba(255, 255, 255, 0.05);
            --sidebar-width: 280px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background-color: var(--bg); color: var(--text); min-height: 100vh; display: flex; 
               background: radial-gradient(circle at 50% 50%, #111 0%, #050505 100%);
        }

        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            left: 0; top: 0;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(25px);
            border-right: 1px solid var(--glass);
            padding: 40px 25px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            z-index: 100;
        }

        .logo { font-size: 2rem; font-weight: 800; color: var(--neon-blue); text-shadow: 0 0 15px var(--neon-blue); margin-bottom: 40px; text-decoration: none; }
        .nav-item { display: flex; align-items: center; gap: 18px; padding: 14px 22px; text-decoration: none; color: #888; border-radius: 15px; transition: 0.4s; font-size: 1.1rem; }
        .nav-item:hover, .nav-item.active { color: #fff; background: var(--glass); }
        .nav-item.active { color: var(--neon-blue); border: 1px solid rgba(0, 242, 255, 0.2); }

        .main-content { margin-left: var(--sidebar-width); flex: 1; padding: 50px; width: 100%; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 25px;
            margin-top: 40px;
        }

        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--glass);
            border-radius: 24px;
            padding: 30px;
            text-align: center;
            transition: 0.4s;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .stat-card:hover {
            transform: translateY(-10px);
            border-color: var(--neon-blue);
            box-shadow: 0 10px 30px rgba(0, 242, 255, 0.1);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 4px;
            background: linear-gradient(90deg, var(--neon-blue), var(--neon-purple));
        }

        .stat-value { font-size: 2.5rem; font-weight: 800; margin-bottom: 10px; color: #fff; }
        .stat-label { color: #888; font-size: 1rem; text-transform: uppercase; letter-spacing: 1px; }

        .chart-placeholder {
            margin-top: 50px;
            background: var(--card-bg);
            border: 1px solid var(--glass);
            border-radius: 24px;
            padding: 40px;
            height: 300px;
            display: flex;
            align-items: flex-end;
            gap: 15px;
            justify-content: center;
            position: relative;
        }

        .chart-bar {
            width: 40px;
            background: linear-gradient(to top, var(--neon-purple), var(--neon-blue));
            border-radius: 8px 8px 0 0;
            transition: 1s ease-out;
            animation: grow 1.5s ease-out;
        }

        @keyframes grow { from { height: 0; } }

        .chart-title { position: absolute; top: 20px; left: 30px; font-weight: 600; color: #fff; font-size: 1.2rem; }

        @media (max-width: 768px) {
            .sidebar { width: 80px; padding: 30px 10px; align-items: center; }
            .logo, .nav-item span { display: none; }
            .main-content { margin-left: 80px; padding: 25px; }
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <a href="home.php" class="logo">NT</a>
        <a href="home.php" class="nav-item"><i class="fas fa-home"></i> <span>Home</span></a>
        <a href="dashboard.php" class="nav-item active"><i class="fas fa-chart-line"></i> <span>Dashboard</span></a>
        <a href="notifications.php" class="nav-item"><i class="fas fa-bell"></i> <span>Notifications</span></a>
        <a href="profile.php" class="nav-item"><i class="fas fa-user"></i> <span>Profile</span></a>
        <a href="logout.php" class="nav-item" style="margin-top: auto;"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </div>

    <div class="main-content">
        <h1>Overview Dashboard</h1>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $total_tweets; ?></div>
                <div class="stat-label">Tweets</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $followers; ?></div>
                <div class="stat-label">Followers</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $total_likes; ?></div>
                <div class="stat-label">Likes</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $engagement; ?></div>
                <div class="stat-label">Engagement</div>
            </div>
        </div>

        <div class="chart-placeholder">
            <div class="chart-title">Activity Growth</div>
            <div class="chart-bar" style="height: 40%;"></div>
            <div class="chart-bar" style="height: 60%;"></div>
            <div class="chart-bar" style="height: 35%;"></div>
            <div class="chart-bar" style="height: 85%;"></div>
            <div class="chart-bar" style="height: 50%;"></div>
            <div class="chart-bar" style="height: 90%;"></div>
            <div class="chart-bar" style="height: 70%;"></div>
        </div>
    </div>

</body>
</html>
