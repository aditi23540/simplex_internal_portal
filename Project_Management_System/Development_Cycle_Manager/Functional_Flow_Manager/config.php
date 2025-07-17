<?php
// Database Configuration for Functional Flow Designer Application
// These credentials should match your MySQL setup.
$db_host = "localhost";
$db_user = "root"; 
$db_pass = "";     
$db_name = "devsecmanager"; // Assuming your 'functional_flow_tracker' table is in this database

// Create database connection for Functional Flow operations
$crud_conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($crud_conn->connect_error) {
    // It's crucial to handle connection errors gracefully in a production environment.
    // For development, 'die' is acceptable, but consider logging the error and showing a user-friendly message.
    die("Functional Flow Database connection failed: " . $crud_conn->connect_error);
}

// Define upload directory for Functional Flow Files for THIS project
// Ensure this directory exists and is writable by your web server.
define('FUNCTIONAL_FLOW_UPLOAD_DIR', 'uploads/functional_flow_files/');

// Ensure the upload directory exists and is writable
if (!is_dir(FUNCTIONAL_FLOW_UPLOAD_DIR)) {
    mkdir(FUNCTIONAL_FLOW_UPLOAD_DIR, 0777, true);
}
?>
