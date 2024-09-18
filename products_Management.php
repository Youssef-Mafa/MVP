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
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // If the user is not a seller, redirect to error404 page
    if ($user['is_seller'] == 0) {
        header('Location: error404.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    header('Location: error.php');
    exit();
}

// Fetch all products from the database
try {
    $stmt_products = $con->prepare("SELECT product_id, name, price, stock_quantity FROM product WHERE seller_id = :seller_id");
    $stmt_products->bindParam(':seller_id', $user_id, PDO::PARAM_INT);
    $stmt_products->execute();
    $products = $stmt_products->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching products: " . $e->getMessage());
    header('Location: error.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Product Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
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
                <h2 class="my-4">Product Management</h2>

                <!-- Add Product Button -->
                <a href="add_product.php" class="btn btn-success mb-3">
                    <i class="bi bi-plus-circle"></i> Add New Product
                </a>

                <?php if (!empty($products)): ?>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Product ID</th>
                                <th>Name</th>
                                <th>Price</th>
                                <th>Stock Quantity</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['product_id']); ?></td>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td>$<?php echo htmlspecialchars(number_format($product['price'], 2)); ?></td>
                                    <td><?php echo htmlspecialchars($product['stock_quantity']); ?></td>
                                    <td>
                                        <a href="edit_product.php?product_id=<?php echo $product['product_id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <a href="delete_product.php?product_id=<?php echo $product['product_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this product?');">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No products found. Add new products using the button above.</p>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
