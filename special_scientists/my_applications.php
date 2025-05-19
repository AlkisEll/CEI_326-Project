<?php
session_start();
require_once "database.php";
require_once "get_config.php";

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION["user"];
$user_id = $user["id"];
$fullName = $user["full_name"];
$role = $user["role"] ?? '';

$_SESSION["user_id"] = $user_id;
$_SESSION["role"] = $role;

$system_title = getSystemConfig("site_title");
$logo_path = getSystemConfig("logo_path");

$query = "
SELECT 
    a.id,
    ap.name AS period_name,
    a.status,
    a.created_at,
    a.rejection_reason,
    GROUP_CONCAT(DISTINCT c.course_name ORDER BY c.course_name SEPARATOR ', ') AS course_list
FROM applications a
JOIN application_periods ap ON a.period_id = ap.id
LEFT JOIN application_courses ac ON ac.application_id = a.id
LEFT JOIN courses c ON ac.course_id = c.id
WHERE a.user_id = $user_id
GROUP BY a.id
ORDER BY a.created_at DESC
";

$result = mysqli_query($conn, $query);

// Build year filter
$available_years = [];
$applications = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $applications[] = $row;
        $year = date('Y', strtotime($row['created_at']));
        if (!in_array($year, $available_years)) {
            $available_years[] = $year;
        }
    }
    rsort($available_years);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Applications - CUT</title>
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
  <h2 class="mb-4"><i class="bi bi-journal-text me-2"></i>My Applications</h2>
  <div class="my-apps-card">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="d-flex flex-wrap gap-3">
  <div class="d-flex flex-column" style="min-width: 160px;">
    <label for="statusFilter" class="form-label mb-1">Filter by Status:</label>
    <select id="statusFilter" class="form-select">
      <option value="all">🔍 Show All</option>
      <option value="pending">🕓 Pending</option>
      <option value="accepted">✅ Accepted</option>
      <option value="rejected">❌ Rejected</option>
    </select>
  </div>

  <div class="d-flex flex-column" style="min-width: 140px;">
    <label for="yearFilter" class="form-label mb-1">Filter by Year:</label>
    <select id="yearFilter" class="form-select">
      <option value="all">📆 All Years</option>
      <?php foreach ($available_years as $year): ?>
        <option value="<?= $year ?>"><?= $year ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="d-flex flex-column" style="min-width: 220px;">
    <label for="courseFilter" class="form-label mb-1">Filter by Course:</label>
    <input type="text" id="courseFilter" class="form-control" placeholder="Type a course name...">
  </div>
</div>


<a href="application_form.php" class="btn btn-success">
  <i class="bi bi-plus-circle me-1"></i>New Application
</a>
    </div>

    <?php if (!empty($applications)): ?>
      <div class="table-responsive">
        <table class="table table-bordered table-hover text-center align-middle">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Courses</th>
              <th>Period</th>
              <th>Status</th>
              <th>Submitted</th>
              <th>Application Form Files</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($applications as $row): ?>
              <?php
              $statusKey = strtolower($row['status']);
              $statusClass = match($statusKey) {
                  'pending' => 'warning',
                  'accepted' => 'success',
                  'rejected' => 'danger',
                  default => 'secondary',
              };
              $statusEmoji = match($statusKey) {
                  'pending' => '🕓',
                  'accepted' => '✅',
                  'rejected' => "<span style='color:white;'>❌</span>",
                  default => ''
              };
              $year = date('Y', strtotime($row['created_at']));
              ?>
              <tr class="status-<?= $statusKey ?> year-<?= $year ?>">
                <td><?= $row['id'] ?></td>
                <td>
                <?php foreach (explode(',', $row['course_list'] ?? '') as $course): ?>
                <?php if (trim($course)): ?>
                <span class="badge bg-secondary me-1"><?= htmlspecialchars(trim($course)) ?></span>
                <?php endif; ?>
                <?php endforeach; ?>
                </td>
                <td><?= htmlspecialchars($row['period_name']) ?></td>
                <td>
                  <span class="badge bg-<?= $statusClass ?>">
                    <?= $statusEmoji . " " . ucfirst($statusKey) ?>
                  </span>
                  <?php if ($statusKey === 'rejected' && !empty($row['rejection_reason'])): ?>
                    <div class="text-muted small mt-1 reason">
                      <i class="bi bi-info-circle me-1"></i>Reason: <?= htmlspecialchars($row['rejection_reason']) ?>
                    </div>
                  <?php endif; ?>
                </td>
                <td><i class="bi bi-calendar3 me-1"></i><?= $row['created_at'] ?></td>
                <td>
  <a href="generate_application_zip.php?application_id=<?= $row['id'] ?>" class="btn btn-outline-primary btn-sm" target="_blank">
    <i class="bi bi-file-earmark-arrow-down me-1"></i>Download
  </a>
</td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="text-center no-apps">
        <i class="bi bi-folder-x"></i><br>
        You have not submitted any applications yet.
      </div>
    <?php endif; ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const savedMode = localStorage.getItem('dark-mode');
if (savedMode === 'true') document.body.classList.add('dark-mode');

const statusFilter = document.getElementById("statusFilter");
const yearFilter = document.getElementById("yearFilter");
const courseFilter = document.getElementById("courseFilter");

function applyFilters() {
  const status = statusFilter.value;
  const year = yearFilter.value;
  const courseText = courseFilter.value.toLowerCase();

  document.querySelectorAll("tbody tr").forEach(row => {
    const matchStatus = status === "all" || row.classList.contains("status-" + status);
    const matchYear = year === "all" || row.classList.contains("year-" + year);
    const courseCell = row.querySelector("td:nth-child(2)");
    const matchCourse = courseCell && courseCell.textContent.toLowerCase().includes(courseText);

    row.style.display = matchStatus && matchYear && matchCourse ? "" : "none";
  });
}

statusFilter.addEventListener("change", applyFilters);
yearFilter.addEventListener("change", applyFilters);
courseFilter.addEventListener("input", applyFilters);
</script>
</body>
</html>
