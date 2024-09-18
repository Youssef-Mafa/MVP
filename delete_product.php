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

// Get product ID from URL
if (!isset($_GET['product_id'])) {
    header('Location: Products_Management.php');
    exit();
}

$product_id = $_GET['product_id'];

// Hide the product and its variants instead of deleting
try {
    $stmt = $con->prepare("UPDATE product SET hide = 1 WHERE product_id = :product_id AND seller_id = :seller_id");
    $stmt->bindParam(':product_id', $product_id);
    $stmt->bindParam(':seller_id', $user_id);
    $stmt->execute();

    $stmt = $con->prepare("UPDATE product_variant SET hide = 1 WHERE product_id = :product_id");
    $stmt->bindParam(':product_id', $product_id);
    $stmt->execute();

    header('Location: Products_Management.php');
    exit();
} catch (PDOException $e) {
    echo "Error hiding product: " . $e->getMessage();
}
?>
