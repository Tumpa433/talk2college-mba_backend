<?php
// admin_get_counsellors.php
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

try {
    $sql = "SELECT 
                c.id, 
                c.name, 
                c.email, 
                c.phone,
                c.status,
                c.created_at,
                COUNT(cr.id) as assigned_leads
            FROM counsellors c
            LEFT JOIN counselling_requests cr ON c.id = cr.assigned_to
            GROUP BY c.id
            ORDER BY 
                CASE c.status 
                    WHEN 'active' THEN 1
                    WHEN 'on_leave' THEN 2
                    WHEN 'inactive' THEN 3
                    ELSE 4
                END,
                c.name ASC";
    
    $result = $conn->query($sql);
    $counsellors = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $counsellors[] = $row;
        }
    }
    
    echo json_encode([
        "success" => true,
        "data" => $counsellors
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
?>