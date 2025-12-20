<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$conn = new mysqli("localhost", "root", "", "talk2college-mba_db");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "DB error"]);
    exit;
}

$sql = "
  SELECT 
    id,
    title,
    category,
    image_url,
    published_date
  FROM exam_news
  ORDER BY published_date DESC
";

$result = $conn->query($sql);

$news = [];
while ($row = $result->fetch_assoc()) {
    $news[] = $row;
}

echo json_encode([
  "success" => true,
  "news" => $news
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

$conn->close();
