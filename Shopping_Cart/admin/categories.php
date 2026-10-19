
<?php
session_start();
require_once "../config/db.php";

// Check admin login
if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

$message = "";
$error = "";

// Add or Update Category
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $categoryName = trim($_POST["category_name"] ?? "");
    $categoryId = (int)($_POST["category_id"] ?? 0);

    if ($categoryName === "") {
        $error = "Please enter a category name.";
    } else {
        try {
            if ($categoryId > 0) {
                // Update category
                $stmt = $pdo->prepare(
                    "UPDATE categories
                     SET category_name = ?
                     WHERE category_id = ?"
                );

                $stmt->execute([$categoryName, $categoryId]);

                $message = "Category updated successfully!";
            } else {
                // Insert new category
                $stmt = $pdo->prepare(
                    "INSERT INTO categories (category_name)
                     VALUES (?)"
                );

                $stmt->execute([$categoryName]);

                $message = "Category added successfully!";
            }
        } catch (PDOException $e) {
            if ($e->getCode() === "23000") {
                $error = "This category already exists.";
            } else {
                $error = "Unable to save category.";
            }
        }
    }
}

// Delete category
if (isset($_GET["delete"])) {
    $deleteId = (int)$_GET["delete"];

    if ($deleteId > 0) {
        try {
            $stmt = $pdo->prepare(
                "DELETE FROM categories WHERE category_id = ?"
            );

            $stmt->execute([$deleteId]);

            header("Location: categories.php?status=deleted");
            exit;
        } catch (PDOException $e) {
            $error = "Unable to delete category.";
        }
    }
}

if (isset($_GET["status"]) && $_GET["status"] === "deleted") {
    $message = "Category deleted successfully!";
}

// Load category for editing
$editCategory = null;

if (isset($_GET["edit"])) {
    $editId = (int)$_GET["edit"];

    $stmt = $pdo->prepare(
        "SELECT * FROM categories WHERE category_id = ?"
    );

    $stmt->execute([$editId]);
    $editCategory = $stmt->fetch();
}

// Fetch all categories
$stmt = $pdo->query(
    "SELECT * FROM categories ORDER BY category_id DESC"
);

$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Categories</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
        }

        header {
            background: #1e3a8a;
            color: white;
            padding: 18px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        header h2 {
            margin: 0;
        }

        header a {
            color: white;
            text-decoration: none;
            margin-left: 15px;
        }

        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 20px;
        }

        .panel {
            background: white;
            padding: 25px;
            margin-bottom: 25px;
            border-radius: 10px;
            box-shadow: 0 3px 12px #0000000d;
        }

        h1, h3 {
            margin-top: 0;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        button, .btn {
            border: none;
            border-radius: 5px;
            padding: 10px 15px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            cursor: pointer;
            display: inline-block;
        }

        .cancel {
            background: #64748b;
        }

        .edit {
            background: #d97706;
        }

        .delete {
            background: #dc2626;
        }

        .success {
            background: #dcfce7;
            color: #166534;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 13px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            background: #eff6ff;
        }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        @media (max-width: 600px) {
            .container {
                padding: 12px;
            }

            .panel {
                padding: 16px;
            }
        }
    </style>
</head>

<body>

<header>
    <h2>Shopping Cart Admin</h2>

    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="categories.php">Categories</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<div class="container">

    <h1>Manage Categories</h1>
    <p>Add, edit, view and delete product categories.</p>

    <?php if ($message !== ""): ?>
        <div class="success">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Add / Edit Form -->
    <div class="panel">

        <h3>
            <?= $editCategory ? "Edit Category" : "Add New Category" ?>
        </h3>

        <form method="POST" action="categories.php">

            <input
                type="hidden"
                name="category_id"
                value="<?= (int)($editCategory["category_id"] ?? 0) ?>"
            >

            <label for="category_name">Category Name</label>

            <input
                type="text"
                id="category_name"
                name="category_name"
                placeholder="Enter category name"
                maxlength="100"
                value="<?= htmlspecialchars(
                    $editCategory["category_name"] ?? ""
                ) ?>"
                required
            >

            <button type="submit">
                <?= $editCategory ? "Update Category" : "Add Category" ?>
            </button>

            <?php if ($editCategory): ?>
                <a class="btn cancel" href="categories.php">
                    Cancel
                </a>
            <?php endif; ?>

        </form>

    </div>

    <!-- Category List -->
    <div class="panel">

        <h3>All Categories</h3>

        <div class="table-wrap">

            <table>
                <thead>
                    <tr>
                        <th>Category ID</th>
                        <th>Category Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (count($categories) > 0): ?>

                    <?php foreach ($categories as $category): ?>

                        <tr>
                            <td>
                                <?= (int)$category["category_id"] ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $category["category_name"]
                                ) ?>
                            </td>

                            <td>
                                <div class="actions">

                                    <a
                                        class="btn edit"
                                        href="categories.php?edit=<?= (int)$category["category_id"] ?>"
                                    >
                                        Edit
                                    </a>

                                    <a
                                        class="btn delete"
                                        href="categories.php?delete=<?= (int)$category["category_id"] ?>"
                                        onclick="return confirm('Are you sure you want to delete this category? Its products will also be deleted.');"
                                    >
                                        Delete
                                    </a>

                                </div>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="3">No categories found.</td>
                    </tr>

                <?php endif; ?>

                </tbody>
            </table>

        </div>
    </div>

</div>

</body>
</html>
