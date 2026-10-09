<?php
//db_connect.php
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "erp_indang"; // or your actual database name

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>
