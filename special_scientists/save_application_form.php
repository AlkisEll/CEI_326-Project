<?php
session_start();
include 'database.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $period_id = $_POST['period_id'];
    $gender = $_POST['gender'];
    $nationality = ($_POST['nationality'] === 'Other') ? $_POST['other_nationality'] : $_POST['nationality'];
    $experience_json = $_POST['experience_json'] ?? '';
    $experience_entries = json_decode($experience_json, true);
    $first_experience = $experience_entries[0] ?? null;

if ($first_experience) {
    $current_position = $first_experience['job_title'];
    $current_employer = $first_experience['employer'];
    $professional_experience = $first_experience['summary'];
    $expertise_area = $first_experience['expertise'];
    $project_highlights = $first_experience['projects'];
    $experience_start_date = !empty($first_experience['from']) ? $first_experience['from'] : null;
    $experience_end_date = !empty($first_experience['to']) ? $first_experience['to'] : null;
    $part_or_full_time = $first_experience['type'];
} else {
    $current_position = $current_employer = $professional_experience = $expertise_area = $project_highlights = null;
    $experience_start_date = $experience_end_date = $part_or_full_time = null;
}

    file_put_contents('debug_gender.txt', $_POST['gender'] ?? 'NOT SET');
    file_put_contents('debug_education_json.txt', $_POST['education_json'] ?? 'NOT SET');

   $file_fields = [
    'cv_file' => 'cv',
    'degree_file' => 'degree',
    'certifications_file' => 'certifications',
    'other_docs' => 'other',
    'reference_letter' => 'reference'
];

$uploaded_files = [];

foreach ($file_fields as $field => $type) {
    if (isset($_FILES[$field])) {
        $files = $_FILES[$field];

        $fileCount = is_array($files['name']) ? count($files['name']) : 1;

        for ($i = 0; $i < $fileCount; $i++) {
    $name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
    $tmp = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
    $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];

    if ($error === UPLOAD_ERR_OK) {
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $new_name = $type . '_' . uniqid() . '.' . $ext;
        $upload_dir = __DIR__ . '/uploads/forms/';
        $destination = $upload_dir . $new_name;

        if (move_uploaded_file($tmp, $destination)) {
            if ($type === 'cv') {
                $uploaded_files[$type] = $new_name;
            } else {
                $uploaded_files[$type][] = $new_name;
            }
        }
    }
  }
 }
}

    // Insert applications and attach files
$id_card = $_POST['id_card'];
// Insert education entries into application_education table
$education_json = $_POST['education_json'] ?? '';
$education = json_decode($education_json, true)[0] ?? null;

if ($education) {
    $degree_level = $education['level'];
    $degree_title = $education['title'];

    if (strtolower(trim($degree_level)) === 'none') {
        $expected_graduation_date = $education['to']; // we reused 'to' for due_date in the frontend
        $institution = null;
        $education_start_date = null;
        $education_end_date = null;
        $institution_country = null;
        $degree_grade = null;
    } else {
        $institution = $education['institution'];
        $education_start_date = $education['from'];
        $education_end_date = $education['to'];
        $institution_country = $education['country'];
        $degree_grade = $education['grade'];
        $expected_graduation_date = null;
    }

    $thesis_title = $education['thesis'];
    $additional_qualifications = $education['qualifications'];
} else {
    $degree_level = $degree_title = $institution = $start_date = $end_date =
    $institution_country = $degree_grade = $expected_graduation_date =
    $thesis_title = $additional_qualifications = null;
}

$stmt = $conn->prepare("INSERT INTO applications (
    user_id, period_id, id_card, gender, nationality,
    current_position, current_employer, professional_experience,
    expertise_area, project_highlights,
    degree_level, degree_title, institution, education_start_date, education_end_date, institution_country,
    degree_grade, thesis_title, additional_qualifications, expected_graduation_date,
    experience_start_date, experience_end_date, part_or_full_time,
    submitted_full_name, submitted_email, submitted_phone, submitted_dob,
    submitted_address, submitted_country, submitted_postcode
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$submitted_full_name = $_SESSION['user']['full_name'] ?? '';
$submitted_email = $_SESSION['user']['email'] ?? '';
$submitted_phone = $_SESSION['user']['phone'] ?? '';
$submitted_dob = $_SESSION['user']['dob'] ?? null;
$submitted_address = $_SESSION['user']['address'] ?? '';
$submitted_country = $_SESSION['user']['country'] ?? '';
$submitted_postcode = $_SESSION['user']['postcode'] ?? '';

$stmt->bind_param("iissssssssssssssssssssssssss",
    $user_id, $period_id, $id_card, $gender, $nationality,
    $current_position, $current_employer, $professional_experience,
    $expertise_area, $project_highlights,
    $degree_level, $degree_title, $institution, $education_start_date, $education_end_date, $institution_country,
    $degree_grade, $thesis_title, $additional_qualifications, $expected_graduation_date,
    $experience_start_date, $experience_end_date, $part_or_full_time,
    $submitted_full_name, $submitted_email, $submitted_phone, $submitted_dob,
    $submitted_address, $submitted_country, $submitted_postcode
);
$stmt->execute();
$application_id = $stmt->insert_id;
$stmt->close();
if (!$application_id) {
    file_put_contents('debug_app_id.txt', 'Application ID is missing or zero.');
}

// Link all selected courses to that one application
if (empty($_POST['course_ids']) || !is_array($_POST['course_ids'])) {
    die("Error: No courses were selected. Please go back and select at least one.");
}

$course_stmt = $conn->prepare("INSERT INTO application_courses (application_id, course_id) VALUES (?, ?)");
foreach ($_POST['course_ids'] as $course_id) {
    $course_stmt->bind_param("ii", $application_id, $course_id);
    $course_stmt->execute();
}
$course_stmt->close();


// Insert additional uploaded files
foreach ($uploaded_files as $type => $file_data) {
    // Support both single file and multiple files
    $files = is_array($file_data) ? $file_data : [$file_data];

    foreach ($files as $file_name) {
        $file_stmt = $conn->prepare("INSERT INTO application_files (application_id, file_type, file_name) VALUES (?, ?, ?)");
        $file_stmt->bind_param("iss", $application_id, $type, $file_name);
        $file_stmt->execute();
        $file_stmt->close();
    }
}



    header("Location: confirmation.php");
    exit();
}
?>
