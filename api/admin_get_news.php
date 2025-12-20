<?php
// admin_get_news.php
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
    $search = $_GET['search'] ?? '';
    $exam_id = $_GET['exam_id'] ?? '';
    $status = $_GET['status'] ?? '';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;
    
    $whereConditions = [];
    $params = [];
    $types = "";
    
    // Build WHERE clause
    if (!empty($search)) {
        $whereConditions[] = "(n.title LIKE ? OR n.summary LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "ss";
    }
    
    if (!empty($exam_id) && $exam_id !== 'all') {
        $whereConditions[] = "n.exam_id = ?";
        $params[] = $exam_id;
        $types .= "i";
    }
    
    if (!empty($status) && $status !== 'all') {
        $whereConditions[] = "n.status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    $whereClause = $whereConditions ? "WHERE " . implode(" AND ", $whereConditions) : "";
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM exam_news n $whereClause";
    $countStmt = $conn->prepare($countSql);
    if ($params) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $countResult = $countStmt->get_result()->fetch_assoc();
    $total = $countResult['total'];
    
    // Get news with pagination
    $sql = "
        SELECT 
            n.*,
            e.name as exam_name
        FROM exam_news n
        LEFT JOIN exams e ON n.exam_id = e.id
        $whereClause
        ORDER BY 
            CASE n.status 
                WHEN 'Published' THEN 1
                ELSE 2
            END,
            n.published_date DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $news = [];
    while ($row = $result->fetch_assoc()) {
        $news[] = $row;
    }
    
    // Get exams for filter
    $examsResult = $conn->query("SELECT id, name FROM exams ORDER BY name");
    $exams = [];
    while ($examRow = $examsResult->fetch_assoc()) {
        $exams[] = $examRow;
    }
    
    echo json_encode([
        "success" => true,
        "data" => $news,
        "filters" => [
            "exams" => $exams
        ],
        "pagination" => [
            "total" => $total,
            "page" => $page,
            "limit" => $limit,
            "pages" => ceil($total / $limit)
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
?>