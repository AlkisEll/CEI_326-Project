<?php
include 'database.php';
require_once "get_config.php";
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['hr', 'evaluator'])) {
    header('Location: login.php');
    exit();
}

$role = $_SESSION['role'];
$system_title = getSystemConfig("site_title");
$logo_path = getSystemConfig("logo_path");

// Handle accept
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'accept') {
  $stmt = $conn->prepare("UPDATE applications SET status = 'accepted', rejection_reason = NULL, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
  $stmt->bind_param("ii", $_SESSION['user_id'], $_GET['id']);
  $stmt->execute();
  
  // Assign 'scientist' role to the applicant
$app_id = intval($_GET['id']);
$user_result = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT user_id FROM applications WHERE id = $app_id
"));
$user_id = $user_result['user_id'] ?? null;

if ($user_id) {
  // Promote to scientist
  mysqli_query($conn, "
      UPDATE users SET role = 'scientist' WHERE id = $user_id
  ");

  // Auto-enable LMS access
  mysqli_query($conn, "
      UPDATE users SET lms_access = 1 WHERE id = $user_id
  ");
}

  // === Auto-Sync Check ===
$autoSync = mysqli_fetch_assoc(mysqli_query($conn, "SELECT auto_sync_enabled FROM system_settings LIMIT 1"));
if ($autoSync && $autoSync['auto_sync_enabled']) {

    include_once 'moodle_api_helpers.php';

    $appId = intval($_GET['id']);

    $query = "
    SELECT 
        a.id,
        u.full_name,
        u.email,
        u.username,
        u.lms_access,
        GROUP_CONCAT(DISTINCT c.course_name ORDER BY c.course_name SEPARATOR ', ') AS course_list,
        GROUP_CONCAT(DISTINCT c.course_code ORDER BY c.course_code SEPARATOR ', ') AS course_codes
    FROM applications a
    JOIN users u ON a.user_id = u.id
    LEFT JOIN application_courses ac ON ac.application_id = a.id
    LEFT JOIN courses c ON ac.course_id = c.id
    WHERE a.id = ?
    GROUP BY a.id
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $appId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();

    if ($row && $row['lms_access']) {
        $token = '3223ebfec77abfe903c27c1468a7d7c5';
        $domain = 'http://localhost/moodle';

        $full_name_parts = explode(' ', $row['full_name']);
        $firstname = array_shift($full_name_parts);
        $lastname = implode(' ', $full_name_parts);

        $user_data = [
            'username' => $row['username'],
            'firstname' => $firstname,
            'lastname' => $lastname ?: $firstname,
            'email' => $row['email'],
            'password' => 'TempPass123!',
            'auth' => 'manual'
        ];

        $userid = create_moodle_user($token, $domain, $user_data);

        $course_codes = explode(',', $row['course_codes'] ?? '');
        $course_names = explode(',', $row['course_list'] ?? '');

        foreach ($course_codes as $index => $code) {
            $shortname = trim($code);
            $fullname = isset($course_names[$index]) ? trim($course_names[$index]) : $shortname;

            if ($shortname && $fullname) {
                $courseid = create_moodle_course_if_not_exists($token, $domain, $shortname, $fullname);
                enroll_user_to_course($token, $domain, $userid, $courseid, 3); // roleid 3 = teacher
            }
        }
    }
}

  // === Moodle Integration Starts Here ===
  include_once 'moodle_api_helpers.php';

  $appId = $_GET['id'];
  $query = "
  SELECT 
      a.id,
      u.full_name,
      u.email,
      u.username,
      ap.name AS period_name,
      a.status,
      a.created_at,
      a.rejection_reason,
      a.reviewed_at,
      GROUP_CONCAT(DISTINCT c.course_name ORDER BY c.course_name SEPARATOR ', ') AS course_list,
      GROUP_CONCAT(DISTINCT c.course_code ORDER BY c.course_code SEPARATOR ', ') AS course_codes
  FROM applications a
  JOIN users u ON a.user_id = u.id
  JOIN application_periods ap ON a.period_id = ap.id
  LEFT JOIN users r ON a.reviewed_by = r.id
  LEFT JOIN application_courses ac ON ac.application_id = a.id
  LEFT JOIN courses c ON ac.course_id = c.id
  WHERE a.id = ?
  GROUP BY a.id
  ";  
  $stmt = $conn->prepare($query);
  $stmt->bind_param("i", $appId);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res->fetch_assoc();

  $token = '3223ebfec77abfe903c27c1468a7d7c5'; // Replace with actual Moodle token
  $domain = 'http://localhost/moodle'; // Replace with your actual Moodle domain
  $full_name_parts = explode(' ', $row['full_name']);
  $firstname = array_shift($full_name_parts); // First word
  $lastname = implode(' ', $full_name_parts); // Everything else

  $user_data = [
    'username' => $row['username'],
    'firstname' => $firstname,   // ✅ Correct
    'lastname' => $lastname,     // ✅ Correct
    'email' => $row['email'],
    'password' => 'TempPass123!',
    'auth' => 'manual'
];

// Call user creation
$userid = create_moodle_user($token, $domain, $user_data);

$course_codes = explode(',', $row['course_codes'] ?? '');
$course_names = explode(',', $row['course_list'] ?? '');

foreach ($course_codes as $index => $code) {
    $shortname = trim($code);
    $fullname = isset($course_names[$index]) ? trim($course_names[$index]) : $shortname;

    if ($shortname && $fullname) {
        $courseid = create_moodle_course_if_not_exists($token, $domain, $shortname, $fullname);
        enroll_user_to_course($token, $domain, $userid, $courseid, 3); // roleid 3 = teacher
    }
}

 // stop execution to inspect results

  // === Moodle Integration Ends ===

  header("Location: view_applications.php?status=updated");
  exit();
}

// Handle reject
if (isset($_POST['reject_id'], $_POST['rejection_reason'])) {
    $stmt = $conn->prepare("UPDATE applications SET status = 'rejected', rejection_reason = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
    $stmt->bind_param("sii", $_POST['rejection_reason'], $_SESSION['user_id'], $_POST['reject_id']);
    $stmt->execute();
    header("Location: view_applications.php?status=updated");
    exit();
}

// Fetch applications
$query = "
SELECT 
    a.id,
    u.full_name AS applicant,
    ap.name AS period_name,
    a.status,
    a.created_at,
    a.rejection_reason,
    a.reviewed_at,
    r.full_name AS reviewer_name,
    GROUP_CONCAT(DISTINCT c.course_name ORDER BY c.course_name SEPARATOR ', ') AS course_list
FROM applications a
JOIN users u ON a.user_id = u.id
JOIN application_periods ap ON a.period_id = ap.id
LEFT JOIN users r ON a.reviewed_by = r.id
LEFT JOIN application_courses ac ON ac.application_id = a.id
LEFT JOIN courses c ON ac.course_id = c.id
GROUP BY a.id
ORDER BY a.created_at DESC
";

$result = mysqli_query($conn, $query);
$applications = [];
$available_years = [];

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
  <title>View Applications</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="indexstyle.css">
  <link rel="stylesheet" href="darkmode.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
</head>
<body>

<!-- Navbar -->
<?php include "navbar.php"; ?>


<!-- Logout Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirm Logout</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">Are you sure you want to log out?</div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
        <form action="logout.php" method="post">
          <button type="submit" class="btn btn-danger">Yes, Logout</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Main Content -->
<div class="container py-5">
  <h2 class="mb-4"><i class="bi bi-binoculars me-2"></i>Submitted Applications</h2>

  <?php if (isset($_GET['status']) && $_GET['status'] === 'updated'): ?>
    <div class="alert alert-success">✅ Application status updated.</div>
  <?php endif; ?>

  <div class="my-apps-card">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
      <div class="d-flex flex-wrap gap-3">
        <div class="d-flex flex-column" style="min-width: 160px;">
          <label for="statusFilter" class="form-label mb-1">Filter by Status:</label>
          <select id="statusFilter" class="form-select">
            <option value="all">Show All</option>
            <option value="pending">Pending</option>
            <option value="accepted">Accepted</option>
            <option value="rejected">Rejected</option>
          </select>
        </div>

        <div class="d-flex flex-column" style="min-width: 140px;">
          <label for="yearFilter" class="form-label mb-1">Filter by Year:</label>
          <select id="yearFilter" class="form-select">
            <option value="all">All Years</option>
            <?php foreach ($available_years as $year): ?>
              <option value="<?= $year ?>"><?= $year ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="d-flex flex-column" style="min-width: 200px;">
          <label for="courseFilter" class="form-label mb-1">Filter by Course:</label>
          <input type="text" id="courseFilter" class="form-control" placeholder="Course name...">
        </div>

        <div class="d-flex flex-column" style="min-width: 200px;">
          <label for="applicantFilter" class="form-label mb-1">Filter by Applicant:</label>
          <input type="text" id="applicantFilter" class="form-control" placeholder="Applicant name...">
        </div>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-bordered table-hover text-center align-middle">
        <thead class="table-light">
          <tr>
          <th>ID</th>
          <th>Applicant</th>
          <th>Courses</th>
          <th>Period</th>
          <th>Application Form Files</th>
          <th>Action</th>
          <?php if ($role === 'hr'): ?><th>Reviewed By</th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($applications as $row): ?>
            <?php
              $statusKey = strtolower($row['status']);
              $year = date('Y', strtotime($row['created_at']));
              $statusClass = match($statusKey) {
                  'pending' => 'warning',
                  'accepted' => 'success',
                  'rejected' => 'danger',
                  default => 'secondary'
              };
            ?>
            <tr class="status-<?= strtolower($row['status']) ?> year-<?= date('Y', strtotime($row['created_at'])) ?>">
  <td><?= $row['id'] ?></td>
  <td class="applicant-name"><?= htmlspecialchars($row['applicant']) ?></td>
  <td>
  <?php foreach (explode(',', $row['course_list'] ?? '') as $course): ?>
  <?php if (trim($course)): ?>
    <span class="badge bg-info text-dark me-1"><?= htmlspecialchars(trim($course)) ?></span>
  <?php endif; ?>
<?php endforeach; ?>
</td>
  <td><?= htmlspecialchars($row['period_name']) ?></td>

  <td>
    <a href="generate_application_zip.php?application_id=<?= $row['id'] ?>" class="btn btn-outline-primary btn-sm" target="_blank">
      <i class="bi bi-file-earmark-arrow-down me-1"></i>Download
    </a>
  </td>

  <td>
    <?php if (strtolower($row['status']) === 'pending'): ?>
      <a href="?action=accept&id=<?= $row['id'] ?>" class="btn btn-sm btn-success mb-1">✔ Accept</a><br>
      <button class="btn btn-sm btn-danger" onclick="showRejectForm(<?= $row['id'] ?>)">✖ Reject</button>
      <div id="reject-form-<?= $row['id'] ?>" style="display:none;" class="mt-2">
        <form method="post">
          <input type="hidden" name="reject_id" value="<?= $row['id'] ?>">
          <input type="text" name="rejection_reason" class="form-control mb-2" placeholder="Reason..." required>
          <button type="submit" class="btn btn-sm btn-outline-danger">Submit</button>
        </form>
      </div>
    <?php else: ?>
      <span class="text-muted">—</span>
    <?php endif; ?>
  </td>

  <?php if ($role === 'hr'): ?>
  <td>
    <?php if ($row['reviewer_name']): ?>
      <?= htmlspecialchars($row['reviewer_name']) ?><br>
      <small class="text-muted"><?= date("M d, Y H:i", strtotime($row['reviewed_at'])) ?></small>
    <?php else: ?><span class="text-muted">—</span><?php endif; ?>
  </td>
  <?php endif; ?>
</tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
function showRejectForm(id) {
  document.getElementById("reject-form-" + id).style.display = "block";
}

const savedMode = localStorage.getItem('dark-mode');
if (savedMode === 'true') document.body.classList.add('dark-mode');

document.querySelectorAll('#statusFilter, #yearFilter').forEach(filter => {
  filter.addEventListener('change', applyFilters);
});
document.querySelectorAll('#courseFilter, #applicantFilter').forEach(input => {
  input.addEventListener('input', applyFilters);
});

function applyFilters() {
  const status = document.getElementById("statusFilter").value;
  const year = document.getElementById("yearFilter").value;
  const courseText = document.getElementById("courseFilter").value.toLowerCase();
  const applicantText = document.getElementById("applicantFilter").value.toLowerCase();

  document.querySelectorAll("tbody tr").forEach(row => {
    const matchStatus = status === "all" || row.classList.contains("status-" + status);
    const matchYear = year === "all" || row.classList.contains("year-" + year);
    const courseCell = row.querySelector(".course-name");
    const applicantCell = row.querySelector(".applicant-name");
    const matchCourse = courseCell && courseCell.textContent.toLowerCase().includes(courseText);
    const matchApplicant = applicantCell && applicantCell.textContent.toLowerCase().includes(applicantText);

    row.style.display = matchStatus && matchYear && matchCourse && matchApplicant ? "" : "none";
  });
}
</script>
<script>
  const toggleBtn = document.getElementById('burgerToggle');
  const floatingMenu = document.getElementById('floatingMenu');
  let isMenuOpen = false;

  toggleBtn?.addEventListener('click', () => {
    if (isMenuOpen) {
      floatingMenu.classList.remove('fade-in');
      floatingMenu.classList.add('fade-out');
      setTimeout(() => {
        floatingMenu.classList.add('d-none');
      }, 200);
    } else {
      floatingMenu.classList.remove('d-none', 'fade-out');
      floatingMenu.classList.add('fade-in');
    }
    isMenuOpen = !isMenuOpen;
  });

  document.addEventListener('click', function (e) {
    if (
      isMenuOpen &&
      !floatingMenu.contains(e.target) &&
      !toggleBtn.contains(e.target)
    ) {
      floatingMenu.classList.remove('fade-in');
      floatingMenu.classList.add('fade-out');
      setTimeout(() => {
        floatingMenu.classList.add('d-none');
        isMenuOpen = false;
      }, 200);
    }
  });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
