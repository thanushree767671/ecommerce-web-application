
<?php
session_start();

// Allow only logged-in administrators
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

// Create a CSRF token for form submissions
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$message = "";
$editProduct = null;

// Handle add, update, and delete
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        exit("Invalid request. Please reload the page.");
    }

    $action = $_POST["action"] ?? "";

    if ($action === "delete") {
        $id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

        if (!$id || $id < 1) {
            $message = "Invalid product ID.";
        } else {
            // Keep products that are already used in orders
            $check = $conn->prepare(
                "SELECT COUNT(*) FROM order_items WHERE product_id = ?"
            );
            $check->bind_param("i", $id);
            $check->execute();
            $check->bind_result($orderCount);
            $check->fetch();
            $check->close();

            if ($orderCount > 0) {
                $message = "This product is used in an order and cannot be deleted.";
            } else {
                $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();

                $message = $stmt->affected_rows > 0
                    ? "Product deleted successfully."
                    : "Product not found.";

                $stmt->close();
            }
        }
    } elseif ($action === "add" || $action === "update") {
        $name = trim($_POST["name"] ?? "");
        $description = trim($_POST["description"] ?? "");
        $categoryId = filter_input(
            INPUT_POST, "category_id", FILTER_VALIDATE_INT
        );
        $priceInput = trim($_POST["price"] ?? "");
        $stockInput = trim($_POST["stock"] ?? "");
        $image = trim($_POST["image"] ?? "");

        if (
            $name === "" ||
            !$categoryId || $categoryId < 1 ||
            $priceInput === "" || !is_numeric($priceInput) ||
            !is_numeric($stockInput) ||
            !preg_match('/^\d+$/', $stockInput) ||
            (float)$priceInput < 0 ||
            (int)$stockInput < 0 ||
            strlen($name) > 150 ||
            strlen($image) > 255
        ) {
            $message = "Please enter valid product details.";
        } else {
            // Check that the selected category exists
            $categoryCheck = $conn->prepare(
                "SELECT id FROM categories WHERE id = ?"
            );
            $categoryCheck->bind_param("i", $categoryId);
            $categoryCheck->execute();
            $categoryCheck->store_result();
            $categoryExists = $categoryCheck->num_rows > 0;
            $categoryCheck->close();

            if (!$categoryExists) {
                $message = "Please select a valid category.";
            } else {
                $price = (float)$priceInput;
                $stock = (int)$stockInput;

                if ($action === "add") {
                    $stmt = $conn->prepare(
                        "INSERT INTO products
                        (category_id, name, description, price, stock, image)
                        VALUES (?, ?, ?, ?, ?, ?)"
                    );
                    $stmt->bind_param(
                        "issdis",
                        $categoryId, $name, $description,
                        $price, $stock, $image
                    );
                    $stmt->execute();
                    $message = "Product added successfully.";
                    $stmt->close();
                } else {
                    $id = filter_input(
                        INPUT_POST, "id", FILTER_VALIDATE_INT
                    );

                    if (!$id || $id < 1) {
                        $message = "Invalid product ID.";
                    } else {
                        $stmt = $conn->prepare(
                            "UPDATE products
                            SET category_id = ?, name = ?, description = ?,
                                price = ?, stock = ?, image = ?
                            WHERE id = ?"
                        );
                        $stmt->bind_param(
                            "issdisi",
                            $categoryId, $name, $description,
                            $price, $stock, $image, $id
                        );
                        $stmt->execute();
                        $message = "Product updated successfully.";
                        $stmt->close();
                    }
                }
            }
        }
    }
}

// Load product details when Edit is clicked
if (isset($_GET["edit"])) {
    $id = filter_input(INPUT_GET, "edit", FILTER_VALIDATE_INT);

    if ($id && $id > 0) {
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $editProduct = $result->fetch_assoc();
        $stmt->close();

        if (!$editProduct) {
            $message = "Product not found.";
        }
    }
}

