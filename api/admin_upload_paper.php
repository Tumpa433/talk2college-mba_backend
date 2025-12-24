<?php
$allowed_origins = [
    "http://127.0.0.1:5500",
    "http://localhost:5500"
];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
}

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Content-Type: application/json");

session_start();
require_once "../config/db.php";

if (!isset($_SESSION["admin_logged_in"])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

if (!isset($_FILES["paper_file"])) {
    echo json_encode(["success" => false, "message" => "No file uploaded"]);
    exit();
}

$exam_id     = (int)($_POST["exam_id"] ?? 0);
$year        = (int)($_POST["year"] ?? date("Y"));
$slot        = trim($_POST["slot"] ?? "");
$paper_title = trim($_POST["paper_title"] ?? "");

if ($exam_id <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid exam ID"]);
    exit();
}

/* ================= FILE VALIDATION ================= */
$file = $_FILES["paper_file"];

if ($file["error"] !== UPLOAD_ERR_OK) {
    echo json_encode(["success" => false, "message" => "Upload error"]);
    exit();
}

$ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
if ($ext !== "pdf") {
    echo json_encode(["success" => false, "message" => "Only PDF allowed"]);
    exit();
}

if ($file["size"] > 50 * 1024 * 1024) {
    echo json_encode(["success" => false, "message" => "File too large"]);
    exit();
}

/* ================= SAVE FILE ================= */
$uploadDir = "../../uploads/question-papers/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$filename = "paper_" . uniqid() . ".pdf";
$targetPath = $uploadDir . $filename;

if (!move_uploaded_file($file["tmp_name"], $targetPath)) {
    echo json_encode(["success" => false, "message" => "Failed to save file"]);
    exit();
}

$file_url = "/uploads/question-papers/" . $filename;

/* ================= SAVE TO DB ================= */
$stmt = $conn->prepare("
    INSERT INTO exam_question_papers
    (exam_id, year, slot, paper_title, file_url)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->bind_param("iisss", $exam_id, $year, $slot, $paper_title, $file_url);
$stmt->execute();

echo json_encode([
    "success" => true,
    "file_url" => $file_url
]);
