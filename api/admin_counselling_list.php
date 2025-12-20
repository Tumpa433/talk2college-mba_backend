<?php
/* ========== CORS + SESSION ========== */
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

if ($_SERVER['REQUEST_METHOD'] === "OPTIONS") exit;

session_set_cookie_params([
    "SameSite" => "Lax",
    "Secure"   => false // OK for localhost
]);

session_start();

/* ========== AUTH CHECK ========== */
if (!isset($_SESSION["admin_logged_in"])) {
    echo json_encode([
        "success" => false,
        "code" => "not_authenticated"
    ]);
    exit;
}

require_once "../config/db.php";

/* ========== FETCH DATA ========== */
try {

    $sql = "
        SELECT
            c.id,
            c.full_name,
            c.contact_number,
            c.email,
            c.city,
            c.course,
            c.status,
            c.created_at,
            c.assigned_to,
            co.name AS counsellor_name
        FROM counselling_requests c
        LEFT JOIN counsellors co ON c.assigned_to = co.id
        ORDER BY c.created_at DESC
    ";

    $result = $conn->query($sql);

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $row["status"] = $row["status"] ?: "New";
        $rows[] = $row;
    }

    echo json_encode([
        "success" => true,
        "data" => $rows
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Server error"
    ]);
}
