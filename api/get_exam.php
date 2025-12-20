<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$conn = new mysqli("localhost", "root", "", "talk2college-mba_db");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "DB Connection Failed"]);
    exit;
}

$slug = $_GET["slug"] ?? "";
if ($slug === "") {
    echo json_encode(["success" => false, "message" => "Slug missing"]);
    exit;
}

/* =========================
   EXAM MASTER
========================= */
$stmt = $conn->prepare("SELECT id, name, slug FROM exams WHERE slug = ?");
$stmt->bind_param("s", $slug);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Exam not found"]);
    exit;
}

$exam = $res->fetch_assoc();
$exam_id = $exam["id"];

/* =========================
   IMPORTANT EVENTS
========================= */
$events = [];
$q = $conn->prepare("
    SELECT event_label, event_date
    FROM exam_events
    WHERE exam_id = ?
    ORDER BY event_date ASC
");
$q->bind_param("i", $exam_id);
$q->execute();
$r = $q->get_result();
while ($row = $r->fetch_assoc()) $events[] = $row;

/* =========================
   FEES
========================= */
$fees = [];
$q = $conn->prepare("
    SELECT fee_label, amount
    FROM exam_fees
    WHERE exam_id = ?
");
$q->bind_param("i", $exam_id);
$q->execute();
$r = $q->get_result();
while ($row = $r->fetch_assoc()) $fees[] = $row;

/* =========================
   CUTOFFS
========================= */
$cutoffs = [];
$q = $conn->prepare("
    SELECT college_name, cutoff
    FROM exam_cutoffs
    WHERE exam_id = ?
");
$q->bind_param("i", $exam_id);
$q->execute();
$r = $q->get_result();
while ($row = $r->fetch_assoc()) $cutoffs[] = $row;

/* =========================
   QUESTION PAPERS
========================= */
$papers = [];
$q = $conn->prepare("
    SELECT year, slot, paper_title, file_url
    FROM exam_question_papers
    WHERE exam_id = ?
    ORDER BY year DESC
");
$q->bind_param("i", $exam_id);
$q->execute();
$r = $q->get_result();
while ($row = $r->fetch_assoc()) $papers[] = $row;

/* =========================
   NEWS
========================= */
$news = [];
$q = $conn->prepare("
    SELECT id, title, summary, published_date
    FROM exam_news
    WHERE exam_id = ?
    ORDER BY published_date DESC
    LIMIT 5
");
$q->bind_param("i", $exam_id);
$q->execute();
$r = $q->get_result();
while ($row = $r->fetch_assoc()) $news[] = $row;

/* =========================
   FINAL RESPONSE
========================= */
echo json_encode([
    "success" => true,
    "exam" => $exam,
    "events" => $events,
    "fees" => $fees,
    "cutoffs" => $cutoffs,
    "question_papers" => $papers,
    "news" => $news
], JSON_PRETTY_PRINT);

$conn->close();
