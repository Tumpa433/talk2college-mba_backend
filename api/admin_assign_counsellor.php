<?php
require_once "../config/cors.php";
session_start();
require_once "../config/db.php";

if (!isset($_SESSION["admin_logged_in"])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['student_id']) || empty($data['counsellor_id'])) {
    echo json_encode(["success" => false, "message" => "Student ID and Counsellor ID required"]);
    exit();
}

$studentId = (int)$data['student_id'];
$counsellorId = (int)$data['counsellor_id'];

/* CHECK COUNSELLOR */
$check = $conn->prepare("SELECT id FROM counsellors WHERE id = ?");
$check->bind_param("i", $counsellorId);
$check->execute();

if ($check->get_result()->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Counsellor not found"]);
    exit();
}

/* ASSIGN */
$stmt = $conn->prepare("
    UPDATE students 
    SET assigned_to = ?, updated_at = NOW()
    WHERE id = ?
");
$stmt->bind_param("ii", $counsellorId, $studentId);

echo json_encode([
    "success" => $stmt->execute()
]);

$stmt->close();
$conn->close();
