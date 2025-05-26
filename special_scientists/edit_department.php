<?php
session_start();
include 'database.php';
require_once "get_config.php";

// DEBUG mode: show all errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// System values
$system_title = getSystemConfig("site_title");
$logo_path = getSystemConfig("logo_path");

echo "Step 1: Starting...<br>";

// Validate department ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Error: Invalid department ID.");
}

$id = intval($_GET['id']);
echo "Step 2: Department ID = $id<br>";

// If form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "Step 3: Handling POST<br>";

    $name = trim($_POST['name']);
    $school_id = intval($_POST['school_id']);

    if (empty($name) || $school_id <= 0) {
        die("Error: Missing or invalid department name or school.");
    }

    $stmt = $conn->prepare("UPDATE departments SET name = ?, school_id = ? WHERE id = ?");
    if (!$stmt) {
        die("Error: Prepare failed - " . $conn->error);
    }

    $stmt->bind_param("sii", $name, $school_id, $id);
    $stmt->execute();
    $stmt->close();

    echo "Step 4: Update successful. Redirecting...<br>";
    header("Location: manage_departments.php");
    exit();
}

// Get department info
echo "Step 5: Fetching department data<br>";
$stmt = $conn->prepare("SELECT name, school_id FROM departments WHERE id = ?");
if (!$stmt) {
    die("Error: Prepare failed - " . $conn->error);
}

$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($name, $school_id);

if (!$stmt->fetch()) {
    $stmt->close();
    die("Error: Department not found.");
}
$stmt->close();

echo "Step 6: Department loaded: $name (School ID: $school_id)<br>";

// Fetch school list
echo "Step 7: Loading schools<br>";
$schools = mysqli_query($conn, "SELECT id, name FROM schools ORDER BY name ASC");
if (!$schools) {
    die("Error: Could not load schools - " . mysqli_error($conn));
}

echo "Step 8: Ready for HTML<br>";
$showBack = true;
$backLink = "manage_departments.php";
?>
