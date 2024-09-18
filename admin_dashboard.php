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

// Fetch user information to check if the user is a seller
try {
    $stmt = $con->prepare("SELECT is_seller FROM user_table WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // If the user is not a seller, redirect to error404 page
    if ($user['is_seller'] == 0) {
        header('Location: error404.php');
        exit();
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Fetch new clients (Users who registered in the last 30 days)
try {
    $stmt_clients = $con->prepare("SELECT COUNT(user_id) as new_clients_count FROM user_table WHERE created_at >= NOW() - INTERVAL 30 DAY");
    $stmt_clients->execute();
    $new_clients_count = $stmt_clients->fetch(PDO::FETCH_ASSOC)['new_clients_count'];
} catch (PDOException $e) {
    echo "Error fetching new clients: " . $e->getMessage();
}

// Fetch total revenue from completed orders (Orders placed in the last 30 days)
try {
    $stmt_revenue = $con->prepare("SELECT SUM(total_amount) as total_revenue FROM order_table WHERE status = 'Completed' AND order_date >= NOW() - INTERVAL 30 DAY");
    $stmt_revenue->execute();
    $total_revenue = $stmt_revenue->fetch(PDO::FETCH_ASSOC)['total_revenue'] ?? 0;
} catch (PDOException $e) {
    echo "Error fetching total revenue: " . $e->getMessage();
}

// Fetch new orders (Orders placed in the last 30 days)
try {
    $stmt_orders = $con->prepare("SELECT order_id, user_id, order_date, status, total_amount FROM order_table WHERE order_date >= NOW() - INTERVAL 30 DAY ORDER BY order_date DESC");
    $stmt_orders->execute();
    $new_orders = $stmt_orders->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching new orders: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/styles.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
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
                                <i class="bi bi-house-door"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>" 
                               href="profile.php" aria-current="<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'page' : ''; ?>">
                                <i class="bi bi-person"></i> Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'products_Management.php' ? 'active' : ''; ?>" 
                               href="products_Management.php" aria-current="<?php echo basename($_SERVER['PHP_SELF']) == 'products_Management.php' ? 'page' : ''; ?>">
                                <i class="bi bi-box"></i> Products
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'orders_Management.php' ? 'active' : ''; ?>" 
                               href="orders_Management.php" aria-current="<?php echo basename($_SERVER['PHP_SELF']) == 'orders_Management.php' ? 'page' : ''; ?>">
                                <i class="bi bi-receipt"></i> Orders
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <h2 class="my-4">Admin Dashboard</h2>

                <!-- Dashboard Cards -->
                <div class="row mb-4">
                    <!-- New Clients Card -->
                    <div class="col-md-6">
                        <div class="card text-white bg-info mb-3">
                            <div class="card-header">
                                <i class="bi bi-people"></i> New Clients (Last 30 Days)
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $new_clients_count; ?> New Clients</h5>
                            </div>
                        </div>
                    </div>

                    <!-- Revenue Card -->
                    <div class="col-md-6">
                        <div class="card text-white bg-success mb-3">
                            <div class="card-header">
                                <i class="bi bi-cash-stack"></i> Total Revenue (Last 30 Days)
                            </div>
                            <div class="card-body">
                                <h5 class="card-title">$<?php echo number_format($total_revenue, 2); ?></h5>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- New Orders Section -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="bi bi-cart"></i> New Orders (Last 30 Days)
                    </div>
                    <div class="card-body">
                        <?php if (!empty($new_orders)): ?>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>User ID</th>
                                        <th>Order Date</th>
                                        <th>Status</th>
                                        <th>Total Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($new_orders as $order): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                                            <td><?php echo htmlspecialchars($order['user_id']); ?></td>
                                            <td><?php echo htmlspecialchars($order['order_date']); ?></td>
                                            <td><?php echo htmlspecialchars($order['status']); ?></td>
                                            <td><?php echo htmlspecialchars($order['total_amount']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p>No new orders in the last 30 days.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Logout Button -->
                <a href="logout.php" class="btn btn-danger btn-block"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>