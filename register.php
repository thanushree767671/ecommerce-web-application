```php
<?php
session_start();

$conn = new mysqli("localhost", "root", "", "mystore_db");

if ($conn->connect_error) {
    die("Database connection failed.");
}

$conn->set_charset("utf8mb4");

$name = "";
$email = "";
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    if ($name === "" || $email === "" ||
        $password === "" || $confirmPassword === "") {

        $error = "Please fill in all fields.";

    } elseif (strlen($name) > 100) {

        $error = "Name must not exceed 100 characters.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) ||
              strlen($email) > 150) {

        $error = "Please enter a valid email address.";

    } elseif (strlen($password) < 8) {

        $error = "Password must contain at least 8 characters.";

    } elseif ($password !== $confirmPassword) {

        $error = "Passwords do not match.";

    } else {

        // Check whether the email already exists
        $stmt = $conn->prepare(
            "SELECT id FROM users WHERE email = ?"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {

            $error = "This email is already registered.";

        } else {

            // Hash the password before saving it
            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // Public registration always creates a normal user
            $role = "user";

            $insert = $conn->prepare(
                "INSERT INTO users (name, email, password, role)
                 VALUES (?, ?, ?, ?)"
            );

            $insert->bind_param(
                "ssss",
                $name,
                $email,
                $hashedPassword,
                $role
            );

            if ($insert->execute()) {
                $success = "Registration successful! You can now log in.";
                $name = "";
                $email = "";
            } else {
                $error = "Registration failed. Please try again.";
            }

            $insert->close();
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

    <title>Register - My Store</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 30px;
        }

        .container {
            max-width: 450px;
            margin: 30px auto;
            padding: 25px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px #ddd;
        }

        h1 {
            text-align: center;
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }

        input {
            width: 100%;
            box-sizing: border-box;
            padding: 12px;
            margin-top: 6px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 15px;
        }

        button {
            width: 100%;
            padding: 12px;
            margin-top: 22px;
            background: #1769d2;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        .error {
            padding: 10px;
            color: #b00020;
            background: #ffe8e8;
            border-radius: 5px;
        }

        .success {
            padding: 10px;
            color: #11652e;
            background: #e5f6e9;
            border-radius: 5px;
        }

        .links {
            text-align: center;
            margin-top: 18px;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>Create Account</h1>

    <?php if ($error !== ""): ?>
        <p class="error">
            <?= htmlspecialchars($error) ?>
        </p>
    <?php endif; ?>

    <?php if ($success !== ""): ?>
        <p class="success">
            <?= htmlspecialchars($success) ?>
        </p>
    <?php endif; ?>

    <form method="POST" action="register.php">

        <label for="name">Full Name</label>
        <input
            type="text"
            id="name"
            name="name"
            maxlength="100"
            required
            value="<?= htmlspecialchars($name) ?>"
        >

        <label for="email">Email Address</label>
        <input
            type="email"
            id="email"
            name="email"
            maxlength="150"
            required
            value="<?= htmlspecialchars($email) ?>"
        >

        <label for="password">Password</label>
        <input
            type="password"
            id="password"
            name="password"
            minlength="8"
            required
        >

        <label for="confirm_password">Confirm Password</label>
        <input
            type="password"
            id="confirm_password"
            name="confirm_password"
            minlength="8"
            required
        >

        <button type="submit">Register</button>

    </form>

    <div class="links">
        <p>Already have an account?</p>
        <a href="login.php">Login here</a>
        <p><a href="products.php">Back to Products</a></p>
    </div>

</div>

</body>
</html>

<?php
$conn->close();
?>
```