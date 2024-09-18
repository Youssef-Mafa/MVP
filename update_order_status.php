<?php
session_start();
include 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Retrieve user_id from session
$user_id = $_SESSION['user_id'];

// Check if the user is an admin or seller
try {
    $stmt = $con->prepare("SELECT is_seller FROM user_table WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user['is_seller'] == 0) {
        header('Location: error404.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    header('Location: error.php');
    exit();
}

// Get order ID from URL
if (!isset($_GET['order_id'])) {
    header('Location: orders_Management.php');
    exit();
}

$order_id = $_GET['order_id'];

// Fetch order details
try {
    $stmt_order = $con->prepare("SELECT * FROM order_table WHERE order_id = :order_id");
    $stmt_order->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $stmt_order->execute();
    $order = $stmt_order->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        header('Location: orders_Management.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("Error fetching order details: " . $e->getMessage());
    header('Location: error.php');
    exit();
}

// Handle form submission for updating order status
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_status = $_POST['status'];

    try {
        $stmt_update = $con->prepare("UPDATE order_table SET status = :status WHERE order_id = :order_id");
        $stmt_update->bindParam(':status', $new_status, PDO::PARAM_STR);
        $stmt_update->bindParam(':order_id', $order_id, PDO::PARAM_INT);
        $stmt_update->execute();

        // Redirect back to the orders management page after updating
        header('Location: orders_Management.php');
        exit();
    } catch (PDOException $e) {
        error_log("Error updating order status: " . $e->getMessage());
        $error_message = "Error updating order status: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Order Status</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet"> <!-- Assuming you have a custom styles.css file -->
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky sidebar-sticky">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>" 
                               href="admin_dashboard.php" aria-current="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'page' : ''; ?>">
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>" 
                               href="profile.php" aria-current="<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'page' : ''; ?>">
                                Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'products_Management.php' ? 'active' : ''; ?>" 
                               href="products_Management.php" aria-current="<?php echo basename($_SERVER['PHP_SELF']) == 'products_Management.php' ? 'page' : ''; ?>">
                                Products
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'orders_Management.php' ? 'active' : ''; ?>" 
                               href="orders_Management.php" aria-current="<?php echo basename($_SERVER['PHP_SELF']) == 'orders_Management.php' ? 'page' : ''; ?>">
                                Orders
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <h2 class="my-4">Update Order Status</h2>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <form method="post" action="update_order_status.php?order_id=<?php echo $order_id; ?>">
                    <div class="mb-3">
                        <label for="order_id" class="form-label">Order ID:</label>
                        <input type="text" class="form-control" id="order_id" value="<?php echo htmlspecialchars($order['order_id']); ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label for="order_date" class="form-label">Order Date:</label>
                        <input type="text" class="form-control" id="order_date" value="<?php echo htmlspecialchars($order['order_date']); ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label for="total_amount" class="form-label">Total Amount:</label>
                        <input type="text" class="form-control" id="total_amount" value="<?php echo htmlspecialchars($order['total_amount']); ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status:</label>
                        <select name="status" id="status" class="form-select" required>
                            <option value="Pending" <?php if ($order['status'] == 'Pending') echo 'selected'; ?>>Pending</option>
                            <option value="Completed" <?php if ($order['status'] == 'Completed') echo 'selected'; ?>>Completed</option>
                            <option value="Cancelled" <?php if ($order['status'] == 'Cancelled') echo 'selected'; ?>>Cancelled</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                    <a href="orders_Management.php" class="btn btn-secondary">Cancel</a>
                </form>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
