<?php
// admin_add_student.php
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
if (!isset($data["full_name"]) || !isset($data["email"]) || !isset($data["mobile"])) {
    echo json_encode(["success" => false, "message" => "Name, Email, and Mobile are required"]);
    exit();
}

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
    // Check if email already exists
    $checkEmail = $conn->prepare("SELECT id FROM students WHERE email = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $checkResult = $checkEmail->get_result();
    
    if ($checkResult->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "Email already exists"]);
        exit();
    }
    
    // Insert new student
    $sql = "INSERT INTO students (
        full_name, email, mobile, gender, city, qualification, 
        preferred_course, status, exam, notes, created_at, updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssssssssss",
        $full_name,
        $email,
        $mobile,
        $gender,
        $city,
        $qualification,
        $preferred_course,
        $status,
        $exam,
        $notes
    );
    
    if ($stmt->execute()) {
        $studentId = $stmt->insert_id;
        echo json_encode([
            "success" => true,
            "message" => "Student added successfully",
            "student_id" => $studentId
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