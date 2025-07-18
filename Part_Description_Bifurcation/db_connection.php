<?php
// db_connection.php
$host = 'localhost'; 
$dbname = 'simplexinternal'; 
$username = 'root'; 
$password = ''; 
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (\PDOException $e) {
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    echo json_encode(['error' => 'Database connection failed. Please check server configuration. DB Error: ' . $e->getMessage()]);
    exit; 
}
?>