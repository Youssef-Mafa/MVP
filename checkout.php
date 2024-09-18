<?php
session_start();
include 'db_connect.php';
require 'vendor/autoload.php'; // Autoload PHPMailer using Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user information
try {
    $stmt = $con->prepare("SELECT full_name, phone_number, address FROM user_table WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching user information: " . $e->getMessage();
    exit();
}

// Fetch cart items
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

    // Calculate total amount
    $total_amount = 0;
    foreach ($cart_items as $item) {
        $total_amount += $item['price'] * $item['quantity'];
    }
} catch (PDOException $e) {
    echo "Error fetching cart items: " . $e->getMessage();
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Begin transaction
        $con->beginTransaction();

        // Insert order
        $stmt = $con->prepare("
            INSERT INTO order_table (user_id, order_date, status, total_amount) 
            VALUES (:user_id, NOW(), 'Pending', :total_amount)
        ");
        $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':total_amount', $total_amount, PDO::PARAM_STR);
        $stmt->execute();
        $order_id = $con->lastInsertId();

        // Insert order items
        $stmt = $con->prepare("
            INSERT INTO order_item (order_id, product_id, variant_id, quantity, unit_price) 
            VALUES (:order_id, :product_id, :variant_id, :quantity, :unit_price)
        ");
        foreach ($cart_items as $item) {
            $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            $stmt->bindParam(':product_id', $item['product_id'], PDO::PARAM_INT);
            $stmt->bindParam(':variant_id', $item['variant_id'], PDO::PARAM_INT);
            $stmt->bindParam(':quantity', $item['quantity'], PDO::PARAM_INT);
            $stmt->bindParam(':unit_price', $item['price'], PDO::PARAM_STR);
            $stmt->execute();
        }

        // Insert payment with COD
        $stmt = $con->prepare("
            INSERT INTO payment (order_id, amount, payment_method, status, payment_date) 
            VALUES (:order_id, :amount, 'COD', 'Pending', NOW())
        ");
        $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
        $stmt->bindParam(':amount', $total_amount, PDO::PARAM_STR);
        $stmt->execute();

        // Clear the user's cart
        $stmt = $con->prepare("
            DELETE FROM cart_item WHERE cart_id = (
                SELECT cart_id FROM cart WHERE user_id = :user_id
            )
        ");
        $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();

        // Commit transaction
        $con->commit();

        // Send email notification
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'youssefmafanze@gmail.com'; // Replace with your Gmail address
            $mail->Password = 'alvh qykc bjoz ryyn'; // Replace with your app password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;

            // Recipients
            $mail->setFrom('youssefmafanze@gmail.com', 'Youssef'); // Replace with your Gmail address and name
            $mail->addAddress('hazimoarad7@gmail.com', 'Hazim'); // Replace with recipient's email and name

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'New Order Placed';
            $mail->Body    = 'A new order has been placed by ' . htmlspecialchars($user_info['full_name']) . ".<br>";
            $mail->Body   .= 'Total Amount: $' . number_format($total_amount, 2) . "<br><br>";
            $mail->Body   .= "Order Details:<br>";
            foreach ($cart_items as $item) {
                $mail->Body .= '- ' . htmlspecialchars($item['name']) . ' (Quantity: ' . htmlspecialchars($item['quantity']) . ')';
                if ($item['variant_id']) {
                    $mail->Body .= ' [Size: ' . htmlspecialchars($item['size']) . ', Color: ' . htmlspecialchars($item['color']) . ']';
                }
                $mail->Body .= "<br>";
            }

            $mail->send();
        } catch (Exception $e) {
            // You might want to log this error or handle it differently in production
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }

        // Redirect to thank you page
        header("Location: thank_you.php");
        exit();
    } catch (PDOException $e) {
        $con->rollBack();
        echo "Error processing order: " . $e->getMessage();
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container">
    <h1>Checkout</h1>

    <h3>Shipping Information</h3>
    <p><strong>Name:</strong> <?php echo htmlspecialchars($user_info['full_name']); ?></p>
    <p><strong>Phone:</strong> <?php echo htmlspecialchars($user_info['phone_number']); ?></p>
    <p><strong>Address:</strong> <?php echo htmlspecialchars($user_info['address']); ?></p>

    <h3>Order Summary</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Product Image</th>
                <th>Product Name</th>
                <th>Variant</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cart_items as $item): ?>
                <tr>
                    <td><img src="<?php echo htmlspecialchars($item['image_url']); ?>" class="img-fluid" style="width: 100px;"></td>
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
                    <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                    <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-right"><strong>Total:</strong></td>
                <td><strong>$<?php echo number_format($total_amount, 2); ?></strong></td>
            </tr>
        </tfoot>
    </table>

    <form method="post" action="">
        <button type="submit" class="btn btn-primary">Place Order (Cash on Delivery)</button>
    </form>
</div>
</body>
</html>
