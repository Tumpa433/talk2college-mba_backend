<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");
require "../config/db.php";

$id = $_GET['id'] ?? 0;

$sql = "SELECT * FROM colleges WHERE id = $id LIMIT 1";
$res = $conn->query($sql);

if ($res->num_rows == 0) {
    echo json_encode(["success" => false, "message" => "College not found"]);
    exit;
}

$row = $res->fetch_assoc();

// FIX: Make image full URL
$row['image'] = "http://localhost/talk2college-mba_backend/uploads/" . $row['image'];

echo json_encode([
    "success" => true,
    "data" => $row
]);
?>