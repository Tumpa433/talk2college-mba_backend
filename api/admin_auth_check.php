<?php
// ---------- CORS ----------
$allowed_origins = [
    "http://127.0.0.1:5500",
    "http://localhost:5500"
];

if (isset($_SERVER["HTTP_ORIGIN"]) && in_array($_SERVER["HTTP_ORIGIN"], $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER["HTTP_ORIGIN"]);
}

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") exit;

// ---------- SESSION ----------
session_set_cookie_params([
    "lifetime" => 0,
    "path" => "/",
    "domain" => "",
    "secure" => false,
    "httponly" => true,
    "samesite" => "Lax"
]);
session_start();

// ---------- AUTH CHECK ----------
if (empty($_SESSION["admin_logged_in"])) {
    echo json_encode(["authenticated" => false]);
    exit;
}

// ---------- SUCCESS ----------
echo json_encode([
    "authenticated" => true,
    "user" => [
        "id" => $_SESSION["admin_id"]
    ]
]);
