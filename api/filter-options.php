<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
require "../config/db.php";

$exams = [];
$locations = [];
$courses = [];
$specializations = [];

$sql = "SELECT exam, location, course, specialization FROM colleges";
$res = $conn->query($sql);

while ($row = $res->fetch_assoc()) {

    // Exams
    if (!empty($row['exam'])) {
        $exams = array_merge($exams, explode(",", strtolower($row['exam'])));
    }

    // Locations
    if (!empty($row['location'])) {
        $locKey = strtolower(str_replace(" ", "-", trim($row["location"])));
        $locations[] = [
            "value" => $locKey,
            "label" => trim($row["location"])
        ];
    }

    // Courses
    if (!empty($row['course'])) {
        $courses = array_merge($courses, explode(",", strtolower($row['course'])));
    }

    // Specializations (NOW CORRECT)
    if (!empty($row['specialization'])) {
        $specializations = array_merge($specializations, explode(",", strtolower($row['specialization'])));
    }
}

echo json_encode([
    "success" => true,
    "data" => [
        "exams" => array_values(array_unique(array_filter(array_map("trim", $exams)))),
        "locations" => array_values(array_unique($locations, SORT_REGULAR)),
        "courses" => array_values(array_unique(array_filter(array_map("trim", $courses)))),
        "specializations" => array_values(array_unique(array_filter(array_map("trim", $specializations))))
    ]
]);
?>
