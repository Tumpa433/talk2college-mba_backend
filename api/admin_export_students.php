<?php
// admin_export_students.php
$allowed_origins = [
    "http://127.0.0.1:5500",
    "http://localhost:5500"
];
$origin = $_SERVER["HTTP_ORIGIN"] ?? "";
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
}
header("Access-Control-Allow-Credentials: true");

session_start();
require_once "../config/db.php";

if (!isset($_SESSION["admin_logged_in"])) {
    echo "Unauthorized access";
    exit();
}

// Set headers for Excel file
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=students_" . date('Y-m-d') . ".xls");

// Build query with filters
$sql = "SELECT 
    s.id as 'ID',
    s.full_name as 'Full Name',
    s.email as 'Email',
    s.mobile as 'Mobile',
    s.gender as 'Gender',
    s.dob as 'Date of Birth',
    s.city as 'City',
    s.qualification as 'Qualification',
    s.marks as 'Marks',
    s.experience as 'Experience',
    s.preferred_course as 'Preferred Course',
    s.preferred_city as 'Preferred City',
    s.budget as 'Budget',
    s.exam as 'Exam',
    s.status as 'Status',
    c.name as 'Assigned Counsellor',
    s.source as 'Source',
    DATE(s.created_at) as 'Created Date',
    DATE(s.updated_at) as 'Last Updated'
FROM students s
LEFT JOIN counsellors c ON s.assigned_to = c.id
WHERE 1=1";

$params = [];
$types = "";

// Add filters from query parameters
if (isset($_GET['status']) && $_GET['status'] !== 'all') {
    $sql .= " AND s.status = ?";
    $params[] = $_GET['status'];
    $types .= "s";
}

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = "%" . $_GET['search'] . "%";
    $sql .= " AND (
        s.full_name LIKE ? OR 
        s.email LIKE ? OR 
        s.mobile LIKE ? OR 
        s.exam LIKE ?
    )";
    for ($i = 0; $i < 4; $i++) {
        $params[] = $search;
        $types .= "s";
    }
}

if (isset($_GET['exam']) && $_GET['exam'] !== 'all') {
    $sql .= " AND s.exam = ?";
    $params[] = $_GET['exam'];
    $types .= "s";
}

$sql .= " ORDER BY s.created_at DESC";

// Execute query
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Generate Excel HTML
?>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th { background-color: #4CAF50; color: white; font-weight: bold; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        tr:nth-child(even) { background-color: #f2f2f2; }
    </style>
</head>
<body>
<table>
    <tr>
        <th>ID</th>
        <th>Full Name</th>
        <th>Email</th>
        <th>Mobile</th>
        <th>Gender</th>
        <th>Date of Birth</th>
        <th>City</th>
        <th>Qualification</th>
        <th>Marks</th>
        <th>Experience</th>
        <th>Preferred Course</th>
        <th>Preferred City</th>
        <th>Budget</th>
        <th>Exam</th>
        <th>Status</th>
        <th>Assigned Counsellor</th>
        <th>Source</th>
        <th>Created Date</th>
        <th>Last Updated</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?= $row['ID'] ?></td>
        <td><?= htmlspecialchars($row['Full Name']) ?></td>
        <td><?= htmlspecialchars($row['Email']) ?></td>
        <td style="mso-number-format:'\@'"><?= htmlspecialchars($row['Mobile']) ?></td>
        <td><?= htmlspecialchars($row['Gender']) ?></td>
        <td><?= $row['Date of Birth'] ?></td>
        <td><?= htmlspecialchars($row['City']) ?></td>
        <td><?= htmlspecialchars($row['Qualification']) ?></td>
        <td><?= htmlspecialchars($row['Marks']) ?></td>
        <td><?= htmlspecialchars($row['Experience']) ?></td>
        <td><?= htmlspecialchars($row['Preferred Course']) ?></td>
        <td><?= htmlspecialchars($row['Preferred City']) ?></td>
        <td><?= htmlspecialchars($row['Budget']) ?></td>
        <td><?= htmlspecialchars($row['Exam']) ?></td>
        <td><?= htmlspecialchars($row['Status']) ?></td>
        <td><?= htmlspecialchars($row['Assigned Counsellor']) ?: 'Not Assigned' ?></td>
        <td><?= htmlspecialchars($row['Source']) ?></td>
        <td><?= $row['Created Date'] ?></td>
        <td><?= $row['Last Updated'] ?></td>
    </tr>
    <?php endwhile; ?>
</table>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>