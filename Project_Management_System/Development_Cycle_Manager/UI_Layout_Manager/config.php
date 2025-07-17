<?php
// Database Configuration for UI Layout Design Manager Application
// These credentials should match your MySQL setup.
$db_host = "localhost";
$db_user = "root"; 
$db_pass = "";     
$db_name = "devsecmanager"; // Assuming your 'ui_layout_tracker' table is in this database

// Create database connection for UI Layout operations
$crud_conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($crud_conn->connect_error) {
    // It's crucial to handle connection errors gracefully in a production environment.
    // For development, 'die' is acceptable, but consider logging the error and showing a user-friendly message.
    die("UI Layout Database connection failed: " . $crud_conn->connect_error);
}

// Define upload directory for UI Layout Images for THIS project
// Ensure this directory exists and is writable by your web server.
define('UI_LAYOUT_IMAGE_UPLOAD_DIR', 'uploads/ui_layout_images/');

// Ensure the upload directory exists and is writable
if (!is_dir(UI_LAYOUT_IMAGE_UPLOAD_DIR)) {
    mkdir(UI_LAYOUT_IMAGE_UPLOAD_DIR, 0777, true);
}
?>
