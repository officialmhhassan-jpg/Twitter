<?php
session_start();
require "db.php";

$error = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $input = trim($_POST["email"]);
    $password = $_POST["password"];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$input, $input]);
    $user = $stmt->fetch();

    if($user && password_verify($password, $user["password"])){
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["username"] = $user["username"];
        header("Location: home.php");
        exit;
    } else {
        $error = "Invalid credentials.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Login</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="auth-container">
    <div class="glass-card fade-in">
        <h2>Login</h2>

        <?php if($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="email" placeholder="Email or Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" class="btn primary">Login</button>
        </form>

        <p>Don't have an account? <a href="signup.php">Signup</a></p>
    </div>
</div>

</body>
</html>
