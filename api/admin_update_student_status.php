<?php
// admin_update_student_status.php
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

if (empty($data['student_id']) || empty($data['status'])) {
    echo json_encode(["success" => false, "message" => "Student ID and status required"]);
    exit();
}

// Update student status
$stmt = $conn->prepare("
    UPDATE students 
    SET status = ?, 
        notes = CONCAT(IFNULL(notes, ''), '\nStatus changed to ', ?, ' on ', NOW(), ': ', ?),
        updated_at = NOW()
    WHERE id = ?
");

$notes = $data['notes'] ?? '';
$stmt->bind_param("sssi", $data['status'], $data['status'], $notes, $data['student_id']);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Student status updated successfully"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Failed to update status: " . $conn->error
    ]);
}

$stmt->close();
$conn->close();
?>