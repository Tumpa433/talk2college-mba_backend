<?php
require_once "../config/cors.php";
session_start();
require_once "../config/db.php";

if (!isset($_SESSION["admin_logged_in"])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['lead_id']) || empty($data['counsellor_id'])) {
    echo json_encode(["success" => false, "message" => "Invalid payload"]);
    exit();
}

$leadId = (int)$data['lead_id'];
$counsellorId = (int)$data['counsellor_id'];

/* CHECK COUNSELLOR */
$check = $conn->prepare("SELECT id FROM counsellors WHERE id = ?");
$check->bind_param("i", $counsellorId);
$check->execute();

if ($check->get_result()->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Counsellor not found"]);
    exit();
}

/* ASSIGN REQUEST */
$stmt = $conn->prepare("
    UPDATE counselling_requests
    SET assigned_to = ?, status = 'assigned', updated_at = NOW()
    WHERE id = ?
");
$stmt->bind_param("ii", $counsellorId, $leadId);

echo json_encode([
    "success" => $stmt->execute()
]);

$stmt->close();
$conn->close();
