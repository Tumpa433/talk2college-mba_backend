<?php
// admin_get_colleges.php
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
    $city = $_GET['city'] ?? '';
    $exam = $_GET['exam'] ?? '';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;
    
    $whereConditions = [];
    $params = [];
    $types = "";
    
    // Build WHERE clause
    if (!empty($search)) {
        $whereConditions[] = "(name LIKE ? OR course LIKE ? OR specialization LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "sss";
    }
    
    if (!empty($city) && $city !== 'all') {
        $whereConditions[] = "city = ?";
        $params[] = $city;
        $types .= "s";
    }
    
    if (!empty($exam) && $exam !== 'all') {
        $whereConditions[] = "exam LIKE ?";
        $examParam = "%$exam%";
        $params[] = $examParam;
        $types .= "s";
    }
    
    $whereClause = $whereConditions ? "WHERE " . implode(" AND ", $whereConditions) : "";
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM colleges $whereClause";
    $countStmt = $conn->prepare($countSql);
    if ($params) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $countResult = $countStmt->get_result()->fetch_assoc();
    $total = $countResult['total'];
    
    // Get colleges with pagination
    $sql = "
        SELECT 
            id,
            name,
            city,
            state,
            course,
            fees,
            exam,
            rating,
            placement,
            placement_avg,
            type,
            salary,
            status,
            created_at
        FROM colleges
        $whereClause
        ORDER BY 
            CASE status 
                WHEN 'Active' THEN 1
                ELSE 2
            END,
            name ASC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $colleges = [];
    while ($row = $result->fetch_assoc()) {
        $colleges[] = $row;
    }
    
    // Get unique cities for filter
    $citiesResult = $conn->query("SELECT DISTINCT city FROM colleges WHERE city IS NOT NULL ORDER BY city");
    $cities = [];
    while ($cityRow = $citiesResult->fetch_assoc()) {
        $cities[] = $cityRow['city'];
    }
    
    echo json_encode([
        "success" => true,
        "data" => $colleges,
        "filters" => [
            "cities" => $cities
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