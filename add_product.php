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

// Initialize variables for product and variant
$product_name = $description = $price = $image_url = "";
$variants = []; // To hold multiple variants
$errors = [];
$success_message = "";

// Handle form submission for adding product and variants
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_name = htmlspecialchars(trim($_POST['product_name']));
    $description = htmlspecialchars(trim($_POST['description']));
    $price = trim($_POST['price']);
    $stock_quantity = trim($_POST['stock_quantity']);
    $image_url = htmlspecialchars(trim($_POST['image_url']));
    $seller_id = $_SESSION['user_id']; // Get seller_id from session

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

    // Check if product name already exists
    try {
        $stmt = $con->prepare("SELECT COUNT(*) FROM product WHERE name = :name");
        $stmt->bindParam(':name', $product_name);
        $stmt->execute();
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "A product with this name already exists.";
        }
    } catch (PDOException $e) {
        $errors[] = "Error checking product name: " . $e->getMessage();
    }

    // Validate variants if provided
    if (isset($_POST['variant'])) {
        foreach ($_POST['variant'] as $index => $variant) {
            $size = htmlspecialchars(trim($variant['size']));
            $color = htmlspecialchars(trim($variant['color']));
            $variant_stock = trim($variant['stock_quantity']);

            if (!empty($size) || !empty($color)) {
                if (empty($variant_stock) || !is_numeric($variant_stock)) {
                    $errors[] = "Valid stock quantity is required for variant $index.";
                } else {
                    $variants[] = [
                        'size' => $size,
                        'color' => $color,
                        'stock_quantity' => $variant_stock,
                    ];
                }
            }
        }
    }

    // Insert product if no errors
    if (empty($errors)) {
        try {
            $stmt = $con->prepare("INSERT INTO product (seller_id, name, description, price, stock_quantity, image_url)
                                   VALUES (:seller_id, :name, :description, :price, :stock_quantity, :image_url)");
            $stmt->bindParam(':seller_id', $seller_id);
            $stmt->bindParam(':name', $product_name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':stock_quantity', $stock_quantity);
            $stmt->bindParam(':image_url', $image_url);
            $stmt->execute();

            $product_id = $con->lastInsertId(); // Get the ID of the newly inserted product

            // Insert variants if any
            if (!empty($variants)) {
                $stmt = $con->prepare("INSERT INTO product_variant (product_id, size, color, stock_quantity)
                                       VALUES (:product_id, :size, :color, :stock_quantity)");
                foreach ($variants as $variant) {
                    $stmt->bindParam(':product_id', $product_id);
                    $stmt->bindParam(':size', $variant['size']);
                    $stmt->bindParam(':color', $variant['color']);
                    $stmt->bindParam(':stock_quantity', $variant['stock_quantity']);
                    $stmt->execute();
                }
            }

            $success_message = "Product added successfully!";
        } catch (PDOException $e) {
            $errors[] = "Error adding product: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container">
    <h2>Add Product</h2>

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
            <label for="product_name">Product Name:</label>
            <input type="text" name="product_name" id="product_name" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="description">Description:</label>
            <textarea name="description" id="description" class="form-control"></textarea>
        </div>

        <div class="form-group">
            <label for="price">Price:</label>
            <input type="text" name="price" id="price" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="stock_quantity">Stock Quantity:</label>
            <input type="text" name="stock_quantity" id="stock_quantity" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="image_url">Image URL:</label>
            <input type="text" name="image_url" id="image_url" class="form-control">
        </div>

        <h4>Variants (Optional)</h4>

        <div id="variants_container">
            <div class="variant-group form-group">
                <label for="variant_size_0">Size:</label>
                <input type="text" name="variant[0][size]" id="variant_size_0" class="form-control">

                <label for="variant_color_0">Color:</label>
                <input type="text" name="variant[0][color]" id="variant_color_0" class="form-control">

                <label for="variant_stock_0">Stock Quantity:</label>
                <input type="text" name="variant[0][stock_quantity]" id="variant_stock_0" class="form-control">
            </div>
        </div>

        <button type="button" class="btn btn-secondary" onclick="addVariant()">Add Another Variant</button>
        <br><br>

        <button type="submit" class="btn btn-primary btn-block">Add Product</button>
    </form>
</div>

<script>
    let variantIndex = 1;

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
</body>
</html>
