<?php
session_start(); // Start the session to access session variables
include 'db_connect.php';

try {
    // Query to retrieve all products
    $stmt = $con->prepare("SELECT p.product_id, p.name, p.description, p.price, p.stock_quantity, p.image_url, u.username as seller_name 
                           FROM product p
                           JOIN user_table u ON p.seller_id = u.user_id");
    $stmt->execute();

    // Fetch all products
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Listing</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<?php include 'navbar.php'; ?>
    <div class="container">
        <h2 class="my-4">Product Listing</h2>
        <div class="row">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" class="card-img-top" alt="Product Image">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars($product['description']); ?></p>
                                <p class="card-text"><strong>Price:</strong> $<?php echo number_format($product['price'], 2); ?></p>
                                <p class="card-text"><strong>Stock:</strong> <?php echo htmlspecialchars($product['stock_quantity']); ?></p>
                                <p class="card-text"><strong>Seller:</strong> <?php echo htmlspecialchars($product['seller_name']); ?></p>
                                <a href="product_detail.php?product_id=<?php echo $product['product_id']; ?>" class="btn btn-primary">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No products available at the moment.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
