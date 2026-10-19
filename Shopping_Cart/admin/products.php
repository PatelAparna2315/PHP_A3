
<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

$message = "";
$error = "";

$uploadDir = __DIR__ . "/../uploads/";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Delete product
if (isset($_GET["delete"])) {
    $id = (int) $_GET["delete"];

    $stmt = $pdo->prepare(
        "SELECT picture FROM products WHERE product_id = ?"
    );
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    if ($product) {
        $stmt = $pdo->prepare(
            "DELETE FROM products WHERE product_id = ?"
        );
        $stmt->execute([$id]);

        $imagePath = $uploadDir . basename($product["picture"]);

        if (is_file($imagePath)) {
            unlink($imagePath);
        }
    }

    header("Location: products.php?status=deleted");
    exit;
}

if (isset($_GET["status"]) && $_GET["status"] === "deleted") {
    $message = "Product deleted successfully!";
}

// Fetch product for editing
$editProduct = null;

if (isset($_GET["edit"])) {
    $id = (int) $_GET["edit"];

    $stmt = $pdo->prepare(
        "SELECT * FROM products WHERE product_id = ?"
    );
    $stmt->execute([$id]);
    $editProduct = $stmt->fetch();
}

// Add / Update product
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = (int) ($_POST["product_id"] ?? 0);
    $categoryId = (int) ($_POST["category_id"] ?? 0);
    $name = trim($_POST["pname"] ?? "");
    $price = $_POST["price"] ?? "";

    $oldProduct = null;

    if ($id > 0) {
        $stmt = $pdo->prepare(
            "SELECT * FROM products WHERE product_id = ?"
        );
        $stmt->execute([$id]);
        $oldProduct = $stmt->fetch();
    }

    if (
        $name === "" ||
        strlen($name) > 150 ||
        !is_numeric($price) ||
        (float)$price < 0 ||
        !is_finite((float)$price) ||
        (float)$price > 99999999.99
    ) {
        $error = "Enter a valid product name and price.";
    } elseif ($categoryId <= 0) {
        $error = "Please select a category.";
    } else {
        $stmt = $pdo->prepare(
            "SELECT category_id FROM categories WHERE category_id = ?"
        );
        $stmt->execute([$categoryId]);

        if (!$stmt->fetch()) {
            $error = "Selected category does not exist.";
        }
    }

    $picture = $oldProduct["picture"] ?? "";

    // Process uploaded image
    if (
        $error === "" &&
        isset($_FILES["picture"]) &&
        $_FILES["picture"]["error"] !== UPLOAD_ERR_NO_FILE
    ) {
        $file = $_FILES["picture"];

        if ($file["error"] !== UPLOAD_ERR_OK) {
            $error = "Image upload failed.";
        } elseif ($file["size"] > 5 * 1024 * 1024) {
            $error = "Image must be 5 MB or smaller.";
        } else {
            $imageInfo = @getimagesize($file["tmp_name"]);

            $allowedTypes = [
                IMAGETYPE_JPEG => "jpg",
                IMAGETYPE_PNG  => "png",
                IMAGETYPE_WEBP => "webp"
            ];

            if (
                $imageInfo === false ||
                !isset($allowedTypes[$imageInfo[2]])
            ) {
                $error = "Upload a valid JPG, PNG or WebP image.";
            } elseif (!extension_loaded("gd")) {
                $error = "Enable the GD extension in WAMP.";
            } else {
                $type = $imageInfo[2];

                switch ($type) {
                    case IMAGETYPE_JPEG:
                        $source = @imagecreatefromjpeg($file["tmp_name"]);
                        break;
                    case IMAGETYPE_PNG:
                        $source = @imagecreatefrompng($file["tmp_name"]);
                        break;
                    case IMAGETYPE_WEBP:
                        $source = @imagecreatefromwebp($file["tmp_name"]);
                        break;
                    default:
                        $source = false;
                }

                if ($source === false) {
                    $error = "Unable to process this image.";
                } else {
                    $width = imagesx($source);
                    $height = imagesy($source);

                    // Keep aspect ratio; fit inside 600 x 600
                    $scale = min(1, 600 / $width, 600 / $height);
                    $newWidth = max(1, (int) round($width * $scale));
                    $newHeight = max(1, (int) round($height * $scale));

                    $resized = imagecreatetruecolor(
                        $newWidth,
                        $newHeight
                    );

                    if ($type === IMAGETYPE_PNG ||
                        $type === IMAGETYPE_WEBP) {
                        imagealphablending($resized, false);
                        imagesavealpha($resized, true);
                        $transparent = imagecolorallocatealpha(
                            $resized, 0, 0, 0, 127
                        );
                        imagefilledrectangle(
                            $resized, 0, 0,
                            $newWidth, $newHeight, $transparent
                        );
                    }

                    imagecopyresampled(
                        $resized, $source,
                        0, 0, 0, 0,
                        $newWidth, $newHeight,
                        $width, $height
                    );

                    $extension = $allowedTypes[$type];
                    $filename = bin2hex(random_bytes(16))
                        . "." . $extension;
                    $destination = $uploadDir . $filename;

                    switch ($type) {
                        case IMAGETYPE_JPEG:
                            $saved = imagejpeg(
                                $resized, $destination, 85
                            );
                            break;
                        case IMAGETYPE_PNG:
                            $saved = imagepng(
                                $resized, $destination, 6
                            );
                            break;
                        case IMAGETYPE_WEBP:
                            $saved = imagewebp(
                                $resized, $destination, 85
                            );
                            break;
                        default:
                            $saved = false;
                    }

                    imagedestroy($source);
                    imagedestroy($resized);

                    if ($saved) {
                        $picture = $filename;
                    } else {
                        $error = "Unable to save resized image.";
                    }
                }
            }
        }
    }

    if ($error === "" && $picture === "") {
        $error = "Please select a product picture.";
    }

    if ($error === "") {
        try {
            if ($id > 0 && $oldProduct) {
                $stmt = $pdo->prepare(
                    "UPDATE products
                     SET category_id = ?, pname = ?, price = ?, picture = ?
                     WHERE product_id = ?"
                );

                $stmt->execute([
                    $categoryId, $name, $price, $picture, $id
                ]);

                if ($picture !== $oldProduct["picture"]) {
                    $oldPath = $uploadDir
                        . basename($oldProduct["picture"]);

                    if (is_file($oldPath)) {
                        unlink($oldPath);
                    }
                }

                header("Location: products.php?status=updated");
                exit;
            } elseif ($id > 0) {
                $error = "Product not found.";
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO products
                     (category_id, pname, price, picture)
                     VALUES (?, ?, ?, ?)"
                );

                $stmt->execute([
                    $categoryId, $name, $price, $picture
                ]);

                header("Location: products.php?status=added");
                exit;
            }
        } catch (PDOException $e) {
            $error = "Unable to save product.";
        }
    }
}

