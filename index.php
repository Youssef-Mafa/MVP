<?php 
session_start(); // Start the session to access session variables
include 'db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - My Website</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>

    <!-- Navbar -->
    <?php include 'navbar.php'; ?>

    <!-- Carousel -->
    <div id="carouselExampleIndicators" class="carousel slide" data-ride="carousel">
        <ol class="carousel-indicators">
            <li data-target="#carouselExampleIndicators" data-slide-to="0" class="active"></li>
            <li data-target="#carouselExampleIndicators" data-slide-to="1"></li>
            <li data-target="#carouselExampleIndicators" data-slide-to="2"></li>
        </ol>
        <div class="carousel-inner">
            <div class="carousel-item active">
                <img src="images/slide1.jpg" class="d-block w-100" alt="Slide 1">
                <div class="carousel-caption d-none d-md-block">
                    <h5>First Slide</h5>
                    <p>Description for first slide.</p>
                </div>
            </div>
            <div class="carousel-item">
                <img src="images/slide2.jpg" class="d-block w-100" alt="Slide 2">
                <div class="carousel-caption d-none d-md-block">
                    <h5>Second Slide</h5>
                    <p>Description for second slide.</p>
                </div>
            </div>
            <div class="carousel-item">
                <img src="images/slide3.jpg" class="d-block w-100" alt="Slide 3">
                <div class="carousel-caption d-none d-md-block">
                    <h5>Third Slide</h5>
                    <p>Description for third slide.</p>
                </div>
            </div>
        </div>
        <a class="carousel-control-prev" href="#carouselExampleIndicators" role="button" data-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="sr-only">Previous</span>
        </a>
        <a class="carousel-control-next" href="#carouselExampleIndicators" role="button" data-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="sr-only">Next</span>
        </a>
    </div>

    <!-- Latest 4 Products -->
    <div class="container my-5">
        <h2>Latest Products</h2>
        <div class="row">
            <?php
            $stmt = $con->prepare("SELECT product_id, name, image_url, price FROM product ORDER BY product_id DESC LIMIT 4");
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($products as $product) {
                echo '<div class="col-md-3">';
                echo '<div class="card">';
                echo '<img src="' . $product['image_url'] . '" class="card-img-top" alt="' . $product['name'] . '">';
                echo '<div class="card-body">';
                echo '<h5 class="card-title">' . $product['name'] . '</h5>';
                echo '<p class="card-text">$' . number_format($product['price'], 2) . '</p>';
                echo '<a href="product_detail.php?product_id=' . $product['product_id'] . '" class="btn btn-primary">View Product</a>';
                echo '</div></div></div>';
            }
            ?>
        </div>
    </div>

    <!-- Featured Products (Last 2 Products) -->
    <div class="container my-5">
        <h2>Featured Products</h2>
        <div class="row">
            <?php
            $stmt = $con->prepare("SELECT product_id, name, image_url, price FROM product ORDER BY product_id DESC LIMIT 2");
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($products as $product) {
                echo '<div class="col-md-6">';
                echo '<div class="card">';
                echo '<img src="' . $product['image_url'] . '" class="card-img-top" alt="' . $product['name'] . '">';
                echo '<div class="card-body">';
                echo '<h5 class="card-title">' . $product['name'] . '</h5>';
                echo '<p class="card-text">$' . number_format($product['price'], 2) . '</p>';
                echo '<a href="product_detail.php?product_id=' . $product['product_id'] . '" class="btn btn-primary">View Product</a>';
                echo '</div></div></div>';
            }
            ?>
        </div>
    </div>

    <!-- YouTube Video -->
    <div class="container my-5">
        <h2>Our Latest Video</h2>
        <div class="embed-responsive embed-responsive-16by9">
            <iframe class="embed-responsive-item" src="https://www.youtube.com/embed/your_video_id" allowfullscreen></iframe>
        </div>
    </div>

    <!-- Mini About Us -->
    <div class="container my-5">
        <h2>About Us</h2>
        <p>Welcome to our website! We offer a wide range of products designed to meet your needs. Our commitment to quality and customer satisfaction sets us apart. Explore our latest offerings and stay connected with us for updates.</p>
    </div>

    <!-- Footer (Optional) -->
    <footer class="text-center py-4">
        <p>&copy; <?php echo date('Y'); ?> My Website. All Rights Reserved.</p>
    </footer>

    <!-- Bootstrap and jQuery JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
