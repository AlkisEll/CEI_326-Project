<?php
include 'database.php';
session_start();
require_once "get_config.php";

$system_title = getSystemConfig("site_title");
$logo_path = getSystemConfig("logo_path");

$selected_period = $_GET['period_id'] ?? 'all';
$selected_course = $_GET['course_id'] ?? 'all';
$selected_school = $_GET['school_id'] ?? 'all';

$periods = mysqli_query($conn, "SELECT id, name FROM application_periods ORDER BY start_date DESC");
$courses = mysqli_query($conn, "SELECT id, course_name FROM courses ORDER BY course_name ASC");
$schools = mysqli_query($conn, "SELECT id, name FROM schools ORDER BY name ASC");

$whereClauses = [];
if ($selected_period !== 'all') $whereClauses[] = "applications.period_id = " . intval($selected_period);
if ($selected_course !== 'all') $whereClauses[] = "applications.course_id = " . intval($selected_course);
if ($selected_school !== 'all') $whereClauses[] = "departments.school_id = " . intval($selected_school);
$where = count($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

$total_apps = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM applications
    JOIN courses ON applications.course_id = courses.id
    JOIN departments ON courses.department_id = departments.id
    JOIN schools ON departments.school_id = schools.id
    $where
"))['total'];

$appsPerCourseData = mysqli_fetch_all(mysqli_query($conn, "
    SELECT courses.course_name, COUNT(applications.id) AS count
    FROM applications
    JOIN courses ON applications.course_id = courses.id
    JOIN departments ON courses.department_id = departments.id
    JOIN schools ON departments.school_id = schools.id
    $where
    GROUP BY applications.course_id
"), MYSQLI_ASSOC);

$appsPerSchoolData = mysqli_fetch_all(mysqli_query($conn, "
    SELECT schools.name AS school_name, COUNT(applications.id) AS count
    FROM applications
    JOIN courses ON applications.course_id = courses.id
    JOIN departments ON courses.department_id = departments.id
    JOIN schools ON departments.school_id = schools.id
    $where
    GROUP BY schools.id
"), MYSQLI_ASSOC);

$appsPerPeriodData = mysqli_fetch_all(mysqli_query($conn, "
    SELECT application_periods.name AS period_name, COUNT(applications.id) AS count
    FROM applications
    JOIN application_periods ON applications.period_id = application_periods.id
    GROUP BY application_periods.id
"), MYSQLI_ASSOC);

function getNameById($list, $id, $field = 'name') {
    foreach ($list as $item) {
        if ($item['id'] == $id) return htmlspecialchars($item[$field]);
    }
    return 'All';
}

$periodList = mysqli_fetch_all(mysqli_query($conn, "SELECT id, name FROM application_periods"), MYSQLI_ASSOC);
$courseList = mysqli_fetch_all(mysqli_query($conn, "SELECT id, course_name FROM courses"), MYSQLI_ASSOC);
$schoolList = mysqli_fetch_all(mysqli_query($conn, "SELECT id, name FROM schools"), MYSQLI_ASSOC);

$selectedPeriodText = $selected_period === 'all' ? 'All Periods' : getNameById($periodList, $selected_period);
$selectedCourseText = $selected_course === 'all' ? 'All Courses' : getNameById($courseList, $selected_course, 'course_name');
$selectedSchoolText = $selected_school === 'all' ? 'All Schools' : getNameById($schoolList, $selected_school);
$showBack = true;
$backLink = "admin_dashboard.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Report</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
  <link rel="stylesheet" href="indexstyle.css">
  <link rel="stylesheet" href="darkmode.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .chart-wrapper {
      max-width: 700px;
      margin: 40px auto;
    }
  </style>
</head>
<body>

<!-- Navbar -->
<?php include "navbar.php"; ?>

<div class="container py-5">
  <h2 class="mb-4"><i class="bi bi-bar-chart-line me-2"></i>Admin Report</h2>

  <form method="GET" class="d-flex flex-wrap gap-3 align-items-center mb-4">
    <select name="period_id" class="form-select" style="max-width: 200px;" onchange="this.form.submit()">
      <option value="all" <?= $selected_period === 'all' ? 'selected' : '' ?>>📅 All Periods</option>
      <?php foreach ($periodList as $p): ?>
        <option value="<?= $p['id'] ?>" <?= $selected_period == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
      <?php endforeach; ?>
    </select>

    <select name="course_id" class="form-select" style="max-width: 200px;" onchange="this.form.submit()">
      <option value="all" <?= $selected_course === 'all' ? 'selected' : '' ?>>📘 All Courses</option>
      <?php foreach ($courseList as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $selected_course == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['course_name']) ?></option>
      <?php endforeach; ?>
    </select>

    <select name="school_id" class="form-select" style="max-width: 200px;" onchange="this.form.submit()">
      <option value="all" <?= $selected_school === 'all' ? 'selected' : '' ?>>🏫 All Schools</option>
      <?php foreach ($schoolList as $s): ?>
        <option value="<?= $s['id'] ?>" <?= $selected_school == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
      <?php endforeach; ?>
    </select>

    <a href="admin_report.php" class="btn btn-danger"><i class="bi bi-x-circle me-1"></i>Clear Filters</a>
  </form>

  <div class="alert alert-info mb-4">
    Showing results for: <strong><?= $selectedPeriodText ?></strong> | <strong><?= $selectedCourseText ?></strong> | <strong><?= $selectedSchoolText ?></strong>
  </div>

  <div class="d-flex gap-3 mb-4">
    <a href="export_applications_course.php" class="btn btn-success"><i class="bi bi-download me-1"></i>Export CSV</a>
    <a href="#" onclick="downloadPDF(); return false;" class="btn btn-primary"><i class="bi bi-file-earmark-pdf me-1"></i>Export PDF</a>
  </div>

  <div class="chart-wrapper"><canvas id="courseChart"></canvas></div>
  <div class="chart-wrapper"><canvas id="schoolChart"></canvas></div>
  <div class="chart-wrapper"><canvas id="periodChart"></canvas></div>
</div>

<script>
const courseLabels = <?= json_encode(array_column($appsPerCourseData, 'course_name')) ?>;
const courseData = <?= json_encode(array_column($appsPerCourseData, 'count')) ?>;

const schoolLabels = <?= json_encode(array_column($appsPerSchoolData, 'school_name')) ?>;
const schoolData = <?= json_encode(array_column($appsPerSchoolData, 'count')) ?>;

const periodLabels = <?= json_encode(array_column($appsPerPeriodData, 'period_name')) ?>;
const periodData = <?= json_encode(array_column($appsPerPeriodData, 'count')) ?>;

new Chart(document.getElementById("courseChart"), {
  type: 'bar',
  data: {
    labels: courseLabels,
    datasets: [{
      label: 'Applications per Course',
      data: courseData,
      backgroundColor: 'rgba(0, 123, 255, 0.7)'
    }]
  }
});

new Chart(document.getElementById("schoolChart"), {
  type: 'pie',
  data: {
    labels: schoolLabels,
    datasets: [{
      label: 'Applications per School',
      data: schoolData,
      backgroundColor: ['#007bff', '#28a745', '#ffc107', '#dc3545', '#17a2b8']
    }]
  }
});

new Chart(document.getElementById("periodChart"), {
  type: 'line',
  data: {
    labels: periodLabels,
    datasets: [{
      label: 'Applications per Period',
      data: periodData,
      borderColor: '#003366',
      backgroundColor: 'rgba(0, 51, 102, 0.1)',
      fill: true
    }]
  }
});
</script>

<script>
async function downloadPDF() {
  const { jsPDF } = window.jspdf;
  const pdf = new jsPDF();
  const canvas = await html2canvas(document.querySelector('.container'), { scale: 2 });
  const img = canvas.toDataURL('image/png');
  const width = pdf.internal.pageSize.getWidth();
  const height = (canvas.height * width) / canvas.width;
  pdf.addImage(img, 'PNG', 0, 0, width, height);
  pdf.save("admin_report.pdf");
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const saved = localStorage.getItem('dark-mode');
if (saved === 'true') document.body.classList.add('dark-mode');
</script>
</body>
</html>
