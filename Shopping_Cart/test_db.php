
<?php
require_once "config/db.php";

echo "Database connected successfully!";

$stmt = $pdo->query("SELECT * FROM categories");

echo "<h3>Available Categories</h3>";

while ($row = $stmt->fetch()) {
    echo htmlspecialchars($row["category_name"]) . "<br>";
}
?>
