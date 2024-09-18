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
    $stmt_order = $con->prepare("
        SELECT o.order_id, o.order_date, o.status, o.total_amount, u.username, u.full_name, u.email
        FROM order_table o
        JOIN user_table u ON o.user_id = u.user_id
        WHERE o.order_id = :order_id
    ");
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

// Fetch order items
try {
    $stmt_items = $con->prepare("
        SELECT oi.quantity, oi.unit_price, p.name, pv.size, pv.color
        FROM order_item oi
        JOIN product p ON oi.product_id = p.product_id
        LEFT JOIN product_variant pv ON oi.variant_id = pv.variant_id
        WHERE oi.order_id = :order_id
    ");
    $stmt_items->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $stmt_items->execute();
    $order_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching order items: " . $e->getMessage());
    header('Location: error.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Order</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
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
                <h2 class="my-4">Order Details</h2>

                <div class="card mb-4">
                    <div class="card-header">
                        <h3>Order ID: <?php echo htmlspecialchars($order['order_id']); ?></h3>
                    </div>
                    <div class="card-body">
                        <p><strong>Customer Name:</strong> <?php echo htmlspecialchars($order['full_name']); ?> (<?php echo htmlspecialchars($order['username']); ?>)</p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                        <p><strong>Order Date:</strong> <?php echo htmlspecialchars($order['order_date']); ?></p>
                        <p><strong>Status:</strong> <?php echo htmlspecialchars($order['status']); ?></p>
                        <p><strong>Total Amount:</strong> $<?php echo htmlspecialchars($order['total_amount']); ?></p>

                        <!-- Update Status Button -->
                        <a href="update_order_status.php?order_id=<?php echo $order['order_id']; ?>" class="btn btn-warning">Update Status</a>
                    </div>
                </div>

                <h4>Ordered Items</h4>
                <?php if (!empty($order_items)): ?>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Variant (Size/Color)</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td>
                                        <?php 
                                        if (!empty($item['size']) || !empty($item['color'])) {
                                            echo htmlspecialchars($item['size']) . ' / ' . htmlspecialchars($item['color']);
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                    <td>$<?php echo htmlspecialchars($item['unit_price']); ?></td>
                                    <td>$<?php echo htmlspecialchars($item['quantity'] * $item['unit_price']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No items found for this order.</p>
                <?php endif; ?>

                <a href="orders_Management.php" class="btn btn-secondary">Back to Orders</a>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
