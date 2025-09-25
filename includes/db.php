<?php
// includes/db.php
// This file should ONLY handle the database connection.

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "car_rental"; // Replace with your actual database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Set charset for proper encoding
$conn->set_charset("utf8");

// Any session_start() or function declarations should NOT be here.
?>