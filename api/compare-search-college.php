<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");
require "../config/db.php";

$query = $_GET['query'] ?? '';

$sql = "SELECT * FROM colleges 
        WHERE name LIKE '%$query%' 
        OR location LIKE '%$query%' 
        OR course LIKE '%$query%' 
        ORDER BY name ASC
        LIMIT 10";

$res = $conn->query($sql);

$results = [];

while ($row = $res->fetch_assoc()) {
    $results[] = $row;
}

echo json_encode([
    "success" => true,
    "data" => $results
]);
?>
