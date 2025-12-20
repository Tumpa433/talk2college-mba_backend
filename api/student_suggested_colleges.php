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

$auth = $conn->prepare("SELECT * FROM students WHERE mobile=? AND token=? LIMIT 1");
$auth->bind_param("ss", $mobile, $token);
$auth->execute();
$res = $auth->get_result();

if ($res->num_rows == 0) {
    echo json_encode(["success"=>false,"message"=>"Unauthorized"]);
    exit;
}

$student = $res->fetch_assoc();

$city = $student['city']; 
$course = $student['preferred_course'];
$budget = intval($student['budget'] ?? 0);

$sql = "
SELECT * FROM colleges 
WHERE 
    (location LIKE ? OR ? = '') AND
    (course LIKE ? OR specialization LIKE ? OR ? = '') AND
    (fees <= ? OR ? = 0)
ORDER BY rating DESC
LIMIT 20
";

$stmt = $conn->prepare($sql);

$likeCity = "%$city%";
$likeCourse = "%$course%";

$stmt->bind_param("ssssiii", 
    $likeCity, $city,
    $likeCourse, $likeCourse, $course,
    $budget, $budget
);

$stmt->execute();
$res2 = $stmt->get_result();

$data = [];
while ($row = $res2->fetch_assoc()) $data[] = $row;

echo json_encode(["success"=>true, "colleges"=>$data]);
?>
