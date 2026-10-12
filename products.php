
<?php
$conn = new mysqli("localhost", "root", "", "mystore_db");

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$sql = "SELECT products.*, categories.name AS category_name
        FROM products
        LEFT JOIN categories
        ON products.category_id = categories.id";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Store - Products</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 30px;
            background: #f4f6f8;
        }

        h1 {
            text-align: center;
            color: #222;
        }

        .products {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            max-width: 1000px;
            margin: 30px auto;
        }

        .product {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px #ddd;
        }

        .product h2 {
            font-size: 20px;
        }

        .price {
            color: #16803c;
            font-size: 22px;
            font-weight: bold;
        }
    </style>
</head>
<body>

<h1>Our Products</h1>

<div class="products">
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($product = $result->fetch_assoc()): ?>
            <div class="product">
                <h2><?= htmlspecialchars($product['name']) ?></h2>
                <p><?= htmlspecialchars($product['description']) ?></p>
                <p class="price">₹<?= number_format((float)$product['price'], 2) ?></p>
                <p>Stock: <?= (int)$product['stock'] ?></p>
                <p>Category: <?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></p>
                <a href="cart.php?add=<?= (int)$product['id'] ?>">
    <button type="button">Add to Cart</button>
</a>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No products found.</p>
    <?php endif; ?>
</div>

</body>
</html>

<?php
$conn->close();
?>
