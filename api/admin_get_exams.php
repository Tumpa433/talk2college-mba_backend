<?php
// admin_get_exams.php
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
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === "OPTIONS") exit();

session_start();
require_once "../config/db.php";

if (!isset($_SESSION["admin_logged_in"])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

try {
    $sql = "
        SELECT 
            e.id,
            e.name,
            e.slug,
            e.created_at,
            -- Get related data counts
            (SELECT COUNT(*) FROM exam_news WHERE exam_id = e.id) as news_count,
            (SELECT COUNT(*) FROM exam_events WHERE exam_id = e.id) as events_count,
            (SELECT COUNT(*) FROM exam_fees WHERE exam_id = e.id) as fees_count,
            (SELECT COUNT(*) FROM exam_cutoffs WHERE exam_id = e.id) as cutoffs_count,
            (SELECT COUNT(*) FROM exam_question_papers WHERE exam_id = e.id) as papers_count,
            (SELECT COUNT(*) FROM students WHERE exam = e.name) as student_count,
            (SELECT COUNT(*) FROM counselling_requests WHERE course = e.name) as request_count
        FROM exams e
        ORDER BY e.name ASC
    ";
    
    $result = $conn->query($sql);
    $exams = [];
    
    while ($row = $result->fetch_assoc()) {
        $exams[] = $row;
    }
    
    echo json_encode([
        "success" => true,
        "data" => $exams
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
?>