
<?php
session_start();

$conn = new mysqli("localhost", "root", "", "mystore_db");

if ($conn->connect_error) {
    die("Database connection failed.");
}

$conn->set_charset("utf8mb4");

$error = "";
$success = false;
$orderId = 0;
$total = 0;

// Calculate cart total
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $total += (float)$item['price'] * (int)$item['quantity'];
    }
}

// Process checkout form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (empty($_SESSION['cart'])) {
        $error = "Your cart is empty. Please add a product first.";
    } else {
        $customerName = trim($_POST['customer_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if ($customerName === '' || $phone === '' || $address === '') {
            $error = "Please fill in all fields.";
        } elseif (!preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) {
            $error = "Please enter a valid phone number.";
        } else {
            try {
                $conn->begin_transaction();

                // Save order details
                $stmt = $conn->prepare(
                    "INSERT INTO orders
                    (customer_name, phone, address, total, status)
                    VALUES (?, ?, ?, ?, 'Pending')"
                );

                $stmt->bind_param(
                    "sssd",
                    $customerName,
                    $phone,
                    $address,
                    $total
                );

                $stmt->execute();
                $orderId = $conn->insert_id;
                $stmt->close();

                // Save products belonging to this order
                $itemStmt = $conn->prepare(
                    "INSERT INTO order_items
                    (order_id, product_id, product_name, price, quantity, subtotal)
                    VALUES (?, ?, ?, ?, ?, ?)"
                );

                foreach ($_SESSION['cart'] as $item) {
                    $productId = (int)$item['id'];
                    $productName = $item['name'];
                    $price = (float)$item['price'];
                    $quantity = (int)$item['quantity'];
                    $subtotal = $price * $quantity;

                    $itemStmt->bind_param(
                        "iisdid",
                        $orderId,
                        $productId,
                        $productName,
                        $price,
                        $quantity,
                        $subtotal
                    );

                    $itemStmt->execute();
                }

                $itemStmt->close();
                $conn->commit();

                // Clear cart after successful order
                $_SESSION['cart'] = [];
                $success = true;

            } catch (Throwable $e) {
                $conn->rollback();
                error_log($e->getMessage());
                $error = "Unable to place your order. Please check your database tables and try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Checkout - My Store</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            padding: 30px;
            margin: 0;
        }

        .checkout {
            max-width: 550px;
            margin: 30px auto;
            padding: 25px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px #ddd;
        }

        h1 {
            text-align: center;
            color: #222;
        }

        .summary {
            background: #f4f6f8;
            padding: 15px;
            border-radius: 8px;
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }

        input, textarea {
            width: 100%;
            box-sizing: border-box;
            padding: 11px;
            margin-top: 6px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font: inherit;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        button {
            width: 100%;
            padding: 12px;
            margin-top: 20px;
            background: #16803c;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background: #11652e;
        }

        .error {
            padding: 10px;
            background: #ffe8e8;
            color: #b00020;
            border-radius: 5px;
        }

        .success {
            color: #16803c;
            text-align: center;
        }

        .link {
            display: inline-block;
            margin-top: 15px;
        }
    </style>
</head>

<body>

<div class="checkout">

    <?php if ($success): ?>

        <h1 class="success">Order Placed Successfully!</h1>

        <p>Your order number is:
            <strong><?= (int)$orderId ?></strong>
        </p>

        <p>Order total: ₹<?= number_format($total, 2) ?></p>

        <p>Order status: <strong>Pending</strong></p>

        <p>Your order details have been saved.</p>

        <a class="link" href="products.php">Continue Shopping</a>

    <?php else: ?>

        <h1>Checkout</h1>

        <?php if (!empty($_SESSION['cart'])): ?>

            <div class="summary">
                <h3>Order Summary</h3>

                <?php foreach ($_SESSION['cart'] as $item): ?>
                    <p>
                        <?= htmlspecialchars($item['name']) ?>
                        × <?= (int)$item['quantity'] ?>
                        — ₹<?= number_format(
                            (float)$item['price'] * (int)$item['quantity'],
                            2
                        ) ?>
                    </p>
                <?php endforeach; ?>

                <h3>Total: ₹<?= number_format($total, 2) ?></h3>
            </div>

            <?php if ($error !== ''): ?>
                <p class="error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <form method="POST" action="checkout.php">

                <label for="customer_name">Full Name</label>
                <input
                    type="text"
                    id="customer_name"
                    name="customer_name"
                    maxlength="100"
                    required
                    value="<?= htmlspecialchars($_POST['customer_name'] ?? '') ?>"
                >

                <label for="phone">Phone Number</label>
                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    maxlength="20"
                    pattern="[0-9+\-\s]{7,20}"
                    required
                    value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                >

                <label for="address">Delivery Address</label>
                <textarea
                    id="address"
                    name="address"
                    maxlength="2000"
                    required
                ><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>

                <button type="submit">Place Order</button>

            </form>

            <a class="link" href="cart.php">Back to Cart</a>

        <?php else: ?>

            <p>Your cart is empty. Add a product before checking out.</p>
            <a class="link" href="products.php">Browse Products</a>

        <?php endif; ?>

    <?php endif; ?>

</div>

</body>
</html>

<?php
$conn->close();
?>
