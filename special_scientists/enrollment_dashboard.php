<?php
include 'database.php';
require_once "get_config.php";
session_start();

// Only HR, Admin, or Special Scientists can access
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['hr', 'admin', 'owner', 'scientist'])) {
    $role = $_SESSION['role'];
    header('Location: login.php');
    exit();
}

$system_title = getSystemConfig("site_title");
$logo_path = getSystemConfig("logo_path");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Enrollment Dashboard - <?= htmlspecialchars($system_title) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="indexstyle.css">
  <link rel="stylesheet" href="darkmode.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
</head>
<body>

<!-- Navbar -->
<?php include "navbar.php"; ?>


<!-- Main Section -->
<div class="container py-5">
<h2 class="text-center fw-bold display-5 mb-5">
  <i class="bi bi-people-fill me-2"></i>Enrollment Dashboard
</h2>

<div class="d-flex flex-wrap justify-content-center gap-4">
<a href="lms_sync.php" class="text-decoration-none">
  <div class="dashboard-box text-center">
    <h4>📡 LMS Sync</h4>
    <p>
      <?php if ($role === 'scientist'): ?>
        Check your LMS Access and receive your login links!
      <?php else: ?>
        Verify or manage Moodle access for Special Scientists
      <?php endif; ?>
    </p>
  </div>
</a>

  <?php if (in_array($role, ['admin', 'owner', 'hr'])): ?>
  <a href="full_sync.php" class="text-decoration-none">
    <div class="dashboard-box text-center">
      <h4>🔁 Full Sync</h4>
      <p>Force full system sync with Moodle</p>
    </div>
  </a>

  <a href="enrollment_report.php" class="text-decoration-none">
    <div class="dashboard-box text-center">
      <h4>📊 Report</h4>
      <p>View syncing and course assignment statistics</p>
    </div>
  </a>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const saved = localStorage.getItem('dark-mode');
  if (saved === 'true') document.body.classList.add('dark-mode');
</script>

</body>
</html>
