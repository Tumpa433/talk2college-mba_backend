<?php
// admin_add_counsellor.php
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

if (!isset($data["name"], $data["email"], $data["password"])) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit();
}

try {
    // Check if email already exists
    $checkStmt = $conn->prepare("SELECT id FROM counsellors WHERE email = ?");
    $checkStmt->bind_param("s", $data["email"]);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "Email already exists"]);
        exit();
    }
    
    // Hash password
    $hashedPassword = password_hash($data["password"], PASSWORD_DEFAULT);
    
    // Insert counsellor with status
    $stmt = $conn->prepare("
        INSERT INTO counsellors (name, email, phone, status, password) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $phone = isset($data["phone"]) ? $data["phone"] : null;
    $status = isset($data["status"]) ? $data["status"] : "active";
    
    $stmt->bind_param("sssss", 
        $data["name"],
        $data["email"],
        $phone,
        $status,
        $hashedPassword
    );
    
    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Counsellor added successfully",
            "id" => $conn->insert_id
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