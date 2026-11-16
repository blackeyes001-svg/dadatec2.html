\<?php

$host = "localhost";
$dbname = "dinenow_db";
$username = "root";
$password = "";

try {
&#x20;   $pdo = new PDO(
&#x20;       "mysql\:host=$host;dbname=$dbname;charset=utf8mb4",
&#x20;       $username,
&#x20;       $password
&#x20;   );

&#x20;   $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
&#x20;   $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
&#x20;   $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

} catch (PDOException $e) {
&#x20;   die("Database connection failed. Please check your database settings.");
}

?>
