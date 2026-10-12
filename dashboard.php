```php
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Store Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            padding: 30px;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            padding: 25px;
            background: white;
            border-radius: 10px;
        }
        a {
            display: inline-block;
            margin: 10px 10px 0 0;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Welcome, <?= htmlspecialchars($_SESSION["user_name"]) ?>!</h1>
    <p>You have successfully logged in to My Store.</p>
    <p>Role: <?= htmlspecialchars($_SESSION["user_role"]) ?></p>

    <a href="products.php">View Products</a>
    <a href="cart.php">View Cart</a>
    <a href="track_order.php">Track Order</a>
    <a href="logout.php">Logout</a>
</div>
</body>
</html>