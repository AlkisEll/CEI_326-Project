<?php
session_start();
include 'database.php';
require_once "get_config.php";

$system_title = getSystemConfig("site_title");
$logo_path = getSystemConfig("logo_path");

// Show errors during debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
  header("Location: manage_departments.php");
  exit();
}

$id = intval($_GET["id"]);

// Fetch all schools
$schools = mysqli_query($conn, "SELECT id, name FROM schools ORDER BY name ASC");

// Initialize values
$name = "";
$school_id = "";

// Fetch department info
$stmt = $conn->prepare("SELECT name, school_id FROM departments WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($name, $school_id);
if (!$stmt->fetch()) {
  $stmt->close();
  header("Location: manage_departments.php");
  exit();
}
$stmt->close();

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name']);
  $school_id = intval($_POST['school_id']);

  if (!empty($name) && $school_id > 0) {
    $stmt = $conn->prepare("UPDATE departments SET name = ?, school_id = ? WHERE id = ?");
    $stmt->bind_param("sii", $name, $school_id, $id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_departments.php");
    exit();
  }
}

$showBack = true;
$backLink = "manage_departments.php";
?>
