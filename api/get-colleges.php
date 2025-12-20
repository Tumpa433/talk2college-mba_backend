<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");
require "../config/db.php";

$sql = "SELECT * FROM colleges ORDER BY name ASC";
$res = $conn->query($sql);

$data = [];

while ($row = $res->fetch_assoc()) {

    // FIX: Make image full URL
    $row['image'] = "http://localhost/talk2college-mba_backend/uploads/" . $row['image'];

    // Now includes placement automatically
    $data[] = $row;
}

echo json_encode([
    "success" => true,
    "data" => $data
]);
?>
