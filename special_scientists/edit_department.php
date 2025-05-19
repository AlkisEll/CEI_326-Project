<?php
session_start();
include 'database.php';
require_once "get_config.php";

$system_title = getSystemConfig("site_title");
$logo_path = getSystemConfig("logo_path");

if (!isset($_GET["id"])) {
  header("Location: manage_departments.php");
  exit();
}
// Fetch all schools
$schools = mysqli_query($conn, "SELECT id, name FROM schools ORDER BY name ASC");

// Handle form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $school_id = $_POST['school_id'];

    if (!empty($name) && !empty($school_id)) {
        $stmt = $conn->prepare("UPDATE departments SET name = ?, school_id = ? WHERE id = ?");
        $stmt->bind_param("sii", $name, $school_id, $id);
        $stmt->execute();
        header("Location: manage_departments.php");
        exit();
    }
} else {
    $stmt = $conn->prepare("SELECT name, school_id FROM departments WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($name, $school_id);
    $stmt->fetch();
}
$showBack = true;
$backLink = "manage_departments.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Department - CUT</title>
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
  <h2 class="mb-4"><i class="bi bi-pencil-square me-2"></i>Edit Department</h2>
  <div class="my-apps-card">
    <form method="post">
      <div class="mb-3">
        <label class="form-label"><strong>Department Name:</strong></label>
        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($name) ?>" required>
      </div>

      <div class="mb-3">
        <label class="form-label"><strong>Assign to School:</strong></label>
        <select name="school_id" class="form-select" required>
          <option value="">-- Select School --</option>
          <?php while ($school = mysqli_fetch_assoc($schools)): ?>
            <option value="<?= $school['id'] ?>" <?= $school['id'] == $school_id ? 'selected' : '' ?>>
              <?= htmlspecialchars($school['name']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <button type="submit" class="btn btn-primary w-100">Update Department</button>
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
