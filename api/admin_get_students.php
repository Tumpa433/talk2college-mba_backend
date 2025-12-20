<?php
// admin_get_students.php
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
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === "OPTIONS") exit();

session_start();
require_once "../config/db.php";

if (!isset($_SESSION["admin_logged_in"])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

// Build query
$sql = "SELECT 
    s.id,
    s.full_name,
    s.email,
    s.mobile,
    s.gender,
    s.dob,
    s.city,
    s.qualification,
    s.marks,
    s.experience,
    s.preferred_course,
    s.preferred_city,
    s.budget,
    s.status,
    s.assigned_to,
    s.notes,
    s.created_at,
    s.exam,
    s.source,
    s.updated_at,
    c.name as assigned_to_name
FROM students s
LEFT JOIN counsellors c ON s.assigned_to = c.id
WHERE 1=1";

$params = [];
$types = "";

// Add filters
if (isset($_GET['status']) && $_GET['status'] !== 'all') {
    $sql .= " AND s.status = ?";
    $params[] = $_GET['status'];
    $types .= "s";
}

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = "%" . $_GET['search'] . "%";
    $sql .= " AND (
        s.full_name LIKE ? OR 
        s.email LIKE ? OR 
        s.mobile LIKE ? OR 
        s.exam LIKE ? OR
        s.city LIKE ?
    )";
    for ($i = 0; $i < 5; $i++) {
        $params[] = $search;
        $types .= "s";
    }
}

if (isset($_GET['exam']) && $_GET['exam'] !== 'all') {
    $sql .= " AND s.exam = ?";
    $params[] = $_GET['exam'];
    $types .= "s";
}

$sql .= " ORDER BY s.created_at DESC";

// Execute query
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

echo json_encode([
    "success" => true,
    "data" => $students,
    "count" => count($students)
]);

$stmt->close();
$conn->close();
?>