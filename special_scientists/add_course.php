<?php
include 'database.php';
require_once "get_config.php";
session_start();

$system_title = getSystemConfig("site_title");
$logo_path = getSystemConfig("logo_path");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['course_code']);
    $name = trim($_POST['course_name']);
    $department_id = $_POST['department_id'];

    if (!empty($code) && !empty($name) && !empty($department_id)) {
        $stmt = $conn->prepare("INSERT INTO courses (course_code, course_name, department_id) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $code, $name, $department_id);
        $stmt->execute();
        header("Location: manage_courses.php");
        exit();
    }
}

$departments = mysqli_query($conn, "
    SELECT departments.id, departments.name, schools.name AS school_name 
    FROM departments 
    LEFT JOIN schools ON departments.school_id = schools.id 
    ORDER BY schools.name, departments.name
");
$showBack = true;
$backLink = "manage_courses.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Course - CUT</title>
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
  <h2 class="mb-4"><i class="bi bi-plus-circle me-2"></i>Add New Course</h2>
  <div class="my-apps-card">
    <form method="post">
      <div class="mb-3">
        <label class="form-label"><strong>Course Code:</strong></label>
        <input type="text" name="course_code" class="form-control" placeholder="e.g., CS101" required>
      </div>

      <div class="mb-3">
        <label class="form-label"><strong>Course Name:</strong></label>
        <input type="text" name="course_name" class="form-control" placeholder="e.g., Introduction to Computer Science" required>
      </div>

      <div class="mb-4">
        <label class="form-label"><strong>Assign to Department:</strong></label>
        <select name="department_id" class="form-select" required>
          <option value="">-- Select Department --</option>
          <?php while ($dept = mysqli_fetch_assoc($departments)): ?>
            <option value="<?= $dept['id'] ?>">
              <?= htmlspecialchars($dept['school_name'] . ' - ' . $dept['name']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <button type="submit" class="btn btn-success w-100">Add Course</button>
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
