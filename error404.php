<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<?php include 'search.php'; ?>
    <div class="container">
        <h2>Error</h2>
        <?php
        if (isset($_GET['message']) && $_GET['message'] == 'not_authorized') {
            echo "<p style='color:red;'>You are not authorized to add products. Only sellers can add products.</p>";
        } else {
            echo "<p style='color:red;'>An unexpected error occurred.</p>";
        }
        ?>
        <a href="index.php" class="btn btn-primary">Go to Home</a>
    </div>
</body>
</html>
