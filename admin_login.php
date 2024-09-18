<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/styles.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h2>Admin Login</h2>
        <form action="admin_login.php" method="POST">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <input type="submit" value="Login" class="btn btn-primary btn-block">
        </form>
        <?php
        session_start();
        require 'db_connect.php';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $username = $_POST['username'];
            $password = $_POST['password'];

            try {
                $stmt = $con->prepare("SELECT user_id, password_hash, is_seller FROM user_table WHERE username = :username LIMIT 1");
                $stmt->bindParam(':username', $username);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    if ($user['is_seller'] == 1) {
                        if (password_verify($password, $user['password_hash'])) {
                            $_SESSION['user_id'] = $user['user_id'];
                            $_SESSION['is_seller'] = $user['is_seller'];
                            header("Location: admin_dashboard.php");
                            exit();
                        } else {
                            echo '<p class="error">Invalid password.</p>';
                        }
                    } else {
                        echo '<p class="error">Access denied. Only sellers can log in.</p>';
                    }
                } else {
                    echo '<p class="error">User not found.</p>';
                }
            } catch (PDOException $e) {
                echo '<p class="error">Error: ' . $e->getMessage() . '</p>';
            }
        }
        ?>
    </div>
    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
