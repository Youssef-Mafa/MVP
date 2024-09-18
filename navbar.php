<?php
// Handle AJAX request for search suggestions
if (isset($_GET['term'])) {
    $term = htmlspecialchars($_GET['term']) . '%';
    $stmt = $con->prepare("SELECT product_id, name, image_url FROM product WHERE name LIKE :term LIMIT 10");
    $stmt->bindParam(':term', $term);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($results);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Website</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .navbar-nav .nav-link {
            margin-right: 15px;
        }
        .search-bar-container {
            position: relative;
        }
        #search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 1000;
            background-color: #fff;
            border: 1px solid #ccc;
            border-radius: 0 0 5px 5px;
            max-height: 300px;
            overflow-y: auto;
        }
        .search-result-item {
            display: flex;
            align-items: center;
            padding: 8px;
        }
        .search-result-item img {
            max-width: 50px;
            margin-right: 10px;
        }
        .search-result-item:hover {
            background-color: #f8f9fa;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="index.php">My Website</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="product_listing.php">Products</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="contact.php">Contact Us</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="about.php">About</a>
                </li>
            </ul>
            <!-- Search Bar -->
            <div class="search-bar-container">
                <div class="input-group">
                    <input type="text" id="product-search" class="form-control" placeholder="Search for products..." autocomplete="off">
                    <div class="input-group-append">
                        <button id="search-button" class="btn btn-primary" type="button">Search</button>
                    </div>
                </div>
                <div id="search-results" class="list-group"></div>
            </div>

            <!-- User Links -->
            <ul class="navbar-nav ml-auto">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">Signup</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- JavaScript to handle search functionality -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#product-search').on('input', function() {
            let searchTerm = $(this).val();
            if (searchTerm.length > 1) {
                $.get('search.php', {term: searchTerm}, function(data) {
                    let results = JSON.parse(data);
                    let resultsContainer = $('#search-results');
                    resultsContainer.empty();

                    results.forEach(function(item) {
                        resultsContainer.append(`
                            <a href="product_detail.php?product_id=${item.product_id}" class="list-group-item list-group-item-action search-result-item">
                                <img src="${item.image_url}" alt="${item.name}">
                                <span>${item.name}</span>
                            </a>
                        `);
                    });
                });
            } else {
                $('#search-results').empty();
            }
        });

        $('#search-button').on('click', function() {
            let searchTerm = $('#product-search').val();
            if (searchTerm.length > 1) {
                $.get('search.php', {term: searchTerm}, function(data) {
                    let results = JSON.parse(data);
                    if (results.length > 0) {
                        window.location.href = 'product_detail.php?product_id=' + results[0].product_id;
                    } else {
                        alert('No products found');
                    }
                });
            }
        });

        $(document).click(function(e) {
            if (!$(e.target).closest('.search-bar-container').length) {
                $('#search-results').empty();
            }
        });
    });
    </script>
</body>
</html>
