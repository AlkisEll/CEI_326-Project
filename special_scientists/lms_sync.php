<?php
include 'database.php';
require_once "get_config.php";
session_start();

// ── Permission check ─────────────────────────────────────────────────
if (
    !isset($_SESSION['user_id'])
    || !in_array($_SESSION['role'], ['admin','owner','hr','scientist'])
) {
    header('Location: login.php');
    exit();
}

$system_title = getSystemConfig("site_title");
$logo_path    = getSystemConfig("logo_path");
$role         = $_SESSION['role'];
$user_id      = $_SESSION['user_id'];

// ── Fetch all available courses for the dropdown ─────────────────────
$course_list = [];
$course_query = mysqli_query(
    $conn,
    "SELECT id, course_name, course_code
       FROM courses
      ORDER BY course_name ASC"
);
while ($course = mysqli_fetch_assoc($course_query)) {
    $course_list[] = $course;
}

// ── Handle unassignment (“click the X”) ──────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['unassign_course'])) {
    file_put_contents('moodle_delete_debug.log', "🟢 Entered unassign_course block\n", FILE_APPEND);

    $scientist_id = intval($_POST['scientist_id']);
    $course_id    = intval($_POST['course_id']);

    // fetch local user & course
    $user_result   = mysqli_fetch_assoc(
        mysqli_query($conn, "SELECT * FROM users WHERE id = $scientist_id")
    );
    $course_result = mysqli_fetch_assoc(
        mysqli_query($conn, "SELECT * FROM courses WHERE id = $course_id")
    );

    if ($user_result && $course_result) {
        include_once 'moodle_api_helpers.php';
        $token  = '72e8b354b48d4af20f56a041c4c4d614';
        $domain = 'https://cei326-omada7.cut.ac.cy/moodle/';

        // get Moodle IDs
        $moodle_user   = get_moodle_user_by_username($token, $domain, $user_result['username']);
        $moodle_userid = $moodle_user['id'] ?? null;
        $shortname     = $course_result['course_code'];
        $moodle_courseid = get_moodle_course_id_by_shortname($token, $domain, $shortname);

        file_put_contents(
            'moodle_delete_debug.log',
            "User: {$user_result['username']}, Moodle UID: $moodle_userid, ".
            "Course: $shortname, Moodle CID: $moodle_courseid\n",
            FILE_APPEND
        );

        // 1) Unenroll in Moodle
        if ($moodle_userid && $moodle_courseid) {
            unenroll_user_from_course($token, $domain, $moodle_userid, $moodle_courseid);
            file_put_contents(
                'moodle_delete_debug.log',
                "Unenrolled user $moodle_userid from course $moodle_courseid\n",
                FILE_APPEND
            );
        }

        // 2) Remove assignment locally
        mysqli_query(
            $conn,
            "DELETE FROM user_course_assignments
               WHERE user_id = $scientist_id
                 AND course_id = $course_id"
        );
        if (mysqli_errno($conn)) {
            file_put_contents(
                'moodle_delete_debug.log',
                "SQL ERROR deleting assignment: ".mysqli_error($conn)."\n",
                FILE_APPEND
            );
        }

        // 3) Check if ANY direct assignments remain
        $is_assigned_elsewhere = mysqli_fetch_assoc(
            mysqli_query(
                $conn,
                "SELECT COUNT(*) AS total
                   FROM user_course_assignments
                  WHERE course_id = $course_id"
            )
        )['total'];
        file_put_contents(
            'moodle_delete_debug.log',
            "Remaining direct assignments for course $course_id: $is_assigned_elsewhere\n",
            FILE_APPEND
        );

        // 4) If none remain, delete the Moodle course itself
        if ($is_assigned_elsewhere == 0 && $moodle_courseid) {
            delete_moodle_course($token, $domain, $moodle_courseid);
            file_put_contents(
                'moodle_delete_debug.log',
                "🚨 Deleting course $shortname from Moodle (ID: $moodle_courseid)\n",
                FILE_APPEND
            );
        }
    }

    header("Location: lms_sync.php?status=unassigned");
    exit();
}

