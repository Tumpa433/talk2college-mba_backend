<?php
// Database connection settings
$host = "localhost";
$user = "root";
$pass = "";
$db   = "talk2college-mba_db";

// Create connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die(json_encode([
        "success" => false,
        "message" => "Database connection failed",
        "error" => $conn->connect_error
    ]));
}

// Set charset for emojis / UTF-8 text
$conn->set_charset("utf8mb4");
?>
