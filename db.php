<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "hospital";
$port = 3307; // <-- Add your port number here

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
