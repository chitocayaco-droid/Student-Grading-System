<?php
$host = 'localhost';
$dbname = 'grading_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    session_start();
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>