<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once dirname(__DIR__) . "/config/db.php";

/* =========================
   READ INPUT SAFELY
========================= */
$input = json_decode(file_get_contents("php://input"), true);

// If JSON empty, fallback to POST
if (!$input) {
    $input = $_POST;
}

$full_name      = trim($input["full_name"] ?? "");
$contact_number = trim($input["contact_number"] ?? "");
$email          = trim($input["email"] ?? "");
$city           = trim($input["city"] ?? "");
$course         = trim($input["course"] ?? "");

/* =========================
   VALIDATION
========================= */
if ($full_name === "" || $contact_number === "" || $email === "") {
    echo json_encode([
        "success" => false,
        "message" => "Full name, contact number and email are required"
    ]);
    exit;
}

/* =========================
   INSERT
========================= */
$stmt = $conn->prepare("
    INSERT INTO counselling_requests 
    (full_name, contact_number, email, city, course, status)
    VALUES (?, ?, ?, ?, ?, 'New')
");

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Prepare failed: " . $conn->error
    ]);
    exit;
}

$stmt->bind_param(
    "sssss",
    $full_name,
    $contact_number,
    $email,
    $city,
    $course
);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Counselling request submitted"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Insert failed",
        "error" => $stmt->error
    ]);
}

$stmt->close();
$conn->close();
