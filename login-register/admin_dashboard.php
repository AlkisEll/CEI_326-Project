<?php
session_start();

require_once "get_config.php";
$system_title = getSystemConfig("site_title");
$logo_path = getSystemConfig("logo_path");


if (!isset($_SESSION["admin"])) {
    header("Location: admin_login.php");
    exit();
}

$adminName = $_SESSION["admin"]["full_name"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - CUT</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="indexstyle.css">
    <link rel="stylesheet" href="darkmode.css">

</head>

<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to log out?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
        <form action="logout.php" method="post">
          <button type="submit" class="btn btn-danger">Yes, Logout</button>
        </form>
      </div>
    </div>
  </div>
</div>


<body>

<!-- Updated CUT-style navbar -->
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="https://www.cut.ac.cy" target="_blank" title="Go to Cyprus University of Technology">
            <?php if (!empty($logo_path) && file_exists($logo_path)): ?>
            <img src="<?= $logo_path ?>" alt="Logo" style="height: 40px; margin-right: 10px;">
            <?php endif; ?>
            <span><?= htmlspecialchars($system_title) ?></span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="adminNavbar">
            <ul class="navbar-nav ms-auto align-items-center">
                <a href="javascript:history.back()" class="btn btn-secondary">← Back</a>
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item text-white mx-3">Welcome, <strong><?= htmlspecialchars($adminName); ?></strong></li>
                <li class="nav-item">
                    <button class="nav-link btn btn-link text-white" data-bs-toggle="modal" data-bs-target="#logoutModal">Logout</button>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Admin Dashboard Content -->
<div class="container py-5">
    <h2 class="mb-4">Admin Dashboard</h2>

    <div class="row g-4">
        <div class="col-md-6">
            <a href="manage_users.php" class="text-decoration-none">
                <div class="dashboard-box">
                    <h4>👥 Manage Users</h4>
                    <p>View and manage registered users</p>
                </div>
            </a>
        </div>
        <div class="col-md-6">
            <a href="manage_applications.php" class="text-decoration-none">
                <div class="dashboard-box">
                    <h4>📄 Manage Applications</h4>
                    <p>Review and handle submissions</p>
                </div>
            </a>
        </div>
        <div class="col-md-6">
            <a href="configure_system.php" class="text-decoration-none">
                <div class="dashboard-box">
                    <h4>⚙️ Configure System</h4>
                    <p>Customize system branding and settings</p>
                </div>
            </a>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const saved = localStorage.getItem('dark-mode');
    if (saved === 'true') document.body.classList.add('dark-mode');
</script>
</body>
</html>
