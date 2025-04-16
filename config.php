<?php

$host = 'localhost';
$dbname = 'go_kikit';
$username = 'root';
$password =''; 

try {
    // $pdo = new PDO("mysql:host=$host; port=$port; dbname=$dbname", $username, $password);
    $pdo = new PDO("mysql:host=$host;  dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
