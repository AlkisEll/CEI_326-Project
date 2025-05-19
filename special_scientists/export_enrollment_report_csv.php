<?php
include 'database.php';
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="enrollment_report.csv"');

$output = fopen('php://output', 'w');

// Section 1: LMS Access Summary
fputcsv($output, ['Total Scientists', 'With LMS Access', 'Without LMS Access']);

$total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'scientist'"))['total'];
$enabled = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS enabled FROM users WHERE role = 'scientist' AND lms_access = 1"))['enabled'];
$disabled = $total - $enabled;

fputcsv($output, [$total, $enabled, $disabled]);

// Spacer
fputcsv($output, []);
fputcsv($output, ['Courses Without Instructors']);

// Section 2: Unassigned Courses
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

while ($row = mysqli_fetch_assoc($courses)) {
    fputcsv($output, [$row['course_name']]);
}

// Spacer
fputcsv($output, []);
fputcsv($output, ['Moodle Sync Stats']);

// Section 3: Sync Stats Summary
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

fputcsv($output, ['Courses Synced', 'Courses Failed', 'Users Synced', 'Users Failed']);
fputcsv($output, [
    $course_syncs['course_success'] ?? 0,
    $course_syncs['course_failure'] ?? 0,
    $user_syncs['user_success'] ?? 0,
    $user_syncs['user_failure'] ?? 0
]);

// Spacer
fputcsv($output, []);
fputcsv($output, ['Failed Moodle Course Syncs']);
fputcsv($output, ['Course Shortname', 'Error Message', 'Timestamp']);

$failed_courses = mysqli_query($conn, "
    SELECT reference_id AS shortname, message, created_at
    FROM moodle_sync_logs
    WHERE type = 'course' AND status = 'failure'
    ORDER BY created_at DESC
");

while ($log = mysqli_fetch_assoc($failed_courses)) {
    fputcsv($output, [$log['shortname'], $log['message'], $log['created_at']]);
}

// Spacer
fputcsv($output, []);
fputcsv($output, ['Failed Moodle User Syncs']);
fputcsv($output, ['Username', 'Error Message', 'Timestamp']);

$failed_users = mysqli_query($conn, "
    SELECT reference_id AS username, message, created_at
    FROM moodle_sync_logs
    WHERE type = 'user' AND status = 'failure'
    ORDER BY created_at DESC
");

while ($log = mysqli_fetch_assoc($failed_users)) {
    fputcsv($output, [$log['username'], $log['message'], $log['created_at']]);
}

fclose($output);
exit();