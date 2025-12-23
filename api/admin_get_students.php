<?php
require_once "../config/cors.php";
session_start();
require_once "../config/db.php";

if (!isset($_SESSION["admin_logged_in"])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

$sql = "
SELECT 
    s.*,
    c.name AS assigned_to_name
FROM students s
LEFT JOIN counsellors c ON s.assigned_to = c.id
WHERE 1=1
";

$params = [];
$types = "";

/* STATUS FILTER */
if (!empty($_GET['status']) && $_GET['status'] !== 'all') {
    $sql .= " AND s.status = ?";
    $params[] = $_GET['status'];
    $types .= "s";
}

/* SEARCH */
if (!empty($_GET['search'])) {
    $search = "%" . $_GET['search'] . "%";
    $sql .= " AND (
        s.full_name LIKE ? OR
        s.email LIKE ? OR
        s.mobile LIKE ? OR
        s.exam LIKE ?
    )";
    for ($i = 0; $i < 4; $i++) {
        $params[] = $search;
        $types .= "s";
    }
}

$sql .= " ORDER BY s.created_at DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();

$result = $stmt->get_result();
$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode([
    "success" => true,
    "data" => $data
]);

$stmt->close();
$conn->close();
