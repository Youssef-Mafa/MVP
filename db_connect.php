<?php
try {
    $host = 'localhost:3308';
    $dbname = 'ecommerce_project1';
    $username = 'root';
    $password = '';

    $con = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    //echo "Connection Success";
} catch (PDOException $e) { 
    echo "Error in connection: " . $e->getMessage();
}
?>