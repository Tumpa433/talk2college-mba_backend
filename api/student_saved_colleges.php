<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require "../config/db.php";

$mobile = $_GET['mobile'] ?? '';
$token = $_GET['token'] ?? '';

if (!$mobile || !$token) {
    echo json_encode(["success"=>false,"message"=>"Unauthorized"]);
    exit;
}

$auth = $conn->prepare("SELECT id FROM students WHERE mobile=? AND token=? LIMIT 1");
$auth->bind_param("ss", $mobile, $token);
$auth->execute();
$authRes = $auth->get_result();

if ($authRes->num_rows === 0) {
    echo json_encode(["success"=>false,"message"=>"Unauthorized"]);
    exit;
}

$student_id = $authRes->fetch_assoc()['id'];

$sql = "
SELECT sc.id AS saved_id, c.*
FROM saved_colleges sc
JOIN colleges c ON sc.college_id = c.id
WHERE sc.student_id = ?
ORDER BY sc.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$res = $stmt->get_result();

$data = [];
while ($row = $res->fetch_assoc()) $data[] = $row;

echo json_encode(["success"=>true, "saved"=>$data]);
?>
