<?php
session_start();
include 'db_connect.php';

// Update quantity or remove item if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update'])) {
        $new_quantity = intval($_POST['quantity']);
        $product_id = intval($_POST['product_id']);
        $variant_id = isset($_POST['variant_id']) ? intval($_POST['variant_id']) : null;

        if ($new_quantity > 0) {
            // Update the quantity
            $stmt = $con->prepare("
                UPDATE cart_item 
                SET quantity = :quantity 
                WHERE cart_id = (
                    SELECT cart_id FROM cart WHERE user_id = :user_id
                ) AND product_id = :product_id AND (variant_id = :variant_id OR (variant_id IS NULL AND :variant_id IS NULL))
            ");
            $stmt->bindParam(':quantity', $new_quantity, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $stmt->bindParam(':variant_id', $variant_id, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            // Remove the item if quantity is zero or less
            $stmt = $con->prepare("
                DELETE FROM cart_item 
                WHERE cart_id = (
                    SELECT cart_id FROM cart WHERE user_id = :user_id
                ) AND product_id = :product_id AND (variant_id = :variant_id OR (variant_id IS NULL AND :variant_id IS NULL))
            ");
            $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $stmt->bindParam(':variant_id', $variant_id, PDO::PARAM_INT);
            $stmt->execute();
        }

        // Redirect to avoid form resubmission
        header("Location: cart.php");
        exit();
    }

    if (isset($_POST['remove'])) {
        $product_id = intval($_POST['product_id']);
        $variant_id = isset($_POST['variant_id']) ? intval($_POST['variant_id']) : null;

        // Remove the item
        $stmt = $con->prepare("
            DELETE FROM cart_item 
            WHERE cart_id = (
                SELECT cart_id FROM cart WHERE user_id = :user_id
            ) AND product_id = :product_id AND (variant_id = :variant_id OR (variant_id IS NULL AND :variant_id IS NULL))
        ");
        $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->bindParam(':variant_id', $variant_id, PDO::PARAM_INT);
        $stmt->execute();

        // Redirect to avoid form resubmission
        header("Location: cart.php");
        exit();
    }
}

// Get the user's cart items
try {
    $stmt = $con->prepare("
        SELECT ci.quantity, ci.variant_id, p.product_id, p.name, p.price, p.image_url, pv.size, pv.color 
        FROM cart_item ci
        JOIN product p ON ci.product_id = p.product_id
        LEFT JOIN product_variant pv ON ci.variant_id = pv.variant_id
        WHERE ci.cart_id = (
            SELECT cart_id FROM cart WHERE user_id = :user_id
        )
    ");
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container">
    <h1>Your Shopping Cart</h1>

    <?php if (!empty($cart_items)): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Product Image</th>
                    <th>Product Name</th>
                    <th>Variant</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Total</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total_price = 0;
                foreach ($cart_items as $item): 
                    $item_total = $item['price'] * $item['quantity'];
                    $total_price += $item_total;
                ?>
                    <tr>
                        <td>
                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" class="img-fluid" alt="Product Image" style="width: 100px;">
                        </td>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td>
                            <?php 
                                if ($item['variant_id']) {
                                    echo 'Size: ' . htmlspecialchars($item['size']) . ', Color: ' . htmlspecialchars($item['color']);
                                } else {
                                    echo 'N/A';
                                }
                            ?>
                        </td>
                        <td>
                            <form method="post" action="">
                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                <input type="hidden" name="variant_id" value="<?php echo $item['variant_id']; ?>">
                                <input type="number" name="quantity" value="<?php echo htmlspecialchars($item['quantity']); ?>" min="0" class="form-control" style="width: 70px;">
                                <button type="submit" name="update" class="btn btn-sm btn-primary mt-2">Update</button>
                            </form>
                        </td>
                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                        <td>$<?php echo number_format($item_total, 2); ?></td>
                        <td>
                            <form method="post" action="">
                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                <input type="hidden" name="variant_id" value="<?php echo $item['variant_id']; ?>">
                                <button type="submit" name="remove" class="btn btn-sm btn-danger">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" class="text-right"><strong>Total:</strong></td>
                    <td colspan="2"><strong>$<?php echo number_format($total_price, 2); ?></strong></td>
                </tr>
            </tfoot>
        </table>
    <?php else: ?>
        <p>Your cart is empty.</p>
    <?php endif; ?>

    <a href="index.php" class="btn btn-secondary">Continue Shopping</a>
    <a href="checkout.php" class="btn btn-primary">Proceed to Checkout</a>
</div>
</body>
</html>
