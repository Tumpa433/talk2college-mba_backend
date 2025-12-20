<?php
// admin_assign_counsellor.php (for students)
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

if (empty($data['student_id']) || empty($data['counsellor_id'])) {
    echo json_encode(["success" => false, "message" => "Student ID and counsellor ID required"]);
    exit();
}

// Check if counsellor exists
$checkCounsellor = $conn->prepare("SELECT id FROM counsellors WHERE id = ?");
$checkCounsellor->bind_param("i", $data['counsellor_id']);
$checkCounsellor->execute();
$counsellorResult = $checkCounsellor->get_result();

if ($counsellorResult->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Counsellor not found"]);
    exit();
}

// Update student with counsellor assignment
$stmt = $conn->prepare("
    UPDATE students 
    SET assigned_to = ?, 
        notes = CONCAT(IFNULL(notes, ''), '\nAssigned to counsellor on ', NOW(), ': ', ?),
        updated_at = NOW()
    WHERE id = ?
");

$notes = $data['notes'] ?? 'Assigned to counsellor';
$stmt->bind_param("isi", $data['counsellor_id'], $notes, $data['student_id']);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Counsellor assigned successfully"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Failed to assign counsellor: " . $conn->error
    ]);
}

$stmt->close();
$conn->close();
?>