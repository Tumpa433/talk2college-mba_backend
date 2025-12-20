<?php
// admin_get_reports.php
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
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === "OPTIONS") exit();

session_start();
require_once "../config/db.php";

if (!isset($_SESSION["admin_logged_in"])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

$reportType = $_GET['type'] ?? 'overview';
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

try {
    switch ($reportType) {
        case 'conversion':
            // Conversion Report
            $report = generateConversionReport($conn, $dateFrom, $dateTo);
            break;
            
        case 'counsellor_performance':
            // Counsellor Performance Report
            $report = generateCounsellorPerformanceReport($conn, $dateFrom, $dateTo);
            break;
            
        case 'exam_analysis':
            // Exam Analysis Report
            $report = generateExamAnalysisReport($conn, $dateFrom, $dateTo);
            break;
            
        case 'college_analysis':
            // College Analysis Report
            $report = generateCollegeAnalysisReport($conn, $dateFrom, $dateTo);
            break;
            
        case 'revenue':
            // Revenue Report
            $report = generateRevenueReport($conn, $dateFrom, $dateTo);
            break;
            
        default:
            // Overview Report
            $report = generateOverviewReport($conn, $dateFrom, $dateTo);
    }
    
    echo json_encode([
        "success" => true,
        "report_type" => $reportType,
        "date_range" => [
            "from" => $dateFrom,
            "to" => $dateTo
        ],
        "data" => $report
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}

// Helper functions for different report types
function generateOverviewReport($conn, $dateFrom, $dateTo) {
    $report = [];
    
    // Student funnel
    $funnelStmt = $conn->prepare("
        SELECT 
            'Total Students' as stage,
            COUNT(*) as count
        FROM students 
        WHERE DATE(created_at) BETWEEN ? AND ?
        
        UNION ALL
        
        SELECT 
            'Counselling Requests' as stage,
            COUNT(*) as count
        FROM counselling_requests 
        WHERE DATE(created_at) BETWEEN ? AND ?
        
        UNION ALL
        
        SELECT 
            'Leads Created' as stage,
            COUNT(*) as count
        FROM leads 
        WHERE DATE(created_at) BETWEEN ? AND ?
        
        UNION ALL
        
        SELECT 
            'Converted Students' as stage,
            COUNT(*) as count
        FROM students 
        WHERE status = 'Converted' 
        AND DATE(created_at) BETWEEN ? AND ?
    ");
    $funnelStmt->bind_param("ssssssss", $dateFrom, $dateTo, $dateFrom, $dateTo, $dateFrom, $dateTo, $dateFrom, $dateTo);
    $funnelStmt->execute();
    $report['funnel'] = $funnelStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Daily trends
    $trendsStmt = $conn->prepare("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as students,
            (SELECT COUNT(*) FROM counselling_requests WHERE DATE(created_at) = d.date) as requests,
            (SELECT COUNT(*) FROM leads WHERE DATE(created_at) = d.date AND status = 'Converted') as conversions
        FROM (
            SELECT DISTINCT DATE(created_at) as created_at 
            FROM students 
            WHERE DATE(created_at) BETWEEN ? AND ?
        ) d
        GROUP BY DATE(created_at)
        ORDER BY date
    ");
    $trendsStmt->bind_param("ss", $dateFrom, $dateTo);
    $trendsStmt->execute();
    $report['daily_trends'] = $trendsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    return $report;
}

function generateConversionReport($conn, $dateFrom, $dateTo) {
    $report = [];
    
    // Conversion by source
    $sourceStmt = $conn->prepare("
        SELECT 
            COALESCE(source, 'Unknown') as source,
            COUNT(*) as total_students,
            SUM(CASE WHEN status = 'Converted' THEN 1 ELSE 0 END) as converted,
            ROUND((SUM(CASE WHEN status = 'Converted' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as conversion_rate
        FROM students 
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY source
        ORDER BY conversion_rate DESC
    ");
    $sourceStmt->bind_param("ss", $dateFrom, $dateTo);
    $sourceStmt->execute();
    $report['by_source'] = $sourceStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Conversion by exam
    $examStmt = $conn->prepare("
        SELECT 
            COALESCE(exam, 'Not Specified') as exam,
            COUNT(*) as total_students,
            SUM(CASE WHEN status = 'Converted' THEN 1 ELSE 0 END) as converted,
            ROUND((SUM(CASE WHEN status = 'Converted' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as conversion_rate
        FROM students 
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY exam
        ORDER BY conversion_rate DESC
    ");
    $examStmt->bind_param("ss", $dateFrom, $dateTo);
    $examStmt->execute();
    $report['by_exam'] = $examStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Monthly conversion trend
    $monthlyStmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as total_students,
            SUM(CASE WHEN status = 'Converted' THEN 1 ELSE 0 END) as converted,
            ROUND((SUM(CASE WHEN status = 'Converted' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as conversion_rate
        FROM students 
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month
    ");
    $monthlyStmt->bind_param("ss", $dateFrom, $dateTo);
    $monthlyStmt->execute();
    $report['monthly_trend'] = $monthlyStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    return $report;
}

function generateCounsellorPerformanceReport($conn, $dateFrom, $dateTo) {
    $report = [];
    
    $stmt = $conn->prepare("
        SELECT 
            c.name,
            c.email,
            c.phone,
            c.status,
            COUNT(DISTINCT s.id) as total_assigned,
            SUM(CASE WHEN s.status = 'Converted' THEN 1 ELSE 0 END) as conversions,
            SUM(CASE WHEN s.status = 'Not Interested' THEN 1 ELSE 0 END) as lost,
            ROUND(
                (SUM(CASE WHEN s.status = 'Converted' THEN 1 ELSE 0 END) / 
                NULLIF(COUNT(DISTINCT s.id), 0)) * 100, 2
            ) as conversion_rate,
            AVG(DATEDIFF(
                COALESCE(s.updated_at, s.created_at), 
                s.created_at
            )) as avg_processing_days
        FROM counsellors c
        LEFT JOIN students s ON c.id = s.assigned_to 
            AND DATE(s.created_at) BETWEEN ? AND ?
        WHERE c.status = 'active'
        GROUP BY c.id, c.name, c.email, c.phone, c.status
        ORDER BY conversions DESC, conversion_rate DESC
    ");
    $stmt->bind_param("ss", $dateFrom, $dateTo);
    $stmt->execute();
    $report['performance'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    return $report;
}

function generateExamAnalysisReport($conn, $dateFrom, $dateTo) {
    $report = [];
    
    $stmt = $conn->prepare("
        SELECT 
            e.name as exam_name,
            e.id as exam_id,
            COUNT(DISTINCT s.id) as total_students,
            COUNT(DISTINCT cr.id) as total_requests,
            COUNT(DISTINCT l.id) as total_leads,
            SUM(CASE WHEN s.status = 'Converted' THEN 1 ELSE 0 END) as converted_students,
            (SELECT COUNT(*) FROM exam_news WHERE exam_id = e.id) as news_count,
            (SELECT COUNT(*) FROM exam_events WHERE exam_id = e.id) as events_count
        FROM exams e
        LEFT JOIN students s ON e.name = s.exam AND DATE(s.created_at) BETWEEN ? AND ?
        LEFT JOIN counselling_requests cr ON e.name = cr.course AND DATE(cr.created_at) BETWEEN ? AND ?
        LEFT JOIN leads l ON l.student_id = s.id AND DATE(l.created_at) BETWEEN ? AND ?
        GROUP BY e.id, e.name
        ORDER BY total_students DESC
    ");
    $stmt->bind_param("ssss", $dateFrom, $dateTo, $dateFrom, $dateTo, $dateFrom, $dateTo);
    $stmt->execute();
    $report['exam_analysis'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    return $report;
}

function generateCollegeAnalysisReport($conn, $dateFrom, $dateTo) {
    $report = [];
    
    $stmt = $conn->prepare("
        SELECT 
            c.name as college_name,
            c.city,
            c.state,
            c.course,
            c.fees,
            c.placement_avg,
            c.exam,
            COUNT(DISTINCT s.id) as interested_students,
            GROUP_CONCAT(DISTINCT s.preferred_course SEPARATOR ', ') as student_preferences
        FROM colleges c
        LEFT JOIN students s ON (
            s.preferred_course LIKE CONCAT('%', c.course, '%') OR
            s.preferred_city LIKE CONCAT('%', c.city, '%')
        ) AND DATE(s.created_at) BETWEEN ? AND ?
        WHERE c.status = 'Active'
        GROUP BY c.id, c.name, c.city, c.state, c.course, c.fees, c.placement_avg, c.exam
        ORDER BY interested_students DESC
        LIMIT 20
    ");
    $stmt->bind_param("ss", $dateFrom, $dateTo);
    $stmt->execute();
    $report['college_analysis'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    return $report;
}

function generateRevenueReport($conn, $dateFrom, $dateTo) {
    $report = [];
    
    // Revenue from converted leads
    $revenueStmt = $conn->prepare("
        SELECT 
            DATE(l.created_at) as date,
            COUNT(DISTINCT l.id) as converted_leads,
            COALESCE(SUM(l.conversion_amount), 0) as revenue,
            AVG(l.conversion_amount) as avg_revenue_per_lead
        FROM leads l
        WHERE l.status = 'Converted' 
            AND DATE(l.created_at) BETWEEN ? AND ?
            AND l.conversion_amount > 0
        GROUP BY DATE(l.created_at)
        ORDER BY date
    ");
    $revenueStmt->bind_param("ss", $dateFrom, $dateTo);
    $revenueStmt->execute();
    $report['daily_revenue'] = $revenueStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Revenue by counsellor
    $counsellorRevenueStmt = $conn->prepare("
        SELECT 
            co.name as counsellor_name,
            COUNT(DISTINCT l.id) as converted_leads,
            COALESCE(SUM(l.conversion_amount), 0) as total_revenue,
            AVG(l.conversion_amount) as avg_revenue_per_lead
        FROM leads l
        JOIN counsellors co ON l.counsellor_id = co.id
        WHERE l.status = 'Converted' 
            AND DATE(l.created_at) BETWEEN ? AND ?
            AND l.conversion_amount > 0
        GROUP BY co.id, co.name
        ORDER BY total_revenue DESC
    ");
    $counsellorRevenueStmt->bind_param("ss", $dateFrom, $dateTo);
    $counsellorRevenueStmt->execute();
    $report['revenue_by_counsellor'] = $counsellorRevenueStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Revenue by exam
    $examRevenueStmt = $conn->prepare("
        SELECT 
            s.exam,
            COUNT(DISTINCT l.id) as converted_leads,
            COALESCE(SUM(l.conversion_amount), 0) as total_revenue,
            AVG(l.conversion_amount) as avg_revenue_per_lead
        FROM leads l
        JOIN students s ON l.student_id = s.id
        WHERE l.status = 'Converted' 
            AND DATE(l.created_at) BETWEEN ? AND ?
            AND l.conversion_amount > 0
        GROUP BY s.exam
        ORDER BY total_revenue DESC
    ");
    $examRevenueStmt->bind_param("ss", $dateFrom, $dateTo);
    $examRevenueStmt->execute();
    $report['revenue_by_exam'] = $examRevenueStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Summary
    $summaryStmt = $conn->prepare("
        SELECT 
            COUNT(DISTINCT l.id) as total_converted_leads,
            COALESCE(SUM(l.conversion_amount), 0) as total_revenue,
            AVG(l.conversion_amount) as overall_avg_revenue,
            MIN(l.conversion_amount) as min_revenue,
            MAX(l.conversion_amount) as max_revenue
        FROM leads l
        WHERE l.status = 'Converted' 
            AND DATE(l.created_at) BETWEEN ? AND ?
            AND l.conversion_amount > 0
    ");
    $summaryStmt->bind_param("ss", $dateFrom, $dateTo);
    $summaryStmt->execute();
    $report['summary'] = $summaryStmt->get_result()->fetch_assoc();
    
    return $report;
}
?>