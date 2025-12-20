<?php
// admin_update_counsellor.php

/* ================= CORS ================= */
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

/* ================= SESSION ================= */
session_start();
require_once "../config/db.php";

if (!isset($_SESSION["admin_logged_in"])) {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized"
    ]);
    exit();
}

/* ================= INPUT ================= */
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data["id"])) {
    echo json_encode([
        "success" => false,
        "message" => "Missing counsellor ID"
    ]);
    exit();
}


$id     = (int)$data["id"];
$name   = trim($data["name"]);
$email  = trim($data["email"]);
$phone  = $data["phone"] ?? null;
$status = $data["status"] ?? "active";

try {

    /* ========= EMAIL UNIQUENESS CHECK ========= */
    $checkStmt = $conn->prepare(
        "SELECT id FROM counsellors WHERE email = ? AND id != ?"
    );
    $checkStmt->bind_param("si", $email, $id);
    $checkStmt->execute();

    if ($checkStmt->get_result()->num_rows > 0) {
        echo json_encode([
            "success" => false,
            "message" => "Email already exists for another counsellor"
        ]);
        exit();
    }

    /* ========= 🔒 DEACTIVATION SAFETY CHECK ========= */
    if ($status === "inactive") {
        $leadCheck = $conn->prepare(
            "SELECT COUNT(*) AS total FROM counselling_requests WHERE assigned_to = ?"
        );
        $leadCheck->bind_param("i", $id);
        $leadCheck->execute();
        $count = $leadCheck->get_result()->fetch_assoc()["total"];

        if ($count > 0) {
            echo json_encode([
                "success" => false,
                "message" => "Cannot deactivate counsellor. Please reassign leads first."
            ]);
            exit();
        }
    }

    /* ========= BUILD UPDATE QUERY ========= */
    $sql = "UPDATE counsellors SET name = ?, email = ?, phone = ?, status = ?";
    $params = [$name, $email, $phone, $status];
    $types  = "ssss";

    if (!empty($data["password"])) {
        $sql .= ", password = ?";
        $params[] = password_hash($data["password"], PASSWORD_DEFAULT);
        $types   .= "s";
    }

    $sql .= " WHERE id = ?";
    $params[] = $id;
    $types   .= "i";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    /* ========= EXECUTE ========= */
    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Counsellor updated successfully"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Database error"
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Server error"
    ]);
}