// ── Handle new assignments (“Assign Selected Courses”) ────────────────
if (
    $_SERVER['REQUEST_METHOD']==='POST'
    && isset($_POST['assign_courses'], $_POST['scientist_id'])
) {
    include_once 'moodle_api_helpers.php';

    $scientist_id    = intval($_POST['scientist_id']);
    $selected_courses = $_POST['course_ids'] ?? [];

    $user_result = mysqli_query($conn, "SELECT * FROM users WHERE id = $scientist_id");
    if ($user = mysqli_fetch_assoc($user_result)) {
        $token  = '72e8b354b48d4af20f56a041c4c4d614';
        $domain = 'https://cei326-omada7.cut.ac.cy/moodle/';

        // build Moodle user payload
        $parts     = explode(' ', $user['full_name']);
        $firstname = array_shift($parts);
        $lastname  = implode(' ', $parts) ?: $firstname;
        $user_data = [
            'username'  => $user['username'],
            'firstname' => $firstname,
            'lastname'  => $lastname,
            'email'     => $user['email'],
            'password'  => 'TempPass123!',
            'auth'      => 'manual'
        ];
        $userid = create_moodle_user($token, $domain, $user_data);

        foreach ($selected_courses as $course_id) {
            $course_info = mysqli_fetch_assoc(
                mysqli_query($conn, "SELECT * FROM courses WHERE id = $course_id")
            );
            if ($course_info) {
                // save to local assignment
                mysqli_query(
                    $conn,
                    "INSERT IGNORE INTO user_course_assignments (user_id, course_id)
                     VALUES ($scientist_id, $course_id)"
                );
                // create/enroll in Moodle
                $shortname = $course_info['course_code'];
                $fullname  = $course_info['course_name'];
                $courseid  = create_moodle_course_if_not_exists(
                    $token, $domain, $shortname, $fullname
                );
                enroll_user_to_course($token, $domain, $userid, $courseid, 3);
            }
        }

        header("Location: lms_sync.php?status=assigned");
        exit();
    }
}

// ── Admin/HR list all scientists ─────────────────────────────────────
if (in_array($role, ['admin','owner','hr'])) {
    $query  = "
      SELECT id, full_name, email, username, lms_access
        FROM users
       WHERE role = 'scientist'
    ORDER BY full_name ASC";
    $result = mysqli_query($conn, $query);
}

// ── Toggle LMS access on/off ─────────────────────────────────────────
if (
    isset($_GET['toggle'])
    && in_array($role, ['admin','owner','hr'])
) {
    $scientist_id = intval($_GET['toggle']);
    $ur = mysqli_query(
        $conn,
        "SELECT * FROM users WHERE id = $scientist_id AND role = 'scientist'"
    );
    if ($user = mysqli_fetch_assoc($ur)) {
        $newStatus = $user['lms_access'] ? 0 : 1;
        mysqli_query(
            $conn,
            "UPDATE users SET lms_access = $newStatus WHERE id = $scientist_id"
        );

        include_once 'moodle_api_helpers.php';
        $token  = '72e8b354b48d4af20f56a041c4c4d614';
        $domain = 'https://cei326-omada7.cut.ac.cy/moodle/';
        $muser  = get_moodle_user_by_username($token, $domain, $user['username']);

        // suspend/reactivate in Moodle
        if ($muser && isset($muser['id'])) {
            $suspend = $newStatus===0 ? 1 : 0;
            suspend_or_unsuspend_moodle_user($token, $domain, $muser['id'], $suspend);
        }

        // If enabling, ensure user exists
        if ($newStatus===1) {
            create_moodle_user($token, $domain, [
                'username'  => $user['username'],
                'firstname' => explode(' ', $user['full_name'])[0],
                'lastname'  => explode(' ', $user['full_name'], 2)[1] ?? '',
                'email'     => $user['email'],
                'password'  => 'TempPass123!',
                'auth'      => 'manual'
            ]);
        } else {
            // if disabling, unenroll from remaining courses
            $assigned_courses = mysqli_query(
                $conn,
                "SELECT c.course_code
                   FROM user_course_assignments uca
                   JOIN courses c ON uca.course_id = c.id
                  WHERE uca.user_id = {$user['id']}"
            );
            while ($cr = mysqli_fetch_assoc($assigned_courses)) {
                $cid = get_moodle_course_id_by_shortname(
                    $token, $domain, $cr['course_code']
                );
                if ($cid) {
                    unenroll_user_from_course($token, $domain, $muser['id'], $cid);
                }
            }
        }

        header("Location: lms_sync.php");
        exit();
    }
}

// ── Scientist self‐view LMS status ───────────────────────────────────
if ($role === 'scientist') {
    $self = mysqli_fetch_assoc(
        mysqli_query($conn, "SELECT lms_access FROM users WHERE id = $user_id")
    );
}

$showBack = true;        // for your back‐link logic
$backLink = "enrollment_dashboard.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>LMS Sync – <?= htmlspecialchars($system_title) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css"
    rel="stylesheet"
  >
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet"
  >
  <link rel="stylesheet" href="indexstyle.css">
  <link rel="stylesheet" href="darkmode.css">
