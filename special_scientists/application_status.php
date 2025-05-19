<?php
include 'database.php';
session_start();
require_once "get_config.php";

// Only accessible to candidates
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$user = $_SESSION['user'];
$user_id = $user['id'];
$fullName = $user['full_name'];
$role = $user['role'] ?? '';

$_SESSION['user_id'] = $user_id;
$_SESSION['role'] = $role;

$system_title = getSystemConfig("site_title");
$logo_path = getSystemConfig("logo_path");

$query = "
    SELECT 
        applications.id,
        courses.course_name,
        application_periods.name AS period_name,
        applications.status,
        applications.rejection_reason,
        applications.created_at
    FROM applications
    JOIN courses ON applications.course_id = courses.id
    JOIN application_periods ON applications.period_id = application_periods.id
    WHERE applications.user_id = $user_id
    ORDER BY applications.created_at DESC
";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Application Status - CUT</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="indexstyle.css">
  <link rel="stylesheet" href="darkmode.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="https://www.cut.ac.cy" target="_blank">
      <?php if (!empty($logo_path) && file_exists($logo_path)): ?>
        <img src="<?= $logo_path ?>" alt="Logo" style="height: 40px; margin-right: 10px;">
      <?php endif; ?>
      <?= htmlspecialchars($system_title) ?>
    </a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto align-items-center">
        <li class="nav-item"><a class="nav-link text-white" href="index.php">Home</a></li>
        <li class="nav-item"><span class="nav-link text-white">Welcome, <strong><?= htmlspecialchars($fullName) ?></strong></span></li>
        <li class="nav-item"><a class="nav-link text-white" href="javascript:history.back()">← Back</a></li>
        <li class="nav-item">
          <button class="nav-link btn btn-link text-white" data-bs-toggle="modal" data-bs-target="#logoutModal">Logout</button>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- Logout Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirm Logout</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">Are you sure you want to log out?</div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
        <form action="logout.php" method="post">
          <button type="submit" class="btn btn-danger">Yes, Logout</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Main Content -->
<div class="container py-5">
  <h2 class="mb-4"><i class="bi bi-clipboard-data me-2"></i>Application Status</h2>
  <div class="my-apps-card">
    <?php if (mysqli_num_rows($result) > 0): ?>
      <div class="table-responsive">
        <table class="table table-bordered table-hover text-center align-middle">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Course</th>
              <th>Period</th>
              <th>Status</th>
              <th>Submitted</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
              <?php
              $statusText = '';
              $badge = '';
              switch ($row['status']) {
                  case 'pending':
                      $badge = 'warning';
                      $statusText = '🕓 Pending';
                      break;
                  case 'accepted':
                      $badge = 'success';
                      $statusText = '✅ Accepted';
                      break;
                  case 'rejected':
                      $badge = 'danger';
                      $statusText = '❌ Rejected';
                      break;
                  default:
                      $badge = 'secondary';
                      $statusText = ucfirst($row['status']);
              }
              ?>
              <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['course_name']) ?></td>
                <td><?= htmlspecialchars($row['period_name']) ?></td>
                <td>
                  <span class="badge bg-<?= $badge ?>"><?= $statusText ?></span>
                  <?php if ($row['status'] === 'rejected' && !empty($row['rejection_reason'])): ?>
                    <div class="text-muted small mt-1">
                      <i class="bi bi-info-circle me-1"></i>Reason: <?= htmlspecialchars($row['rejection_reason']) ?>
                    </div>
                  <?php endif; ?>
                </td>
                <td><i class="bi bi-calendar3 me-1"></i><?= $row['created_at'] ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="text-center text-muted">You have not submitted any applications yet.</p>
    <?php endif; ?>
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
