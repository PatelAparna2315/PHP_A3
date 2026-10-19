
<?php
session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f1f5f9;
        }

        header {
            background: #1e3a8a;
            color: white;
            padding: 18px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        header h2 { margin: 0; }

        .logout {
            color: white;
            background: #dc2626;
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 5px;
        }

        main { padding: 25px; }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 3px 12px #0000000d;
        }

        .card h3 { color: #1e40af; }

        .card a {
            display: inline-block;
            background: #2563eb;
            color: white;
            text-decoration: none;
            padding: 10px 14px;
            border-radius: 5px;
        }
    </style>
</head>
<body>

<header>
    <h2>Shopping Cart Admin</h2>
    <div>
        Welcome,
        <?= htmlspecialchars($_SESSION["admin_username"]) ?>
        &nbsp;
        <a class="logout" href="logout.php">Logout</a>
    </div>
</header>

<main>
    <h1>Admin Dashboard</h1>
    <p>Manage your shopping website from here.</p>

    <div class="cards">
        <div class="card">
            <h3>Manage Categories</h3>
            <p>Add, edit and delete product categories.</p>
            <a href="categories.php">Manage Categories</a>
        </div>

        <div class="card">
            <h3>Manage Products</h3>
            <p>Add products, prices and pictures.</p>
            <a href="products.php">Manage Products</a>
        </div>
    </div>
</main>

</body>
</html>
