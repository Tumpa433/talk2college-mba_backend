<?php
// get-leads.php
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
    $status = $_GET['status'] ?? '';
    $counsellor_id = $_GET['counsellor_id'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    
    $whereConditions = [];
    $params = [];
    $types = "";
    
    // Build WHERE clause
    if (!empty($search)) {
        $whereConditions[] = "(s.full_name LIKE ? OR s.mobile LIKE ? OR s.email LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "sss";
    }
    
    if (!empty($status) && $status !== 'all') {
        $whereConditions[] = "l.status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    if (!empty($counsellor_id) && $counsellor_id !== 'all') {
        $whereConditions[] = "l.counsellor_id = ?";
        $params[] = $counsellor_id;
        $types .= "i";
    }
    
    if (!empty($date_from)) {
        $whereConditions[] = "DATE(l.created_at) >= ?";
        $params[] = $date_from;
        $types .= "s";
    }
    
    if (!empty($date_to)) {
        $whereConditions[] = "DATE(l.created_at) <= ?";
        $params[] = $date_to;
        $types .= "s";
    }
    
    $whereClause = $whereConditions ? "WHERE " . implode(" AND ", $whereConditions) : "";
    
    $sql = "
        SELECT 
            l.*,
            s.full_name as student_name,
            s.mobile as student_phone,
            s.email as student_email,
            s.exam,
            s.preferred_course,
            s.city as student_city,
            c.name as counsellor_name,
            cr.full_name as request_name
        FROM leads l
        LEFT JOIN students s ON l.student_id = s.id
        LEFT JOIN counselling_requests cr ON l.counselling_request_id = cr.id
        LEFT JOIN counsellors c ON l.counsellor_id = c.id
        $whereClause
        ORDER BY 
            CASE l.status
                WHEN 'New' THEN 1
                WHEN 'Follow-up Scheduled' THEN 2
                WHEN 'Contacted' THEN 3
                WHEN 'Counselling Done' THEN 4
                WHEN 'Converted' THEN 5
                WHEN 'Lost' THEN 6
                ELSE 7
            END,
            l.next_followup ASC,
            l.created_at DESC
    ";
    
    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $leads = [];
    while ($row = $result->fetch_assoc()) {
        $leads[] = $row;
    }
    
    // Get statistics
    $statsSql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'New' THEN 1 ELSE 0 END) as new_leads,
                    SUM(CASE WHEN status = 'Converted' THEN 1 ELSE 0 END) as converted,
                    SUM(CASE WHEN status = 'Lost' THEN 1 ELSE 0 END) as lost,
                    SUM(conversion_amount) as total_revenue
                 FROM leads";
    $statsResult = $conn->query($statsSql);
    $stats = $statsResult->fetch_assoc();
    
    echo json_encode([
        "success" => true,
        "data" => $leads,
        "stats" => $stats
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
?>