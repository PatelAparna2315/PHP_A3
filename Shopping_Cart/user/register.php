
<?php
session_start();
require_once "../config/db.php";

$error = "";
$success = "";

if (!isset($_SESSION["captcha_answer"])) {
    $num1 = random_int(1, 9);
    $num2 = random_int(1, 9);

    $_SESSION["captcha_answer"] = $num1 + $num2;
    $_SESSION["captcha_question"] = "$num1 + $num2";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";
    $captcha = trim($_POST["captcha"] ?? "");

    $expectedCaptcha = (string) ($_SESSION["captcha_answer"] ?? "");

    if (
        $name === "" ||
        $email === "" ||
        $password === "" ||
        $confirmPassword === "" ||
        $captcha === ""
    ) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($captcha !== $expectedCaptcha) {
        $error = "Incorrect CAPTCHA answer.";
    } else {
        try {
            $check = $pdo->prepare(
                "SELECT user_id FROM users WHERE email = ?"
            );
            $check->execute([$email]);

            if ($check->fetch()) {
                $error = "This email address is already registered.";
            } else {
                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $insert = $pdo->prepare(
                    "INSERT INTO users (name, email, password)
                     VALUES (?, ?, ?)"
                );

                $insert->execute([
                    $name,
                    $email,
                    $hashedPassword
                ]);

                unset(
                    $_SESSION["captcha_answer"],
                    $_SESSION["captcha_question"]
                );

                header("Location: login.php?registered=1");
                exit;
            }
        } catch (PDOException $e) {
            $error = "Registration failed. Please try again.";
        }
    }

    // Generate a new CAPTCHA after a failed submission.
    $num1 = random_int(1, 9);
    $num2 = random_int(1, 9);

    $_SESSION["captcha_answer"] = $num1 + $num2;
    $_SESSION["captcha_question"] = "$num1 + $num2";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            margin: 0;
            padding: 30px 15px;
        }
        .container {
            max-width: 430px;
            margin: 20px auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 3px 15px #00000015;
        }
        h2 { text-align: center; color: #1d4ed8; }
        label { display: block; margin-top: 14px; }
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
        .message { padding: 10px; border-radius: 5px; }
        .error { background: #fee2e2; color: #991b1b; }
        .links { text-align: center; margin-top: 18px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Create Account</h2>

        <?php if ($error !== ""): ?>
            <p class="message error">
                <?= htmlspecialchars($error) ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="">
            <label for="name">Full Name</label>
            <input
                type="text"
                id="name"
                name="name"
                value="<?= htmlspecialchars($_POST["name"] ?? "") ?>"
                required
            >

            <label for="email">Email Address</label>
            <input
                type="email"
                id="email"
                name="email"
                value="<?= htmlspecialchars($_POST["email"] ?? "") ?>"
                required
            >

            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                minlength="6"
                required
            >

            <label for="confirm_password">Confirm Password</label>
            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                minlength="6"
                required
            >

            <label for="captcha">
                CAPTCHA: What is
                <?= htmlspecialchars($_SESSION["captcha_question"]) ?>?
            </label>
            <input
                type="number"
                id="captcha"
                name="captcha"
                required
            >

            <button type="submit">Register</button>
        </form>

        <div class="links">
            Already have an account?
            <a href="login.php">Login Here</a>
        </div>
    </div>
</body>
</html>
