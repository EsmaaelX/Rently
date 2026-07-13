<?php
// includes/db.php
// This file connects to our database

$host = 'localhost';
$dbname = 'rently';
$user = 'root'; // default XAMPP/WAMP user
$pass = '';     // default XAMPP/WAMP password (empty)

try {
    // Create a new PDO connection
    $pdo = new PDO("mysql:host=$host;charset=utf8", $user, $pass);
    
    // Set error mode to exception to help catch errors easily
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database if it doesn't exist and select it
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    $pdo->exec("USE $dbname");

} catch (PDOException $e) {
    // If there is an error, stop everything and show it
    die("Database Connection failed: " . $e->getMessage());
}
?>
