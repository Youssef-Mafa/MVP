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

// Get product ID from URL
if (!isset($_GET['product_id'])) {
    header('Location: products_Management.php');
    exit();
}

$product_id = $_GET['product_id'];

// Fetch product details
try {
    $stmt_product = $con->prepare("SELECT * FROM product WHERE product_id = :product_id AND seller_id = :seller_id");
    $stmt_product->bindParam(':product_id', $product_id, PDO::PARAM_INT);
    $stmt_product->bindParam(':seller_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt_product->execute();
    $product = $stmt_product->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header('Location: products_Management.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("Error fetching product details: " . $e->getMessage());
    header('Location: error.php');
    exit();
}

// Fetch product variants
try {
    $stmt_variants = $con->prepare("SELECT * FROM product_variant WHERE product_id = :product_id");
    $stmt_variants->bindParam(':product_id', $product_id, PDO::PARAM_INT);
    $stmt_variants->execute();
    $variants = $stmt_variants->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching product variants: " . $e->getMessage());
    header('Location: error.php');
    exit();
}

// Handle form submission for updating product and variants
$errors = [];
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_name = htmlspecialchars(trim($_POST['product_name']));
    $description = htmlspecialchars(trim($_POST['description']));
    $price = trim($_POST['price']);
    $stock_quantity = trim($_POST['stock_quantity']);
    $image_url = htmlspecialchars(trim($_POST['image_url']));

    // Validate product input
    if (empty($product_name)) {
        $errors[] = "Product name is required.";
    }
    if (empty($price) || !is_numeric($price)) {
        $errors[] = "Valid price is required.";
    }
    if (empty($stock_quantity) || !is_numeric($stock_quantity)) {
        $errors[] = "Valid stock quantity is required.";
    }

    // Update product if no errors
    if (empty($errors)) {
        try {
            $stmt = $con->prepare("UPDATE product SET name = :name, description = :description, price = :price, stock_quantity = :stock_quantity, image_url = :image_url WHERE product_id = :product_id AND seller_id = :seller_id");
            $stmt->bindParam(':name', $product_name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':stock_quantity', $stock_quantity);
            $stmt->bindParam(':image_url', $image_url);
            $stmt->bindParam(':product_id', $product_id);
            $stmt->bindParam(':seller_id', $_SESSION['user_id']);
            $stmt->execute();

            // Handle variants
            $stmt = $con->prepare("DELETE FROM product_variant WHERE product_id = :product_id");
            $stmt->bindParam(':product_id', $product_id);
            $stmt->execute();

            if (isset($_POST['variant'])) {
                $stmt = $con->prepare("INSERT INTO product_variant (product_id, size, color, stock_quantity) VALUES (:product_id, :size, :color, :stock_quantity)");
                foreach ($_POST['variant'] as $index => $variant) {
                    $size = htmlspecialchars(trim($variant['size']));
                    $color = htmlspecialchars(trim($variant['color']));
                    $variant_stock = trim($variant['stock_quantity']);

                    if (!empty($size) || !empty($color)) {
                        if (empty($variant_stock) || !is_numeric($variant_stock)) {
                            $errors[] = "Valid stock quantity is required for variant $index.";
                        } else {
                            $stmt->bindParam(':product_id', $product_id);
                            $stmt->bindParam(':size', $size);
                            $stmt->bindParam(':color', $color);
                            $stmt->bindParam(':stock_quantity', $variant_stock);
                            $stmt->execute();
                        }
                    }
                }
            }

            // Redirect to products_Management.php after successful update
            header('Location: products_Management.php');
            exit();
        } catch (PDOException $e) {
            $errors[] = "Error updating product: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
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
                <h2 class="my-4">Edit Product</h2>

                <?php
                if (!empty($success_message)) {
                    echo "<div class='alert alert-success'>$success_message</div>";
                }
                if (!empty($errors)) {
                    echo "<div class='alert alert-danger'>";
                    foreach ($errors as $error) {
                        echo "<p>$error</p>";
                    }
                    echo "</div>";
                }
                ?>

                <form method="post" action="edit_product.php?product_id=<?php echo $product_id; ?>">
                    <div class="form-group">
                        <label for="product_name">Product Name:</label>
                        <input type="text" name="product_name" id="product_name" class="form-control" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea name="description" id="description" class="form-control"><?php echo htmlspecialchars($product['description']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="price">Price:</label>
                        <input type="text" name="price" id="price" class="form-control" value="<?php echo htmlspecialchars($product['price']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="stock_quantity">Stock Quantity:</label>
                        <input type="text" name="stock_quantity" id="stock_quantity" class="form-control" value="<?php echo htmlspecialchars($product['stock_quantity']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="image_url">Image URL:</label>
                        <input type="text" name="image_url" id="image_url" class="form-control" value="<?php echo htmlspecialchars($product['image_url']); ?>">
                    </div>

                    <h4>Variants (Optional)</h4>

                    <div id="variants_container">
                        <?php foreach ($variants as $index => $variant): ?>
                            <div class="variant-group form-group">
                                <label for="variant_size_<?php echo $index; ?>">Size:</label>
                                <input type="text" name="variant[<?php echo $index; ?>][size]" id="variant_size_<?php echo $index; ?>" class="form-control" value="<?php echo htmlspecialchars($variant['size']); ?>">

                                <label for="variant_color_<?php echo $index; ?>">Color:</label>
                                <input type="text" name="variant[<?php echo $index; ?>][color]" id="variant_color_<?php echo $index; ?>" class="form-control" value="<?php echo htmlspecialchars($variant['color']); ?>">

                                <label for="variant_stock_<?php echo $index; ?>">Stock Quantity:</label>
                                <input type="text" name="variant[<?php echo $index; ?>][stock_quantity]" id="variant_stock_<?php echo $index; ?>" class="form-control" value="<?php echo htmlspecialchars($variant['stock_quantity']); ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <button type="button" class="btn btn-secondary" onclick="addVariant()">Add Another Variant</button>
                    <br><br>

                    <button type="submit" class="btn btn-primary btn-block">Update Product</button>
                    <a href="products_Management.php" class="btn btn-danger btn-block">Cancel</a>
                </form>
            </main>
        </div>
    </div>

    <script>
        let variantIndex = <?php echo count($variants); ?>;

        function addVariant() {
            const container = document.getElementById('variants_container');
            const newVariant = `
                <div class="variant-group form-group">
                    <label for="variant_size_${variantIndex}">Size:</label>
                    <input type="text" name="variant[${variantIndex}][size]" id="variant_size_${variantIndex}" class="form-control">

                    <label for="variant_color_${variantIndex}">Color:</label>
                    <input type="text" name="variant[${variantIndex}][color]" id="variant_color_${variantIndex}" class="form-control">

                    <label for="variant_stock_${variantIndex}">Stock Quantity:</label>
                    <input type="text" name="variant[${variantIndex}][stock_quantity]" id="variant_stock_${variantIndex}" class="form-control">
                </div>`;
            container.insertAdjacentHTML('beforeend', newVariant);
            variantIndex++;
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
