<?php
// admin_save_exam.php - COMPLETE VERSION

header("Content-Type: application/json");
session_start();
require_once "../config/db.php";

// Add CORS headers for your frontend
$allowed_origins = ["http://127.0.0.1:5500", "http://localhost:5500", "http://localhost"];
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
}
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (!isset($_SESSION["admin_logged_in"])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);
$action = $data["action"] ?? "";

try {   

    /* ================= SAVE BASIC ================= */
    if ($action === "save_basic") {

        if (empty($data["id"]) || empty($data["name"])) {
            throw new Exception("Exam ID and name required");
        }

        $stmt = $conn->prepare("
            UPDATE exams 
            SET name=?, slug=?, category=?, level=? 
            WHERE id=?
        ");

        if (!$stmt) throw new Exception($conn->error);

        $slug = $data["slug"] ?: strtolower(str_replace(" ", "-", $data["name"]));

        $stmt->bind_param(
            "ssssi",
            $data["name"],
            $slug,
            $data["category"],
            $data["level"],
            $data["id"]
        );

        $stmt->execute();
        echo json_encode(["success" => true]);
        exit();
    }

    /* ================= SAVE EVENTS ================= */
    if ($action === "save_events") {

        $examId = (int)$data["exam_id"];

        $stmt = $conn->prepare("DELETE FROM exam_events WHERE exam_id=?");
        if (!$stmt) throw new Exception($conn->error);
        $stmt->bind_param("i", $examId);
        $stmt->execute();

        $stmt = $conn->prepare("
            INSERT INTO exam_events (exam_id, event_label, event_date)
            VALUES (?, ?, ?)
        ");
        if (!$stmt) throw new Exception($conn->error);

        foreach ($data["events"] as $e) {
            if ($e["event_label"] && $e["event_date"]) {
                $stmt->bind_param("iss", $examId, $e["event_label"], $e["event_date"]);
                $stmt->execute();
            }
        }

        echo json_encode(["success" => true]);
        exit();
    }

    /* ================= SAVE FEES ================= */
    if ($action === "save_fees") {

        $examId = (int)$data["exam_id"];

        $stmt = $conn->prepare("DELETE FROM exam_fees WHERE exam_id=?");
        if (!$stmt) throw new Exception($conn->error);
        $stmt->bind_param("i", $examId);
        $stmt->execute();

        $stmt = $conn->prepare("
            INSERT INTO exam_fees (exam_id, fee_label, amount)
            VALUES (?, ?, ?)
        ");
        if (!$stmt) throw new Exception($conn->error);

        foreach ($data["fees"] as $f) {
            if ($f["fee_label"] && $f["amount"]) {
                $stmt->bind_param("iss", $examId, $f["fee_label"], $f["amount"]);
                $stmt->execute();
            }
        }

        echo json_encode(["success" => true]);
        exit();
    }

    /* ================= SAVE CUTOFFS ================= */
    if ($action === "save_cutoffs") {

        $examId = (int)$data["exam_id"];

        $stmt = $conn->prepare("DELETE FROM exam_cutoffs WHERE exam_id=?");
        if (!$stmt) throw new Exception($conn->error);
        $stmt->bind_param("i", $examId);
        $stmt->execute();

        $stmt = $conn->prepare("
            INSERT INTO exam_cutoffs (exam_id, college_name, cutoff)
            VALUES (?, ?, ?)
        ");
        if (!$stmt) throw new Exception($conn->error);

        foreach ($data["cutoffs"] as $c) {
            if ($c["college_name"] && $c["cutoff"]) {
                $stmt->bind_param("iss", $examId, $c["college_name"], $c["cutoff"]);
                $stmt->execute();
            }
        }

        echo json_encode(["success" => true]);
        exit();
    }

    /* ================= SAVE PAPERS ================= */
    if ($action === "save_papers") {
        
        $examId = (int)$data["exam_id"];
        
        if (!isset($data["papers"]) || !is_array($data["papers"])) {
            throw new Exception("No papers data provided");
        }
        
        // First, delete all existing papers for this exam
        // This is necessary because we're replacing all papers
        $stmt = $conn->prepare("DELETE FROM exam_question_papers WHERE exam_id = ?");
        if (!$stmt) throw new Exception($conn->error);
        $stmt->bind_param("i", $examId);
        $stmt->execute();
        
        // Prepare insert statement
        $stmt = $conn->prepare("
            INSERT INTO exam_question_papers (exam_id, year, slot, paper_title, file_url, uploaded_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        if (!$stmt) throw new Exception($conn->error);
        
        $inserted_count = 0;
        foreach ($data["papers"] as $paper) {
            // Validate required fields
            if (empty($paper["year"]) || empty($paper["paper_title"]) || empty($paper["file_url"])) {
                // Skip invalid papers but don't fail the whole operation
                error_log("Skipping invalid paper: " . json_encode($paper));
                continue;
            }
            
            $year = (int)$paper["year"];
            $slot = isset($paper["slot"]) ? trim($paper["slot"]) : '';
            $paper_title = trim($paper["paper_title"]);
            $file_url = trim($paper["file_url"]);
            
            // Bind parameters and execute
            $stmt->bind_param("iisss", $examId, $year, $slot, $paper_title, $file_url);
            
            if ($stmt->execute()) {
                $inserted_count++;
            } else {
                error_log("Failed to insert paper: " . $stmt->error);
            }
        }
        
        echo json_encode([
            "success" => true,
            "message" => "Saved {$inserted_count} question paper(s) successfully"
        ]);
        exit();
    }

    // If we get here, the action is not recognized
    throw new Exception("Invalid action: $action");

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>