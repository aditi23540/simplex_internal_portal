<?php
// File: db_connection.php
$servername = "localhost"; // Or your DB server IP/hostname
$username = "root";        // Your MySQL username (replace if different)
$password = "";            // Your MySQL password (replace if different)
$dbname = "project_management_db"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    // API scripts including this should handle sending a JSON error response.
    die("Connection failed: " . $conn->connect_error); 
}

// Set charset to utf8mb4 for broader character support
if (!$conn->set_charset("utf8mb4")) {
    error_log("Error loading character set utf8mb4: " . $conn->error);
}
?>