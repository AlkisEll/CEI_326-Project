<?php
require_once "database.php";

// Moodle API Config
$token = '72e8b354b48d4af20f56a041c4c4d614';  // Use your real token
$domain = 'https://cei326-omada7.cut.ac.cy/moodle';
$function = 'core_user_create_users';
$serverurl = "$domain/webservice/rest/server.php?wstoken=$token&wsfunction=$function&moodlewsrestformat=json";

// Prepare user data in Moodle-compatible format
$params = [
    'users[0][username]'  => 'nikoscy100',
    'users[0][password]'  => 'Crystal060506#',
    'users[0][firstname]' => 'Nikos',
    'users[0][lastname]'  => 'Nikolaou',
    'users[0][email]'     => 'em.solomonides@gmail.com',
    'users[0][auth]'      => 'manual'
];

// Debug
echo "<h4>Payload being sent to Moodle:</h4><pre>";
print_r($params);
echo "</pre>";

// Send using x-www-form-urlencoded (default expected by Moodle REST)
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $serverurl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($params), // Moodle expects this format
]);

$response = curl_exec($curl);
curl_close($curl);

// Output Moodle's response
echo "<h4>Raw Moodle Response:</h4><pre>";
print_r($response);
echo "</pre>";

echo "<h4>Decoded Response:</h4><pre>";
print_r(json_decode($response, true));
echo "</pre>";
?>
