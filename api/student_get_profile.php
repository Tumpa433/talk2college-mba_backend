<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") { exit; }

require "../config/db.php";

$mobile = $_GET['mobile'] ?? '';
$token  = $_GET['token'] ?? '';

if (!$mobile || !$token) {
    echo json_encode(["success"=>false, "message"=>"Unauthorized"]);
    exit;
}

/* ---------------------------------------
   FETCH STUDENT BASIC PROFILE
----------------------------------------- */
$stmt = $conn->prepare("
SELECT id, full_name, email, mobile, gender, dob, city,
qualification, marks, experience,
preferred_course, preferred_city, budget,
profile_image,
created_at
FROM students
WHERE mobile=? AND token=? 
LIMIT 1
");

$stmt->bind_param("ss", $mobile, $token);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(["success"=>false, "message"=>"Unauthorized"]);
    exit;
}

$student = $res->fetch_assoc();
$student_id = (int)$student['id'];

/* ---------------------------------------
   ADD FULL IMAGE URL OR DEFAULT PLACEHOLDER
----------------------------------------- */
$baseURL = "http://localhost/talk2college-mba_backend/uploads/students/";

if (!empty($student["profile_image"])) {
    // Check if it's already a full URL or just a filename
    if (filter_var($student["profile_image"], FILTER_VALIDATE_URL)) {
        // It's already a full URL
        $student["profile_image_url"] = $student["profile_image"];
    } else {
        // It's just a filename
        $filename = basename($student["profile_image"]);
        $filepath = "../uploads/students/" . $filename;
        
        if (file_exists($filepath)) {
            $student["profile_image_url"] = $baseURL . $filename;
        } else {
            $student["profile_image_url"] = "https://cdn-icons-png.flaticon.com/512/3135/3135715.png";
            $student["profile_image"] = null; // Mark as empty for profile strength calculation
        }
    }
} else {
    $student["profile_image_url"] = "https://cdn-icons-png.flaticon.com/512/3135/3135715.png";
    $student["profile_image"] = null;
}

/* ---------------------------------------
   DASHBOARD COUNTS
----------------------------------------- */

// SAVED COLLEGES
$q1 = $conn->prepare("SELECT COUNT(*) AS c FROM saved_colleges WHERE student_id=?");
$q1->bind_param("i", $student_id);
$q1->execute();
$student['saved_count'] = $q1->get_result()->fetch_assoc()['c'] ?? 0;

// APPLICATIONS
$q2 = $conn->prepare("SELECT COUNT(*) AS c FROM applications WHERE student_id=?");
$q2->bind_param("i", $student_id);
$q2->execute();
$student['applications_count'] = $q2->get_result()->fetch_assoc()['c'] ?? 0;

// COUNSELLING REQUESTS
$q3 = $conn->prepare("SELECT COUNT(*) AS c FROM counselling_requests WHERE contact_number=?");
$q3->bind_param("s", $mobile);
$q3->execute();
$student['counselling_count'] = $q3->get_result()->fetch_assoc()['c'] ?? 0;

/* ---------------------------------------
   CALCULATE PROFILE COMPLETENESS (FIXED)
----------------------------------------- */
$fieldsToCheck = [
    "full_name",
    "email",
    "city",
    "preferred_course",
    "budget",
    "profile_image"  
];

$filled = 0;
$totalFields = count($fieldsToCheck);

foreach ($fieldsToCheck as $field) {
    // Check if field exists and is not empty
    if (!empty($student[$field]) && trim($student[$field]) !== '') {
        $filled++;
    }
}

// Calculate percentage
$percentage = $totalFields > 0 ? round(($filled / $totalFields) * 100) : 0;
$student["profile_strength"] = $percentage;

/* ---------------------------------------
   DEBUG LOG (optional - remove in production)
----------------------------------------- */
error_log("Profile strength calculation for $mobile:");
error_log("Fields checked: " . implode(", ", $fieldsToCheck));
error_log("Filled: $filled out of $totalFields");
error_log("Percentage: $percentage%");
error_log("Field values:");
foreach ($fieldsToCheck as $field) {
    error_log("  $field: " . ($student[$field] ?? 'NULL'));
}

/* ---------------------------------------
   FINAL RESPONSE
----------------------------------------- */
echo json_encode([
    "success" => true,
    "student" => $student
]);
?>