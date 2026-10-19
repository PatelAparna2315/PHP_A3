
<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["error" => "Please log in first."]);
    exit;
}

require_once "../config/db.php";

$categoryId = filter_input(INPUT_GET, "category_id", FILTER_VALIDATE_INT);
$page = filter_input(INPUT_GET, "page", FILTER_VALIDATE_INT);

if (!$categoryId || $categoryId < 1) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid category."]);
    exit;
}

$page = (!$page || $page < 1) ? 1 : $page;

$limit = 6;
$offset = ($page - 1) * $limit;

try {
    // Check whether the selected category exists.
    $categoryStmt = $pdo->prepare(
        "SELECT category_id, category_name
         FROM categories
         WHERE category_id = ?"
    );
    $categoryStmt->execute([$categoryId]);
    $category = $categoryStmt->fetch();

    if (!$category) {
        http_response_code(404);
        echo json_encode(["error" => "Category not found."]);
        exit;
    }

    // Count products in the selected category.
    $countStmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM products
         WHERE category_id = ?"
    );
    $countStmt->execute([$categoryId]);

    $totalProducts = (int) $countStmt->fetchColumn();
    $totalPages = (int) ceil($totalProducts / $limit);

    // Fetch products for the requested page.
    $productStmt = $pdo->prepare(
        "SELECT product_id, pname, price, picture
         FROM products
         WHERE category_id = ?
         ORDER BY product_id DESC
         LIMIT $limit OFFSET $offset"
    );
    $productStmt->execute([$categoryId]);

    $products = $productStmt->fetchAll();

    echo json_encode([
        "category" => $category["category_name"],
        "products" => $products,
        "current_page" => $page,
        "total_pages" => $totalPages,
        "total_products" => $totalProducts
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Unable to load products."]);
}
?>
