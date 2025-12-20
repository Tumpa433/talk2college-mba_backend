<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") { exit; }

require "../config/db.php";

$input = json_decode(file_get_contents("php://input"), true);

$mobile = $input['mobile'] ?? '';
$token  = $input['token'] ?? '';
$college_id = intval($input['college_id'] ?? 0);

if (!$mobile || !$token || !$college_id) {
    echo json_encode(["success"=>false,"message"=>"Missing data"]);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM students WHERE mobile=? AND token=? LIMIT 1");
$stmt->bind_param("ss", $mobile, $token);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows == 0) {
    echo json_encode(["success"=>false,"message"=>"Unauthorized"]);
    exit;
}

$student_id = $res->fetch_assoc()['id'];

// prevent duplicate
$check = $conn->prepare("SELECT id FROM saved_colleges WHERE student_id=? AND college_id=? LIMIT 1");
$check->bind_param("ii", $student_id, $college_id);
$check->execute();
$checkRes = $check->get_result();

if ($checkRes->num_rows > 0) {
    echo json_encode(["success"=>false,"message"=>"Already saved"]);
    exit;
}

$save = $conn->prepare("INSERT INTO saved_colleges (student_id, college_id) VALUES (?,?)");
$save->bind_param("ii", $student_id, $college_id);

$done = $save->execute();

echo json_encode([
    "success" => $done,
    "message" => $done ? "Saved successfully" : "Could not save"
]);
?>
