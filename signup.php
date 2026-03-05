<?php
session_start();
require "db.php";

$error = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $name = trim($_POST["name"]);
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if(empty($name) || empty($username) || empty($email) || empty($password)){
        $error = "All fields are required.";
    } else {

        $hashed = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO users (name, username, email, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $username, $email, $hashed]);

            header("Location: login.php");
            exit;

        } catch(PDOException $e){
            $error = "Username or Email already exists.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Signup</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="auth-container">
    <div class="glass-card fade-in">
        <h2>Create Account</h2>

        <?php if($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="text" name="username" placeholder="Username" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" class="btn primary">Signup</button>
        </form>

        <p>Already have an account? <a href="login.php">Login</a></p>
    </div>
</div>

</body>
</html>
