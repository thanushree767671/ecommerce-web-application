```php
<?php
$conn = new mysqli("localhost", "root", "", "mystore_db");

if ($conn->connect_error) {
    die("Database connection failed.");
}

$conn->set_charset("utf8mb4");

$order = null;
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $orderId = filter_input(
        INPUT_POST,
        "order_id",
        FILTER_VALIDATE_INT
    );

    if ($orderId === false || $orderId === null || $orderId < 1) {
        $error = "Please enter a valid order number.";
    } else {
        $stmt = $conn->prepare(
            "SELECT id, total, status, created_at
             FROM orders
             WHERE id = ?"
        );

        $stmt->bind_param("i", $orderId);
        $stmt->execute();

        $result = $stmt->get_result();
        $order = $result->fetch_assoc();

        if (!$order) {
            $error = "No order found with that number.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Track Order - My Store</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 30px;
        }

        .container {
            max-width: 550px;
            margin: 40px auto;
            padding: 25px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px #ddd;
        }

        h1, h2 {
            text-align: center;
        }

        label {
            display: block;
            margin: 15px 0 6px;
            font-weight: bold;
        }

        input {
            width: 100%;
            box-sizing: border-box;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }

        button {
            width: 100%;
            margin-top: 15px;
            padding: 12px;
            border: none;
            border-radius: 5px;
            background: #1769d2;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        .result {
            margin-top: 25px;
            padding: 15px;
            background: #f0f7ff;
            border-radius: 8px;
        }

        .error {
            margin-top: 15px;
            color: #b00020;
        }

        .back {
            display: inline-block;
            margin-top: 20px;
            color: #1769d2;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>Track Your Order</h1>

    <p>Enter the order number you received after checkout.</p>

    <form method="POST" action="track_order.php">

        <label for="order_id">Order Number</label>

        <input
            type="number"
            id="order_id"
            name="order_id"
            min="1"
            required
            placeholder="Example: 1"
            value="<?= htmlspecialchars(
                $_POST['order_id'] ?? ''
            ) ?>"
        >

        <button type="submit">Track Order</button>

    </form>

    <?php if ($error !== ""): ?>

        <p class="error">
            <?= htmlspecialchars($error) ?>
        </p>

    <?php endif; ?>

    <?php if ($order): ?>

        <div class="result">

            <h2>Order Details</h2>

            <p>
                <strong>Order Number:</strong>
                <?= (int) $order['id'] ?>
            </p>

            <p>
                <strong>Order Total:</strong>
                ₹<?= number_format((float) $order['total'], 2) ?>
            </p>

            <p>
                <strong>Status:</strong>
                <?= htmlspecialchars($order['status']) ?>
            </p>

            <p>
                <strong>Order Date:</strong>
                <?= htmlspecialchars($order['created_at']) ?>
            </p>

        </div>

    <?php endif; ?>

    <a class="back" href="products.php">Back to Products</a>

</div>

</body>
</html>

<?php
$conn->close();
?>
```