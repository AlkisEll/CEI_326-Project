<?php
include 'database.php';
session_start();
require_once "get_config.php";

$system_title = getSystemConfig("site_title");
$logo_path = getSystemConfig("logo_path");

$query = "SELECT * FROM application_periods ORDER BY start_date DESC";
$result = mysqli_query($conn, $query);
$showBack = true;
$backLink = "manage_recruitment.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Application Periods</title>
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
  <h2 class="mb-4"><i class="bi bi-calendar-range me-2"></i>Manage Application Periods</h2>

  <div class="my-apps-card">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
      <div class="d-flex flex-wrap gap-3">
        <div class="d-flex flex-column" style="min-width: 160px;">
          <label for="monthFilter" class="form-label mb-1">Month:</label>
          <select id="monthFilter" class="form-select">
            <option value="all">📅 All Months</option>
            <?php for ($m = 1; $m <= 12; $m++): ?>
              <option value="<?= $m ?>"><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
            <?php endfor; ?>
          </select>
        </div>

        <div class="d-flex flex-column" style="min-width: 160px;">
          <label for="yearFilter" class="form-label mb-1">Year:</label>
          <select id="yearFilter" class="form-select">
            <option value="all">📆 All Years</option>
            <?php
              $yearQuery = "SELECT DISTINCT YEAR(start_date) as y FROM application_periods ORDER BY y DESC";
              $years = mysqli_query($conn, $yearQuery);
              while ($y = mysqli_fetch_assoc($years)): ?>
                <option value="<?= $y['y'] ?>"><?= $y['y'] ?></option>
            <?php endwhile; ?>
          </select>
        </div>
      </div>

      <a href="add_period.php" class="btn btn-success"><i class="bi bi-plus-circle me-1"></i>Add Period</a>
    </div>

    <div class="table-responsive">
      <table class="table table-bordered table-hover text-center align-middle">
        <thead class="table-light border border-white">
          <tr>
            <th>ID</th>
            <th>Period Name</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="periodTable">
          <?php while ($row = mysqli_fetch_assoc($result)):
              $start = date_create($row['start_date']);
              $month = date_format($start, 'n');
              $year = date_format($start, 'Y');
          ?>
            <tr data-month="<?= $month ?>" data-year="<?= $year ?>">
              <td><?= $row['id'] ?></td>
              <td><?= htmlspecialchars($row['name']) ?></td>
              <td><?= $row['start_date'] ?></td>
              <td><?= $row['end_date'] ?></td>
              <td>
                <a href="edit_period.php?id=<?= $row['id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                <a href="delete_period.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this period?');">Delete</a>
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

  const monthFilter = document.getElementById("monthFilter");
  const yearFilter = document.getElementById("yearFilter");

  function applyFilters() {
    const selectedMonth = monthFilter.value;
    const selectedYear = yearFilter.value;

    document.querySelectorAll("#periodTable tr").forEach(row => {
      const rowMonth = row.getAttribute("data-month");
      const rowYear = row.getAttribute("data-year");

      const matchMonth = selectedMonth === "all" || rowMonth === selectedMonth;
      const matchYear = selectedYear === "all" || rowYear === selectedYear;

      row.style.display = matchMonth && matchYear ? "" : "none";
    });
  }

  monthFilter.addEventListener("change", applyFilters);
  yearFilter.addEventListener("change", applyFilters);
</script>
</body>
</html>