if (isset($_GET["status"])) {
    if ($_GET["status"] === "added") {
        $message = "Product added successfully!";
    } elseif ($_GET["status"] === "updated") {
        $message = "Product updated successfully!";
    }
}

// Load categories
$categories = $pdo->query(
    "SELECT category_id, category_name
     FROM categories ORDER BY category_name"
)->fetchAll();

// Load products
$products = $pdo->query(
    "SELECT p.*, c.category_name
     FROM products p
     JOIN categories c ON p.category_id = c.category_id
     ORDER BY p.product_id DESC"
)->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>

    <style>
        * { box-sizing: border-box; }

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
            flex-wrap: wrap;
            gap: 12px;
        }

        header h2 { margin: 0; }

        header a {
            color: white;
            text-decoration: none;
            margin-left: 12px;
        }

        .container {
            max-width: 1100px;
            margin: 25px auto;
            padding: 15px;
        }

        .panel {
            background: white;
            padding: 22px;
            border-radius: 10px;
            margin-bottom: 24px;
            box-shadow: 0 3px 12px #0000000d;
        }

        label {
            display: block;
            margin: 14px 0 6px;
            font-weight: bold;
        }

        input, select {
            width: 100%;
            padding: 11px;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
        }

        button, .btn {
            display: inline-block;
            border: 0;
            border-radius: 5px;
            padding: 9px 13px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            cursor: pointer;
        }

        button { margin-top: 18px; }

        .edit { background: #d97706; }
        .delete { background: #dc2626; }
        .cancel { background: #64748b; }

        .success {
            background: #dcfce7;
            color: #166534;
            padding: 12px;
            margin-bottom: 15px;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            margin-bottom: 15px;
        }

        .table-wrap { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th { background: #eff6ff; }

        .product-image {
            width: 75px;
            height: 75px;
            object-fit: contain;
            background: white;
        }

        .actions {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>

<header>
    <h2>Shopping Cart Admin</h2>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="categories.php">Categories</a>
        <a href="products.php">Products</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<div class="container">
    <h1>Manage Products</h1>

    <?php if ($message !== ""): ?>
        <div class="success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="panel">
        <h3><?= $editProduct ? "Edit Product" : "Add New Product" ?></h3>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="product_id"
                value="<?= (int)($editProduct["product_id"] ?? 0) ?>">

            <label for="category_id">Category</label>
            <select id="category_id" name="category_id" required>
                <option value="">Select Category</option>
                <?php foreach ($categories as $category): ?>
                    <option
                        value="<?= (int)$category["category_id"] ?>"
                        <?= (int)($editProduct["category_id"] ?? 0)
                            === (int)$category["category_id"]
                            ? "selected" : "" ?>>
                        <?= htmlspecialchars($category["category_name"]) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="pname">Product Name</label>
            <input type="text" id="pname" name="pname"
                maxlength="150" required
                value="<?= htmlspecialchars($editProduct["pname"] ?? "") ?>">

            <label for="price">Price</label>
            <input type="number" id="price" name="price"
                min="0" max="99999999.99" step="0.01" required
                value="<?= htmlspecialchars((string)($editProduct["price"] ?? "")) ?>">

            <label for="picture">
                Product Picture
                <?= $editProduct ? "(Optional if keeping existing image)" : "" ?>
            </label>
            <input type="file" id="picture" name="picture"
                accept="image/jpeg,image/png,image/webp"
                <?= $editProduct ? "" : "required" ?>>

            <?php if ($editProduct): ?>
                <p>Current image:</p>
                <img class="product-image"
                    src="../uploads/<?= rawurlencode(basename($editProduct["picture"])) ?>"
                    alt="Current product image">
            <?php endif; ?>

            <br>
            <button type="submit">
                <?= $editProduct ? "Update Product" : "Add Product" ?>
            </button>

            <?php if ($editProduct): ?>
                <a class="btn cancel" href="products.php">Cancel</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="panel">
        <h3>All Products</h3>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Picture</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($products): ?>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?= (int)$product["product_id"] ?></td>
                            <td>
                                <img class="product-image"
                                    src="../uploads/<?= rawurlencode(basename($product["picture"])) ?>"
                                    alt="<?= htmlspecialchars($product["pname"]) ?>">
                            </td>
                            <td><?= htmlspecialchars($product["pname"]) ?></td>
                            <td><?= htmlspecialchars($product["category_name"]) ?></td>
                            <td>₹<?= number_format((float)$product["price"], 2) ?></td>
                            <td>
                                <div class="actions">
                                    <a class="btn edit"
                                        href="products.php?edit=<?= (int)$product["product_id"] ?>">
                                        Edit
                                    </a>
                                    <a class="btn delete"
                                        href="products.php?delete=<?= (int)$product["product_id"] ?>"
                                        onclick="return confirm('Delete this product?')">
                                        Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">No products found.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
