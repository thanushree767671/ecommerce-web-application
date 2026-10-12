```php
<?php
session_start();

$conn = new mysqli("localhost", "root", "", "mystore_db");

if ($conn->connect_error) {
    die("Database connection failed.");
}

$conn->set_charset("utf8mb4");

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// REMOVE PRODUCT FROM CART
if (isset($_GET['remove'])) {
    $id = (int) $_GET['remove'];

    unset($_SESSION['cart'][$id]);

    header("Location: cart.php");
    exit;
}

// UPDATE PRODUCT QUANTITY
if (isset($_GET['update'], $_GET['id'])) {
    $id = (int) $_GET['id'];
    $quantity = (int) $_GET['update'];

    if (isset($_SESSION['cart'][$id])) {
        if ($quantity <= 0) {
            unset($_SESSION['cart'][$id]);
        } else {
            $_SESSION['cart'][$id]['quantity'] = $quantity;
        }
    }

    header("Location: cart.php");
    exit;
}

// ADD PRODUCT TO CART
if (isset($_GET['add'])) {
    $id = (int) $_GET['add'];

    $stmt = $conn->prepare(
        "SELECT id, name, price FROM products WHERE id = ?"
    );

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($product = $result->fetch_assoc()) {
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['quantity']++;
        } else {
            $_SESSION['cart'][$id] = [
                'id' => (int) $product['id'],
                'name' => $product['name'],
                'price' => (float) $product['price'],
                'quantity' => 1
            ];
        }
    }

    $stmt->close();

    header("Location: cart.php");
    exit;
}

// CALCULATE TOTAL
$total = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Shopping Cart - My Store</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            padding: 30px;
            margin: 0;
        }

        h1 {
            text-align: center;
            color: #222;
        }

        .cart {
            max-width: 700px;
            margin: 30px auto;
            padding: 25px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px #ddd;
        }

        .item {
            padding: 15px 0;
            border-bottom: 1px solid #ddd;
        }

        .quantity {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 12px 0;
        }

        .quantity a {
            text-decoration: none;
        }

        .quantity button {
            width: 32px;
            height: 32px;
            font-size: 20px;
            cursor: pointer;
            border: 1px solid #ccc;
            border-radius: 5px;
            background: #f4f6f8;
        }

        .remove {
            display: inline-block;
            margin-left: 10px;
            padding: 7px 12px;
            background: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .continue {
            display: inline-block;
            margin-top: 15px;
            margin-right: 8px;
            padding: 10px 15px;
            background: #16803c;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .checkout {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 15px;
            background: #1769d2;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .total {
            text-align: right;
            color: #16803c;
            margin-top: 25px;
        }

        .empty {
            text-align: center;
            padding: 20px;
        }
    </style>
</head>

<body>

<div class="cart">

    <h1>Shopping Cart</h1>

    <?php if (empty($_SESSION['cart'])): ?>

        <div class="empty">
            <p>Your cart is empty.</p>

            <a class="continue" href="products.php">
                Browse Products
            </a>
        </div>

    <?php else: ?>

        <?php foreach ($_SESSION['cart'] as $item): ?>

            <?php
            $itemPrice = (float) $item['price'];
            $quantity = (int) $item['quantity'];
            $subtotal = $itemPrice * $quantity;

            $total += $subtotal;
            ?>

            <div class="item">

                <strong>
                    <?= htmlspecialchars($item['name']) ?>
                </strong>

                <p>
                    Price: ₹<?= number_format($itemPrice, 2) ?>
                </p>

                <div class="quantity">

                    <span>Quantity:</span>

                    <a href="cart.php?id=<?= (int) $item['id'] ?>&update=<?= $quantity - 1 ?>">
                        <button type="button" aria-label="Decrease quantity">−</button>
                    </a>

                    <strong><?= $quantity ?></strong>

                    <a href="cart.php?id=<?= (int) $item['id'] ?>&update=<?= $quantity + 1 ?>">
                        <button type="button" aria-label="Increase quantity">+</button>
                    </a>

                </div>

                <p>
                    Subtotal: ₹<?= number_format($subtotal, 2) ?>

                    <a
                        class="remove"
                        href="cart.php?remove=<?= (int) $item['id'] ?>"
                        onclick="return confirm('Remove this product from your cart?');"
                    >
                        Remove
                    </a>
                </p>

            </div>

        <?php endforeach; ?>

        <h2 class="total">
            Total: ₹<?= number_format($total, 2) ?>
        </h2>

        <a class="continue" href="products.php">
            Continue Shopping
        </a>

        <a class="checkout" href="checkout.php">
            Proceed to Checkout
        </a>

    <?php endif; ?>

</div>

</body>
</html>

<?php
$conn->close();
?>
```