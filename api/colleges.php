<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
require "../config/db.php";

$where = [];

// course filter
if (!empty($_GET["course"])) {
    foreach (explode(",", strtolower($_GET["course"])) as $c) {
        $where[] = "LOWER(course) LIKE '%$c%'";
    }
}

// exam filter
if (!empty($_GET["exam"])) {
    foreach (explode(",", strtolower($_GET["exam"])) as $e) {
        $where[] = "LOWER(exam) LIKE '%$e%'";
    }
}

// location filter
if (!empty($_GET["location"])) {
    foreach (explode(",", strtolower($_GET["location"])) as $loc) {
        $loc = str_replace("-", " ", $loc);
        $where[] = "LOWER(location) LIKE '%$loc%'";
    }
}

// specialization filter (NOW CORRECT)
if (!empty($_GET["specialization"])) {
    foreach (explode(",", strtolower($_GET["specialization"])) as $sp) {
        $where[] = "LOWER(specialization) LIKE '%$sp%'";
    }
}

$whereSQL = count($where) ? "WHERE " . implode(" AND ", $where) : "";

$sql = "SELECT * FROM colleges $whereSQL ORDER BY id DESC";
$res = $conn->query($sql);

$colleges = [];

while ($row = $res->fetch_assoc()) {

    $colleges[] = [
        "id" => $row["id"],
        "name" => $row["name"],
        "city" => $row["location"],
        "type" => $row["type"],
        "rating" => $row["rating"],
        "courses" => $row["course"],
        "exams" => $row["exam"],
        "specialization" => $row["specialization"], // FIXED
        "fees" => $row["fees"],
        "salary" => $row["salary"],
        "logo" => "http://localhost/talk2college-mba_backend/uploads/" . $row["image"],
        "link" => "colleges/" . $row["slug"] . ".html"

    ];
}

echo json_encode([ "success" => true, "data" => $colleges ]);
?>