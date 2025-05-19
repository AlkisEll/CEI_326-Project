<?php
include 'database.php';
session_start();

// Only HR or Evaluators can use this
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['hr', 'evaluator'])) {
    die("Unauthorized");
}

// Create zip file
$zip = new ZipArchive();
$zip_name = "CVs_" . date("Y-m-d_H-i-s") . ".zip";
$temp_zip_path = sys_get_temp_dir() . '/' . $zip_name;

if ($zip->open($temp_zip_path, ZipArchive::CREATE) !== true) {
    die("Could not create ZIP archive.");
}

// Get CV files from applications table
$query = "SELECT cv_filename FROM applications WHERE cv_filename IS NOT NULL";
$result = mysqli_query($conn, $query);

while ($row = mysqli_fetch_assoc($result)) {
    $file_path = "uploads/" . $row['cv_filename'];
    if (file_exists($file_path)) {
        $zip->addFile($file_path, basename($file_path));
    }
}

$zip->close();

// Send zip to browser
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zip_name . '"');
header('Content-Length: ' . filesize($temp_zip_path));

readfile($temp_zip_path);

// Delete the temp file after download
unlink($temp_zip_path);
exit;