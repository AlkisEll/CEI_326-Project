<?php
include 'database.php';
require_once "get_config.php";
session_start();

// Restrict access
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'owner', 'hr'])) {
    header("Location: login.php");
    exit();
}

$system_title = getSystemConfig("site_title");
$logo_path = getSystemConfig("logo_path");

// Get auto sync setting
$setting = mysqli_fetch_assoc(mysqli_query($conn, "SELECT auto_sync_enabled FROM system_settings LIMIT 1"));
$auto_sync = $setting['auto_sync_enabled'] ?? 0;

// Handle toggle
if (isset($_GET['toggle_auto_sync'])) {
    $new_status = $auto_sync ? 0 : 1;
    mysqli_query($conn, "UPDATE system_settings SET auto_sync_enabled = $new_status");
    header("Location: full_sync.php");
    exit();
}

// Manual sync simulation
if (isset($_POST['manual_sync'])) {
  include_once 'moodle_api_helpers.php';

  $token = '3223ebfec77abfe903c27c1468a7d7c5';
  $domain = 'http://localhost/moodle';
  
  // Get all scientists with LMS access ON
  $scientists = mysqli_query($conn, "SELECT * FROM users WHERE role = 'scientist' AND lms_access = 1");
  $count = 0;
  
  while ($user = mysqli_fetch_assoc($scientists)) {
      $full_name_parts = explode(' ', $user['full_name']);
      $firstname = array_shift($full_name_parts);
      $lastname = implode(' ', $full_name_parts);
  
      $user_data = [
          'username' => $user['username'],
          'firstname' => $firstname,
          'lastname' => $lastname ?: $firstname,
          'email' => $user['email'],
          'password' => 'TempPass123!',
          'auth' => 'manual'
      ];
  
      $userid = create_moodle_user($token, $domain, $user_data);
  
      // Get all assigned course_ids
      $course_query = mysqli_query($conn, "
          SELECT DISTINCT c.id, c.course_code, c.course_name
          FROM courses c
          JOIN (
              SELECT course_id FROM user_course_assignments WHERE user_id = {$user['id']}
              UNION
              SELECT ac.course_id
              FROM application_courses ac
              JOIN applications a ON ac.application_id = a.id
              WHERE a.user_id = {$user['id']}
          ) AS all_courses ON c.id = all_courses.course_id
      ");
  
      while ($course = mysqli_fetch_assoc($course_query)) {
          $courseid = create_moodle_course_if_not_exists($token, $domain, $course['course_code'], $course['course_name']);
          enroll_user_to_course($token, $domain, $userid, $courseid, 3); // roleid 3 = teacher
      }
  
      $count++;
  }
  
  $sync_message = "✅ Full system sync completed successfully for $count user(s).";  
}

$showBack = true;
$backLink = "enrollment_dashboard.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Full Sync - <?= htmlspecialchars($system_title) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="indexstyle.css">
  <link rel="stylesheet" href="darkmode.css">
</head>
<body>

<!-- Navbar -->
<?php include "navbar.php"; ?>

<!-- Main -->
<div class="container py-5">
  <h2 class="mb-4 text-center"><i class="bi bi-arrow-repeat me-2"></i>Full System Sync</h2>

  <?php if (isset($sync_message)): ?>
    <div class="alert alert-success text-center"><?= $sync_message ?></div>
  <?php endif; ?>

  <div class="my-apps-card text-center">
    <form method="post">
      <button type="submit" name="manual_sync" class="btn btn-primary me-3">
        <i class="bi bi-arrow-clockwise me-1"></i>Trigger Full Sync
      </button>
    </form>

    <a href="full_sync.php?toggle_auto_sync=1" class="btn mt-4 <?= $auto_sync ? 'btn-danger' : 'btn-success' ?>">
      <i class="bi <?= $auto_sync ? 'bi-x-circle' : 'bi-check-circle' ?> me-1"></i>
      <?= $auto_sync ? 'Disable Auto-Sync' : 'Enable Auto-Sync' ?>
    </a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const saved = localStorage.getItem('dark-mode');
  if (saved === 'true') document.body.classList.add('dark-mode');
</script>

</body>
</html>
