<?php
session_start();
include 'db_connect.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Initialize error and success messages
$errors = [];
$success_message = "";

// Handle form submission for profile update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = htmlspecialchars(trim($_POST['username']));
    $email = htmlspecialchars(trim($_POST['email']));
    $full_name = htmlspecialchars(trim($_POST['full_name']));
    $phone_number = htmlspecialchars(trim($_POST['phone_number']));
    $address = htmlspecialchars(trim($_POST['address']));
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validate input
    if (empty($username)) {
        $errors[] = "Username is required.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email is required.";
    }
    if (empty($full_name)) {
        $errors[] = "Full name is required.";
    }
    if (empty($phone_number)) {
        $errors[] = "Phone number is required.";
    }
    if (empty($address)) {
        $errors[] = "Address is required.";
    }
    if (!empty($password) && $password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // Check if username or email is already taken by another user
    if (empty($errors)) {
        try {
            $stmt = $con->prepare("SELECT user_id FROM user_table WHERE (username = :username OR email = :email) AND user_id != :user_id");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing_user) {
                if ($existing_user['username'] === $username) {
                    $errors[] = "Username is already taken.";
                }
                if ($existing_user['email'] === $email) {
                    $errors[] = "Email is already registered.";
                }
            }
        } catch (PDOException $e) {
            $errors[] = "Error checking username/email: " . $e->getMessage();
        }
    }

    // Update user data if there are no errors
    if (empty($errors)) {
        try {
            $sql = "UPDATE user_table SET username = :username, email = :email, full_name = :full_name, phone_number = :phone_number, address = :address";
            $params = [
                'username' => $username,
                'email' => $email,
                'full_name' => $full_name,
                'phone_number' => $phone_number,
                'address' => $address
            ];

            // Update password if provided
            if (!empty($password)) {
                $password_hash = password_hash($password, PASSWORD_BCRYPT);
                $sql .= ", password_hash = :password_hash";
                $params['password_hash'] = $password_hash;
            }

            $sql .= " WHERE user_id = :user_id";
            $params['user_id'] = $user_id;

            $stmt = $con->prepare($sql);
            $stmt->execute($params);

            $success_message = "Profile updated successfully.";
        } catch (PDOException $e) {
            $errors[] = "Error updating profile: " . $e->getMessage();
        }
    }
}

// Fetch user data
try {
    $stmt = $con->prepare("SELECT * FROM user_table WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Error fetching user data: " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<?php include 'navbar.php'; ?>
    <div class="container">
        <h2>Update Profile</h2>

        <?php
        // Display success message
        if (!empty($success_message)) {
            echo "<div class='alert alert-success'>$success_message</div>";
        }

        // Display errors
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
                <input type="text" name="username" id="username" class="form-control" required value="<?php echo htmlspecialchars($user['username']); ?>">
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" class="form-control" required value="<?php echo htmlspecialchars($user['email']); ?>">
            </div>

            <div class="form-group">
                <label for="full_name">Full Name:</label>
                <input type="text" name="full_name" id="full_name" class="form-control" required value="<?php echo htmlspecialchars($user['full_name']); ?>">
            </div>

            <div class="form-group">
                <label for="phone_number">Phone Number:</label>
                <input type="text" name="phone_number" id="phone_number" class="form-control" required value="<?php echo htmlspecialchars($user['phone_number']); ?>">
            </div>

            <div class="form-group">
                <label for="address">Address:</label>
                <input type="text" name="address" id="address" class="form-control" required value="<?php echo htmlspecialchars($user['address']); ?>">
            </div>

            <div class="form-group">
                <label for="password">New Password (Leave blank to keep current):</label>
                <input type="password" name="password" id="password" class="form-control">
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password:</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control">
            </div>

            <button type="submit" class="btn btn-primary btn-block">Update Profile</button>
        </form>
        
        <br>
        
        <!-- Logout Button -->
        <form action="logout.php" method="post">
            <button type="submit" class="btn btn-danger btn-block">Logout</button>
        </form>
    </div>
</body>
</html>
