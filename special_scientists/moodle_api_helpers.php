<?php
// Moodle API Helper Functions for Special Scientists Application

// 1. Create Moodle Course (if it doesn't exist)
function create_moodle_course_if_not_exists($token, $domain, $shortname, $fullname, $categoryid = 1) {
    $functionname = 'core_course_create_courses';

    // Check if course exists
    $check_url = $domain . '/webservice/rest/server.php'
        . '?wstoken=' . $token
        . '&wsfunction=core_course_get_courses_by_field'
        . '&field=shortname'
        . '&value=' . urlencode($shortname)
        . '&moodlewsrestformat=json';

    $check_response = file_get_contents($check_url);
    $check_data = json_decode($check_response, true);

    if (!empty($check_data['courses'])) {
        return $check_data['courses'][0]['id']; // Return existing course ID
    }


    // Create new course
    $params = [
        'courses' => [[
            'fullname' => $fullname,
            'shortname' => $shortname,
            'categoryid' => $categoryid,
            'visible' => 1
        ]]
    ];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $domain . '/webservice/rest/server.php?wstoken=' . $token . '&wsfunction=' . $functionname . '&moodlewsrestformat=json',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($params)
    ]);

    $response = curl_exec($curl);
curl_close($curl);

// TEMP: Log the raw response
file_put_contents("moodle_course_debug.log", $response . PHP_EOL, FILE_APPEND);

$data = json_decode($response, true);

if (isset($data[0]['id'])) {
    log_moodle_sync('user', $user_data['username'], 'success', 'User created successfully.');
} elseif (isset($data['exception'])) {
    $msg = $data['message'] ?? 'Unknown error';
    log_moodle_sync('user', $user_data['username'], 'failure', $msg);
}

if (isset($data[0]['id'])) {
    log_moodle_sync('course', $shortname, 'success', 'Course created successfully.');
    return $data[0]['id'];
} elseif (isset($data['exception'])) {
    $msg = $data['message'] ?? 'Unknown error';
    log_moodle_sync('course', $shortname, 'failure', $msg);
    return null;
}
}

function check_moodle_user_exists($token, $domain, $username) {
    $url = $domain . '/webservice/rest/server.php' .
           '?wstoken=' . $token .
           '&wsfunction=core_user_get_users' .
           '&criteria[0][key]=username' .
           '&criteria[0][value]=' . urlencode($username) .
           '&moodlewsrestformat=json';

    $response = file_get_contents($url);
    $data = json_decode($response, true);
    file_put_contents('moodle_delete_debug.log', "Response: " . print_r($data, true) . "\n", FILE_APPEND);
    return !empty($data['users']);
}

// 2. Create Moodle User
function create_moodle_user($token, $domain, $user_data) {
    $functionname = 'core_user_create_users';

    // Manually flatten into Moodle's required format: users[0][username], users[0][email], etc.
    $params = [];
    foreach ($user_data as $key => $value) {
        $params["users[0][$key]"] = $value;
    }

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $domain . '/webservice/rest/server.php',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => array_merge($params, [
            'wstoken' => $token,
            'wsfunction' => $functionname,
            'moodlewsrestformat' => 'json'
        ])
    ]);

    $response = curl_exec($curl);
    curl_close($curl);

    // DEBUGGING: show raw Moodle response
    echo "<pre>Moodle Create User Response:\n$response</pre>";
    exit;

    $data = json_decode($response, true);
    return $data[0]['id'] ?? null;
}


// 3. Enroll User in Course and Assign Role
function enroll_user_to_course($token, $domain, $userid, $courseid, $roleid = 5) {
    $functionname = 'enrol_manual_enrol_users';

    $params = ['enrolments' => [[
        'roleid' => $roleid,
        'userid' => $userid,
        'courseid' => $courseid
    ]]];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $domain . '/webservice/rest/server.php?wstoken=' . $token . '&wsfunction=' . $functionname . '&moodlewsrestformat=json',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($params)
    ]);

    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response, true);
}

// 4. Get Moodle user object by username
function get_moodle_user_by_username($token, $domain, $username) {
    $url = $domain . '/webservice/rest/server.php' .
           '?wstoken=' . $token .
           '&wsfunction=core_user_get_users' .
           '&criteria[0][key]=username' .
           '&criteria[0][value]=' . urlencode($username) .
           '&moodlewsrestformat=json';

    $response = file_get_contents($url);
    $data = json_decode($response, true);

    return $data['users'][0] ?? null;
}

// 5. Get Moodle course ID by shortname
function get_moodle_course_id_by_shortname($token, $domain, $shortname) {
    $url = $domain . '/webservice/rest/server.php' .
           '?wstoken=' . $token .
           '&wsfunction=core_course_get_courses_by_field' .
           '&field=shortname' .
           '&value=' . urlencode($shortname) .
           '&moodlewsrestformat=json';

    $response = file_get_contents($url);
    $data = json_decode($response, true);

    file_put_contents('moodle_delete_debug.log', "Course lookup for shortname '$shortname'\nResponse:\n" . print_r($data, true) . "\n", FILE_APPEND);

    if (isset($data['courses']) && count($data['courses']) > 0) {
        return $data['courses'][0]['id'];
    }

    return null;
}

// 6. Unenroll user from course
function unenroll_user_from_course($token, $domain, $userid, $courseid) {
    $functionname = 'enrol_manual_unenrol_users';

    $params = ['enrolments' => [[
        'userid' => $userid,
        'courseid' => $courseid
    ]]];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $domain . '/webservice/rest/server.php?wstoken=' . $token . '&wsfunction=' . $functionname . '&moodlewsrestformat=json',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($params)
    ]);

    $response = curl_exec($curl);
    curl_close($curl);

    return json_decode($response, true);
}

// 7. Log Moodle sync events
function log_moodle_sync($type, $ref, $status, $message) {
    include 'database.php';
    $type = mysqli_real_escape_string($conn, $type);
    $ref = mysqli_real_escape_string($conn, $ref);
    $status = mysqli_real_escape_string($conn, $status);
    $message = mysqli_real_escape_string($conn, $message);

    mysqli_query($conn, "
        INSERT INTO moodle_sync_logs (type, reference_id, status, message)
        VALUES ('$type', '$ref', '$status', '$message')
    ");
}

// 8. Suspend or Unsuspend Moodle User
function suspend_or_unsuspend_moodle_user($token, $domain, $userid, $suspend = 1) {
    $functionname = 'core_user_update_users';

    $params = ['users' => [[
        'id' => $userid,
        'suspended' => $suspend
    ]]];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $domain . '/webservice/rest/server.php?wstoken=' . $token . '&wsfunction=' . $functionname . '&moodlewsrestformat=json',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($params)
    ]);

    $response = curl_exec($curl);
    curl_close($curl);

    return json_decode($response, true);
}

// 9. Delete a Moodle course by ID
function delete_moodle_course($token, $domain, $courseid) {
    $functionname = 'core_course_delete_courses';

    $params = ['courseids' => [$courseid]];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $domain . '/webservice/rest/server.php?wstoken=' . $token . '&wsfunction=' . $functionname . '&moodlewsrestformat=json',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($params)
    ]);

    $response = curl_exec($curl);
    curl_close($curl);

    return json_decode($response, true);
}
