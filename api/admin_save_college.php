<?php
// admin_save_college.php
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
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === "OPTIONS") exit();

session_start();
require_once "../config/db.php";

if (!isset($_SESSION["admin_logged_in"])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data["name"]) || empty($data["name"])) {
    echo json_encode(["success" => false, "message" => "College name is required"]);
    exit();
}

try {
    // Generate slug
    $slug = strtolower(str_replace(' ', '-', $data["name"]));
    
    if (isset($data["id"]) && !empty($data["id"])) {
        // Update existing college
        $stmt = $conn->prepare("
            UPDATE colleges 
            SET name = ?, location = ?, city = ?, state = ?, course = ?, 
                fees = ?, exam = ?, rating = ?, placement = ?, placement_avg = ?,
                type = ?, salary = ?, slug = ?, specialization = ?, status = ?
            WHERE id = ?
        ");
        
        $placement_avg = !empty($data["placement_avg"]) ? (float)$data["placement_avg"] : null;
        
        $stmt->bind_param("sssssssssssssssi", 
            $data["name"],
            $data["location"] ?? "",
            $data["city"] ?? "",
            $data["state"] ?? "",
            $data["course"] ?? "",
            $data["fees"] ?? "",
            $data["exam"] ?? "",
            $data["rating"] ?? "",
            $data["placement"] ?? "",
            $placement_avg,
            $data["type"] ?? "Private",
            $data["salary"] ?? "",
            $slug,
            $data["specialization"] ?? "",
            $data["status"] ?? "Active",
            $data["id"]
        );
        
        $message = "College updated successfully";
    } else {
        // Insert new college
        $stmt = $conn->prepare("
            INSERT INTO colleges (
                name, location, city, state, course, fees, exam, rating, 
                placement, placement_avg, type, salary, slug, specialization, status
            ) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $placement_avg = !empty($data["placement_avg"]) ? (float)$data["placement_avg"] : null;
        
        $stmt->bind_param("sssssssssssssss", 
            $data["name"],
            $data["location"] ?? "",
            $data["city"] ?? "",
            $data["state"] ?? "",
            $data["course"] ?? "",
            $data["fees"] ?? "",
            $data["exam"] ?? "",
            $data["rating"] ?? "",
            $data["placement"] ?? "",
            $placement_avg,
            $data["type"] ?? "Private",
            $data["salary"] ?? "",
            $slug,
            $data["specialization"] ?? "",
            $data["status"] ?? "Active"
        );
        
        $message = "College added successfully";
    }
    
    if ($stmt->execute()) {
        $collegeId = isset($data["id"]) ? $data["id"] : $conn->insert_id;
        
        // Handle image upload if provided
        if (isset($data["image_base64"]) && !empty($data["image_base64"])) {
            $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $data["image_base64"]));
            $imageName = 'college-' . $collegeId . '-' . time() . '.jpg';
            $imagePath = '../uploads/colleges/' . $imageName;
            
            // Ensure directory exists
            if (!is_dir('../uploads/colleges')) {
                mkdir('../uploads/colleges', 0777, true);
            }
            
            if (file_put_contents($imagePath, $imageData)) {
                // Update college with image path
                $updateImage = $conn->prepare("UPDATE colleges SET image = ? WHERE id = ?");
                $updateImage->bind_param("si", $imageName, $collegeId);
                $updateImage->execute();
            }
        }
        
        echo json_encode([
            "success" => true,
            "message" => $message,
            "id" => $collegeId
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Database error: " . $conn->error
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
?>