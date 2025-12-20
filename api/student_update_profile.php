<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") exit;

require "../config/db.php";

// Get JSON input
$input = json_decode(file_get_contents("php://input"), true);

// Debug: Log the received data
error_log("Received profile update data: " . print_r($input, true));

$mobile = $input['mobile'] ?? '';
$token  = $input['token'] ?? '';

if (empty($mobile) || empty($token)) {
    error_log("Missing mobile or token. Mobile: $mobile, Token: $token");
    echo json_encode(["success"=>false, "message"=>"Unauthorized: Missing credentials"]);
    exit;
}

// Validate student
$auth = $conn->prepare("SELECT id FROM students WHERE mobile = ? AND token = ? LIMIT 1");
$auth->bind_param("ss", $mobile, $token);
$auth->execute();
$res = $auth->get_result();

if ($res->num_rows == 0) {
    error_log("Invalid token for mobile: $mobile");
    echo json_encode(["success"=>false, "message"=>"Unauthorized: Invalid token or session expired"]);
    exit;
}

$student_id = $res->fetch_assoc()['id'];
error_log("Student ID: $student_id authenticated successfully");

// Prepare update data
$fields = [];
$params = [];
$types = "";

if (isset($input['full_name'])) {
    $fields[] = "full_name = ?";
    $params[] = trim($input['full_name']);
    $types .= "s";
}

if (isset($input['city'])) {
    $fields[] = "city = ?";
    $params[] = trim($input['city']);
    $types .= "s";
}

if (isset($input['preferred_course'])) {
    $fields[] = "preferred_course = ?";
    $params[] = trim($input['preferred_course']);
    $types .= "s";
}

if (isset($input['budget'])) {
    $fields[] = "budget = ?";
    $params[] = trim($input['budget']);
    $types .= "s";
}

if (isset($input['exam_score'])) {
    $fields[] = "marks = ?";
    $params[] = trim($input['exam_score']);
    $types .= "s";
}

// If no fields to update
if (empty($fields)) {
    echo json_encode(["success"=>true, "message"=>"No changes to update"]);
    exit;
}

// Add student_id to params
$params[] = $student_id;
$types .= "i";

// Build SQL query
$sql = "UPDATE students SET " . implode(", ", $fields) . " WHERE id = ?";
error_log("SQL Query: $sql");
error_log("Params: " . print_r($params, true));
error_log("Types: $types");

$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    echo json_encode(["success"=>false, "message"=>"Database error: " . $conn->error]);
    exit;
}

$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    error_log("Profile updated successfully for student ID: $student_id");
    echo json_encode(["success"=>true, "message"=>"Profile updated successfully"]);
} else {
    error_log("Execute failed: " . $stmt->error);
    echo json_encode(["success"=>false, "message"=>"Failed to update profile: " . $stmt->error]);
}

$stmt->close();
?>