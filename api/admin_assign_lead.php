<?php
// admin_assign_lead.php
$allowed_origins = [
    "http://127.0.0.1:5500",
    "http://localhost:5500"
];
$origin = $_SERVER["HTTP_ORIGIN"] ?? "";
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
}
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === "OPTIONS") exit();

session_start();
require_once "../config/db.php";

if (!isset($_SESSION["admin_logged_in"])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data["lead_id"], $data["counsellor_id"])) {
    echo json_encode(["success" => false, "message" => "Invalid payload"]);
    exit();
}

$leadId = (int)$data["lead_id"];
$counsellorId = (int)$data["counsellor_id"];

try {
    // Check if counsellor exists (no status check since no status column)
    $checkStmt = $conn->prepare("SELECT id FROM counsellors WHERE id = ?");
    $checkStmt->bind_param("i", $counsellorId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Counsellor not found"]);
        exit();
    }
    
    // Check if lead exists
    $checkLead = $conn->prepare("SELECT id FROM counselling_requests WHERE id = ?");
    $checkLead->bind_param("i", $leadId);
    $checkLead->execute();
    $leadResult = $checkLead->get_result();
    
    if ($leadResult->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Lead not found"]);
        exit();
    }
    
    // Update the lead
    $stmt = $conn->prepare("
        UPDATE counselling_requests 
        SET assigned_to = ?, status = 'assigned', updated_at = NOW() 
        WHERE id = ?
    ");
    $stmt->bind_param("ii", $counsellorId, $leadId);
    
    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Counsellor assigned successfully"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Database error: " . $conn->error
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
?>