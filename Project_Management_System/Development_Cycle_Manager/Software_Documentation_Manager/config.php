<?php
// Database Configuration for Software Module Documentation Application
// These credentials should match your MySQL setup.
$db_host = "localhost";
$db_user = "root"; 
$db_pass = "";     
$db_name = "devsecmanager"; // Assuming your 'software_module_tracker' table is in this database

// Create database connection for Software Module Documentation operations
$crud_conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($crud_conn->connect_error) {
    // It's crucial to handle connection errors gracefully in a production environment.
    // For development, 'die' is acceptable, but consider logging the error and showing a user-friendly message.
    die("Software Module Documentation Database connection failed: " . $crud_conn->connect_error);
}

// Define upload directory for Software Module Documents for THIS project
// Ensure this directory exists and is writable by your web server.
define('SOFTWARE_DOC_UPLOAD_DIR', 'uploads/software_doc_files/');

// Ensure the upload directory exists and is writable
if (!is_dir(SOFTWARE_DOC_UPLOAD_DIR)) {
    mkdir(SOFTWARE_DOC_UPLOAD_DIR, 0777, true);
}
?>
