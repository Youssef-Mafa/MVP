<?php
// Include the database connection
include 'db_connect.php';

// Initialize error variables
$password_error = $username_error = $email_error = "";

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data and sanitize
    $username = htmlspecialchars(trim($_POST["username"]));
    $email = htmlspecialchars(trim($_POST["email"]));
    $password = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm_password"]);
    $full_name = htmlspecialchars(trim($_POST["full_name"]));
    $phone_number = htmlspecialchars(trim($_POST["phone_number"]));
    $address = htmlspecialchars(trim($_POST["address"]));
    $is_seller = isset($_POST["is_seller"]) ? 1 : 0;

    // Validate form data
    $errors = [];
    if (empty($username)) {
        $errors[] = "Username is required";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    if (empty($password) || strlen($password) < 8) {  // Increased minimum length
        $errors[] = "Password must be at least 8 characters";
    }
    if ($password !== $confirm_password) {
        $password_error = "Passwords do not match";
        $errors[] = $password_error;
    }
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }
    if (empty($phone_number)) {
        $errors[] = "Phone number is required";
    }
    if (empty($address)) {
        $errors[] = "Address is required";
    }

    // Check if username or email is already taken
    if (empty($errors)) {
        try {
            $stmt = $con->prepare("SELECT username, email FROM user_table WHERE username = :username OR email = :email");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing_user) {
                if ($existing_user['username'] === $username) {
                    $username_error = "Username is already taken";
                    $errors[] = $username_error;
                }
                if ($existing_user['email'] === $email) {
                    $email_error = "Email is already registered";
                    $errors[] = $email_error;
                }
            }
        } catch (PDOException $e) {
            $errors[] = "Error checking username/email: " . $e->getMessage();
        }
    }

    // Check if there are any errors
    if (empty($errors)) {
        // Hash the password
        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        // Insert the user into the database
        try {
            $stmt = $con->prepare("INSERT INTO user_table (username, email, password_hash, full_name, phone_number, address, created_at, is_seller)
                                   VALUES (:username, :email, :password_hash, :full_name, :phone_number, :address, NOW(), :is_seller)");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password_hash', $password_hash);
            $stmt->bindParam(':full_name', $full_name);
            $stmt->bindParam(':phone_number', $phone_number);
            $stmt->bindParam(':address', $address);
            $stmt->bindParam(':is_seller', $is_seller);
            $stmt->execute();

            // Redirect to avoid form resubmission
            header("Location: index.php");
            exit();
        } catch (PDOException $e) {
            echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container">
        <h2>User Registration</h2>
        <?php
        // Check for success message after redirect
        if (isset($_GET['success']) && $_GET['success'] == 1) {
            echo "<p style='color:green;'>Registration successful!</p>";
        }

        // Display any errors
        if (!empty($errors)) {
            echo "<div class='alert alert-danger'>";
            foreach ($errors as $error) {
                echo "<p>$error</p>";
            }
            echo "</div>";
        }
        ?>
        <form method="post" action="">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" name="username" id="username" class="form-control" required value="<?php echo htmlspecialchars($username ?? ''); ?>">
                <?php if (!empty($username_error)): ?>
                    <span class="text-danger"><?php echo $username_error; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" class="form-control" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
                <?php if (!empty($email_error)): ?>
                    <span class="text-danger"><?php echo $email_error; ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                <?php if (!empty($password_error)): ?>
                    <span class="text-danger"><?php echo $password_error; ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="full_name">Full Name:</label>
                <input type="text" name="full_name" id="full_name" class="form-control" required value="<?php echo htmlspecialchars($full_name ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="phone_number">Phone Number:</label>
                <input type="text" name="phone_number" id="phone_number" class="form-control" required value="<?php echo htmlspecialchars($phone_number ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="address">Address:</label>
                <input type="text" name="address" id="address" class="form-control" required value="<?php echo htmlspecialchars($address ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="is_seller">Register as Seller:</label>
                <input type="checkbox" name="is_seller" id="is_seller" <?php echo isset($is_seller) && $is_seller ? 'checked' : ''; ?>>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Register</button>
        </form>
        <p class="mt-3 text-center">Already have an account? <a href="login.php">Login here</a></p>
    </div>
</body>
</html>
