<?php
include 'database.php';
require_once "get_config.php";
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'owner','hr'])) {
    header("Location: login.php");
    exit();
}

$system_title = getSystemConfig("site_title");
$logo_path = getSystemConfig("logo_path");

$total_scientists = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'scientist'"))['total'];
$enabled = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS enabled FROM users WHERE role = 'scientist' AND lms_access = 1"))['enabled'];
$disabled = $total_scientists - $enabled;

$courses_without_instructor = mysqli_query($conn, "
    SELECT c.course_name
    FROM courses c
    WHERE c.id NOT IN (
        SELECT DISTINCT course_id FROM user_course_assignments
        UNION
        SELECT DISTINCT ac.course_id
        FROM application_courses ac
        JOIN applications a ON ac.application_id = a.id
        WHERE a.status = 'accepted'
    )
    ORDER BY c.course_name ASC
");

$num_unassigned = mysqli_num_rows($courses_without_instructor);
$course_syncs = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        SUM(status = 'success') AS success,
        SUM(status = 'failure') AS failure
    FROM moodle_sync_logs
    WHERE type = 'course'
"));
$user_syncs = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total_created
    FROM moodle_sync_logs
    WHERE type = 'user' AND status = 'success'
"));
$failed_course_logs = mysqli_query($conn, "
    SELECT reference_id AS shortname, message, created_at
    FROM moodle_sync_logs
    WHERE type = 'course' AND status = 'failure'
    ORDER BY created_at DESC
");
$showBack = true;
$backLink = "enrollment_dashboard.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Enrollment Report - <?= htmlspecialchars($system_title) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="indexstyle.css">
  <link rel="stylesheet" href="darkmode.css">
</head>
<body>

<!-- Navbar -->
<?php include "navbar.php"; ?>

<!-- Main -->
<div class="container py-5">
  <h2 class="mb-4 text-center"><i class="bi bi-bar-chart-line-fill me-2"></i>Enrollment Report</h2>

  <div class="text-center mb-4">
    <a href="export_enrollment_report_csv.php" class="btn btn-primary me-2">
      <i class="bi bi-download me-1"></i>Export CSV
    </a>
    <a href="export_applications_course_pdf.php" class="btn btn-danger">
  <i class="bi bi-file-earmark-pdf me-1"></i>Export PDF
</a>
  </div>

  <div class="my-apps-card text-center mb-5">
  <h4 class="mb-3">LMS Access Distribution</h4>
  <div class="chart-container">
    <canvas id="lmsChart"></canvas>
  </div>
</div>

  <div class="row g-4 mt-4">
  <div class="col-md-4">
    <div class="dashboard-box text-center">
      <h5>Total Scientists</h5>
      <span class="fs-3 fw-bold"><?= $total_scientists ?></span>
    </div>
  </div>

  <div class="col-md-4">
    <div class="dashboard-box text-center">
      <h5 class="text-primary">📚 Moodle Courses Synced</h5>
      <span class="fs-3 fw-bold text-primary"><?= $course_syncs['success'] ?? 0 ?></span>
    </div>
  </div>

  <div class="col-md-4">
    <div class="dashboard-box text-center">
      <h5 class="text-danger">⚠️ Sync Failures</h5>
      <span class="fs-3 fw-bold text-danger"><?= $course_syncs['failure'] ?? 0 ?></span>
    </div>
  </div>

  <div class="col-md-4">
    <div class="dashboard-box text-center">
      <h5 class="text-success">✅ With LMS Access</h5>
      <span class="fs-3 fw-bold text-success"><?= $enabled ?></span>
    </div>
  </div>

  <div class="col-md-4">
    <div class="dashboard-box text-center">
      <h5 class="text-danger">❌ Without LMS Access</h5>
      <span class="fs-3 fw-bold text-danger"><?= $disabled ?></span>
    </div>
  </div>

  <div class="col-md-4">
  <div class="dashboard-box text-center">
    <h5 class="text-info">👤 Moodle Users Created</h5>
    <span class="fs-3 fw-bold text-info"><?= $user_syncs['total_created'] ?? 0 ?></span>
  </div>
</div>
</div>

  <div class="my-apps-card mt-5">
  <h5 class="mb-3">
  <i class="bi bi-journal-x me-1"></i>Courses Without Assigned Scientists
  <span class="badge bg-danger"><?= $num_unassigned ?></span>
</h5>
    <?php if (mysqli_num_rows($courses_without_instructor) > 0): ?>
      <ul class="list-unstyled">
        <?php while ($row = mysqli_fetch_assoc($courses_without_instructor)): ?>
          <li><i class="bi bi-dot"></i> <?= htmlspecialchars($row['course_name']) ?></li>
        <?php endwhile; ?>
      </ul>
    <?php else: ?>
      <p class="text-success">All courses are currently covered.</p>
    <?php endif; ?>
  </div>
  <div class="my-apps-card mt-5">
  <h5 class="mb-3 text-danger"><i class="bi bi-exclamation-triangle me-1"></i>Failed Moodle Course Syncs</h5>

  <?php if (mysqli_num_rows($failed_course_logs) > 0): ?>
    <div class="table-responsive">
      <table class="table table-bordered table-hover text-center align-middle">
        <thead class="table-danger">
          <tr>
            <th>Course Code (Shortname)</th>
            <th>Error Message</th>
            <th>Timestamp</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($log = mysqli_fetch_assoc($failed_course_logs)): ?>
            <tr>
              <td><?= htmlspecialchars($log['shortname']) ?></td>
              <td><?= htmlspecialchars($log['message']) ?></td>
              <td><?= date('Y-m-d H:i', strtotime($log['created_at'])) ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="text-success">✅ No failed Moodle course sync attempts found.</p>
  <?php endif; ?>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>

<script>
const saved = localStorage.getItem('dark-mode');
if (saved === 'true') document.body.classList.add('dark-mode');

// LMS Access Chart
const ctx = document.getElementById('lmsChart').getContext('2d');
new Chart(ctx, {
    type: 'pie',
    data: {
        labels: ['With LMS Access', 'Without LMS Access'],
        datasets: [{
            label: 'LMS Access',
            data: [<?= $enabled ?>, <?= $disabled ?>],
            backgroundColor: ['#28a745', '#dc3545'],
            borderColor: '#fff',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});

// PDF export
async function downloadEnrollmentPDF() {
    const { jsPDF } = window.jspdf;
    const pdf = new jsPDF('p', 'pt', 'a4');
    const container = document.querySelector('.container');

    const canvas = await html2canvas(container, { scale: 2 });
    const img = canvas.toDataURL('image/png');
    const props = pdf.getImageProperties(img);
    const pdfWidth = pdf.internal.pageSize.getWidth();
    const pdfHeight = (props.height * pdfWidth) / props.width;

    pdf.addImage(img, 'PNG', 0, 0, pdfWidth, pdfHeight);
    pdf.save("Enrollment_Report.pdf");
}
</script>

<style>
  .chart-container {
    width: 300px;
    height: 300px;
    margin: 0 auto;
  }

  #lmsChart {
    width: 100% !important;
    height: 100% !important;
  }
</style>
</body>
</html>
