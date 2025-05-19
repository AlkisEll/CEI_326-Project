<?php
session_start();
include 'database.php';
require_once "get_config.php";

$system_title = getSystemConfig("site_title");
$logo_path = getSystemConfig("logo_path");

// Fetch courses with departments
$query = "
    SELECT courses.id, courses.course_code, courses.course_name, courses.category, departments.name AS department_name
    FROM courses
    LEFT JOIN departments ON courses.department_id = departments.id
    ORDER BY departments.name, courses.category, courses.course_name
";
$result = mysqli_query($conn, $query);

$courses = [];
$categories = [];

foreach ($courses as $row) {
    if (!in_array($row['category'], $categories)) {
        $categories[] = $row['category'];
    }
}
sort($categories);
$departments = [];

if ($result) {
  while ($row = mysqli_fetch_assoc($result)) {
      $courses[] = $row;

      if (!in_array($row['department_name'], $departments)) {
          $departments[] = $row['department_name'];
      }

      if (!in_array($row['category'], $categories)) {
          $categories[] = $row['category'];
      }
  }
  sort($departments);
  sort($categories);
}
$showBack = true;
$backLink = "manage_recruitment.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Courses - CUT</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="indexstyle.css">
  <link rel="stylesheet" href="darkmode.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
</head>
<body>

<!-- Navbar -->
<?php include "navbar.php"; ?>

<!-- Main -->
<div class="container py-5">
  <h2 class="mb-4"><i class="bi bi-book-half me-2"></i>Manage Courses</h2>
  <div class="my-apps-card">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
      <div class="d-flex flex-wrap gap-3">
        <div class="d-flex flex-column" style="min-width: 150px;">
          <label for="codeFilter" class="form-label mb-1">Filter by Code:</label>
          <input type="text" id="codeFilter" class="form-control" placeholder="Enter course code...">
        </div>

        <div class="d-flex flex-column" style="min-width: 180px;">
          <label for="nameFilter" class="form-label mb-1">Filter by Name:</label>
          <input type="text" id="nameFilter" class="form-control" placeholder="Enter course name...">
        </div>

        <div class="d-flex flex-column" style="min-width: 200px;">
          <label for="departmentFilter" class="form-label mb-1">Filter by Department:</label>
          <select id="departmentFilter" class="form-select">
            <option value="all">All Departments</option>
            <?php foreach ($departments as $dep): ?>
              <option value="<?= htmlspecialchars($dep) ?>"><?= htmlspecialchars($dep) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="d-flex flex-column" style="min-width: 220px;">
  <label for="categoryFilter" class="form-label mb-1">Filter by Category:</label>
  <select id="categoryFilter" class="form-select">
    <option value="all">All Categories</option>
    <?php foreach ($categories as $cat): ?>
      <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
    <?php endforeach; ?>
  </select>
</div>
      </div>

      <a href="add_course.php" class="btn btn-success"><i class="bi bi-plus-circle me-1"></i>New Course</a>
    </div>

    <?php if (!empty($courses)): ?>
      <div class="table-responsive">
        <table class="table table-bordered table-hover text-center align-middle">
        <thead class="table-light">
  <tr>
    <th>ID</th>
    <th>Course Code</th>
    <th>Course Name</th>
    <th>Category</th>
    <th>Department</th>
    <th>Actions</th>
  </tr>
</thead>
          <tbody>
            <?php foreach ($courses as $row): ?>
              <tr>
  <td><?= $row['id'] ?></td>
  <td class="course-code"><?= htmlspecialchars($row['course_code']) ?></td>
  <td class="course-name"><?= htmlspecialchars($row['course_name']) ?></td>
  <td class="course-category"><?= htmlspecialchars($row['category']) ?></td>
  <td class="department-name"><?= htmlspecialchars($row['department_name']) ?></td>
  <td>
    <a href="edit_course.php?id=<?= $row['id'] ?>" class="btn btn-primary btn-sm">Edit</a>
    <a href="delete_course.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this course?');">Delete</a>
  </td>
</tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="text-center text-muted">No courses found.</div>
    <?php endif; ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const saved = localStorage.getItem('dark-mode');
  if (saved === 'true') document.body.classList.add('dark-mode');

  const codeFilter = document.getElementById('codeFilter');
  const nameFilter = document.getElementById('nameFilter');
  const departmentFilter = document.getElementById('departmentFilter');

  function applyFilters() {
    const codeVal = codeFilter.value.toLowerCase();
    const nameVal = nameFilter.value.toLowerCase();
    const depVal = departmentFilter.value.toLowerCase();
    const catVal = categoryFilter.value.toLowerCase();

    document.querySelectorAll('tbody tr').forEach(row => {
      const code = row.querySelector('.course-code').textContent.toLowerCase();
      const name = row.querySelector('.course-name').textContent.toLowerCase();
      const dept = row.querySelector('.department-name').textContent.toLowerCase();
      const category = row.querySelector('.course-category')?.textContent.toLowerCase() || '';

      const show = (code.includes(codeVal)) &&
             (name.includes(nameVal)) &&
             (depVal === 'all' || dept === depVal) &&
             (catVal === 'all' || category === catVal);

      row.style.display = show ? '' : 'none';
    });
  }

  codeFilter.addEventListener('input', applyFilters);
  nameFilter.addEventListener('input', applyFilters);
  departmentFilter.addEventListener('change', applyFilters);
  categoryFilter.addEventListener('change', applyFilters);
</script>
</body>
</html>
