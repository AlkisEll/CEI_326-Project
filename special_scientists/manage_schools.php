<?php
session_start();
require_once "database.php";
require_once "get_config.php";

if (!isset($_SESSION["user"]) || !in_array($_SESSION["user"]["role"], ['admin', 'owner'])) {
    header("Location: index.php");
    exit();
}

$system_title = getSystemConfig("site_title");
$logo_path = getSystemConfig("logo_path");

$query = "SELECT * FROM schools ORDER BY name ASC";
$result = mysqli_query($conn, $query);
$schools = mysqli_fetch_all($result, MYSQLI_ASSOC);
$showBack = true;
$backLink = "manage_recruitment.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Schools</title>
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
  <h2 class="mb-4"><i class="bi bi-building-fill me-2"></i>Manage Schools</h2>

  <div class="my-apps-card">
    <div class="d-flex justify-content-between flex-wrap align-items-center mb-3 gap-3">
      <div style="min-width: 220px;">
        <label for="schoolFilter" class="form-label mb-1">Filter by Name:</label>
        <input type="text" id="schoolFilter" class="form-control" placeholder="Type school name...">
      </div>
      <a href="add_school.php" class="btn btn-success"><i class="bi bi-plus-circle me-1"></i>Add New School</a>
    </div>

    <?php if (!empty($schools)): ?>
      <div class="table-responsive">
        <table class="table table-bordered table-hover text-center align-middle">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>School Name</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="schoolsTable">
            <?php foreach ($schools as $school): ?>
              <tr>
                <td><?= $school['id'] ?></td>
                <td class="school-name"><?= htmlspecialchars($school['name']) ?></td>
                <td>
                  <a href="edit_school.php?id=<?= $school['id'] ?>" class="btn btn-primary btn-sm me-1">
                    <i class="bi bi-pencil-square"></i> Edit
                  </a>
                  <a href="delete_school.php?id=<?= $school['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this school?');">
                    <i class="bi bi-trash"></i> Delete
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="alert alert-info text-center">No schools found.</div>
    <?php endif; ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const saved = localStorage.getItem('dark-mode');
  if (saved === 'true') document.body.classList.add('dark-mode');

  const schoolFilter = document.getElementById('schoolFilter');
  const rows = document.querySelectorAll('#schoolsTable tr');

  schoolFilter.addEventListener('input', () => {
    const term = schoolFilter.value.toLowerCase();
    rows.forEach(row => {
      const name = row.querySelector('.school-name').textContent.toLowerCase();
      row.style.display = name.includes(term) ? '' : 'none';
    });
  });
</script>
</body>
</html>
