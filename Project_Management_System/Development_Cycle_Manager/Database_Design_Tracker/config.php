<?php
// --- Database_Design_Tracker Application Database Configuration ---
// IMPORTANT: Replace with your actual database credentials for the Database_Design_Tracker app
define('CRUD_DB_SERVER', 'localhost');
define('CRUD_DB_USERNAME', 'root');     // Your MySQL username for Database_Design_Tracker app
define('CRUD_DB_PASSWORD', '');         // Your MySQL password for Database_Design_Tracker app
define('CRUD_DB_NAME', 'DevSecManager'); // The database name for Database_Design_Tracker app

// --- Define Upload Directories for Database_Design_Tracker App ---
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('IMAGE_UPLOAD_DIR', UPLOAD_DIR . 'images/');
define('SQL_UPLOAD_DIR', UPLOAD_DIR . 'sql_files/');

/**
 * Ensures upload directories exist and are writable.
 * This function should be called once during application initialization.
 */
function create_crud_upload_dirs() {
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0777, true);
    }
    if (!is_dir(IMAGE_UPLOAD_DIR)) {
        mkdir(IMAGE_UPLOAD_DIR, 0777, true);
    }
    if (!is_dir(SQL_UPLOAD_DIR)) {
        mkdir(SQL_UPLOAD_DIR, 0777, true);
    }
}

// Call the function to create directories when this config file is included
create_crud_upload_dirs();

// --- Database Connection for Database_Design_Tracker App ---
$crud_conn = new mysqli(CRUD_DB_SERVER, CRUD_DB_USERNAME, CRUD_DB_PASSWORD, CRUD_DB_NAME);

// Check Database_Design_Tracker app connection
if ($crud_conn->connect_error) {
    die("Database_Design_Tracker Database Connection failed: " . $crud_conn->connect_error);
}
?>
