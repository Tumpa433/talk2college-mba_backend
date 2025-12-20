<?php
// admin_update_student.php
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
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === "OPTIONS") exit();

session_start();
require_once "../config/db.php";

if (!isset($_SESSION["admin_logged_in"])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

// Validate required fields
if (!isset($data["id"]) || !isset($data["full_name"]) || !isset($data["email"]) || !isset($data["mobile"])) {
    echo json_encode(["success" => false, "message" => "ID, Name, Email, and Mobile are required"]);
    exit();
}

$id = (int)$data["id"];
$full_name = trim($data["full_name"]);
$email = trim($data["email"]);
$mobile = trim($data["mobile"]);
$gender = isset($data["gender"]) ? trim($data["gender"]) : null;
$city = isset($data["city"]) ? trim($data["city"]) : null;
$qualification = isset($data["qualification"]) ? trim($data["qualification"]) : null;
$preferred_course = isset($data["preferred_course"]) ? trim($data["preferred_course"]) : null;
$status = isset($data["status"]) ? trim($data["status"]) : "New";
$exam = isset($data["exam"]) ? trim($data["exam"]) : null;
$notes = isset($data["notes"]) ? trim($data["notes"]) : null;

try {
    // Check if student exists
    $checkStmt = $conn->prepare("SELECT id FROM students WHERE id = ?");
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Student not found"]);
        exit();
    }
    
    // Check if email is already used by another student
    $checkEmail = $conn->prepare("SELECT id FROM students WHERE email = ? AND id != ?");
    $checkEmail->bind_param("si", $email, $id);
    $checkEmail->execute();
    $emailResult = $checkEmail->get_result();
    
    if ($emailResult->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "Email already in use by another student"]);
        exit();
    }
    
    // Update student
    $sql = "UPDATE students SET 
        full_name = ?,
        email = ?,
        mobile = ?,
        gender = ?,
        city = ?,
        qualification = ?,
        preferred_course = ?,
        status = ?,
        exam = ?,
        notes = ?,
        updated_at = NOW()
    WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssssssssssi",
        $full_name,
        $email,
        $mobile,
        $gender,
        $city,
        $qualification,
        $preferred_course,
        $status,
        $exam,
        $notes,
        $id
    );
    
    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Student updated successfully"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Database error: " . $conn->error
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
?>