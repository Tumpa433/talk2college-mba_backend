<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") { exit; }

require "../config/db.php";

$input = json_decode(file_get_contents("php://input"), true);
$mobile = trim($input['mobile'] ?? '');
$password = $input['password'] ?? '';

if (!$mobile || !$password) {
    echo json_encode(["success"=>false,"message"=>"Missing credentials"]);
    exit;
}

$stmt = $conn->prepare("SELECT id, password FROM students WHERE mobile=? LIMIT 1");
$stmt->bind_param("s", $mobile);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(["success"=>false,"message"=>"Account not found"]);
    exit;
}

$user = $res->fetch_assoc();
if (!password_verify($password, $user['password'])) {
    echo json_encode(["success"=>false,"message"=>"Incorrect password"]);
    exit;
}

$token = bin2hex(random_bytes(20));

$upd = $conn->prepare("UPDATE students SET token=? WHERE id=?");
$upd->bind_param("si", $token, $user['id']);
$upd->execute();


echo json_encode(["success"=>true,"mobile"=>$mobile,"token"=>$token, ]);
?>
