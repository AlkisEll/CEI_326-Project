<?php
include 'database.php';
require_once "get_config.php";
session_start();

$system_title = getSystemConfig("site_title");
$logo_path = getSystemConfig("logo_path");

// Show PHP errors while debugging (disable on production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Redirect if no ID
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: manage_courses.php");
    exit();
}

$id = intval($_GET["id"]); // ✅ Sanitize ID

// Fetch departments for dropdown
$departments_result = mysqli_query($conn, "
    SELECT departments.id, departments.name, schools.name AS school_name 
    FROM departments 
    LEFT JOIN schools ON departments.school_id = schools.id 
    ORDER BY schools.name, departments.name
");

// Fetch existing course details
$code = $name = $department_id = "";

$stmt = $conn->prepare("SELECT course_code, course_name, department_id FROM courses WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($code, $name, $department_id);

if (!$stmt->fetch()) {
    // Course not found — redirect back
    $stmt->close();
    header("Location: manage_courses.php");
    exit();
}
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['course_code']);
    $name = trim($_POST['course_name']);
    $department_id = intval($_POST['department_id']);

    if (!empty($code) && !empty($name) && $department_id > 0) {
        $stmt = $conn->prepare("UPDATE courses SET course_code = ?, course_name = ?, department_id = ? WHERE id = ?");
        $stmt->bind_param("ssii", $code, $name, $department_id, $id);
        $stmt->execute();
        $stmt->close();

        header("Location: manage_courses.php");
        exit();
    }
}

$showBack = true;
$backLink = "manage_courses.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Course - CUT</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="indexstyle.css">
  <link rel="stylesheet" href="darkmode.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
</head>
<body>

<?php include "navbar.php"; ?>

<div class="container py-5">
  <h2 class="mb-4"><i class="bi bi-pencil-square me-2"></i>Edit Course</h2>
  <div class="my-apps-card">
    <form method="post">
      <div class="mb-3">
        <label class="form-label"><strong>Course Code:</strong></label>
        <input type="text" name="course_code" class="form-control" value="<?= htmlspecialchars($code) ?>" required>
      </div>

      <div class="mb-3">
        <label class="form-label"><strong>Course Name:</strong></label>
        <input type="text" name="course_name" class="form-control" value="<?= htmlspecialchars($name) ?>" required>
      </div>

      <div class="mb-4">
        <label class="form-label"><strong>Department:</strong></label>
        <select name="department_id" class="form-select" required>
          <option value="">-- Select Department --</option>
          <?php while ($dept = mysqli_fetch_assoc($departments_result)): ?>
            <option value="<?= $dept['id'] ?>" <?= $dept['id'] == $department_id ? 'selected' : '' ?>>
              <?= htmlspecialchars($dept['school_name'] . ' - ' . $dept['name']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <button type="submit" class="btn btn-primary w-100">Update Course</button>
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
