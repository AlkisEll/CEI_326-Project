<?php
include 'database.php';
require_once "get_config.php";
session_start();

$system_title = getSystemConfig("site_title");
$logo_path = getSystemConfig("logo_path");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    if (!empty($name)) {
        $stmt = $conn->prepare("INSERT INTO schools (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        header("Location: manage_schools.php");
        exit();
    }
}
$showBack = true;
$backLink = "manage_schools.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add New School - CUT</title>
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
  <h2 class="mb-4"><i class="bi bi-building-add me-2"></i>Add New School</h2>
  <div class="my-apps-card">
    <form method="post">
      <div class="mb-3">
        <label class="form-label">School Name</label>
        <input type="text" name="name" class="form-control" placeholder="Enter school name" required>
      </div>
      <button type="submit" class="btn btn-success w-100">Add School</button>
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
