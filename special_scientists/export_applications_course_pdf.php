<?php
require_once 'database.php';
require_once __DIR__ . '/dompdf/autoload.inc.php';
use Dompdf\Dompdf;

// === Collect all required data ===

// 1. LMS Stats
$total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'scientist'"))['total'];
$enabled = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS enabled FROM users WHERE role = 'scientist' AND lms_access = 1"))['enabled'];
$disabled = $total - $enabled;

// 2. Courses Without Assigned Scientists
$courses = mysqli_query($conn, "
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

// 3. Moodle Sync Stats
$course_syncs = mysqli_fetch_assoc(mysqli_query($conn, "
  SELECT 
    SUM(status = 'success') AS course_success,
    SUM(status = 'failure') AS course_failure
  FROM moodle_sync_logs
  WHERE type = 'course'
"));

$user_syncs = mysqli_fetch_assoc(mysqli_query($conn, "
  SELECT 
    SUM(status = 'success') AS user_success,
    SUM(status = 'failure') AS user_failure
  FROM moodle_sync_logs
  WHERE type = 'user'
"));

// 4. Failed Course Logs
$failed_courses = mysqli_query($conn, "
  SELECT reference_id AS shortname, message, created_at
  FROM moodle_sync_logs
  WHERE type = 'course' AND status = 'failure'
  ORDER BY created_at DESC
");

// 5. Failed User Logs
$failed_users = mysqli_query($conn, "
  SELECT reference_id AS username, message, created_at
  FROM moodle_sync_logs
  WHERE type = 'user' AND status = 'failure'
  ORDER BY created_at DESC
");


// === Build HTML ===
$html = '
<style>
  @page {
    margin: 100px 50px;
  }

  header {
    position: fixed;
    top: -80px;
    left: 0;
    right: 0;
    text-align: center;
    font-size: 18px;
    font-weight: bold;
    border-bottom: 1px solid #000;
    padding-bottom: 5px;
  }

  footer {
    position: fixed; 
    bottom: -50px;
    left: 0;
    right: 0;
    text-align: center;
    font-size: 12px;
    color: #888;
  }

  .pagenum:before {
    content: counter(page);
  }
</style>

<header>Enrollment Report</header>
<footer>Page <span class="pagenum"></span></footer>
';
$html = '<h2 style="text-align:center;">Enrollment Report</h2>';

$html .= '<h4>LMS Access Summary</h4>';
$html .= '<table border="1" cellpadding="6" cellspacing="0" width="100%">';
$html .= '<tr><th>Total Scientists</th><th>With LMS Access</th><th>Without LMS Access</th></tr>';
$html .= "<tr><td>$total</td><td>$enabled</td><td>$disabled</td></tr>";
$html .= '</table><br>';

// Courses Without Instructors
$html .= '<h4>Courses Without Assigned Scientists</h4>';
$html .= '<ul>';
if (mysqli_num_rows($courses) > 0) {
    while ($row = mysqli_fetch_assoc($courses)) {
        $html .= '<li>' . htmlspecialchars($row['course_name']) . '</li>';
    }
} else {
    $html .= '<li>All courses are currently covered.</li>';
}
$html .= '</ul><br>';

// Sync Stats
// New page for sync stats
$html .= '<div style="page-break-before: always;"></div>';
$html .= '<h4>Moodle Sync Summary</h4>';
$html .= '<table border="1" cellpadding="6" cellspacing="0" width="100%">';
$html .= '<tr><th>Courses Synced</th><th>Courses Failed</th><th>Users Synced</th><th>Users Failed</th></tr>';
$html .= '<tr>';
$html .= '<td>' . ($course_syncs['course_success'] ?? 0) . '</td>';
$html .= '<td>' . ($course_syncs['course_failure'] ?? 0) . '</td>';
$html .= '<td>' . ($user_syncs['user_success'] ?? 0) . '</td>';
$html .= '<td>' . ($user_syncs['user_failure'] ?? 0) . '</td>';
$html .= '</tr></table><br>';

// Failed Course Syncs
$html .= '<h4>Failed Moodle Course Syncs</h4>';
$html .= '<table border="1" cellpadding="6" cellspacing="0" width="100%">';
$html .= '<tr><th>Course Shortname</th><th>Error Message</th><th>Timestamp</th></tr>';
if (mysqli_num_rows($failed_courses) > 0) {
    while ($log = mysqli_fetch_assoc($failed_courses)) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($log['shortname']) . '</td>';
        $html .= '<td>' . htmlspecialchars($log['message']) . '</td>';
        $html .= '<td>' . $log['created_at'] . '</td>';
        $html .= '</tr>';
    }
} else {
    $html .= '<tr><td colspan="3">No failed course syncs.</td></tr>';
}
$html .= '</table><br>';

// Failed User Syncs
$html .= '<h4>Failed Moodle User Syncs</h4>';
$html .= '<table border="1" cellpadding="6" cellspacing="0" width="100%">';
$html .= '<tr><th>Username</th><th>Error Message</th><th>Timestamp</th></tr>';
if (mysqli_num_rows($failed_users) > 0) {
    while ($log = mysqli_fetch_assoc($failed_users)) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($log['username']) . '</td>';
        $html .= '<td>' . htmlspecialchars($log['message']) . '</td>';
        $html .= '<td>' . $log['created_at'] . '</td>';
        $html .= '</tr>';
    }
} else {
    $html .= '<tr><td colspan="3">No failed user syncs.</td></tr>';
}
$html .= '</table>';

// === Generate PDF ===
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("enrollment_report.pdf", ["Attachment" => true]);
exit;