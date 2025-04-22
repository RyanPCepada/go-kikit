<?php

$host = 'localhost';
$dbname = 'go_kikit';
$username = 'root';
$password =''; 

try {
    // Include charset=utf8mb4 here
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // echo "Connection successful!";
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

?>
