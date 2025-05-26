<?php
session_start();
require_once 'database.php';
require_once 'fpdf/fpdf.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_GET['application_id'])) {
    die("Application ID is missing.");
}

$application_id = intval($_GET['application_id']);

// Fetch application data
$app_query = $conn->prepare("
    SELECT 
        a.*, 
        p.name AS period_name
    FROM applications a
    LEFT JOIN application_periods p ON a.period_id = p.id
    WHERE a.id = ?
");
$app_query->bind_param("i", $application_id);
$app_query->execute();
$app_result = $app_query->get_result();
$application = $app_result->fetch_assoc();
$app_query->close();

if (!$application) {
    die("Application not found.");
}

// Fetch courses
$course_stmt = $conn->prepare("SELECT c.course_name FROM application_courses ac JOIN courses c ON ac.course_id = c.id WHERE ac.application_id = ?");
$course_stmt->bind_param("i", $application_id);
$course_stmt->execute();
$course_result = $course_stmt->get_result();
$courses = [];
while ($row = $course_result->fetch_assoc()) {
    $courses[] = $row['course_name'];
}
$course_stmt->close();

// --- Generate PDF ---
class PDF extends FPDF {
    function Header() {}
    function Footer() {}
}

// Ensure temp folder exists
$temp_dir = __DIR__ . '/temp';
if (!is_dir($temp_dir)) {
    mkdir($temp_dir, 0777, true);
}

$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'Step 1: Personal Information', 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);

$nationality = $application['nationality'] ?? 'N/A';

$step1 = [
    "Full Name" => $application['submitted_full_name'],
    "Email" => $application['submitted_email'],
    "Phone" => $application['submitted_phone'],
    "ID/Passport No." => $application['id_card'],
    "Gender" => $application['gender'],
    "Nationality" => $nationality,
    "Date of Birth" => $application['submitted_dob'],
    "Address" => $application['submitted_address'],
    "Country" => $application['submitted_country'],
    "Postal Code" => $application['submitted_postcode']
];
foreach ($step1 as $label => $value) {
    $pdf->Cell(0, 10, "$label: $value", 0, 1);
}

$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'Step 2: Selected Courses & Application Period', 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, "Application Period: " . $application['period_name'], 0, 1);
$pdf->Ln(5);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Selected Courses:', 0, 1);
$pdf->SetFont('Arial', '', 12);
foreach ($courses as $course) {
    $pdf->Cell(0, 8, "- " . $course, 0, 1);
}

$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'Step 3: Educational Background', 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);

if (empty($application['degree_title']) && empty($application['institution'])) {
    $pdf->Cell(0, 10, "No education entry provided.", 0, 1);
} else {
    $pdf->Ln(5);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, $application['degree_title'] . " (" . $application['degree_level'] . ")", 0, 1);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, "Institution: " . $application['institution'] . " (" . $application['institution_country'] . ")", 0, 1);
    $pdf->Cell(0, 8, "From: " . $application['education_start_date'] . "  To: " . $application['education_end_date'], 0, 1);
    if (!empty($application['degree_grade'])) $pdf->Cell(0, 8, "Grade: " . $application['degree_grade'], 0, 1);
    if (!empty($application['thesis_title'])) $pdf->Cell(0, 8, "Thesis: " . $application['thesis_title'], 0, 1);
    if (!empty($application['additional_qualifications'])) $pdf->Cell(0, 8, "Additional Qualifications: " . $application['additional_qualifications'], 0, 1);
}

$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'Step 4: Professional Experience', 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, "Current Position: " . $application['current_position'], 0, 1);
$pdf->Cell(0, 10, "Current Employer: " . $application['current_employer'], 0, 1);
$pdf->MultiCell(0, 8, "Experience Summary: " . $application['professional_experience']);
$start = new DateTime($application['experience_start_date']);
$end = new DateTime($application['experience_end_date']);
$interval = $start->diff($end);
$years_experience = $interval->y + ($interval->m / 12);

$pdf->Cell(0, 10, "Total Years of Experience: " . round($years_experience, 1) . " years", 0, 1);
$pdf->Cell(0, 10, "Expertise Area: " . $application['expertise_area'], 0, 1);
if (!empty($application['project_highlights'])) {
    $pdf->MultiCell(0, 8, "Projects/Publications: " . $application['project_highlights']);
}

// Save PDF
$pdf_path = __DIR__ . "/temp/application_summary.pdf";
$pdf->Output('F', $pdf_path);

// Create ZIP with PDF and uploaded files
$today = date("Ymd");
$username_part = explode("@", $application['email'])[0]; // "nikoscy100"
$filename = "{$username_part}_application_{$today}_id{$application_id}.zip";
$zip_path = $temp_dir . '/' . $filename;
$zip = new ZipArchive();
if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
    $zip->addFile($pdf_path, "application_summary.pdf");

    // 🟩 Always create an empty /documents/ folder in the ZIP
    $zip->addEmptyDir("documents");

    $files = $conn->prepare("SELECT file_name FROM application_files WHERE application_id = ?");
    $files->bind_param("i", $application_id);
    $files->execute();
    $files_result = $files->get_result();
    while ($f = $files_result->fetch_assoc()) {
        $filepath = __DIR__ . "/uploads/forms/" . $f['file_name'];
        if (file_exists($filepath)) {
            $cleaned = basename($f['file_name']); // prevent directory traversal
            $zip->addFile($filepath, "documents/" . $cleaned);
        }
    }
    $files->close();
    $zip->close();
}

header("Content-Type: application/zip");
header("Content-Disposition: attachment; filename=$filename");
header("Content-Length: " . filesize($zip_path));
readfile($zip_path);
exit;
?>
