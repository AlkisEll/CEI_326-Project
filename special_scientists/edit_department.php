<?php
session_start();
include 'database.php';
require_once "get_config.php";

// DEBUG MODE — turn ON error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// System info
$system_title = getSystemConfig("site_title");
$logo_path = getSystemConfig("logo_path");

// Ensure ID is present
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
  die("Invalid department ID.");
}

$id = intval($_GET["id"]);

// Handle form submission FIRST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name']);
  $school_id = intval($_POST['school_id']);

  if (!empty($name) && $school_id > 0) {
    $stmt = $conn->prepare("UPDATE departments SET name = ?, school_id = ? WHERE id = ?");
    if (!$stmt) {
      die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("sii", $name, $school_id, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: manage_departments.php");
    exit();
  }
}

// Fetch department info
$stmt = $conn->prepare("SELECT name, school_id FROM departments WHERE id = ?");
if (!$stmt) {
  die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($name, $school_id);
if (!$stmt->fetch()) {
  $stmt->close();
  die("Department not found.");
}
$stmt->close();

// Fetch schools for dropdown
$schools = mysqli_query($conn, "SELECT id, name FROM schools ORDER BY name ASC");
if (!$schools) {
  die("Failed to fetch schools: " . mysqli_error($conn));
}

$showBack = true;
$backLink = "manage_departments.php";
?>
