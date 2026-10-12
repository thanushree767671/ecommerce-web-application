
<?php
session_start();

// Check whether the user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

// Allow only administrators
if (($_SESSION["user_role"] ?? "") !== "admin") {
    http_response_code(403);
    exit("Access denied. Admins only.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - My Store</title>
</head>
<body>
    <h1>Admin Panel</h1>

    <p>
        Welcome,
        <?= htmlspecialchars($_SESSION["user_name"] ?? "Admin") ?>!
    </p>

    <h2>Admin Management</h2>

    <ul>
        <li>
            <a href="../products.php">View Store Products</a>
        </li>
        <li>
            <a href="products.php">Manage Products (Admin)</a>
        </li>
        <li>
            <a href="orders.php">Manage Orders</a>
        </li>
        <li>
            <a href="../dashboard.php">User Dashboard</a>
        </li>
        <li>
            <a href="../logout.php">Logout</a>
        </li>
    </ul>
</body>
</html>
