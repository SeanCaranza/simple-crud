<?php
$servername = "localhost";
$username = "root";
$password = "";
$databaseName = "logindb";

$connection = new mysqli($servername, $username, $password, $databaseName);

if ($connection->connect_error) {
    die("Database connection failed: " . $connection->connect_error);
}

$connection->set_charset("utf8mb4");
?>