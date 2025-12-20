<?php
// admin_save_exam.php
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
$action = $data["action"] ?? "";

try {
    if ($action === "save_basic") {
        // Save basic exam info
        if (!isset($data["name"]) || empty($data["name"])) {
            echo json_encode(["success" => false, "message" => "Exam name is required"]);
            exit();
        }
        
        $slug = strtolower(str_replace(' ', '-', $data["name"]));
        
        if (isset($data["id"]) && !empty($data["id"])) {
            // Update existing exam
            $stmt = $conn->prepare("
                UPDATE exams 
                SET name = ?, slug = ?
                WHERE id = ?
            ");
            $stmt->bind_param("ssi", $data["name"], $slug, $data["id"]);
        } else {
            // Insert new exam
            $stmt = $conn->prepare("
                INSERT INTO exams (name, slug) 
                VALUES (?, ?)
            ");
            $stmt->bind_param("ss", $data["name"], $slug);
        }
        
        if ($stmt->execute()) {
            $examId = isset($data["id"]) ? $data["id"] : $conn->insert_id;
            echo json_encode([
                "success" => true,
                "message" => "Exam saved successfully",
                "id" => $examId
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $conn->error
            ]);
        }
        
    } elseif ($action === "save_events") {
        // Save exam events
        if (!isset($data["exam_id"]) || !isset($data["events"])) {
            echo json_encode(["success" => false, "message" => "Invalid data"]);
            exit();
        }
        
        // Delete existing events
        $deleteStmt = $conn->prepare("DELETE FROM exam_events WHERE exam_id = ?");
        $deleteStmt->bind_param("i", $data["exam_id"]);
        $deleteStmt->execute();
        
        // Insert new events
        $insertStmt = $conn->prepare("
            INSERT INTO exam_events (exam_id, event_label, event_date) 
            VALUES (?, ?, ?)
        ");
        
        $successCount = 0;
        foreach ($data["events"] as $event) {
            if (!empty($event["event_label"]) && !empty($event["event_date"])) {
                $insertStmt->bind_param("iss", 
                    $data["exam_id"],
                    $event["event_label"],
                    $event["event_date"]
                );
                if ($insertStmt->execute()) {
                    $successCount++;
                }
            }
        }
        
        echo json_encode([
            "success" => true,
            "message" => "Saved $successCount events"
        ]);
        
    } elseif ($action === "save_fees") {
        // Save exam fees
        if (!isset($data["exam_id"]) || !isset($data["fees"])) {
            echo json_encode(["success" => false, "message" => "Invalid data"]);
            exit();
        }
        
        // Delete existing fees
        $deleteStmt = $conn->prepare("DELETE FROM exam_fees WHERE exam_id = ?");
        $deleteStmt->bind_param("i", $data["exam_id"]);
        $deleteStmt->execute();
        
        // Insert new fees
        $insertStmt = $conn->prepare("
            INSERT INTO exam_fees (exam_id, fee_label, amount) 
            VALUES (?, ?, ?)
        ");
        
        $successCount = 0;
        foreach ($data["fees"] as $fee) {
            if (!empty($fee["fee_label"]) && !empty($fee["amount"])) {
                $insertStmt->bind_param("iss", 
                    $data["exam_id"],
                    $fee["fee_label"],
                    $fee["amount"]
                );
                if ($insertStmt->execute()) {
                    $successCount++;
                }
            }
        }
        
        echo json_encode([
            "success" => true,
            "message" => "Saved $successCount fee items"
        ]);
        
    } elseif ($action === "save_cutoffs") {
        // Save exam cutoffs
        if (!isset($data["exam_id"]) || !isset($data["cutoffs"])) {
            echo json_encode(["success" => false, "message" => "Invalid data"]);
            exit();
        }
        
        // Delete existing cutoffs
        $deleteStmt = $conn->prepare("DELETE FROM exam_cutoffs WHERE exam_id = ?");
        $deleteStmt->bind_param("i", $data["exam_id"]);
        $deleteStmt->execute();
        
        // Insert new cutoffs
        $insertStmt = $conn->prepare("
            INSERT INTO exam_cutoffs (exam_id, college_name, cutoff) 
            VALUES (?, ?, ?)
        ");
        
        $successCount = 0;
        foreach ($data["cutoffs"] as $cutoff) {
            if (!empty($cutoff["college_name"]) && !empty($cutoff["cutoff"])) {
                $insertStmt->bind_param("iss", 
                    $data["exam_id"],
                    $cutoff["college_name"],
                    $cutoff["cutoff"]
                );
                if ($insertStmt->execute()) {
                    $successCount++;
                }
            }
        }
        
        echo json_encode([
            "success" => true,
            "message" => "Saved $successCount cutoff entries"
        ]);
        
    } elseif ($action === "save_news") {
        // Save exam news
        if (!isset($data["exam_id"]) || !isset($data["news"])) {
            echo json_encode(["success" => false, "message" => "Invalid data"]);
            exit();
        }
        
        $news = $data["news"];
        
        if (isset($news["id"]) && !empty($news["id"])) {
            // Update existing news
            $stmt = $conn->prepare("
                UPDATE exam_news 
                SET title = ?, summary = ?, full_text = ?, 
                    published_date = ?, status = ?, meta_title = ?, meta_description = ?
                WHERE id = ? AND exam_id = ?
            ");
            $stmt->bind_param("sssssssii", 
                $news["title"],
                $news["summary"] ?? "",
                $news["full_text"] ?? "",
                $news["published_date"],
                $news["status"] ?? "Draft",
                $news["meta_title"] ?? "",
                $news["meta_description"] ?? "",
                $news["id"],
                $data["exam_id"]
            );
        } else {
            // Insert new news
            $slug = strtolower(str_replace(' ', '-', $news["title"])) . '-' . time();
            $stmt = $conn->prepare("
                INSERT INTO exam_news (exam_id, title, slug, summary, full_text, published_date, status, meta_title, meta_description) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("issssssss", 
                $data["exam_id"],
                $news["title"],
                $slug,
                $news["summary"] ?? "",
                $news["full_text"] ?? "",
                $news["published_date"],
                $news["status"] ?? "Draft",
                $news["meta_title"] ?? "",
                $news["meta_description"] ?? ""
            );
        }
        
        if ($stmt->execute()) {
            $newsId = isset($news["id"]) ? $news["id"] : $conn->insert_id;
            echo json_encode([
                "success" => true,
                "message" => "News saved successfully",
                "id" => $newsId
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $conn->error
            ]);
        }
        
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Invalid action"
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
?>