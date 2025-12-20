<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") exit;

require "../config/db.php";

// Check if it's FormData (file upload) or JSON (remove request)
$isFormData = isset($_FILES['image']);

if ($isFormData) {
    // Handle image upload (FormData)
    $mobile = $_POST["mobile"] ?? '';
    $token = $_POST["token"] ?? '';
} else {
    // Handle remove request (JSON)
    $input = json_decode(file_get_contents("php://input"), true);
    $mobile = $input["mobile"] ?? '';
    $token = $input["token"] ?? '';
    $remove = $input["remove"] ?? false;
}

if (!$mobile || !$token) {
    echo json_encode(["success"=>false, "message"=>"Unauthorized"]);
    exit;
}

// Validate student
$stmt = $conn->prepare("SELECT id, profile_image FROM students WHERE mobile=? AND token=? LIMIT 1");
$stmt->bind_param("ss", $mobile, $token);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(["success"=>false, "message"=>"Invalid token"]);
    exit;
}

$student = $res->fetch_assoc();
$student_id = $student["id"];

// Handle remove request
if (!$isFormData && $remove) {
    // Delete old image file if exists
    if (!empty($student["profile_image"])) {
        // Extract filename from URL if it's a full URL
        $filename = basename($student["profile_image"]);
        $filepath = "../uploads/students/" . $filename;
        
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }
    
    // Clear profile_image in database
    $update = $conn->prepare("UPDATE students SET profile_image = NULL WHERE id = ?");
    $update->bind_param("i", $student_id);
    
    if ($update->execute()) {
        echo json_encode(["success"=>true, "message"=>"Profile image removed"]);
    } else {
        echo json_encode(["success"=>false, "message"=>"Failed to remove image"]);
    }
    exit;
}

// Handle image upload
if (!$isFormData) {
    echo json_encode(["success"=>false, "message"=>"No image file uploaded"]);
    exit;
}

$img = $_FILES["image"];

$allowed = ["image/jpeg","image/png","image/jpg","image/webp"];
if (!in_array($img["type"], $allowed)) {
    echo json_encode(["success"=>false, "message"=>"Invalid file type. Only JPG, PNG, WEBP allowed"]);
    exit;
}

// Check file size (max 2MB)
if ($img["size"] > 2 * 1024 * 1024) {
    echo json_encode(["success"=>false, "message"=>"File too large. Max 2MB allowed"]);
    exit;
}

// Delete old image file if exists
if (!empty($student["profile_image"])) {
    $filename = basename($student["profile_image"]);
    $filepath = "../uploads/students/" . $filename;
    
    if (file_exists($filepath)) {
        unlink($filepath);
    }
}

// Generate new filename
$ext = pathinfo($img["name"], PATHINFO_EXTENSION);
$filename = "student_" . $student_id . "_" . time() . "." . $ext;
$path = "../uploads/students/" . $filename;

// Ensure uploads directory exists
if (!file_exists("../uploads/students/")) {
    mkdir("../uploads/students/", 0777, true);
}

// Move uploaded file
if (!move_uploaded_file($img["tmp_name"], $path)) {
    echo json_encode(["success"=>false, "message"=>"Failed to save image"]);
    exit;
}

// Save filename in DB (just filename, not full URL)
$update = $conn->prepare("UPDATE students SET profile_image = ? WHERE id = ?");
$update->bind_param("si", $filename, $student_id);

if ($update->execute()) {
    $url = "http://localhost/talk2college-mba_backend/uploads/students/" . $filename;
    echo json_encode([
        "success" => true, 
        "message" => "Profile image updated", 
        "url" => $url,
        "filename" => $filename
    ]);
} else {
    echo json_encode(["success"=>false, "message"=>"Failed to update database"]);
}
?>