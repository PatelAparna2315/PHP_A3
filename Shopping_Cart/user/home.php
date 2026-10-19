
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../config/db.php";

$stmt = $pdo->query(
    "SELECT category_id, category_name
     FROM categories
     ORDER BY category_id ASC"
);
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>

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
            background: #1d4ed8;
            color: white;
            padding: 18px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        header h2 {
            margin: 0;
        }

        header a {
            color: white;
            text-decoration: none;
            background: #1e40af;
            padding: 10px 14px;
            border-radius: 5px;
        }

        main {
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 18px;
        }

        .welcome {
            margin-bottom: 25px;
        }

        .category-list {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin: 18px 0 30px;
        }

        .category-btn {
            padding: 12px 20px;
            border: 1px solid #2563eb;
            border-radius: 6px;
            background: white;
            color: #1d4ed8;
            cursor: pointer;
            font-size: 15px;
        }

        .category-btn.active,
        .category-btn:hover {
            background: #2563eb;
            color: white;
        }

        .products {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .product-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px #00000010;
        }

        .product-card img {
            width: 100%;
            height: 190px;
            object-fit: contain;
            background: white;
            padding: 12px;
        }

        .product-info {
            padding: 15px;
        }

        .product-info h3 {
            margin: 0 0 10px;
        }

        .price {
            color: #15803d;
            font-weight: bold;
            font-size: 18px;
        }

        .status {
            margin: 15px 0;
        }

        .pagination {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 8px;
            margin: 28px 0;
        }

        .page-btn {
            padding: 9px 14px;
            background: white;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            cursor: pointer;
        }

        .page-btn.active {
            background: #2563eb;
            color: white;
        }

        .page-btn:disabled {
            cursor: not-allowed;
            opacity: 0.5;
        }

        @media (max-width: 500px) {
            header {
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>

<header>
    <h2>Shopping Cart</h2>

    <div>
        Welcome,
        <?= htmlspecialchars($_SESSION["user_name"]) ?>
        &nbsp;
        <a href="logout.php">Logout</a>
    </div>
</header>

<main>
    <div class="welcome">
        <h1>Our Products</h1>
        <p>Select a category to explore our products.</p>
    </div>

    <h2>Categories</h2>

    <div class="category-list" id="categoryList">
        <?php foreach ($categories as $category): ?>
            <button
                type="button"
                class="category-btn"
                data-id="<?= (int) $category["category_id"] ?>"
            >
                <?= htmlspecialchars($category["category_name"]) ?>
            </button>
        <?php endforeach; ?>
    </div>

    <h2 id="selectedCategory">Products</h2>

    <p class="status" id="status" role="status">
        Select a category to view products.
    </p>

    <div class="products" id="products"></div>

    <div class="pagination" id="pagination"></div>
</main>

<script>
    const categoryList = document.getElementById("categoryList");
    const productsContainer = document.getElementById("products");
    const paginationContainer = document.getElementById("pagination");
    const statusElement = document.getElementById("status");
    const selectedCategory = document.getElementById("selectedCategory");

    let currentCategoryId = null;
    let currentPage = 1;

    categoryList.addEventListener("click", function (event) {
        const button = event.target.closest(".category-btn");

        if (!button) {
            return;
        }

        currentCategoryId = button.dataset.id;
        currentPage = 1;

        document.querySelectorAll(".category-btn").forEach(function (item) {
            item.classList.toggle("active", item === button);
        });

        loadProducts(currentCategoryId, currentPage);
    });

    async function loadProducts(categoryId, page) {
        productsContainer.replaceChildren();
        paginationContainer.replaceChildren();
        statusElement.textContent = "Loading products...";

        try {
            const response = await fetch(
                "../ajax/get_products.php?category_id=" +
                encodeURIComponent(categoryId) +
                "&page=" + encodeURIComponent(page),
                {
                    headers: {
                        "Accept": "application/json"
                    }
                }
            );

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || "Unable to load products.");
            }

            selectedCategory.textContent = data.category;

            if (data.products.length === 0) {
                statusElement.textContent =
                    "No products are available in this category.";
                return;
            }

            statusElement.textContent =
                "Showing " + data.products.length +
                " of " + data.total_products + " products.";

            data.products.forEach(function (product) {
                const card = document.createElement("article");
                card.className = "product-card";

                const image = document.createElement("img");
                image.src = "../uploads/" + encodeURIComponent(product.picture);
                image.alt = product.pname;
                image.loading = "lazy";

                image.addEventListener("error", function () {
                    image.alt = "Product image unavailable";
                    image.removeAttribute("src");
                }, { once: true });

                const info = document.createElement("div");
                info.className = "product-info";

                const name = document.createElement("h3");
                name.textContent = product.pname;

                const price = document.createElement("p");
                price.className = "price";
                price.textContent =
                    "₹" + Number(product.price).toLocaleString("en-IN", {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });

                info.append(name, price);
                card.append(image, info);
                productsContainer.appendChild(card);
            });

            renderPagination(data.total_pages, data.current_page);

        } catch (error) {
            statusElement.textContent = error.message;
        }
    }

    function renderPagination(totalPages, activePage) {
        paginationContainer.replaceChildren();

        if (totalPages <= 1) {
            return;
        }

        addPageButton("Previous", activePage - 1, activePage === 1);

        for (let page = 1; page <= totalPages; page++) {
            addPageButton(String(page), page, false, page === activePage);
        }

        addPageButton(
            "Next",
            activePage + 1,
            activePage === totalPages
        );
    }

    function addPageButton(label, page, disabled, active = false) {
        const button = document.createElement("button");
        button.type = "button";
        button.className = "page-btn";
        button.textContent = label;
        button.disabled = disabled;

        if (active) {
            button.classList.add("active");
            button.setAttribute("aria-current", "page");
        }

        button.addEventListener("click", function () {
            currentPage = page;
            loadProducts(currentCategoryId, currentPage);
        });

        paginationContainer.appendChild(button);
    }

    // Automatically load the first category when available.
    const firstCategory = categoryList.querySelector(".category-btn");

    if (firstCategory) {
        firstCategory.click();
    } else {
        statusElement.textContent = "No categories are available.";
    }
</script>

</body>
</html>
