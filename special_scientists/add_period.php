<?php
include 'database.php';
session_start();
require_once "get_config.php";

$system_title = getSystemConfig("site_title");
$logo_path = getSystemConfig("logo_path");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    if (!empty($name) && !empty($start_date) && !empty($end_date)) {
        $stmt = $conn->prepare("INSERT INTO application_periods (name, start_date, end_date) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $start_date, $end_date);
        $stmt->execute();
        header("Location: manage_periods.php");
        exit();
    }
}
$showBack = true;
$backLink = "manage_periods.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Application Period</title>
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
  <h2 class="mb-4"><i class="bi bi-calendar-plus me-2"></i>Add Application Period</h2>
  <div class="my-apps-card">
    <form method="post">
      <div class="mb-3">
        <label class="form-label">Period Name</label>
        <input type="text" name="name" class="form-control" placeholder="e.g., Fall 2025" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Start Date</label>
        <input type="date" name="start_date" class="form-control" required>
      </div>

      <div class="mb-3">
        <label class="form-label">End Date</label>
        <input type="date" name="end_date" class="form-control" required>
      </div>

      <button type="submit" class="btn btn-success w-100"><i class="bi bi-plus-circle me-1"></i>Add Period</button>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const savedMode = localStorage.getItem('dark-mode');
  if (savedMode === 'true') document.body.classList.add('dark-mode');
</script>
</body>
</html>
