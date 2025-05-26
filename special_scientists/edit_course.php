<?php
include 'database.php';
require_once "get_config.php";
session_start();

$system_title = getSystemConfig("site_title");
$logo_path = getSystemConfig("logo_path");

if (!isset($_GET["id"])) {
  header("Location: manage_courses.php");
  exit();
}

$id = intval($_GET["id"]); // ✅ define the ID safely

// Fetch departments early (you’ll use this in both GET and POST)
$departments = mysqli_query($conn, "
    SELECT departments.id, departments.name, schools.name AS school_name 
    FROM departments 
    LEFT JOIN schools ON departments.school_id = schools.id 
    ORDER BY schools.name, departments.name
");

// Initialize variables
$code = "";
$name = "";
$department_id = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['course_code']);
    $name = trim($_POST['course_name']);
    $department_id = $_POST['department_id'];

    if (!empty($code) && !empty($name) && !empty($department_id)) {
        $stmt = $conn->prepare("UPDATE courses SET course_code = ?, course_name = ?, department_id = ? WHERE id = ?");
        $stmt->bind_param("ssii", $code, $name, $department_id, $id);
        $stmt->execute();
        header("Location: manage_courses.php");
        exit();
    }
} else {
    $stmt = $conn->prepare("SELECT course_code, course_name, department_id FROM courses WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($code, $name, $department_id);
    $stmt->fetch();
    $stmt->close(); // Optional but good practice
}

$showBack = true;
$backLink = "manage_courses.php";
?>
