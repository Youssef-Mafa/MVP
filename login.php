<?php
// Include the database connection
include 'db_connect.php';

// Initialize variables
$login_input = $password = "";
$login_error = "";

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data
    $login_input = trim($_POST["login_input"]);
    $password = trim($_POST["password"]);

    // Validate form data
    if (empty($login_input) || empty($password)) {
        $login_error = "Both username/email and password are required";
    } else {
        // Determine if input is an email or username
        if (filter_var($login_input, FILTER_VALIDATE_EMAIL)) {
            $sql = "SELECT user_id, email, password_hash FROM user_table WHERE email = :login_input";
        } else {
            $sql = "SELECT user_id, username, password_hash FROM user_table WHERE username = :login_input";
        }

        // Check the user credentials
        try {
            $stmt = $con->prepare($sql);
            $stmt->bindParam(':login_input', $login_input);
            $stmt->execute();

            if ($stmt->rowCount() == 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if (password_verify($password, $user['password_hash'])) {
                    // Password is correct, start a new session
                    session_start();
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['email'] = $user['email'] ?? null;
                    $_SESSION['username'] = $user['username'] ?? null;
                    
                    // Redirect to the profile page
                    header("Location: profile.php");
                    exit();
                } else {
                    $login_error = "Invalid username/email or password";
                }
            } else {
                $login_error = "Invalid username/email or password";
            }
        } catch (PDOException $e) {
            $login_error = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container">
        <h2>User Login</h2>
        <?php
        if (!empty($login_error)) {
            echo "<p class='error'>$login_error</p>";
        }
        ?>
        <form method="post" action="">
            <label for="login_input">Username or Email:</label>
            <input type="text" name="login_input" id="login_input" required value="<?php echo htmlspecialchars($login_input); ?>">

            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required>

            <input type="submit" value="Login" class="btn btn-primary btn-block">
        </form>
        <p class="mt-3 text-center">Don't have an account? <a href="register.php">Register here</a></p>
    </div>
</body>
</html>
