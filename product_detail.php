<?php
session_start();
include 'db_connect.php';

// Get the product ID from the URL
if (isset($_GET['product_id'])) {
    $product_id = $_GET['product_id'];

    // Fetch product information
    try {
        $stmt = $con->prepare("SELECT * FROM product WHERE product_id = :product_id");
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            // If product not found, redirect to error404 page
            header('Location: error404.php');
            exit();
        }

        // Fetch variants information
        $variant_stmt = $con->prepare("SELECT * FROM product_variant WHERE product_id = :product_id");
        $variant_stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $variant_stmt->execute();
        $variants = $variant_stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        exit();
    }
} else {
    // Redirect to home or another appropriate page if no product ID is provided
    header('Location: index.php');
    exit();
}

// Handle the form submission to add to cart
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $variant_id = isset($_POST['variant_id']) && $_POST['variant_id'] !== '' ? $_POST['variant_id'] : null;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

    try {
        // Create or get the cart for the user
        $stmt = $con->prepare("SELECT cart_id FROM cart WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cart) {
            // If no cart exists, create one
            $stmt = $con->prepare("INSERT INTO cart (user_id, created_at) VALUES (:user_id, NOW())");
            $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->execute();
            $cart_id = $con->lastInsertId();
        } else {
            $cart_id = $cart['cart_id'];
        }

        // Insert the cart item
        $stmt = $con->prepare("INSERT INTO cart_item (cart_id, product_id, variant_id, quantity) 
                               VALUES (:cart_id, :product_id, :variant_id, :quantity)
                               ON DUPLICATE KEY UPDATE quantity = quantity + :quantity");
        $stmt->bindParam(':cart_id', $cart_id, PDO::PARAM_INT);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        // Handle variant_id being NULL
        if ($variant_id === null) {
            $stmt->bindValue(':variant_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':variant_id', $variant_id, PDO::PARAM_INT);
        }
        $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
        $stmt->execute();

        // Redirect to cart page
        header('Location: cart.php');
        exit();

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Product Details</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container">
    <h1><?php echo htmlspecialchars($product['name']); ?></h1>

    <div class="row">
        <div class="col-md-6">
            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" class="img-fluid" alt="Product Image">
        </div>
        <div class="col-md-6">
            <h3>Price: $<?php echo number_format($product['price'], 2); ?></h3>
            <p><?php echo htmlspecialchars($product['description']); ?></p>
            <p>Stock Quantity: <?php echo htmlspecialchars($product['stock_quantity']); ?></p>

            <?php if (!empty($variants)): ?>
                <h4>Available Variants:</h4>
                <form method="post" action="">
                    <div class="form-group">
                        <label for="variant_id">Select Variant:</label>
                        <select name="variant_id" id="variant_id" class="form-control">
                            <?php foreach ($variants as $variant): ?>
                                <option value="<?php echo $variant['variant_id']; ?>">
                                    Size: <?php echo htmlspecialchars($variant['size']); ?>, 
                                    Color: <?php echo htmlspecialchars($variant['color']); ?>, 
                                    Stock: <?php echo htmlspecialchars($variant['stock_quantity']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="quantity">Quantity:</label>
                        <input type="number" name="quantity" id="quantity" class="form-control" value="1" min="1" max="<?php echo htmlspecialchars($product['stock_quantity']); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Add to Cart</button>
                </form>
            <?php else: ?>
                <form method="post" action="">
                    <div class="form-group">
                        <label for="quantity">Quantity:</label>
                        <input type="number" name="quantity" id="quantity" class="form-control" value="1" min="1" max="<?php echo htmlspecialchars($product['stock_quantity']); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Add to Cart</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <a href="index.php" class="btn btn-secondary">Back to Products</a>
</div>
</body>
</html>
