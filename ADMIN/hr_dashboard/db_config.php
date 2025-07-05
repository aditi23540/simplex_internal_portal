<?php
// Database configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');      // <-- Your database username
define('DB_PASSWORD', '');          // <-- Your database password
define('DB_NAME', 'user_master_db'); // <-- Your database name

// Attempt to connect to MySQL database
try {
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e){
    // Kill the script and show a friendly error message
    die("ERROR: Could not connect to the database. Please check your configuration. " . $e->getMessage());
}
?>