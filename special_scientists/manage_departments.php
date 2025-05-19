<?php
include 'database.php';
session_start();
require_once "get_config.php";

$system_title = getSystemConfig("site_title");
$logo_path = getSystemConfig("logo_path");

$query = "
    SELECT departments.id, departments.name AS department_name, schools.name AS school_name
    FROM departments
    LEFT JOIN schools ON departments.school_id = schools.id
    ORDER BY schools.name, departments.name
";
$result = mysqli_query($conn, $query);
$showBack = true;
$backLink = "manage_recruitment.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Departments</title>
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
  <h2 class="mb-4"><i class="bi bi-diagram-3 me-2"></i>Manage Departments</h2>
  <div class="my-apps-card">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
      <div class="d-flex flex-wrap gap-3">
        <div class="d-flex flex-column" style="min-width: 200px;">
          <label for="deptFilter" class="form-label mb-1">Department Filter:</label>
          <input type="text" id="deptFilter" class="form-control" placeholder="Search by department...">
        </div>
        <div class="d-flex flex-column" style="min-width: 200px;">
          <label for="schoolFilter" class="form-label mb-1">School Filter:</label>
          <input type="text" id="schoolFilter" class="form-control" placeholder="Search by school...">
        </div>
      </div>
      <a href="add_department.php" class="btn btn-success"><i class="bi bi-plus-circle me-1"></i>Add Department</a>
    </div>

    <div class="table-responsive">
      <table class="table table-bordered table-hover text-center align-middle">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Department Name</th>
            <th>School</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="departmentTable">
          <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
              <td><?= $row['id'] ?></td>
              <td><?= htmlspecialchars($row['department_name']) ?></td>
              <td><?= htmlspecialchars($row['school_name']) ?></td>
              <td>
                <a href="edit_department.php?id=<?= $row['id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                <a href="delete_department.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this department?');">Delete</a>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const saved = localStorage.getItem('dark-mode');
  if (saved === 'true') document.body.classList.add('dark-mode');

  const deptInput = document.getElementById("deptFilter");
  const schoolInput = document.getElementById("schoolFilter");

  function applyFilter() {
    const deptVal = deptInput.value.toLowerCase();
    const schoolVal = schoolInput.value.toLowerCase();

    const rows = document.querySelectorAll("#departmentTable tr");
    rows.forEach(row => {
      const dept = row.children[1].textContent.toLowerCase();
      const school = row.children[2].textContent.toLowerCase();
      const match = dept.includes(deptVal) && school.includes(schoolVal);
      row.style.display = match ? "" : "none";
    });
  }

  deptInput.addEventListener("input", applyFilter);
  schoolInput.addEventListener("input", applyFilter);
</script>
</body>
</html>
