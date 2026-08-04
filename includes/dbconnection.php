<?php
// Set PHP timezone to Sri Lanka Standard Time
date_default_timezone_set('Asia/Colombo');

// Database credentials
$servername = "localhost";
$username   = "root";
$password   = "";
$database   = "techsharkdbv1";

// Open the connection — this halts execution if the server is unreachable
$conn = mysqli_connect($servername, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set the MySQL session timezone to match the PHP timezone
mysqli_query($conn, "SET time_zone = '+05:30'");
