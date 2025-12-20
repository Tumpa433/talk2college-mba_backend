<?php
// admin_get_counsellor.php
$allowed_origins = [
    "http://127.0.0.1:5500",
    "http://localhost:5500"
];
$origin = $_SERVER["HTTP_ORIGIN"] ?? "";
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
}
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

session_start();
require_once "../config/db.php";

if (!isset($_SESSION["admin_logged_in"])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

if (!isset($_GET["id"])) {
    echo json_encode(["success" => false, "message" => "Missing counsellor ID"]);
    exit();
}

$id = (int)$_GET["id"];

try {
    $stmt = $conn->prepare("
        SELECT 
            id, 
            name, 
            email, 
            phone,
            status,
            created_at
        FROM counsellors 
        WHERE id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Counsellor not found"]);
        exit();
    }
    
    $counsellor = $result->fetch_assoc();
    
    echo json_encode([
        "success" => true,
        "data" => $counsellor
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
?>