</head>
<body>

  <!-- Navbar -->
  <?php include "navbar.php"; ?>

  <div class="container py-5">
    <h2 class="mb-4 text-center">
      <i class="bi bi-wifi me-2"></i>LMS Access Management
    </h2>

    <?php if (isset($_GET['status']) && $_GET['status']==='assigned'): ?>
      <div class="alert alert-success text-center">
        ✅ Courses assigned and synced with Moodle.
      </div>
    <?php endif; ?>

    <?php if ($role === 'scientist'): ?>
      <div class="alert <?= $self['lms_access']
            ? 'alert-success' : 'alert-danger' ?> text-center">
        <?php if ($self['lms_access']): ?>
          ✅ Your LMS Access is <strong>Enabled</strong>.<br>
          <a
            href="https://cei326-omada7.cut.ac.cy/moodle/"
            target="_blank"
            class="btn btn-sm btn-light mt-2"
          >
            Go to Moodle <i class="bi bi-box-arrow-up-right"></i>
          </a>
        <?php else: ?>
          ❌ LMS Access is <strong>Not Yet Activated</strong>.
        <?php endif; ?>
      </div>

    <?php else: /* admin/HR list */ ?>
      <div class="table-responsive my-apps-card">
        <table
          class="table table-bordered text-center align-middle"
        >
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
                // 1) only direct assignments now:
                $assigned_courses    = [];
                $assigned_course_ids = [];
                $cq = mysqli_query(
                    $conn,
                    "SELECT c.id, c.course_name
                       FROM courses c
                       JOIN user_course_assignments uca
                         ON c.id = uca.course_id
                      WHERE uca.user_id = {$row['id']}"
                );
                while ($c = mysqli_fetch_assoc($cq)) {
                    $assigned_courses[]    = $c['course_name'];
                    $assigned_course_ids[] = $c['id'];
                }
                // 2) sync status
                include_once 'moodle_api_helpers.php';
                $token   = '72e8b354b48d4af20f56a041c4c4d614';
                $domain  = 'https://cei326-omada7.cut.ac.cy/moodle/';
                if ($row['lms_access']) {
                    $sync_status = check_moodle_user_exists(
                        $token, $domain, $row['username']
                    ) ? 'Synced' : 'Pending';
                } else {
                    $sync_status = 'Disabled';
                }
              ?>
              <tr>
                <td rowspan="2"><?= htmlspecialchars($row['full_name']) ?></td>
                <td rowspan="2"><?= htmlspecialchars($row['email']) ?></td>
                <td rowspan="2">
                  <?= $row['lms_access']
                      ? '<span class="badge bg-success">Enabled</span>'
                      : '<span class="badge bg-danger">Disabled</span>' ?>
                </td>
                <td rowspan="2">
                  <?php if ($sync_status==='Synced'): ?>
                    <span class="badge bg-success">🟢 Synced</span>
                  <?php elseif ($sync_status==='Pending'): ?>
                    <span class="badge bg-warning text-dark">🟡 Pending</span>
                  <?php else: ?>
                    <span class="badge bg-secondary">🔴 Disabled</span>
                  <?php endif; ?>
                </td>
                <td rowspan="2">
                  <?php if ($assigned_courses): ?>
                    <?php foreach ($assigned_courses as $i => $name): ?>
                      <form method="POST" class="d-inline">
                        <input type="hidden" name="unassign_course" value="1">
                        <input type="hidden" name="scientist_id" value="<?= $row['id'] ?>">
                        <input type="hidden" name="course_id" value="<?= $assigned_course_ids[$i] ?>">
                        <span class="badge bg-info text-dark me-1 mb-1">
                          <?= htmlspecialchars($name) ?>
                          <button
                            type="submit"
                            class="btn-close btn-close-white btn-sm ms-2"
                            aria-label="Unassign"
                            style="font-size:0.6em"
                          ></button>
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
                    <select
                      name="course_ids[]"
                      multiple
                      required
                      class="form-select form-select-sm mb-2"
                      style="max-width:300px; height:120px"
                    >
                      <?php foreach ($course_list as $course): ?>
                        <option
                          value="<?= $course['id'] ?>"
                          <?= in_array($course['id'], $assigned_course_ids) ? 'disabled' : '' ?>
                        >
                          <?= htmlspecialchars($course['course_name']) ?>
                          (<?= $course['course_code'] ?>)
                        </option>
                      <?php endforeach; ?>
                    </select>
                    <button
                      type="submit"
                      name="assign_courses"
                      class="btn btn-sm btn-primary w-100"
                    >
                      <i class="bi bi-send-plus me-1"></i>Assign Selected Courses
                    </button>
                    <a
                      href="lms_sync.php?toggle=<?= $row['id'] ?>"
                      class="btn btn-sm mt-2 <?= $row['lms_access'] ? 'btn-danger' : 'btn-success' ?> w-100"
                    >
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
    if (localStorage.getItem('dark-mode')==='true') {
      document.body.classList.add('dark-mode');
    }
  </script>
</body>
</html>
