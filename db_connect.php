<?php

// Database connection parameters for XAMPP (MySQL)
define('DB_SERVER', 'localhost'); // Usually 'localhost'
define('DB_USERNAME', 'root'); // Default XAMPP username is 'root'
define('DB_PASSWORD', '');     // Default XAMPP password is an empty string
define('DB_NAME', 'tavern_publico'); // Your database name

// Attempt to connect to MySQL database
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($link === false) {
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

// Set the charset to utf8mb4 for proper emoji and special character handling
mysqli_set_charset($link, "utf8mb4");

?>