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
$showBack = true;
$backLink = "admin_dashboard.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Recruitment - CUT</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="indexstyle.css">
    <link rel="stylesheet" href="darkmode.css">
</head>
<body>

<!-- Navbar -->
<?php include "navbar.php"; ?>

<!-- Main Content -->
<div class="container py-5">
    <h2 class="mb-4">Recruitment Management Panel</h2>
    <div class="row g-4">
        <div class="col-md-6">
            <a href="manage_schools.php" class="text-decoration-none">
                <div class="dashboard-box">
                    <h4>🏫 Manage Schools</h4>
                    <p>Adding, Removing, Updating Schools</p>
                </div>
            </a>
        </div>
        <div class="col-md-6">
            <a href="manage_departments.php" class="text-decoration-none">
                <div class="dashboard-box">
                    <h4>🏢 Manage Departments</h4>
                    <p>Adding, Removing, Updating Departments</p>
                </div>
            </a>
        </div>
        <div class="col-md-6">
            <a href="manage_courses.php" class="text-decoration-none">
                <div class="dashboard-box">
                    <h4>📘 Manage Courses</h4>
                    <p>Adding, Removing, Updating Courses</p>
                </div>
            </a>
        </div>
        <div class="col-md-6">
            <a href="manage_periods.php" class="text-decoration-none">
                <div class="dashboard-box">
                    <h4>📅 Manage Application Periods</h4>
                    <p>Adding, Removing, Updating Periods</p>
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
