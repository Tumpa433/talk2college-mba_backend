<?php
// admin_delete_exam.php
$allowed_origins = [
    "http://127.0.0.1:5500",
    "http://localhost:5500",
    "http://localhost"
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
$examId = isset($data["id"]) ? (int)$data["id"] : 0;

if ($examId <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid exam ID"]);
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // 1. Delete related records first (in correct order to avoid foreign key constraints)
    
    // Delete exam news
    $stmt = $conn->prepare("DELETE FROM exam_news WHERE exam_id = ?");
    $stmt->bind_param("i", $examId);
    $stmt->execute();
    
    // Delete exam events
    $stmt = $conn->prepare("DELETE FROM exam_events WHERE exam_id = ?");
    $stmt->bind_param("i", $examId);
    $stmt->execute();
    
    // Delete exam fees
    $stmt = $conn->prepare("DELETE FROM exam_fees WHERE exam_id = ?");
    $stmt->bind_param("i", $examId);
    $stmt->execute();
    
    // Delete exam cutoffs
    $stmt = $conn->prepare("DELETE FROM exam_cutoffs WHERE exam_id = ?");
    $stmt->bind_param("i", $examId);
    $stmt->execute();
    
    // Delete exam question papers
    $stmt = $conn->prepare("DELETE FROM exam_question_papers WHERE exam_id = ?");
    $stmt->bind_param("i", $examId);
    $stmt->execute();
    
    // 2. Now delete the main exam record
    $stmt = $conn->prepare("DELETE FROM exams WHERE id = ?");
    $stmt->bind_param("i", $examId);
    $stmt->execute();
    
    // Check if exam was actually deleted
    if ($stmt->affected_rows > 0) {
        $conn->commit();
        echo json_encode([
            "success" => true, 
            "message" => "Exam and all related data deleted successfully"
        ]);
    } else {
        $conn->rollback();
        echo json_encode([
            "success" => false, 
            "message" => "Exam not found or already deleted"
        ]);
    }
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        "success" => false,
        "message" => "Error deleting exam: " . $e->getMessage()
    ]);
}
?>