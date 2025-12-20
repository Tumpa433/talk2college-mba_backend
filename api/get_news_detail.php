<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$conn = new mysqli("localhost", "root", "", "talk2college-mba_db");
if ($conn->connect_error) {
    echo json_encode(["success" => false]);
    exit;
}

$id = intval($_GET["id"] ?? 0);
if (!$id) {
    echo json_encode(["success" => false]);
    exit;
}

$stmt = $conn->prepare("
  SELECT title, full_text, image_url, published_date
  FROM exam_news
  WHERE id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(["success" => false]);
    exit;
}

echo json_encode([
    "success" => true,
    "news" => $res->fetch_assoc()
]);

$conn->close();
