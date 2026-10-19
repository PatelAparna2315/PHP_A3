
<?php
session_start();
require_once "../config/db.php";

if (isset($_SESSION["user_id"])) {
    header("Location: home.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $error = "Please enter your email and password.";
    } else {
        $stmt = $pdo->prepare(
            "SELECT user_id, name, email, password
             FROM users
             WHERE email = ?"
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user["password"])) {
            session_regenerate_id(true);

            $_SESSION["user_id"] = $user["user_id"];
            $_SESSION["user_name"] = $user["name"];
            $_SESSION["user_email"] = $user["email"];

            header("Location: home.php");
            exit;
        } else {
            $error = "Invalid email address or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            margin: 0;
            padding: 30px 15px;
        }
        .container {
            max-width: 400px;
            margin: 40px auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 3px 15px #00000015;
        }
        h2 { text-align: center; color: #1d4ed8; }
        label { display: block; margin-top: 16px; }
        input {
            box-sizing: border-box;
            width: 100%;
            padding: 11px;
            margin-top: 6px;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
        }
        button {
            width: 100%;
            margin-top: 20px;
            padding: 12px;
            background: #2563eb;
            color: white;
            border: 0;
            border-radius: 5px;
            cursor: pointer;
        }
        .error { color: #991b1b; background: #fee2e2; padding: 10px; }
        .success { color: #166534; background: #dcfce7; padding: 10px; }
        .links { text-align: center; margin-top: 18px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>User Login</h2>

        <?php if (isset($_GET["registered"])): ?>
            <p class="success">Registration successful. Please log in.</p>
        <?php endif; ?>

        <?php if ($error !== ""): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Login</button>
        </form>

        <div class="links">
            Don't have an account?
            <a href="register.php">Register Here</a>
        </div>
    </div>
</body>
</html>
