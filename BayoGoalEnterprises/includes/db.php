
<?php
/*
|--------------------------------------------------------------------------
| GLOBAL ZONE SPORT ENTERPRISE
| Database Connection
|--------------------------------------------------------------------------
*/

$host = "localhost";
$dbname = "global_zone_sport";
$username = "root";
$password = ""; // Default XAMPP password is usually empty.

$charset = "utf8mb4";

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());

    http_response_code(500);
    exit("Sorry, the website could not connect to its database. Please try again later.");
}
?>