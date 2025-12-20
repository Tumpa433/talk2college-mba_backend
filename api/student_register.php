<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") { exit; }

require "../config/db.php";

$input = json_decode(file_get_contents("php://input"), true);
if (!$input) { 
    echo json_encode(["success"=>false, "message"=>"Invalid input"]);
    exit;
}

$full_name = trim($input['full_name'] ?? '');
$email = trim($input['email'] ?? '');
$mobile = trim($input['mobile'] ?? '');
$password_raw = $input['password'] ?? '';

if (!$full_name || !$email || !$mobile || !$password_raw) {
    echo json_encode(["success"=>false, "message"=>"Missing required fields"]);
    exit;
}

if (!preg_match('/^[6-9]\d{9}$/', $mobile)) {
    echo json_encode(["success"=>false, "message"=>"Invalid mobile number"]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success"=>false, "message"=>"Invalid email"]);
    exit;
}

// check duplicate
$stmt = $conn->prepare("SELECT id FROM students WHERE email=? OR mobile=? LIMIT 1");
$stmt->bind_param("ss", $email, $mobile);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(["success"=>false,"message"=>"Email or mobile already registered"]);
    exit;
}

$pass_hash = password_hash($password_raw, PASSWORD_DEFAULT);

// optional fields
$gender = $input['gender'] ?? null;
$dob = $input['dob'] ?? null;
$city = $input['city'] ?? null;
$qualification = $input['qualification'] ?? null;
$marks = $input['marks'] ?? null;
$experience = $input['experience'] ?? null;
$preferred_course = $input['preferred_course'] ?? null;
$preferred_city = $input['preferred_city'] ?? null;
$budget = $input['budget'] ?? null;

$insert = $conn->prepare("
INSERT INTO students
(full_name,email,mobile,password,gender,dob,city,qualification,marks,experience,preferred_course,preferred_city,budget)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
");

$insert->bind_param("sssssssssssss",
  $full_name, $email, $mobile, $pass_hash,
  $gender, $dob, $city, $qualification, $marks,
  $experience, $preferred_course, $preferred_city, $budget
);

if ($insert->execute()) {
    echo json_encode(["success"=>true]);
} else {
    echo json_encode(["success"=>false, "message"=>"Database error"]);
}
?>