// Load categories
$categories = $conn->query("SELECT id, name FROM categories ORDER BY name");

// Load products
$products = $conn->query(
    "SELECT p.*, c.name AS category_name
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     ORDER BY p.id DESC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Products</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 25px; }
        input, textarea, select, button {
            display: block; width: 100%; max-width: 450px;
            padding: 9px; margin: 8px 0; box-sizing: border-box;
        }
        button, .link { cursor: pointer; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 9px; text-align: left; }
        th { background: #eee; }
        .message { padding: 10px; background: #f0f0f0; }
        .actions { white-space: nowrap; }
        .actions form { display: inline; }
        .actions button { display: inline; width: auto; }
    </style>
</head>
<body>

<h1>Admin - Manage Products</h1>
<p>Welcome, <?= htmlspecialchars($_SESSION["user_name"] ?? "Admin") ?>!</p>
<p><a href="index.php">Admin Home</a> | <a href="../dashboard.php">Dashboard</a></p>

<?php if ($message !== ""): ?>
    <p class="message"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<h2><?= $editProduct ? "Edit Product" : "Add New Product" ?></h2>

<form method="POST" action="products.php">
    <input type="hidden" name="csrf_token"
           value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

    <input type="hidden" name="action"
           value="<?= $editProduct ? "update" : "add" ?>">

    <?php if ($editProduct): ?>
        <input type="hidden" name="id"
               value="<?= (int)$editProduct["id"] ?>">
    <?php endif; ?>

    <label>Product Name</label>
    <input type="text" name="name" maxlength="150" required
           value="<?= htmlspecialchars($editProduct["name"] ?? "") ?>">

    <label>Description</label>
    <textarea name="description" rows="4"><?= htmlspecialchars(
        $editProduct["description"] ?? ""
    ) ?></textarea>

    <label>Category</label>
    <select name="category_id" required>
        <option value="">Select category</option>
        <?php while ($category = $categories->fetch_assoc()): ?>
            <option value="<?= (int)$category["id"] ?>"
                <?= isset($editProduct["category_id"]) &&
                    (int)$editProduct["category_id"] === (int)$category["id"]
                    ? "selected" : "" ?>>
                <?= htmlspecialchars($category["name"]) ?>
            </option>
        <?php endwhile; ?>
    </select>

    <label>Price (₹)</label>
    <input type="number" name="price" min="0" step="0.01" required
           value="<?= htmlspecialchars($editProduct["price"] ?? "") ?>">

    <label>Stock</label>
    <input type="number" name="stock" min="0" step="1" required
           value="<?= htmlspecialchars($editProduct["stock"] ?? "") ?>">

    <label>Image filename or path (optional)</label>
    <input type="text" name="image" maxlength="255"
           value="<?= htmlspecialchars($editProduct["image"] ?? "") ?>">

    <button type="submit">
        <?= $editProduct ? "Save Changes" : "Add Product" ?>
    </button>

    <?php if ($editProduct): ?>
        <a href="products.php">Cancel Editing</a>
    <?php endif; ?>
</form>

<hr>

<h2>All Products</h2>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Category</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php while ($product = $products->fetch_assoc()): ?>
        <tr>
            <td><?= (int)$product["id"] ?></td>
            <td><?= htmlspecialchars($product["name"]) ?></td>
            <td><?= htmlspecialchars($product["category_name"] ?? "") ?></td>
            <td>₹<?= number_format((float)$product["price"], 2) ?></td>
            <td><?= (int)$product["stock"] ?></td>
            <td class="actions">
                <a href="products.php?edit=<?= (int)$product["id"] ?>">
                    Edit
                </a>

                <form method="POST" action="products.php"
                      onsubmit="return confirm('Are you sure you want to delete this product?');">
                    <input type="hidden" name="csrf_token"
                           value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id"
                           value="<?= (int)$product["id"] ?>">
                    <button type="submit">Delete</button>
                </form>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>

</body>
</html>
<?php $conn->close(); ?>
