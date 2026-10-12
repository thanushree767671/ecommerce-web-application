
<?php
session_start();

// Allow only administrators
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

if (($_SESSION["user_role"] ?? "") !== "admin") {
    exit("Access denied. Admins only.");
}

$conn = new mysqli("localhost", "root", "", "mystore_db");

if ($conn->connect_error) {
    die("Database connection failed.");
}

$conn->set_charset("utf8mb4");

$message = "";
$allowedStatuses = ["Pending", "Processing", "Shipped", "Delivered", "Cancelled"];

// Create CSRF token
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Update order status
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (!is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)) {
        exit("Invalid request. Please reload the page.");
    }

    $orderId = filter_input(INPUT_POST, "order_id", FILTER_VALIDATE_INT);
    $status = $_POST["status"] ?? "";

    if (!$orderId || $orderId < 1) {
        $message = "Invalid order ID.";
    } elseif (!in_array($status, $allowedStatuses, true)) {
        $message = "Please select a valid status.";
    } else {
        $stmt = $conn->prepare(
            "UPDATE orders SET status = ? WHERE id = ?"
        );
        $stmt->bind_param("si", $status, $orderId);
        $stmt->execute();

        $message = $stmt->affected_rows > 0
            ? "Order status updated successfully."
            : "No change made, or order not found.";

        $stmt->close();
    }
}

// Get all orders, newest first
$orders = $conn->query(
    "SELECT id, customer_name, phone, address, total, status, created_at
     FROM orders
     ORDER BY id DESC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Orders</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 25px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }
        th {
            background: #eee;
        }
        select, button {
            padding: 7px;
            margin: 3px 0;
        }
        .message {
            padding: 10px;
            background: #f0f0f0;
        }
        .address {
            max-width: 220px;
            overflow-wrap: anywhere;
        }
    </style>
</head>
<body>

<h1>Admin - Manage Orders</h1>

<p>
    <a href="index.php">Admin Home</a> |
    <a href="products.php">Manage Products</a> |
    <a href="../dashboard.php">Dashboard</a>
</p>

<?php if ($message !== ""): ?>
    <p class="message"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<h2>All Customer Orders</h2>

<?php if ($orders && $orders->num_rows > 0): ?>
<table>
    <thead>
        <tr>
            <th>Order ID</th>
            <th>Customer</th>
            <th>Phone</th>
            <th>Address</th>
            <th>Total</th>
            <th>Date</th>
            <th>Status</th>
            <th>Update Status</th>
        </tr>
    </thead>
    <tbody>
    <?php while ($order = $orders->fetch_assoc()): ?>
        <tr>
            <td><?= (int)$order["id"] ?></td>
            <td><?= htmlspecialchars($order["customer_name"]) ?></td>
            <td><?= htmlspecialchars($order["phone"]) ?></td>
            <td class="address"><?= nl2br(htmlspecialchars($order["address"])) ?></td>
            <td>₹<?= number_format((float)$order["total"], 2) ?></td>
            <td><?= htmlspecialchars($order["created_at"]) ?></td>
            <td><?= htmlspecialchars($order["status"] ?? "Pending") ?></td>
            <td>
                <form method="POST" action="orders.php">
                    <input type="hidden" name="csrf_token"
                           value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">
                    <input type="hidden" name="order_id"
                           value="<?= (int)$order["id"] ?>">

                    <select name="status" required>
                        <?php foreach ($allowedStatuses as $option): ?>
                            <option value="<?= htmlspecialchars($option) ?>"
                                <?= ($order["status"] ?? "Pending") === $option
                                    ? "selected" : "" ?>>
                                <?= htmlspecialchars($option) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit">Update</button>
                </form>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
<?php else: ?>
    <p>No orders found yet.</p>
<?php endif; ?>

</body>
</html>

<?php $conn->close(); ?>
