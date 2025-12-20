<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") { exit; }

require "../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

$mobile = $data['mobile'] ?? '';
$token = $data['token'] ?? '';
$saved_id = intval($data['saved_id'] ?? 0);

if (!$mobile || !$token || !$saved_id) {
    echo json_encode(["success"=>false,"message"=>"Missing parameters"]);
    exit;
}

$auth = $conn->prepare("SELECT id FROM students WHERE mobile=? AND token=? LIMIT 1");
$auth->bind_param("ss", $mobile, $token);
$auth->execute();
$res = $auth->get_result();

if ($res->num_rows === 0) {
    echo json_encode(["success"=>false, "message"=>"Unauthorized"]);
    exit;
}

$student_id = $res->fetch_assoc()['id'];

$del = $conn->prepare("DELETE FROM saved_colleges WHERE id=? AND student_id=?");
$del->bind_param("ii", $saved_id, $student_id);

$done = $del->execute();

echo json_encode([
    "success" => $done,
    "message" => $done ? "Removed" : "Failed to remove"
]);
?>
