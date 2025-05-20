<?php
include 'database.php';
require_once "get_config.php";
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'owner', 'hr', 'scientist'])) {
    header('Location: login.php');
    exit();
}

$system_title = getSystemConfig("site_title");
$logo_path = getSystemConfig("logo_path");

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Fetch available courses
$course_list = [];
$course_query = mysqli_query($conn, "SELECT id, course_name, course_code FROM courses ORDER BY course_name ASC");
while ($course = mysqli_fetch_assoc($course_query)) {
    $course_list[] = $course;
}

// Handle course unassignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unassign_course'])) {
  file_put_contents('moodle_delete_debug.log', "🟢 Entered unassign_course block\n", FILE_APPEND);
  $scientist_id = intval($_POST['scientist_id']);
  $course_id = intval($_POST['course_id']);

  // Fetch user and course info
  $user_result = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $scientist_id"));
  $course_result = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM courses WHERE id = $course_id"));

  if ($user_result && $course_result) {
      include_once 'moodle_api_helpers.php';

      $token = '3223ebfec77abfe903c27c1468a7d7c5';
      $domain = 'http://cei326-omada7.cut.ac.cy/moodle/';

      // Get Moodle user and course
      $moodle_user = get_moodle_user_by_username($token, $domain, $user_result['username']);
      $moodle_userid = $moodle_user['id'] ?? null;
      $shortname = $course_result['course_code'];
      $moodle_courseid = get_moodle_course_id_by_shortname($token, $domain, $shortname);
      file_put_contents('moodle_delete_debug.log', "User: {$user_result['username']}, Moodle UID: $moodle_userid, Course: $shortname, Moodle CID: $moodle_courseid\n", FILE_APPEND);

      // 1. Unenroll from course in Moodle
      if ($moodle_userid && $moodle_courseid) {
          unenroll_user_from_course($token, $domain, $moodle_userid, $moodle_courseid);
          file_put_contents('moodle_delete_debug.log', "Unenrolled user $moodle_userid from course $moodle_courseid\n", FILE_APPEND);
      }

      // 2. THEN delete from local DB
      mysqli_query($conn, "
          DELETE FROM user_course_assignments 
          WHERE user_id = $scientist_id AND course_id = $course_id
      ");

      // 3. Check if course is still assigned to anyone else AFTER the deletion
      $is_assigned_elsewhere = mysqli_fetch_assoc(mysqli_query($conn, "
          SELECT COUNT(*) AS total
          FROM (
              SELECT user_id FROM user_course_assignments WHERE course_id = $course_id
              UNION
              SELECT a.user_id
              FROM application_courses ac
              JOIN applications a ON ac.application_id = a.id
              WHERE ac.course_id = $course_id AND a.status = 'accepted'
          ) AS active_assignments
      "))['total'];
      file_put_contents('moodle_delete_debug.log', "Remaining assignments for course $course_id: $is_assigned_elsewhere\n", FILE_APPEND);

      if ($is_assigned_elsewhere == 0 && $moodle_courseid) {
          delete_moodle_course($token, $domain, $moodle_courseid);
          file_put_contents('moodle_delete_debug.log', "🚨 Deleting course $shortname from Moodle (ID: $moodle_courseid)\n", FILE_APPEND);
      }
  }

  header("Location: lms_sync.php?status=unassigned");
  exit();
}

// Handle course assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_courses']) && isset($_POST['scientist_id'])) {
    include_once 'moodle_api_helpers.php';

    $scientist_id = intval($_POST['scientist_id']);
    $selected_courses = $_POST['course_ids'] ?? [];

    $user_result = mysqli_query($conn, "SELECT * FROM users WHERE id = $scientist_id");
    if ($user = mysqli_fetch_assoc($user_result)) {
        $token = '3223ebfec77abfe903c27c1468a7d7c5';
        $domain = 'http://cei326-omada7.cut.ac.cy/moodle/';

        $full_name_parts = explode(' ', $user['full_name']);
        $firstname = array_shift($full_name_parts);
        $lastname = implode(' ', $full_name_parts);

        $user_data = [
            'username' => $user['username'],
            'firstname' => $firstname,
            'lastname' => $lastname ?: $firstname,
            'email' => $user['email'],
            'password' => 'TempPass123!',
            'auth' => 'manual'
        ];

        $userid = create_moodle_user($token, $domain, $user_data);

        foreach ($selected_courses as $course_id) {
            $course_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM courses WHERE id = $course_id"));
            if ($course_info) {
              // Save assignment if not already exists
              mysqli_query($conn, "
              INSERT IGNORE INTO user_course_assignments (user_id, course_id)
              VALUES ($scientist_id, $course_id)
              ");
                $shortname = $course_info['course_code'];
                $fullname = $course_info['course_name'];
                $courseid = create_moodle_course_if_not_exists($token, $domain, $shortname, $fullname);
                enroll_user_to_course($token, $domain, $userid, $courseid, 3); // roleid 3 = teacher
            }
        }

        header("Location: lms_sync.php?status=assigned");
        exit();
    }
}

// Admin or HR: View all scientists
if (in_array($role, ['admin', 'owner', 'hr'])) {
  $query = "SELECT id, full_name, email, username, lms_access FROM users WHERE role = 'scientist' ORDER BY full_name ASC";
  $result = mysqli_query($conn, $query);
}

// LMS toggle
if (isset($_GET['toggle']) && in_array($role, ['admin', 'owner', 'hr'])) {
  $scientist_id = intval($_GET['toggle']);
  $user_result = mysqli_query($conn, "SELECT * FROM users WHERE id = $scientist_id AND role = 'scientist'");
  if ($user = mysqli_fetch_assoc($user_result)) {
      $newStatus = $user['lms_access'] ? 0 : 1;
      mysqli_query($conn, "UPDATE users SET lms_access = $newStatus WHERE id = $scientist_id");

      include_once 'moodle_api_helpers.php';

$token = '3223ebfec77abfe903c27c1468a7d7c5';
$domain = 'http://cei326-omada7.cut.ac.cy/moodle/';

$moodle_user = get_moodle_user_by_username($token, $domain, $user['username']);

if ($moodle_user && isset($moodle_user['id'])) {
    // Suspend if turning OFF, reactivate if turning ON
    $suspend = ($newStatus === 0) ? 1 : 0;
    suspend_or_unsuspend_moodle_user($token, $domain, $moodle_user['id'], $suspend);
}

      // === Moodle Sync only if turning ON ===
      if ($newStatus === 1) {
          include_once 'moodle_api_helpers.php';

          $token = '3223ebfec77abfe903c27c1468a7d7c5'; // Your Moodle token
          $domain = 'http://cei326-omada7.cut.ac.cy/moodle/';       // Your Moodle domain

          $full_name_parts = explode(' ', $user['full_name']);
          $firstname = array_shift($full_name_parts);
          $lastname = implode(' ', $full_name_parts);

          $user_data = [
              'username' => $user['username'],
              'firstname' => $firstname,
              'lastname' => $lastname ?: $firstname,
              'email' => $user['email'],
              'password' => 'TempPass123!',
              'auth' => 'manual'
          ];

          create_moodle_user($token, $domain, $user_data);
      }

      else {
        // LMS is being deactivated — unenroll from all Moodle courses
        include_once 'moodle_api_helpers.php';
    
        $token = '3223ebfec77abfe903c27c1468a7d7c5';
        $domain = 'http://cei326-omada7.cut.ac.cy/moodle/';
    
        $user_id = $user['id'];
        $moodle_user = get_moodle_user_by_username($token, $domain, $user['username']);
    
        if ($moodle_user && isset($moodle_user['id'])) {
            $moodle_userid = $moodle_user['id'];
    
            $assigned_courses = mysqli_query($conn, "
                SELECT c.course_code
                FROM user_course_assignments uca
                JOIN courses c ON uca.course_id = c.id
                WHERE uca.user_id = $user_id
            ");
    
            while ($course = mysqli_fetch_assoc($assigned_courses)) {
                $shortname = $course['course_code'];
                $course_id = get_moodle_course_id_by_shortname($token, $domain, $shortname);
                if ($course_id) {
                    unenroll_user_from_course($token, $domain, $moodle_userid, $course_id);
                }
            }
        }
    }    

      header("Location: lms_sync.php");
      exit();
  }
}

// Scientist self-view
if ($role === 'scientist') {
    $self = mysqli_fetch_assoc(mysqli_query($conn, "SELECT lms_access FROM users WHERE id = $user_id"));
}

$showBack = true;
$backLink = "enrollment_dashboard.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>LMS Sync - <?= htmlspecialchars($system_title) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="indexstyle.css">
  <link rel="stylesheet" href="darkmode.css">
</head>
<body>

<!-- Navbar -->
<?php include "navbar.php"; ?>

<!-- Main -->
<div class="container py-5">
  <h2 class="mb-4 text-center"><i class="bi bi-wifi me-2"></i>LMS Access Management</h2>

  <?php if (isset($_GET['status']) && $_GET['status'] === 'assigned'): ?>
  <div class="alert alert-success text-center">✅ Courses assigned and synced with Moodle.</div>
<?php endif; ?>

<?php if ($role === 'scientist'): ?>
  <div class="alert <?= $self['lms_access'] ? 'alert-success' : 'alert-danger' ?> text-center">
    <?php if ($self['lms_access']): ?>
      ✅ Your LMS Access is <strong>Enabled</strong>.<br>
      <a href="http://cei326-omada7.cut.ac.cy/moodle/" class="btn btn-sm btn-light mt-2" target="_blank">
        Go to Moodle <i class="bi bi-box-arrow-up-right"></i>
      </a>
    <?php else: ?>
      ❌ LMS Access is <strong>Not Yet Activated</strong>.
    <?php endif; ?>
  </div>
<?php else: ?>
    <div class="table-responsive my-apps-card">
      <table class="table table-bordered text-center align-middle">
        <thead class="table-dark">
          <tr>
            <th>Scientist</th>
            <th>Email</th>
            <th>LMS Access</th>
            <th>Sync Status</th>
            <th>Assigned Courses</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <?php
// 1. Get assigned courses
$assigned_courses = [];
$assigned_course_ids = [];

$course_query = mysqli_query($conn, "
  SELECT DISTINCT c.id, c.course_name 
  FROM courses c
  JOIN (
    SELECT course_id FROM user_course_assignments WHERE user_id = {$row['id']}
    UNION
    SELECT ac.course_id
    FROM application_courses ac
    JOIN applications a ON ac.application_id = a.id
    WHERE a.user_id = {$row['id']}
  ) AS all_courses ON c.id = all_courses.course_id
");

while ($course = mysqli_fetch_assoc($course_query)) {
    $assigned_courses[] = $course['course_name']; // for display
    $assigned_course_ids[] = $course['id'];       // for disabling in dropdown
}

// 2. Get sync status
include_once 'moodle_api_helpers.php';
$token = '3223ebfec77abfe903c27c1468a7d7c5';
$domain = 'http://cei326-omada7.cut.ac.cy/moodle/';

$sync_status = 'Unknown';
if ($row['lms_access']) {
    $exists = check_moodle_user_exists($token, $domain, $row['username']);
    $sync_status = $exists ? 'Synced' : 'Pending';
} else {
    $sync_status = 'Disabled';
}
?>
  <tr>
  <td rowspan="2"><?= htmlspecialchars($row['full_name']) ?></td>
  <td rowspan="2"><?= htmlspecialchars($row['email']) ?></td>
  <td rowspan="2">
    <?= $row['lms_access'] ? '<span class="badge bg-success">Enabled</span>' : '<span class="badge bg-danger">Disabled</span>' ?>
  </td>
  <td rowspan="2">
    <?php if ($sync_status === 'Synced'): ?>
      <span class="badge bg-success">🟢 Synced</span>
    <?php elseif ($sync_status === 'Pending'): ?>
      <span class="badge bg-warning text-dark">🟡 Pending</span>
    <?php else: ?>
      <span class="badge bg-secondary">🔴 Disabled</span>
    <?php endif; ?>
  </td>
  <td rowspan="2">
    <?php if ($assigned_courses): ?>
      <?php foreach ($assigned_courses as $index => $course_name): ?>
  <form method="POST" class="d-inline">
    <input type="hidden" name="unassign_course" value="1">
    <input type="hidden" name="scientist_id" value="<?= $row['id'] ?>">
    <input type="hidden" name="course_id" value="<?= $assigned_course_ids[$index] ?>">
    <span class="badge bg-info text-dark me-1 mb-1">
      <?= htmlspecialchars($course_name) ?>
      <button type="submit" class="btn-close btn-close-white btn-sm ms-2" aria-label="Unassign" style="font-size: 0.6em;"></button>
    </span>
  </form><br>
<?php endforeach; ?>
    <?php else: ?>
      <span class="text-muted">None</span>
    <?php endif; ?>
  </td>
</tr>
<tr>
  <td colspan="2">
    <form method="POST" class="d-flex flex-column align-items-center">
      <input type="hidden" name="scientist_id" value="<?= $row['id'] ?>">
      <select name="course_ids[]" class="form-select form-select-sm mb-2" multiple required style="max-width: 300px; height: 120px;">
        <?php foreach ($course_list as $course): ?>
          <option value="<?= $course['id'] ?>" <?= in_array($course['id'], $assigned_course_ids) ? 'disabled' : '' ?>>
  <?= htmlspecialchars($course['course_name']) ?> (<?= $course['course_code'] ?>)
</option>
        <?php endforeach; ?>
      </select>
      <button type="submit" name="assign_courses" class="btn btn-sm btn-primary w-100">
        <i class="bi bi-send-plus me-1"></i>Assign Selected Courses
      </button>
      <a href="lms_sync.php?toggle=<?= $row['id'] ?>" class="btn btn-sm mt-2 <?= $row['lms_access'] ? 'btn-danger' : 'btn-success' ?> w-100">
        <?= $row['lms_access'] ? 'Disable' : 'Enable' ?>
      </a>
    </form>
  </td>
</tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const saved = localStorage.getItem('dark-mode');
  if (saved === 'true') document.body.classList.add('dark-mode');
</script>

</body>
</html>
