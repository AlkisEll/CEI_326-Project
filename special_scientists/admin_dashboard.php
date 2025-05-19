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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="indexstyle.css">
    <link rel="stylesheet" href="darkmode.css">
</head>
<body>
<!-- Navbar -->
<?php include "navbar.php"; ?>

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
            <a href="configure_system.php" class="text-decoration-none">
                <div class="dashboard-box">
                    <h4>⚙️ Configure System</h4>
                    <p>Customize system branding and settings</p>
                </div>
            </a>
        </div>
        <div class="col-md-6">
            <a href="manage_recruitment.php" class="text-decoration-none">
                <div class="dashboard-box">
                    <h4>✍️ Manage Recruitment</h4>
                    <p>Managing Applications, Schools, Departments, Courses and Application Periods</p>
                </div>
            </a>
        </div>
        <div class="col-md-6">
            <a href="admin_report.php" class="text-decoration-none">
                <div class="dashboard-box">
                    <h4>📊 Reports</h4>
                    <p>View graphical statistics on applications</p>
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
