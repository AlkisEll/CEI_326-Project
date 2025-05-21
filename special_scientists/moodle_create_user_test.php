<?php
require_once "database.php";

// Moodle API Config
$token = '72e8b354b48d4af20f56a041c4c4d614';  // ✅ Use your actual token
$domain = 'https://cei326-omada7.cut.ac.cy/moodle';
$function = 'core_user_create_users';
$serverurl = "$domain/webservice/rest/server.php?wstoken=$token&wsfunction=$function&moodlewsrestformat=json";

// Prepare test user manually
$params = [
    'users' => [[
        'username'  => 'nikoscy100',
        'password'  => 'Crystal060506#',
        'firstname' => 'Nikos',
        'lastname'  => 'Nikolaou',
        'email'     => 'em.solomonides@gmail.com',
        'auth'      => 'manual'
    ]]
];

// Debug Payload
echo "<h4>Payload being sent to Moodle:</h4><pre>";
print_r($params);
echo "</pre>";

// Send via CURL using JSON body
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $serverurl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($params), // ✅ Proper nested JSON
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'], // ✅ Must include header
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
