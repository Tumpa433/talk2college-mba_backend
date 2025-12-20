<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") exit;

require "../config/db.php";

$input = json_decode(file_get_contents("php://input"), true);

$mobile   = $input['mobile'] ?? '';
$token    = $input['token'] ?? '';
$current  = $input['current'] ?? '';
$newPass  = $input['new'] ?? '';

if (!$mobile || !$token || !$current || !$newPass) {
    echo json_encode(["success"=>false,"message"=>"Missing fields"]);
    exit;
}

$auth = $conn->prepare("SELECT id,password FROM students WHERE mobile=? AND token=? LIMIT 1");
$auth->bind_param("ss", $mobile, $token);
$auth->execute();
$res = $auth->get_result();

if ($res->num_rows == 0) {
    echo json_encode(["success"=>false,"message"=>"Unauthorized"]);
    exit;
}

$student = $res->fetch_assoc();

if (!password_verify($current, $student['password'])) {
    echo json_encode(["success"=>false,"message"=>"Current password incorrect"]);
    exit;
}

$newHash = password_hash($newPass, PASSWORD_DEFAULT);

$upd = $conn->prepare("UPDATE students SET password=? WHERE id=?");
$upd->bind_param("si", $newHash, $student['id']);

echo json_encode([
    "success" => $upd->execute(),
    "message" => $upd->execute() ? "Password updated" : "Failed to update"
]);
?>
