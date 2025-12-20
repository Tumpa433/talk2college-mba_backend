<?php
// admin_get_exam_detail.php
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

if (!isset($_GET["id"])) {
    echo json_encode(["success" => false, "message" => "Exam ID required"]);
    exit();
}

$examId = (int)$_GET["id"];

try {
    // Get exam basic info
    $stmt = $conn->prepare("
        SELECT 
            e.id,
            e.name,
            e.slug,
            e.created_at
        FROM exams e
        WHERE e.id = ?
    ");
    $stmt->bind_param("i", $examId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Exam not found"]);
        exit();
    }
    
    $exam = $result->fetch_assoc();
    
    // Get exam events
    $eventsStmt = $conn->prepare("
        SELECT * FROM exam_events 
        WHERE exam_id = ? 
        ORDER BY event_date ASC
    ");
    $eventsStmt->bind_param("i", $examId);
    $eventsStmt->execute();
    $events = $eventsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get exam fees
    $feesStmt = $conn->prepare("
        SELECT * FROM exam_fees 
        WHERE exam_id = ? 
        ORDER BY id ASC
    ");
    $feesStmt->bind_param("i", $examId);
    $feesStmt->execute();
    $fees = $feesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get exam cutoffs
    $cutoffsStmt = $conn->prepare("
        SELECT * FROM exam_cutoffs 
        WHERE exam_id = ? 
        ORDER BY college_name ASC
    ");
    $cutoffsStmt->bind_param("i", $examId);
    $cutoffsStmt->execute();
    $cutoffs = $cutoffsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get exam news
    $newsStmt = $conn->prepare("
        SELECT * FROM exam_news 
        WHERE exam_id = ? 
        ORDER BY published_date DESC
    ");
    $newsStmt->bind_param("i", $examId);
    $newsStmt->execute();
    $news = $newsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get question papers
    $papersStmt = $conn->prepare("
        SELECT * FROM exam_question_papers 
        WHERE exam_id = ? 
        ORDER BY year DESC
    ");
    $papersStmt->bind_param("i", $examId);
    $papersStmt->execute();
    $papers = $papersStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get colleges that accept this exam
    $collegesStmt = $conn->prepare("
        SELECT * FROM colleges 
        WHERE exam LIKE CONCAT('%', ?, '%') 
        OR exam = ?
        ORDER BY name ASC
    ");
    $examName = $exam["name"];
    $collegesStmt->bind_param("ss", $examName, $examName);
    $collegesStmt->execute();
    $colleges = $collegesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        "success" => true,
        "exam" => $exam,
        "events" => $events,
        "fees" => $fees,
        "cutoffs" => $cutoffs,
        "news" => $news,
        "papers" => $papers,
        "colleges" => $colleges
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
?>