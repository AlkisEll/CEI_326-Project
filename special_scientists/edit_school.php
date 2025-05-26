<?php
session_start();
include 'database.php';
require_once "get_config.php";

// Enable error reporting during development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$system_title = getSystemConfig("site_title");
$logo_path = getSystemConfig("logo_path");

// Verify user role
if (!isset($_SESSION["user"]) || !in_array($_SESSION["user"]["role"], ['admin', 'owner'])) {
    header("Location: index.php");
    exit();
}

// Check for valid school ID
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: manage_schools.php");
    exit();
}

$id = intval($_GET["id"]);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);

    if (!empty($name)) {
        $stmt = $conn->prepare("UPDATE schools SET name = ? WHERE id = ?");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("si", $name, $id);
        $stmt->execute();
        $stmt->close();

        header("Location: manage_schools.php");
        exit();
    }
}

// Fetch current school name
$stmt = $conn->prepare("SELECT name FROM schools WHERE id = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($name);
if (!$stmt->fetch()) {
    $stmt->close();
    die("School not found.");
}
$stmt->close();

$showBack = true;
$backLink = "manage_schools.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit School - CUT</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="indexstyle.css">
  <link rel="stylesheet" href="darkmode.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
</head>
<body>

<!-- Navbar -->
<?php include "navbar.php"; ?>


<!-- Main Content -->
<div class="container py-5">
  <h2 class="mb-4"><i class="bi bi-pencil-square me-2"></i>Edit School</h2>
  <div class="my-apps-card">
    <form method="post">
      <div class="mb-3">
        <label class="form-label">School Name</label>
        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($name) ?>" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">Update School</button>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const savedMode = localStorage.getItem('dark-mode');
  if (savedMode === 'true') {
    document.body.classList.add('dark-mode');
  }
</script>
</body>
</html>
