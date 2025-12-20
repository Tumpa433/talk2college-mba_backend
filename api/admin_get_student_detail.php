<?php
// admin_get_student_detail.php
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
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === "OPTIONS") exit();

session_start();
require_once "../config/db.php";

if (!isset($_SESSION["admin_logged_in"])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(["success" => false, "message" => "Invalid student ID"]);
    exit();
}

$studentId = (int)$_GET['id'];

// Get student details
$stmt = $conn->prepare("
    SELECT 
        s.*,
        c.name as assigned_to_name
    FROM students s
    LEFT JOIN counsellors c ON s.assigned_to = c.id
    WHERE s.id = ?
");

$stmt->bind_param("i", $studentId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Student not found"]);
    exit();
}

$student = $result->fetch_assoc();

echo json_encode([
    "success" => true,
    "student" => $student
]);

$stmt->close();
$conn->close();
?>