<?php
// /includes/db_config.php

define('DB_SERVER', 'localhost'); // Or your DB server
define('DB_USERNAME', 'root');    // <<< REPLACE with your DB username
define('DB_PASSWORD', '');        // <<< REPLACE with your DB password
define('DB_NAME', 'user_master_db'); // <<< REPLACE with your DB name

// Attempt to connect to MySQL database
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if($link === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

mysqli_set_charset($link, "utf8mb4");
?>