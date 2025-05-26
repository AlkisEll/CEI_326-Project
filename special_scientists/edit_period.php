<?php
session_start();
include 'database.php';
require_once "get_config.php";

// Enable error reporting during development (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$system_title = getSystemConfig("site_title");
$logo_path = getSystemConfig("logo_path");

// Validate ID
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
  header("Location: manage_periods.php");
  exit();
}

$id = intval($_GET["id"]);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    if (!empty($name) && !empty($start_date) && !empty($end_date)) {
        $stmt = $conn->prepare("UPDATE application_periods SET name = ?, start_date = ?, end_date = ? WHERE id = ?");
        if (!$stmt) {
            die("Error: " . $conn->error);
        }
        $stmt->bind_param("sssi", $name, $start_date, $end_date, $id);
        $stmt->execute();
        $stmt->close();

        header("Location: manage_periods.php");
        exit();
    }
}

// Fetch period info
$stmt = $conn->prepare("SELECT name, start_date, end_date FROM application_periods WHERE id = ?");
if (!$stmt) {
    die("Error: " . $conn->error);
}
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($name, $start_date, $end_date);
if (!$stmt->fetch()) {
    $stmt->close();
    die("Period not found.");
}
$stmt->close();

$showBack = true;
$backLink = "manage_periods.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Application Period</title>
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
  <h2 class="mb-4"><i class="bi bi-pencil-square me-2"></i>Edit Application Period</h2>
  <div class="my-apps-card">
    <form method="post">
      <div class="mb-3">
        <label class="form-label"><strong>Period Name:</strong></label>
        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($name) ?>" required>
      </div>

      <div class="mb-3">
        <label class="form-label"><strong>Start Date:</strong></label>
        <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>" required>
      </div>

      <div class="mb-3">
        <label class="form-label"><strong>End Date:</strong></label>
        <input type="date" name="end_date" class="form-control" value="<?= $end_date ?>" required>
      </div>

      <button type="submit" class="btn btn-primary w-100">Update Period</button>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const saved = localStorage.getItem('dark-mode');
  if (saved === 'true') document.body.classList.add('dark-mode');
</script>
</body>
</html>
